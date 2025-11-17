<?php
session_start();
require_once 'config/database.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'redirect' => 'login.php'
    ]);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $plan = $input['plan'] ?? '';
    $credits = (int)($input['credits'] ?? 0);
    $amount = $input['amount'] ?? 0;
    $paymentMethod = $input['payment_method'] ?? 'Unknown';
    $transactionId = $input['transaction_id'] ?? null;

    if (!$plan || $credits <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid plan or credits']);
        exit;
    }

    // Start a transaction for data safety
    $conn->beginTransaction();

    // Update the user's usable and total credit counters
    $stmt = $conn->prepare("
        UPDATE users
        SET 
            additional_credits = COALESCE(additional_credits, 0) + :credits,
            additional_credits_total = COALESCE(additional_credits_total, 0) + :credits
        WHERE id = :id
    ");
    $stmt->execute([
        'credits' => $credits,
        'id' => $_SESSION['user_id']
    ]);

    // Log the purchase in a separate table for record keeping
    $logStmt = $conn->prepare("
        INSERT INTO credit_purchases 
        (user_id, plan_name, credits_added, amount_paid, payment_method, transaction_id)
        VALUES (:user_id, :plan, :credits, :amount, :payment_method, :transaction_id)
    ");
    $logStmt->execute([
        'user_id' => $_SESSION['user_id'],
        'plan' => $plan,
        'credits' => $credits,
        'amount' => $amount,
        'payment_method' => $paymentMethod,
        'transaction_id' => $transactionId
    ]);

    // Commit both queries
    $conn->commit();

    // Update session for immediate reflection
    $_SESSION['additional_credits'] = ($_SESSION['additional_credits'] ?? 0) + $credits;
    $_SESSION['additional_credits_total'] = ($_SESSION['additional_credits_total'] ?? 0) + $credits;

    // Response
    echo json_encode([
        'success' => true,
        'message' => "Successfully added $credits credit(s) to your account.",
        'new_total_credits' => $_SESSION['additional_credits'],
        'lifetime_credits' => $_SESSION['additional_credits_total']
    ]);

} catch (Exception $e) {
    // Rollback in case of error
    if ($conn->inTransaction()) $conn->rollBack();
    error_log('Credit update error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>
