<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config/database.php';
require_once 'functions/user_management.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$db = Database::getInstance();
$userManager = new UserManagement($db->getConnection());

// Debug: Log received data
error_log('Blueprint form submission - POST data: ' . print_r($_POST, true));

// Get form data
$age = trim($_POST['age'] ?? '');
$country = trim($_POST['country'] ?? '');
$occupation = trim($_POST['occupation'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$motivation = trim($_POST['motivation'] ?? '');

// Debug: Log processed data
error_log("Processed data - Age: $age, Country: $country, Occupation: $occupation, Gender: $gender, Motivation: $motivation");

// Validate required fields
if (empty($age) || empty($country) || empty($occupation) || empty($gender) || empty($motivation)) {
    error_log('Validation failed - missing fields');
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate age
if (!is_numeric($age) || $age < 18 || $age > 120) {
    error_log('Validation failed - invalid age: ' . $age);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid age between 18 and 120']);
    exit;
}

// Update user profile with blueprint data
$pdo = $db->getConnection();
$stmt = $pdo->prepare("UPDATE users SET age = ?, country = ?, occupation = ?, gender = ?, motivation = ?, blueprint_unlocked = 1, blueprint_unlocked_at = NOW() WHERE id = ?");

try {
    $result = $stmt->execute([$age, $country, $occupation, $gender, $motivation, $_SESSION['user_id']]);

    if ($result) {
        // Mark referral as completed if this user was referred
        $userManager->completeReferral($_SESSION['user_id']);
    
        error_log('Blueprint data saved successfully for user ID: ' . $_SESSION['user_id']);
    
        // Redirect back to blueprint page with success message
        header('Location: blueprint.php?success=1');
        exit;
    } else {
        error_log('Database update failed');
    
        // Redirect back with error
        header('Location: blueprint.php?error=1');
        exit;
    }
} catch (Exception $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>