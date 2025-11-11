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
$userIncome = '';

if (isset($_SESSION['user_id'])) {
    $db = Database::getInstance();
    $userManager = new UserManagement($db->getConnection());
    $user = $userManager->getUserById($_SESSION['user_id']);

    if ($user) {
        $userHasAccount = true;
        $userName = $user['name'];
        $userEmail = $user['email'];
        $userIncome = $user['income'] ?? '';
        
        // Set cookies for user data (expires in 15 days)
        if (!empty($user['name'])) {
            setcookie('user_name', $user['name'], time() + 15*24*60*60, "/");
        }
        if (!empty($user['email'])) {
            setcookie('user_email', $user['email'], time() + 15*24*60*60, "/");
        }
        if (!empty($user['income'])) {
            setcookie('user_income', $user['income'], time() + 15*24*60*60, "/");
        }
    }
}

// Check for temporary user data in session (from previous form submission)
if (!$userHasAccount && isset($_SESSION['temp_name'], $_SESSION['temp_email'], $_SESSION['temp_income'])) {
    $userName = $_SESSION['temp_name'];
    $userEmail = $_SESSION['temp_email'];
    $userIncome = $_SESSION['temp_income'];
    
    // Set cookies for temporary user data (expires in 15 days)
    if (!empty($_SESSION['temp_name'])) {
        setcookie('user_name', $_SESSION['temp_name'], time() + 15*24*60*60, "/");
    }
    if (!empty($_SESSION['temp_email'])) {
        setcookie('user_email', $_SESSION['temp_email'], time() + 15*24*60*60, "/");
    }
    if (!empty($_SESSION['temp_income'])) {
        setcookie('user_income', $_SESSION['temp_income'], time() + 15*24*60*60, "/");
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

        // Track the click if not already tracked in this session
        if (!isset($_SESSION['referral_tracked'])) {
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

// Page-specific variables
$pageTitle = 'unSpend | Elite Financial Analysis';
$content = '';
ob_start();
include 'includes/landing_page.php';
$content = ob_get_clean();

// Include the common layout
include 'layouts/app.php';
?>
