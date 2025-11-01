<?php
// API endpoint to get referral statistics for dashboard
session_start();
header('Content-Type: application/json');

require 'config/database.php';
require_once 'functions/user_management.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$db = Database::getInstance();
$userManager = new UserManagement($db->getConnection());

try {
    $stats = $userManager->getReferralStats($_SESSION['user_id']);
    $referralList = $userManager->getReferralList($_SESSION['user_id']);

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'referrals' => $referralList
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to load referral data']);
}
?>