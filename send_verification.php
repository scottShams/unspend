<?php
session_start();
header('Content-Type: application/json');

require 'vendor/autoload.php';
require 'config/database.php';
require_once 'functions/user_management.php';
require_once 'functions/email_sender.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$source = $data['source'] ?? 'unknown';

$db = Database::getInstance();
$userManager = new UserManagement($db->getConnection());
$emailSender = new EmailSender();

$user = $userManager->getUserById($_SESSION['user_id']);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

if ($user['email_verified'] == 1) {
    echo json_encode(['success' => false, 'message' => 'Email already verified']);
    exit;
}

// Send verification email
if ($emailSender->sendVerificationEmail($user['email'], $user['name'], $user['verification_token'])) {
    // Set redirect session for verification page
    if ($source === 'summary') {
        $_SESSION['redirect_after_verify'] = 'summary';
    }

    echo json_encode([
        'success' => true,
        'message' => 'Verification email sent successfully',
        'source' => $source
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send verification email']);
}
?>