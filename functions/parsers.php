<?php

use GuzzleHttp\Client;
use Smalot\PdfParser\Parser;
// Load environment variables
require_once __DIR__ . '/../config/env.php';

// Use your existing OpenAI client or the library wrapper you already use.
// Returns standard 3-letter currency code or 'UNKNOWN'
function detectCurrencyWithAI(string $text) : string {
    $apiKey = getApiKey(); // your helper
    $client = OpenAI::client($apiKey);

    // Keep prompt minimal and strict
    $system = "You are a concise extractor. Return ONLY a single 3-letter ISO currency code (e.g. GBP, USD, EUR, AED, BDT) or UNKNOWN if you cannot determine it.";
    $user = "Identify the currency used in this bank statement text. Return just the code (no explanation).\n\n" . substr($text, 0, 2000); // limit length

    try {
        $resp = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user]
            ],
            'temperature' => 0,
            'max_tokens' => 10,
        ]);

        $code = trim($resp->choices[0]->message->content ?? '');
        // cleanup: only uppercase letters
        $code = strtoupper(preg_replace('/[^A-Z]/', '', $code));
        if (strlen($code) === 3) return $code;
    } catch (Exception $e) {
        // log but continue to fallback
        error_log("Currency AI detect failed: " . $e->getMessage());
    }

    return 'UNKNOWN';
}

function detectCurrencyFromText(string $text) : string {
    $map = [
        '/\£|GBP|pound/i' => 'GBP',
        '/\$|USD|dollar/i' => 'USD',
        '/€|EUR|euro/i' => 'EUR',
        '/৳|BDT|taka/i' => 'BDT',
        '/AED|dirham/i' => 'AED',
        '/INR|₹|rupee/i' => 'INR',
        '/SAR|riyals?/i' => 'SAR',
        '/HKD|HK\$|Hong Kong/i' => 'HKD',
        '/JPY|¥|yen/i' => 'JPY'
    ];

    foreach ($map as $regex => $code) {
        if (preg_match($regex, $text)) return $code;
    }
    return 'UNKNOWN';
}


function parseBankStatement($filePath, $fileType) {
    $text = '';

    if ($fileType == "pdf") {
        $parser = new Parser();
        try {
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
        } catch (Exception $e) {
            throw new Exception("Error extracting text: " . $e->getMessage());
        }
    } else { // CSV
        $text = file_get_contents($filePath);
    }

    if (!validateBankStatement($text)) {
        throw new Exception("The uploaded file does not appear to be a valid bank statement.");
    }

    // first try fast regex-based detection
    $currency = detectCurrencyFromText($text);

    // if regex cannot decide, fallback to small AI detector (recommended)
    if ($currency === 'UNKNOWN') {
        $currency = detectCurrencyWithAI($text);
    }

    // parse transactions (unchanged)
    $transactions = parseTransactionsAIinChunks($text);

    // return both
    return [
        'currency' => $currency,
        'transactions' => $transactions
    ];
}


function parseTransactions($text) {
    $lines = explode("\n", $text);
    $transactions = [];
    $currentTransaction = [];
    $isInTransaction = false;

    foreach ($lines as $line) {
        $line = trim($line);
        // file_put_contents('line_debug.txt', $line . "\n", FILE_APPEND); 

        // Skip header lines and empty lines
        if (preg_match('/^(Date Description|EMIRATES NBD|Account Statement|Statement From|Account Number|Name|Opening Balance|Date Description Debit Credit Account Balance)/', $line) || empty($line)) {
            continue;
        }

        // Start of a new transaction (date line)
        if (preg_match('/^(\d{2} \w{3} \d{4})/', $line)) {
            // If we were already in a transaction, process the previous one
            if ($isInTransaction && !empty($currentTransaction)) {
                $transaction = processTransaction($currentTransaction);
                if ($transaction) {
                    $transactions[] = $transaction;
                }
            }

            $isInTransaction = true;
            $currentTransaction = [$line];
        }
        // Continue building current transaction
        elseif ($isInTransaction && !empty($line)) {
            $currentTransaction[] = $line;
        }
    }

    // Process final transaction if exists
    if ($isInTransaction && !empty($currentTransaction)) {
        $transaction = processTransaction($currentTransaction);
        if ($transaction) {
            $transactions[] = $transaction;
        }
    }

    if (empty($transactions)) {
        throw new Exception("No transactions parsed. Check pdfparser_debug.txt or line_debug.txt for extracted text.");
    }

    return $transactions;
}

function parseTransactionsAI($text)
{
    $apiKey = Env::get('OPENAI_API_KEY');// Store in .env or cPanel environment variable
    
    $client = new Client([
        'base_uri' => 'https://api.openai.com/v1/',
        'timeout' => 60,
    ]);

    $prompt = "
    You are a financial data extraction AI. 
    Read the following bank statement text and extract all transactions in a structured JSON format.

    Each transaction must include:
    - date (string, in YYYY-MM-DD or DD MMM YYYY format)
    - description (string)
    - debit (number or 0 if not applicable)
    - credit (number or 0 if not applicable)
    - balance (number or null if missing)

    Return only a valid JSON array named 'transactions', like:
    {
    \"transactions\": [
        {\"date\": \"2025-07-04\", \"description\": \"TESCO MOBILE\", \"debit\": 15.00, \"credit\": 0, \"balance\": 855.98},
        ...
    ]
    }

    Now extract transactions from this text:
    ----------------
    $text
    ----------------
    ";

    try {
        $response = $client->post('chat/completions', [
            'headers' => [
                'Authorization' => "Bearer $apiKey",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a precise financial transaction extraction assistant.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'response_format' => ['type' => 'json_object'], // forces clean JSON
            ],
        ]);

        $result = json_decode($response->getBody(), true);
        $jsonOutput = $result['choices'][0]['message']['content'] ?? null;

        if (!$jsonOutput) {
            throw new Exception("No content returned from AI.");
        }

        $data = json_decode($jsonOutput, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['transactions'])) {
            throw new Exception("Invalid JSON structure returned.");
        }

        return $data['transactions'];
    } catch (Exception $e) {
        throw new Exception("AI parsing failed: " . $e->getMessage());
    }
}

/**
 * Safely convert any string to UTF-8, removing malformed characters.
 */
function safeUtf8Encode(string $input): string
{
    // Detect encoding
    $encoding = mb_detect_encoding($input, ['UTF-8','ISO-8859-1','Windows-1252','ASCII'], true);

    if ($encoding) {
        $input = mb_convert_encoding($input, 'UTF-8', $encoding);
    } else {
        $input = utf8_encode($input); // fallback
    }

    // Remove invalid UTF-8 characters
    return preg_replace('//u', '', $input);
}


function parseTransactionsAIinChunks($text)
{
    $apiKey = Env::get('OPENAI_API_KEY');
    $client = new Client([
        'base_uri' => 'https://api.openai.com/v1/',
        'timeout'  => 120,
    ]);

    // Force text to valid UTF-8 using global helper
    $text = safeUtf8Encode($text);

    // Split text into 2000-character chunks
    $chunks = str_split($text, 2000);
    $allTransactions = [];

    foreach ($chunks as $index => $chunk) {
        $chunk = safeUtf8Encode($chunk);

        $prompt = <<<PROMPT
        Extract ONLY transactions from the most recent 30 days in this text.
        Output strict JSON:
        {"transactions":[{"date":"YYYY-MM-DD","description":"string","debit":number,"credit":number,"balance":number|null}]}

        Rules:
        - Include transactions within last 30 days of latest date in text.
        - Ignore summaries/non-transactions.
        - Normalize dates to YYYY-MM-DD.

        CHUNK #{$index}:
        {$chunk}
        PROMPT;

        try {
            $response = $client->post('chat/completions', [
                'headers' => [
                    'Authorization' => "Bearer $apiKey",
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4o-mini',
                    'temperature' => 0,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Extract clean JSON only.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'response_format' => ['type' => 'json_object'],
                ],
            ]);

            $result = json_decode($response->getBody(), true);
            $jsonOutput = $result['choices'][0]['message']['content'] ?? '';

            $jsonOutput = safeUtf8Encode($jsonOutput);

            $data = json_decode($jsonOutput, true);

            if (!empty($data['transactions'])) {
                $allTransactions = array_merge($allTransactions, $data['transactions']);
            }

        } catch (Exception $e) {
            $logFile = __DIR__ . '/../logs/ai_parser_errors.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) mkdir($logDir, 0755, true);
            file_put_contents($logFile, "Chunk $index failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }

        usleep(300000); // 0.3 sec delay
    }

    if (empty($allTransactions)) {
        throw new Exception("No transactions parsed from any chunk.");
    }

    return $allTransactions;
}


function processTransaction($currentTransaction) {
    $fullText = implode(' ', $currentTransaction);
    if (preg_match('/^(\d{2} \w{3} \d{4})(.+?)(-?\d{1,3}(?:,\d{3})*\.\d{2})\s+AED\s+(\d{1,3}(?:,\d{3})*\.\d{2})$/', $fullText, $matches)) {
        $date = trim($matches[1]);
        $description = trim($matches[2]);
        $amount = trim($matches[3]);

        // Determine if it's a debit (negative) or credit (positive)
        if (strpos($amount, '-') === 0) {
            $debit = str_replace(['-', ','], '', $amount);
            $credit = 0;
        } else {
            $debit = 0;
            $credit = str_replace(',', '', $amount);
        }

        return [
            $date,
            $description,
            $debit,
            $credit
        ];
    }
    return null;
}

function validateBankStatement($text) {
    // Normalize text
    $textLower = strtolower($text);

    // Broaden keyword coverage
    $bankKeywords = [
        'account statement',
        'bank statement',
        'statement period',
        'statement date',
        'statement of account',
        'transaction',
        'transactions',
        'balance',
        'debit',
        'credit',
        'opening balance',
        'closing balance',
        'available balance',
        'account number',
        'customer id',
        'your account'
    ];

    // Count keyword hits
    $keywordCount = 0;
    foreach ($bankKeywords as $keyword) {
        if (strpos($textLower, $keyword) !== false) {
            $keywordCount++;
        }
    }

    // Require at least 2 strong indicators
    if ($keywordCount < 2) {
        return false;
    }

    // Accept flexible date formats:
    // 01-05-2025, 01/05/2025, 01 May 2025, May 01 2025
    if (!preg_match('/\b\d{2}[-\/]\d{2}[-\/]\d{2,4}\b|\b\d{1,2}\s+\w{3,9}\s+\d{2,4}\b/i', $text)) {
        return false;
    }

    // Check for numeric money values (with or without commas)
    if (!preg_match('/\b\d{1,3}(?:,\d{3})*(?:\.\d{2})?\b/', $text)) {
        return false;
    }

    // Also ensure presence of at least one word like "AED", "USD", "BDT" etc.
    if (!preg_match('/\b(AED|USD|BDT|INR|SAR|EUR)\b/i', $text)) {
        // optional, but helps ensure it's financial
        $keywordCount--;
    }

    return $keywordCount >= 2;
}

function prepareCSV($transactions, $currency = '') {
    $meta = $currency ? "# Currency: {$currency}\n" : "";
    $rawStatement = $meta . "Date,Description,Debit,Credit,Balance\n";
    foreach ($transactions as $trans) {
        $date = $trans['date'] ?? '';
        $desc = str_replace(',', ' ', $trans['description'] ?? '');
        $debit = $trans['debit'] ?? 0;
        $credit = $trans['credit'] ?? 0;
        $balance = $trans['balance'] ?? '';

        $rawStatement .= "{$date},{$desc},{$debit},{$credit},{$balance}\n";
    }
    return $rawStatement;
}


?>