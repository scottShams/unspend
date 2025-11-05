<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $plan = $input['plan'] ?? '';
    $credits = $input['credits'] ?? 0;

    if (!$plan || !$credits) {
        echo json_encode(['success' => false, 'message' => 'Invalid plan or credits']);
        exit;
    }

    // Update credits for the logged-in user
    $stmt = $conn->prepare("UPDATE users 
        SET additional_credits = COALESCE(additional_credits, 0) + :credits 
        WHERE id = :id");

    $stmt->execute([
        'credits' => $credits,
        'id' => $_SESSION['user_id']
    ]);

    // Check if any row was updated
    if ($stmt->rowCount() > 0) {
        // Update session to reflect new credits
        if (!isset($_SESSION['additional_credits'])) {
            $_SESSION['additional_credits'] = 0;
        }
        $_SESSION['additional_credits'] += $credits;

        echo json_encode([
            'success' => true,
            'message' => "Successfully added $credits credit(s) to your account",
            'new_total_credits' => $_SESSION['additional_credits']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update credits (no rows changed)'
        ]);
    }


} catch (Exception $e) {
    error_log('Credit update error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>