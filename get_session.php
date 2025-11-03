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
}

echo json_encode($data);
?>