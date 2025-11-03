<?php

use GuzzleHttp\Client;
use Smalot\PdfParser\Parser;
// Load environment variables
require_once __DIR__ . '/../config/env.php';

function parseBankStatement($filePath, $fileType) {
    $text = '';

    if ($fileType == "pdf") {
        $parser = new Parser();
        try {
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            // file_put_contents('pdfparser_debug.txt', $text);
        } catch (Exception $e) {
            throw new Exception("Error extracting text: " . $e->getMessage());
        }
    } else { // CSV
        $text = file_get_contents($filePath);
    }

    // Validate if the text is from a valid bank statement
    if (!validateBankStatement($text)) {
        throw new Exception("The uploaded file does not appear to be a valid bank statement.");
    }

    // return parseTransactions($text);
    return parseTransactionsAIinChunks($text);
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

function parseTransactionsAIinChunks($text)
{
    $apiKey = Env::get('OPENAI_API_KEY');
    $client = new Client([
        'base_uri' => 'https://api.openai.com/v1/',
        'timeout'  => 120, // increased slightly
    ]);

    // Split text into 5000-character chunks to prevent timeouts
    $chunks = str_split($text, 5000);
    $allTransactions = [];

    foreach ($chunks as $index => $chunk) {
        $prompt = "
        You are a financial transaction parser and analyst. 
        Your task is to extract **only transactions from the most recent 30 days** found in the text below. 
        Ignore all older entries.

        Output must be valid JSON in this structure:
        {
        \"transactions\": [
            {
            \"date\": \"YYYY-MM-DD\",
            \"description\": \"string\",
            \"debit\": number,
            \"credit\": number,
            \"balance\": number | null
            }
        ]
        }

        IMPORTANT RULES:
        - Only include transactions dated within the last 30 days of the document's most recent date.
        - Skip any lines or summary data not representing actual transactions.
        - If no clear date format is present, infer from context.
        - Ensure dates are normalized to YYYY-MM-DD format.

        TEXT CHUNK #{$index}:
        ----------------
        $chunk
        ----------------
        ";

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
            $data = json_decode($jsonOutput, true);

            if (!empty($data['transactions'])) {
                $allTransactions = array_merge($allTransactions, $data['transactions']);
            }

        } catch (Exception $e) {
            // Log and continue — don’t fail the whole process
            file_put_contents(__DIR__ . '/../logs/ai_parser_errors.log', "Chunk $index failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }

        // Delay slightly between requests to avoid rate limits
        usleep(300000); // 0.3 sec
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

function prepareCSV($transactions) {
    $rawStatement = "Date,Description,Debit,Credit,Balance\n"; // include balance
    foreach ($transactions as $trans) {
        // Handle missing fields gracefully
        $date = $trans['date'] ?? '';
        $desc = str_replace(',', ' ', $trans['description'] ?? ''); // remove commas from desc
        $debit = $trans['debit'] ?? 0;
        $credit = $trans['credit'] ?? 0;
        $balance = $trans['balance'] ?? '';

        $rawStatement .= "{$date},{$desc},{$debit},{$credit},{$balance}\n";
    }
    return $rawStatement;
}

?>