<?php
// Landing/Home Page
// Landing/Home Page
session_start();

// Check if BOTH session AND cookie are empty → redirect user
if (
    (!isset($_SESSION['user_email']) || empty($_SESSION['user_email'])) &&
    (!isset($_COOKIE['user_email']) || empty($_COOKIE['user_email']))
) {
    header('Location: index.php');
    exit();
}

// If session is empty BUT cookie exists → restore session automatically
if (
    (!isset($_SESSION['user_email']) || empty($_SESSION['user_email'])) &&
    (isset($_COOKIE['user_email']) && !empty($_COOKIE['user_email']))
) {
    $_SESSION['user_email'] = $_COOKIE['user_email'];
}

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

// Page-specific variables
$pageTitle = 'unSpend | Pricing';
$customHead = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">';
$content = '';
ob_start();
include 'includes/pricing.php';
$content = ob_get_clean();

// Include the common layout
include 'layouts/app.php';
?>