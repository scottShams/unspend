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
    $action = $input['action'] ?? '';

    if ($action !== 'decrement') {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }

    // Fetch user credits
    $stmt = $conn->prepare("SELECT additional_credits FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $currentCredits = (int)($user['additional_credits'] ?? 0);

    if ($currentCredits <= 0) {
        echo json_encode(['success' => false, 'message' => 'No additional credits to decrement']);
        exit;
    }

    // Decrement credits
    $stmt = $conn->prepare("UPDATE users SET additional_credits = additional_credits - 1 WHERE id = ?");
    $success = $stmt->execute([$_SESSION['user_id']]);

    if ($success) {
        $_SESSION['additional_credits'] = max(0, $currentCredits - 1);
        echo json_encode([
            'success' => true,
            'message' => 'Credit decremented successfully',
            'remaining_credits' => $_SESSION['additional_credits']
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to decrement credits']);
        exit;
    }

} catch (Exception $e) {
    error_log('Credit decrement error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
    exit;
}
?>
