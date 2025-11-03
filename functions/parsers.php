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
        You are a financial transaction parser. 
        Extract transactions from this text into valid JSON: 
        {\"transactions\": [{\"date\": \"YYYY-MM-DD\", \"description\": \"string\", \"debit\": number, \"credit\": number, \"balance\": number|null}]}

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