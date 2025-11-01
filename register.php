<?php
// Registration Page
session_start();
require 'vendor/autoload.php';
require 'config/database.php';

// Page-specific variables
$pageTitle = 'unSpend | Register';
$content = '';
ob_start();
include 'includes/register.php';
$content = ob_get_clean();

// Include the common layout
include 'layouts/app.php';
?>