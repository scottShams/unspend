<?php
session_start();

$data = [
    'email' => $_SESSION['user_email'] ?? $_SESSION['temp_email'] ?? '',
    'name' => $_SESSION['user_name'] ?? $_SESSION['temp_name'] ?? '',
    'income' => $_SESSION['user_income'] ?? $_SESSION['temp_income'] ?? '',
    'analysis_count' => 0
];

if (!empty($data['email'])) {
    require_once 'config/database.php';
    require_once 'functions/user_management.php';
    $pdo = Database::getInstance()->getConnection();
    $userMgmt = new UserManagement($pdo);

    $user = $userMgmt->getUserByEmail($data['email']);
    if ($user) {
        $data['analysis_count'] = $userMgmt->getCompletedAnalysisCount($user['id']);
    }

    if ($data['analysis_count'] >= 3 && (!isset($_SESSION['user_authorized']) || $_SESSION['user_authorized'] === false)) {
        $referrerId = $_SESSION['referrer_id'] ?? null;

        // Check if user is already logged in (existing user uploading another file)
        if (isset($_SESSION['user_id'])) {
            // Existing user - get their data from session
            $user = [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'],
                'name' => $_SESSION['user_name'],
                'income' => $_SESSION['user_income']
            ];
        } else {
            // New user - create account
            $user = $userMgmt->createOrGetUser($data['email'], $data['name'], $data['income'], null, $referrerId);
            // Store user info in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_income'] = $user['income'];
        }
        // Update data array with new session values
        $data['email'] = $_SESSION['user_email'];
        $data['name'] = $_SESSION['user_name'];
        $data['income'] = $_SESSION['user_income'];
    }
}

echo json_encode($data);
?>