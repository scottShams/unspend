<?php
session_start();
header('Content-Type: application/json'); // ensures correct JSON header
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'vendor/autoload.php';
require 'config/database.php';
require_once 'functions/user_management.php';
require_once 'functions/email_sender.php';

$db = Database::getInstance();
$userManager = new UserManagement($db->getConnection());
$emailSender = new EmailSender();
// Get database connection
$pdo = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'register') {
        // Handle user registration
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $income = trim($_POST['income'] ?? 0);

        if ($password !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
            exit;
        }

        // Check if user already exists
        $existingUser = $userManager->getUserByEmail($email);
        if ($existingUser) {
            echo json_encode(['success' => false, 'message' => 'Email already registered']);
            exit;
        }

        // Get referrer ID from session if exists
        $referrerId = $_SESSION['referrer_id'] ?? null;

        // Create new user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $user = $userManager->createOrGetUser($email, $name, $income, $hashedPassword, $referrerId);

        // Send verification email
        if ($emailSender->sendVerificationEmail($user['email'], $user['name'], $user['verification_token'])) {
            echo json_encode([
                'success' => true,
                'message' => 'Account created successfully! Please check your email to verify your account.',
                'needs_verification' => true
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Account created but failed to send verification email. Please contact support.']);
        }
    } elseif ($action === 'set_password') {
        // Handle password setting for existing user
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        if ($password !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
            exit;
        }

        $existingUser = $userManager->getUserByEmail($email);
        if (!$existingUser) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        // Update user with password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $existingUser['id']]);

        // Check if user is already verified
        if ($existingUser['email_verified'] == 1) {
            // User is already verified, set session and redirect to dashboard
            $_SESSION['user_id'] = $existingUser['id'];
            $_SESSION['user_authorized'] = true;
            $_SESSION['user_email'] = $existingUser['email'];
            $_SESSION['user_name'] = $existingUser['name'];

            echo json_encode([
                'success' => true,
                'redirect' => 'dashboard.php',
                'message' => 'Password set successfully! Redirecting to dashboard...'
            ]);
        } else {
            // User needs verification, send email
            if ($emailSender->sendVerificationEmail($existingUser['email'], $existingUser['name'], $existingUser['verification_token'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Password set successfully! Please check your email to verify your account.',
                    'needs_verification' => true
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Password set but failed to send verification email. Please contact support.']);
            }
        }
    } else {
        // Regular login
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $name = trim($_POST['name'] ?? $_SESSION['temp_name'] ?? '');
        $income = trim($_POST['income'] ?? $_SESSION['temp_income'] ?? 0);

        // Check if user exists
        $existingUser = $userManager->getUserByEmail($email);

        if (!$existingUser) {
            // Create new user with provided password
            if (empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Password is required for new users']);
                exit;
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $user = $userManager->createOrGetUser($email, $name, $income, $hashedPassword, $referrerId);
        } else {
            // Check if user has password set
            if (empty($existingUser['password'])) {
                echo json_encode(['success' => false, 'message' => 'Please set your password first', 'needs_password' => true]);
                exit;
            }

            // Authenticate existing user
            if (!$userManager->authenticateUser($email, $password)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
                exit;
            }
            $user = $existingUser;
        }

        // Check if user is verified
        if ($user['email_verified'] == 0) {
            echo json_encode(['success' => false, 'message' => 'Please verify your email address before logging in.']);
            exit;
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_authorized'] = true;
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];

        // Redirect after login
        $redirectUrl = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
        unset($_SESSION['redirect_after_login']);

        echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>