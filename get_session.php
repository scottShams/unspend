<?php
session_start();
require_once 'config/database.php';
require_once 'functions/user_management.php';
header('Content-Type: application/json');

$data = [
    'email' => $_SESSION['user_email'] ?? $_SESSION['temp_email'] ?? '',
    'name' => $_SESSION['user_name'] ?? $_SESSION['temp_name'] ?? '',
    'income' => $_SESSION['user_income'] ?? $_SESSION['temp_income'] ?? '',
    'analysis_count' => 0,
    'additional_credits' => 0,
    'additional_credits_total' => 0,
    'remaining_credits' => 0
];

try {
    if (!empty($data['email'])) {
        $pdo = Database::getInstance()->getConnection();
        $userMgmt = new UserManagement($pdo);
        $user = $userMgmt->getUserByEmail($data['email']);

        if ($user) {
            $analysisCount = $userMgmt->getCompletedAnalysisCount($user['id']);
            $additionalCredits = (int)($user['additional_credits'] ?? 0);
            $additionalCreditsTotal = (int)($user['additional_credits_total'] ?? 0);

            // Default free limit = 3
            $freeLimit = 3;

            // Remaining credits = (free limit - used) + additional
            $remaining = max(0, ($freeLimit - $analysisCount) + $additionalCredits);

            $data['analysis_count'] = $analysisCount;
            $data['additional_credits'] = $additionalCredits;
            $data['additional_credits_total'] = $additionalCreditsTotal;
            $data['remaining_credits'] = $remaining;

            // add data at sessions
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_income'] = $user['income'];
            
            // Keep sessions updated
            $_SESSION['additional_credits'] = $additionalCredits;
            $_SESSION['additional_credits_total'] = $additionalCreditsTotal;
        }
    }

    echo json_encode($data);
} catch (Exception $e) {
    error_log('get_session.php error: ' . $e->getMessage());
    echo json_encode(['error' => 'Server error occurred']);
}
?>
