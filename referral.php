<?php
// Referral Link Handler
session_start();
require 'vendor/autoload.php';
require 'config/database.php';
require_once 'functions/user_management.php';

$db = Database::getInstance();
$userManager = new UserManagement($db->getConnection());

// Get referral token from URL
$referralToken = $_GET['token'] ?? '';

if (empty($referralToken)) {
    // No referral token, redirect to regular landing page
    header('Location: index.php');
    exit;
}

// Find the referrer
$referrer = $userManager->getUserByReferralToken($referralToken);

if (!$referrer) {
    // Invalid referral token, redirect to regular landing page
    header('Location: index.php');
    exit;
}

// Store referrer info in session for later use during registration
$_SESSION['referrer_id'] = $referrer['id'];
$_SESSION['referrer_token'] = $referralToken;

// Track the click in the referral_clicks table
$userManager->trackReferralClick(
    $referrer['id'],
    $_SERVER['REMOTE_ADDR'] ?? null,
    $_SERVER['HTTP_USER_AGENT'] ?? null,
    session_id()
);

// Redirect to index page (landing page) where user can register
header('Location: index.php?ref=' . $referralToken);
exit;
?>