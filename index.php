<?php

// Landing/Home Page
session_start();
require 'vendor/autoload.php';
require 'config/database.php';
require_once 'functions/user_management.php';

// Check if user is logged in and has account
$userHasAccount = false;
$userName = '';
$userEmail = '';

if (isset($_SESSION['user_id'])) {
    $db = Database::getInstance();
    $userManager = new UserManagement($db->getConnection());
    $user = $userManager->getUserById($_SESSION['user_id']);

    if ($user) {
        $userHasAccount = true;
        $userName = $user['name'];
        $userEmail = $user['email'];
    }
}

// Check for referral parameter in URL
$refToken = $_GET['ref'] ?? '';
if (!empty($refToken)) {
    $db = Database::getInstance();
    $userManager = new UserManagement($db->getConnection());

    // Find the referrer
    $referrer = $userManager->getUserByReferralToken($refToken);

    if ($referrer) {
        // Store referrer info in session for later use during registration
        $_SESSION['referrer_id'] = $referrer['id'];
        $_SESSION['referrer_token'] = $refToken;
    }
}

// Page-specific variables
$pageTitle = 'unSpend | Elite Financial Analysis';
$content = '';
ob_start();
include 'includes/landing_page.php';
$content = ob_get_clean();

// Include the common layout
include 'layouts/app.php';
?>
