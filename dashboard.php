<?php
// Landing/Home Page
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_email']) || empty($_SESSION['user_email'])) {
    header('Location: index.php');
    exit();
}

require 'vendor/autoload.php';
require 'config/database.php';

// Page-specific variables
$pageTitle = 'unSpend | Dashboard';
$customHead = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">';
$content = '';
ob_start();
include 'includes/dashboard.php';
$content = ob_get_clean();

// Custom scripts for dashboard page
$customScripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="js/dashboard.js"></script>
';

// Include the common layout
include 'layouts/app.php';
?>