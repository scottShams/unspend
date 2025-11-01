<?php
// Landing/Home Page
session_start();
require 'vendor/autoload.php';
require 'config/database.php';

// Check if user is already logged in
if (isset($_SESSION['user_authorized']) && $_SESSION['user_authorized'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Check if user data exists in session and database
$userExistsInDB = false;
$userNeedsPassword = false;

if (isset($_SESSION['temp_email'])) {
    require_once 'functions/user_management.php';
    $db = Database::getInstance();
    $userManager = new UserManagement($db->getConnection());
    $existingUser = $userManager->getUserByEmail($_SESSION['temp_email']);

    if ($existingUser) {
        $userExistsInDB = true;
        // Check if user has password (new users won't have password)
        $userNeedsPassword = empty($existingUser['password']);
    }
}

// Page-specific variables
$pageTitle = 'unSpend | Login';
$content = '';
ob_start();
include 'includes/login.php';
$content = ob_get_clean();

// Include the common layout
include 'layouts/app.php';
?>