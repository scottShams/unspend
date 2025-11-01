<?php

session_start();
require '../vendor/autoload.php';
require '../config/database.php';
require 'user_management.php';

// Get database connection
$pdo = Database::getInstance()->getConnection();

// Check if request is AJAX
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Set JSON header for AJAX requests
if ($isAjax) {
    header('Content-Type: application/json');
}

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["bankStatementFile"])) {
    // $target_dir = "../uploads\\";
    // if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

    // Always build paths dynamically instead of hardcoding slashes
    $target_dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;

    // Create the folder if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $target_dir . basename($_FILES["bankStatementFile"]["name"]);
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if ($fileType != "pdf" && $fileType != "csv") {
        $error = "Sorry, only PDF or CSV files are allowed.";
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        } else {
            echo $error;
            exit;
        }
    }

    if (move_uploaded_file($_FILES["bankStatementFile"]["tmp_name"], $target_file)) {
        try {
            // Include function files
            require 'parsers.php';
            require 'api_handler.php';
            require 'database_handler.php';

            // Get or create user first
            $userManager = new UserManagement($pdo);
            $referrerId = $_SESSION['referrer_id'] ?? null;

            // Check if user is already logged in (existing user uploading another file)
            if (isset($_SESSION['user_id'])) {
                // Existing user - get their data from session
                $user = [
                    'id' => $_SESSION['user_id'],
                    'email' => $_SESSION['user_email'],
                    'name' => $_SESSION['user_name'],
                    'income' => $_SESSION['user_income']
                ];
            } else {
                // New user - create account
                $user = $userManager->createOrGetUser($_POST['modal-email'], $_POST['modal-name'], $_POST['modal-income'], null, $referrerId);
                // Store user info in session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_income'] = $user['income'];
            }

            // Check if this file has already been analyzed for this user
            $stmt = $pdo->prepare("SELECT analysis_result FROM uploads WHERE user_id = ? AND filename = ?");
            $stmt->execute([$user['id'], $target_file]);
            $existingAnalysis = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingAnalysis) {
                // File already analyzed, fetch the data
                $analysisData = json_decode($existingAnalysis['analysis_result'], true);
            } else {
                // Proceed with parsing and analysis
                // Parse the file
                $transactions = parseBankStatement($target_file, $fileType);

                // Prepare CSV
                $rawStatement = prepareCSV($transactions);

                // API Call (now checks for existing analysis first)
                $analysisData = callGeminiAPI($rawStatement, $pdo, $user['id'], $target_file);

                // Save to database with user ID
                saveAnalysisToDatabase($pdo, $target_file, $user['id'], json_encode($analysisData));
            }

            // Store in session
            $_SESSION['analysisData'] = $analysisData;

            if ($isAjax) {
                echo json_encode(['success' => true]);
            } else {
                header("Location: ../summary.php");
            }
            exit();
        } catch (Exception $e) {
            $error = "Error processing file: " . $e->getMessage();
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $error]);
            } else {
                throw $e;
            }
            exit();
        }
    } else {
        $error = "Sorry, there was an error uploading your file.";
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => $error]);
        } else {
            echo $error;
        }
        exit();
    }
} else {
    $error = "Invalid request.";
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $error]);
    } else {
        echo $error;
    }
    exit();
}

?>