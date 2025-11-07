<?php
// Landing/Home Page
session_start();


require 'vendor/autoload.php';
require 'config/database.php';

// Page-specific variables
$pageTitle = 'unSpend | Refer & Earn';
$customHead = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">';
$content = '';
ob_start();
include 'includes/refer_earn.php';
$content = ob_get_clean();

// Include the common layout
include 'layouts/app.php';
?>