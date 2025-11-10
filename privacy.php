<?php


// Page-specific variables
$pageTitle = "Privacy Policy | unSpend";
$customHead = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">';
$content = '';
ob_start();
include 'includes/privacy.php';
$content = ob_get_clean();

// Include the common layout
include 'layouts/app.php';
?>