<?php

ob_clean();
ob_start();
header('Content-Type: application/json; charset=utf-8');

// Error handling function to ensure JSON output
function sendJsonResponse($status, $message, $data = null) {
    while (ob_get_level()) {
        ob_end_clean(); // clear any buffer content
    }

    $response = ['status' => $status, 'message' => $message];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response);
    exit;
}


try {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Simple debug logging
    error_log("Blueprint email: Starting processing");

    // Include required files with better error handling
    $files_to_include = [
        'config/database.php' => 'Database configuration',
        'functions/user_management.php' => 'User management functions', 
        'functions/email_sender.php' => 'Email sending functionality'
    ];

    foreach ($files_to_include as $file => $description) {
        if (file_exists($file)) {
            try {
                require_once $file;
                error_log("Successfully included: $file");
            } catch (Exception $e) {
                error_log("Error including $file: " . $e->getMessage());
                sendJsonResponse('error', "Failed to load $description. Please try again later.");
            }
        } else {
            error_log("File not found: $file");
            sendJsonResponse('error', "Required file not found: $description ($file)");
        }
    }

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        error_log("User not logged in");
        sendJsonResponse('error', 'User not logged in');
    }

    // Get user data
    try {
        $db = Database::getInstance();
        $userManager = new UserManagement($db->getConnection());
        $user = $userManager->getUserById($_SESSION['user_id']);
        
        if (!$user) {
            error_log("User not found for ID: " . $_SESSION['user_id']);
            sendJsonResponse('error', 'User not found');
        }
        
        error_log("User found: " . $user['email']);
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        sendJsonResponse('error', 'Database connection failed. Please try again later.');
    }

    // Get JSON data from request
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input === null) {
        error_log("Invalid JSON input");
        sendJsonResponse('error', 'Invalid request data');
    }

    // Validate input
    if (empty($input['blueprint_content'])) {
        error_log("No blueprint content provided");
        sendJsonResponse('error', 'No blueprint content provided');
    }

    $blueprint_content = $input['blueprint_content'];
    $user_name = $input['user_name'] ?? $user['name'];

    // Clean blueprint content to remove emojis and fix character encoding
    function cleanEmailContent($content) {
        // Remove all emojis using Unicode regex pattern
        $content = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $content); // emoticons
        $content = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $content); // misc symbols and pictographs
        $content = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $content); // transport and map symbols
        $content = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $content); // misc symbols
        $content = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $content); // dingbats
        $content = preg_replace('/[\x{1F900}-\x{1F9FF}]/u', '', $content); // supplemental symbols and pictographs
        $content = preg_replace('/[\x{1FA70}-\x{1FAFF}]/u', '', $content); // symbols and pictographs extended-a
        $content = preg_replace('/[\x{1F1E6}-\x{1F1FF}]/u', '', $content); // regional indicator symbols
        $content = preg_replace('/[\x{FE00}-\x{FE0F}]/u', '', $content); // variation selectors
        
        // Fix character encoding issues
        // Convert HTML entities to proper characters
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Fix the "Â" character issue by properly handling UTF-8
        $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
        
        // Remove any remaining control characters except basic formatting
        $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);
        
        // Clean up multiple spaces and normalize whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);
        
        return $content;
    }

    // Clean the blueprint content
    $blueprint_content = cleanEmailContent($blueprint_content);

    // Initialize email sender
    try {
        $emailSender = new EmailSender();
        error_log("EmailSender initialized successfully");
    } catch (Exception $e) {
        error_log("Failed to initialize EmailSender: " . $e->getMessage());
        sendJsonResponse('error', 'Email service not available. Please try again later.');
    }
    
    // Prepare email content
    $subject = "Your Personalized Wealth Blueprint - unSpend";
    
    // Create HTML email template
    $email_html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Your Wealth Blueprint</title>
        <style>
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 0; 
                background-color: #f5f5f5; 
            }
            .email-container { 
                max-width: 800px; 
                margin: 20px auto; 
                background: white; 
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); 
                border-radius: 8px; 
                overflow: hidden; 
            }
            .email-header { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                color: white; 
                padding: 30px; 
                text-align: center; 
            }
            .email-header h1 { 
                margin: 0; 
                font-size: 28px; 
                font-weight: 700; 
            }
            .email-header p { 
                margin: 10px 0 0 0; 
                font-size: 16px; 
                opacity: 0.9; 
            }
            .email-content { 
                padding: 40px; 
            }
            .blueprint-section { 
                margin-bottom: 30px; 
            }
            .blueprint-section h2 { 
                color: #2d3748; 
                border-left: 4px solid #667eea; 
                padding-left: 16px; 
                margin-bottom: 20px; 
            }
            .highlight-box { 
                background: #f7fafc; 
                border: 1px solid #e2e8f0; 
                border-radius: 8px; 
                padding: 20px; 
                margin: 20px 0; 
            }
            .action-item { 
                background: #f0fff4; 
                border-left: 4px solid #48bb78; 
                padding: 15px; 
                margin: 10px 0; 
            }
            .action-item h4 { 
                margin: 0 0 8px 0; 
                color: #2d3748; 
            }
            .currency { 
                font-weight: 600; 
                color: #48bb78; 
            }
            .percentage { 
                font-weight: 600; 
                color: #667eea; 
            }
            .email-footer { 
                background: #f8f9fa; 
                padding: 30px; 
                text-align: center; 
                color: #6b7280; 
                font-size: 14px; 
            }
            .cta-button { 
                display: inline-block; 
                background: #667eea; 
                color: white; 
                padding: 12px 30px; 
                text-decoration: none; 
                border-radius: 6px; 
                font-weight: 600; 
                margin: 20px 10px; 
            }
            @media (max-width: 600px) {
                .email-container { 
                    margin: 10px; 
                }
                .email-content { 
                    padding: 20px; 
                }
                .email-header { 
                    padding: 20px; 
                }
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='email-header'>
                <h1>Your Personalized Wealth Blueprint</h1>
                <p>Hi {$user_name}, here's your comprehensive financial roadmap</p>
            </div>
            
            <div class='email-content'>
                <div class='highlight-box'>
                    <h3>Your Financial Journey Starts Here</h3>
                    <p>Thank you for using unSpend! We've analyzed your financial data and created this personalized blueprint to help you build wealth and achieve financial freedom.</p>
                </div>
                
                <div class='blueprint-section'>
                    {$blueprint_content}
                </div>
                
                <div class='highlight-box'>
                    <h3>Next Steps</h3>
                    <p>Use this blueprint as your guide:</p>
                    <ul>
                        <li>Review your 50/30/20 allocation targets</li>
                        <li>Start with the highest-priority action items</li>
                        <li>Track your progress monthly</li>
                        <li>Come back for a new analysis in 30 days</li>
                    </ul>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='https://" . $_SERVER['HTTP_HOST'] . "/dashboard.php' class='cta-button'>
                        Return to Dashboard
                    </a>
                    <a href='https://" . $_SERVER['HTTP_HOST'] . "/blueprint.php' class='cta-button'>
                        View Full Blueprint
                    </a>
                </div>
            </div>
            
            <div class='email-footer'>
                <p><strong>unSpend</strong> - Take control of your financial future</p>
                <p>This blueprint was generated based on your financial data analysis.</p>
                <p style='font-size: 12px; margin-top: 20px;'>
                    © 2024 unSpend. All rights reserved. | 
                    <a href='https://" . $_SERVER['HTTP_HOST'] . "/privacy.php'>Privacy Policy</a> | 
                    <a href='https://" . $_SERVER['HTTP_HOST'] . "/terms.php'>Terms of Service</a>
                </p>
            </div>
        </div>
    </body>
    </html>";
    
    // Clean up the blueprint content for email (remove script tags and adjust inline styles)
    $email_html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $email_html);
    $email_html = str_replace('<script', '<!-- script', $email_html);
    $email_html = str_replace('</script>', '-->', $email_html);
    
    // Create a simple text version for non-HTML email clients
    $clean_blueprint_text = cleanEmailContent(strip_tags($blueprint_content));
    $text_content = "
Hi {$user_name},

Thank you for using unSpend! Here's your personalized wealth blueprint:

{$clean_blueprint_text}

Next Steps:
- Review your 50/30/20 allocation targets
- Start with the highest-priority action items  
- Track your progress monthly
- Return to unSpend for a new analysis in 30 days

Visit your dashboard: https://" . $_SERVER['HTTP_HOST'] . "/dashboard.php

Best regards,
The unSpend Team
    ";

    // Ensure proper UTF-8 encoding for the email
    $email_html = mb_convert_encoding($email_html, 'HTML-ENTITIES', 'UTF-8');
    $text_content = mb_convert_encoding($text_content, 'HTML-ENTITIES', 'UTF-8');
    
    // Send email using the enhanced email sender
    error_log("Attempting to send email to: " . $user['email']);
    $result = $emailSender->sendBlueprintEmail(
        $user['email'], 
        $user_name, 
        $subject, 
        $email_html, 
        $text_content
    );
    
    if ($result) {
        error_log("Email sent successfully");
        sendJsonResponse('success', 'Blueprint sent successfully!');
    } else {
        error_log("Email sending failed");
        sendJsonResponse('error', 'Failed to send email. Please try again.');
    }

} catch (Exception $e) {
    error_log("Blueprint email Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    sendJsonResponse('error', 'An error occurred while sending the email: ' . $e->getMessage());
} catch (Error $e) {
    error_log("Blueprint email Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    sendJsonResponse('error', 'A system error occurred: ' . $e->getMessage());
}
?>
