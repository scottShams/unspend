<?php
// Page-specific variables
$pageTitle = "Terms and Conditions | unSpend";
$customHead = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">';
$content = '';
ob_start();
include 'includes/terms.php';
$content = ob_get_clean();

// Include the common layout
include 'layouts/app.php';
?>