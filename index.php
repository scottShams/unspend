<?php
session_start();
require 'vendor/autoload.php';
require 'config/database.php';
require_once 'functions/user_management.php';

$db = Database::getInstance();
$userManager = new UserManagement($db->getConnection());

$userHasAccount = false;
$userName = $userEmail = $userIncome = '';

// --- Check existing user (by cookie or session)
if (!empty($_COOKIE['user_email'])) {
    $user = $userManager->getUserByEmail($_COOKIE['user_email']);
} elseif (!empty($_SESSION['user_id'])) {
    $user = $userManager->getUserById($_SESSION['user_id']);
} else {
    $user = null;
}

if ($user) {
    $userHasAccount = true;
    [$userName, $userEmail, $userIncome] = [$user['name'], $user['email'], $user['income'] ?? ''];

    $analysisCount = $userManager->getCompletedAnalysisCount($user['id']);
    $additionalCredits = (int)($user['additional_credits'] ?? 0);
    $additionalCreditsTotal = (int)($user['additional_credits_total'] ?? 0);
    $freeLimit = 3;
    $remaining = max(0, ($freeLimit - $analysisCount) + $additionalCredits);

    // Set persistent cookies
    foreach (['name' => $userName, 'email' => $userEmail, 'income' => $userIncome] as $key => $val) {
        if (!empty($val)) setcookie("user_$key", $val, time() + 15*24*60*60, '/');
    }
}
// --- If not found, fallback to temporary session data
elseif (isset($_SESSION['temp_name'], $_SESSION['temp_email'], $_SESSION['temp_income'])) {
    [$userName, $userEmail, $userIncome] = [$_SESSION['temp_name'], $_SESSION['temp_email'], $_SESSION['temp_income']];

    foreach (['name' => $userName, 'email' => $userEmail, 'income' => $userIncome] as $key => $val) {
        if (!empty($val)) setcookie("user_$key", $val, time() + 15*24*60*60, '/');
    }
}

// --- Handle referral tracking
if (!empty($_GET['ref'])) {
    $refToken = $_GET['ref'];
    $referrer = $userManager->getUserByReferralToken($refToken);

    if ($referrer) {
        $_SESSION['referrer_id'] = $referrer['id'];
        $_SESSION['referrer_token'] = $refToken;

        if (empty($_SESSION['referral_tracked'])) {
            $userManager->trackReferralClick(
                $referrer['id'],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                session_id()
            );
            $_SESSION['referral_tracked'] = true;
        }
    }
}

// --- Render page
$pageTitle = 'unSpend | Elite Financial Analysis';
ob_start();
include 'includes/landing_page.php';
$content = ob_get_clean();
include 'layouts/app.php';
