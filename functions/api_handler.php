<?php
use GuzzleHttp\Client;

// Load environment variables
require_once __DIR__ . '/../config/env.php';

function callGeminiAPI($rawStatement, $pdo, $userId, $filename, $currency = 'UNKNOWN') {
    // Get fresh PDO connection from database_handler.php
    require_once 'database_handler.php';
    try {
        if (!$pdo || !$pdo->query('SELECT 1')) {
            $pdo = getPDO();
        }
    } catch (Exception $e) {
        $pdo = getPDO();
    }

    // First, check if user has already uploaded this exact file
    $existingAnalysis = checkExistingAnalysis($pdo, $userId, $filename, $rawStatement);

    if ($existingAnalysis) {
        // Return existing analysis data instead of calling API
        return json_decode($existingAnalysis['analysis_result'], true);
    }

    // If no existing analysis, call the API with retry logic
    return callOpenAIAPIWithRetry($rawStatement, $currency);
}

function checkExistingAnalysis($pdo, $userId, $filename, $rawStatement) {
    // Check if user has uploaded the same file before
    // First check if the file exists in uploads folder and has analysis data
    $stmt = $pdo->prepare("
        SELECT analysis_result, filename
        FROM uploads
        WHERE user_id = ? AND filename = ?
        ORDER BY upload_date DESC
        LIMIT 1
    ");
    $stmt->execute([$userId, $filename]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && !empty($result['analysis_result'])) {
        // Check if the actual file still exists in uploads folder
        $filePath = "../uploads/" . basename($result['filename']);
        if (file_exists($filePath)) {
            return $result;
        }
    }

    return false;
}

function getApiKey(){
    return Env::get('OPENAI_API_KEY');
}

function callOpenAIAPI($rawStatement)
{
    $apiKey = getApiKey();

    // Initialize OpenAI client
    $client = OpenAI::client($apiKey);

    try {
        $response = $client->chat()->create([
            'model' => 'gpt-4.1-mini', // you can use 'gpt-4.1' for better accuracy
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'bank_statement_analysis',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'summary' => [
                                'type' => 'object',
                                'properties' => [
                                    'totalSpent' => ['type' => 'number'],
                                    'totalCredit' => ['type' => 'number'],
                                    'totalDiscretionaryLeaks' => ['type' => 'number'],
                                    'statementPeriod' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'startDate' => ['type' => 'string'],
                                            'endDate' => ['type' => 'string']
                                        ],
                                        'required' => ['startDate', 'endDate']
                                    ]
                                ],
                                'required' => [
                                    'totalSpent',
                                    'totalCredit',
                                    'totalDiscretionaryLeaks',
                                    'statementPeriod'
                                ]
                            ],
                            'categorizedExpenses' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'category' => ['type' => 'string'],
                                        'amount' => ['type' => 'number'],
                                        'isLeak' => ['type' => 'boolean']
                                    ],
                                    'required' => ['category', 'amount', 'isLeak']
                                ]
                            ]
                        ],
                        'required' => ['summary', 'categorizedExpenses']
                    ]
                ]
            ],
            'messages' => [
                    [
                        'role' => 'system',
                        'content' => <<<EOT
                You are a precise financial analysis assistant.

                You read raw bank statements and produce structured financial summaries.

                Your goals:
                1. Calculate total spent (sum of all Debit values).
                2. Calculate total credited (sum of all Credit values).
                3. Detect statement start and end dates from the first and last transaction dates.
                4. Categorize transactions into **only the most relevant 5–8 categories** that cover the data.
                Prefer broad categories such as:
                - Groceries & Supermarkets
                - Food & Dining
                - Utilities & Bills
                - Shopping & Lifestyle
                - Travel & Transport
                - Income & Transfers
                - Other / Miscellaneous
                5. Detect discretionary leaks (non-essential or impulsive spending).

                Rules:
                - Always return clean, valid JSON matching the given schema.
                - Combine similar merchant types into one category.
                - Do not create too many custom or merchant-specific categories.
                EOT
                ],
                [
                        'role' => 'user',
                        'content' => <<<EOT
                Analyze the following raw bank statement data and return JSON exactly matching the schema.

                {$rawStatement}
                EOT
                ],
            ],

        ]);

        // Parse the clean JSON result
        $json = $response->choices[0]->message->content;
        return json_decode($json, true);

    } catch (Exception $e) {
        throw new Exception("OpenAI API request failed: " . $e->getMessage());
    }
}

function callOpenAIAPIWithRetry($rawStatement, $currency = 'UNKNOWN'){
    $maxRetries = 3;
    $wait = 2;
    $apiKey = getApiKey();
    $client = OpenAI::client($apiKey);

    for ($i = 1; $i <= $maxRetries; $i++) {
        try {
            $systemPrompt = <<<SYS
            You are a financial analysis assistant. Return ONLY JSON strictly following the schema.
            Rules:
            1. The transactions are in currency: {$currency}. Use that currency for all totals. If currency is UNKNOWN, try to infer but prefer UNKNOWN -> ask fallback.
            2. "isLeak" = discretionary spending (non-essential). Include real leaks only.
            3. Generate at most 10 categories (essential + discretionary).
            4. Do NOT create extra categories not present in the statement.
            5. Numbers only (no symbols) for amounts.
            6. JSON must validate schema.
            SYS;

            $response = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'bank_statement_analysis',
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'summary' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'currency' => ['type' => 'string'],
                                        'totalSpent' => ['type' => 'number'],
                                        'totalCredit' => ['type' => 'number'],
                                        'totalDiscretionaryLeaks' => ['type' => 'number'],
                                        'statementPeriod' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'startDate' => ['type' => 'string'],
                                                'endDate' => ['type' => 'string']
                                            ],
                                            'required' => ['startDate', 'endDate']
                                        ]
                                    ],
                                    'required' => ['currency','totalSpent','totalCredit','totalDiscretionaryLeaks','statementPeriod']
                                ],
                                'categorizedExpenses' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'category' => ['type' => 'string'],
                                            'amount' => ['type' => 'number'],
                                            'isLeak' => ['type' => 'boolean']
                                        ],
                                        'required' => ['category','amount','isLeak']
                                    ]
                                ]
                            ],
                            'required' => ['summary','categorizedExpenses']
                        ]
                    ]
                ],
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => "Analyze this CSV of transactions (currency: {$currency}).\n\n{$rawStatement}"]
                ],
            ]);

            $json = $response->choices[0]->message->content;
            return json_decode($json, true);

        } catch (Exception $e) {
            if ($i === $maxRetries) {
                throw new Exception("OpenAI API failed after $maxRetries attempts: " . $e->getMessage());
            }
            sleep($wait);
            $wait *= 2;
        }
    }
}


function generateBlueprintWithOpenAI($analysisData, $userInfo = null) {
    $analysisData = preg_replace('/\s+/', ' ', $analysisData); // remove extra whitespace

    $apiKey = getApiKey();
    // Initialize OpenAI client
    $client = OpenAI::client($apiKey);

    try {
        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'wealth_blueprint',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'wealth_allocation' => [
                                'type' => 'object',
                                'properties' => [
                                    'needs_percent' => ['type' => 'number'],
                                    'wants_percent' => ['type' => 'number'],
                                    'savings_percent' => ['type' => 'number'],
                                    'needs_amount' => ['type' => 'number'],
                                    'wants_amount' => ['type' => 'number'],
                                    'savings_amount' => ['type' => 'number']
                                ],
                                'required' => ['needs_percent', 'wants_percent', 'savings_percent', 'needs_amount', 'wants_amount', 'savings_amount']
                            ],
                            'action_plan' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'step_number' => ['type' => 'integer'],
                                        'title' => ['type' => 'string'],
                                        'description' => ['type' => 'string'],
                                        'estimated_savings' => ['type' => 'number'],
                                        'difficulty' => ['type' => 'string'],
                                        'timeframe' => ['type' => 'string']
                                    ],
                                    'required' => ['step_number', 'title', 'description', 'estimated_savings', 'difficulty', 'timeframe']
                                ]
                            ],
                            'improvement_areas' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'category' => ['type' => 'string'],
                                        'current_spending' => ['type' => 'number'],
                                        'recommended_spending' => ['type' => 'number'],
                                        'potential_savings' => ['type' => 'number'],
                                        'priority' => ['type' => 'string']
                                    ],
                                    'required' => ['category', 'current_spending', 'recommended_spending', 'potential_savings', 'priority']
                                ]
                            ],
                            'monthly_targets' => [
                                'type' => 'object',
                                'properties' => [
                                    'emergency_fund_target' => ['type' => 'number'],
                                    'investment_target' => ['type' => 'number'],
                                    'debt_reduction_target' => ['type' => 'number'],
                                    'wealth_accumulation_goal' => ['type' => 'number']
                                ],
                                'required' => ['emergency_fund_target', 'investment_target', 'debt_reduction_target', 'wealth_accumulation_goal']
                            ],
                            'financial_health_score' => ['type' => 'number'],
                            'key_insights' => [
                                'type' => 'array',
                                'items' => ['type' => 'string']
                            ]
                        ],
                        'required' => ['wealth_allocation', 'action_plan', 'improvement_areas', 'monthly_targets', 'financial_health_score', 'key_insights']
                    ]
                ]
            ],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => <<<EOT
                You are a wealth-building financial advisor.
                Based on provided spending analysis, create a JSON plan that:
                - Allocates income by the 50/30/20 rule (Needs/Wants/Savings).
                - Recommends 3–5 improvement areas (where to cut and save).
                - Generates 3–6 clear action steps with estimated savings.
                - Suggests monthly targets for emergency fund, investment, and debt reduction.
                Return valid JSON only, following the schema.
                EOT
                    ],
                    [
                        'role' => 'user',
                        'content' => <<<EOT
                Analyze this financial summary and produce a structured wealth blueprint:

                DATA:
                {$analysisData}

                USER INFO (if available):
                {$userInfo}
                EOT
                ]
            ],

        ]);

        // Parse the clean JSON result
        $json = $response->choices[0]->message->content;
        return json_decode($json, true);

    } catch (Exception $e) {
        throw new Exception("OpenAI Blueprint API request failed: " . $e->getMessage());
    }
}

?>