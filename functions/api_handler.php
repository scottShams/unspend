<?php
use GuzzleHttp\Client;

// Load environment variables
require_once __DIR__ . '/../config/env.php';

function callGeminiAPI($rawStatement, $pdo, $userId, $filename) {
    // Get fresh PDO connection from database_handler.php
    require_once 'database_handler.php';
    $pdo = getPDO();

    // First, check if user has already uploaded this exact file
    $existingAnalysis = checkExistingAnalysis($pdo, $userId, $filename, $rawStatement);

    if ($existingAnalysis) {
        // Return existing analysis data instead of calling API
        return json_decode($existingAnalysis['analysis_result'], true);
    }

    // If no existing analysis, call the API with retry logic
    return callOpenAIAPIWithRetry($rawStatement);
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

function callOpenAIAPIWithRetry($rawStatement)
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

function generateBlueprintWithOpenAI($analysisData, $userInfo = null) {
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
                You are a premier wealth building expert and financial advisor. Your task is to analyze spending data and create a comprehensive wealth blueprint based on proven financial principles.

                CORE WEALTH BUILDING PRINCIPLES TO FOLLOW:
                1. **50/30/20 Rule**: 50% Needs, 30% Wants, 20% Savings/Investments
                2. **Emergency Fund**: 3-6 months of expenses saved first
                3. **Debt Management**: High-interest debt elimination priority
                4. **Investment Strategy**: Consistent investing (20% of income minimum)
                5. **Behavioral Finance**: Address spending psychology and habits

                ANALYZE the provided spending data and create a personalized blueprint that:
                - Identifies spending leaks and improvement opportunities
                - Provides step-by-step action plan for wealth building
                - Sets realistic monthly targets based on current financial situation
                - Uses behavioral psychology to encourage positive financial habits
                - Focuses on sustainable, long-term wealth accumulation

                Return structured JSON with actionable, specific recommendations.
                EOT
                                ],
                                [
                                    'role' => 'user',
                                    'content' => <<<EOT
                Analyze this spending analysis data and create a comprehensive wealth blueprint:

                ANALYSIS DATA:
                {$analysisData}

                USER INFORMATION:
                {$userInfo}

                Create a detailed wealth blueprint with specific action steps, savings targets, and improvement recommendations based on proven financial principles.
                EOT
                ],
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