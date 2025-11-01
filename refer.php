<?php
// Landing/Home Page
session_start();
require 'vendor/autoload.php';
require 'config/database.php';

// Page-specific variables
$pageTitle = 'unSpend | Refer & Earn';
$content = '';
ob_start();
include 'includes/refer_earn.php';
$content = ob_get_clean();

// Include the common layout
include 'layouts/app.php';
?>