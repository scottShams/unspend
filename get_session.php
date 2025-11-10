<?php
session_start();
require_once 'config/database.php';
require_once 'functions/user_management.php';
header('Content-Type: application/json');

// Helper function to get user data from session or cookies
function getUserData() {
    return [
        'email' => $_SESSION['user_email'] ?? $_COOKIE['user_email'] ?? $_SESSION['temp_email'] ?? '',
        'name' => $_SESSION['user_name'] ?? $_COOKIE['user_name'] ?? $_SESSION['temp_name'] ?? '',
        'income' => $_SESSION['user_income'] ?? $_COOKIE['user_income'] ?? $_SESSION['temp_income'] ?? '',
        'analysis_count' => 0,
        'additional_credits' => 0,
        'additional_credits_total' => 0,
        'remaining_credits' => 0,
        'last_analysis_date' => ''
    ];
}

$data = getUserData();

try {
    if (!empty($data['email'])) {
        $pdo = Database::getInstance()->getConnection();
        $userMgmt = new UserManagement($pdo);
        $user = $userMgmt->getUserByEmail($data['email']);

        if ($user) {
            // Fetch analysis info
            $analysisCount = $userMgmt->getCompletedAnalysisCount($user['id']);
            $lastAnalysisData = $userMgmt->getLatestAnalysis($user['id']);
            $additionalCredits = (int)($user['additional_credits'] ?? 0);
            $additionalCreditsTotal = (int)($user['additional_credits_total'] ?? 0);

            $freeLimit = 3;
            $remaining = max(0, ($freeLimit - $analysisCount) + $additionalCredits);

            // Update $data
            $data['analysis_count'] = $analysisCount;
            $data['additional_credits'] = $additionalCredits;
            $data['additional_credits_total'] = $additionalCreditsTotal;
            $data['remaining_credits'] = $remaining;
            $data['last_analysis_date'] = $lastAnalysisData['upload_date'] ?? '';

            // Update sessions
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_income'] = $user['income'];
            $_SESSION['last_analysis_date'] = $lastAnalysisData['upload_date'] ?? '';
            $_SESSION['additional_credits'] = $additionalCredits;
            $_SESSION['additional_credits_total'] = $additionalCreditsTotal;

            // Update cookies (expires in 15 days)
            setcookie('user_email', $user['email'], time() + 15*24*60*60, "/");
            setcookie('user_name', $user['name'], time() + 15*24*60*60, "/");
            setcookie('user_income', $user['income'], time() + 15*24*60*60, "/");
        }
    }

    echo json_encode($data);

} catch (Exception $e) {
    error_log('get_session.php error: ' . $e->getMessage());
    echo json_encode(['error' => 'Server error occurred']);
}
?>
