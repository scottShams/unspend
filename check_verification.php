<?php
session_start();
header('Content-Type: application/json');

require 'config/database.php';
require_once 'functions/user_management.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['verified' => false, 'message' => 'User not logged in']);
    exit;
}

$db = Database::getInstance();
$userManager = new UserManagement($db->getConnection());
$user = $userManager->getUserById($_SESSION['user_id']);

if (!$user) {
    echo json_encode(['verified' => false, 'message' => 'User not found']);
    exit;
}

echo json_encode(['verified' => ($user['email_verified'] == 1)]);
?>