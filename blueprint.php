<?php
// Blueprint Page
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'vendor/autoload.php';
require 'config/database.php';
require_once 'functions/user_management.php';

// Check if user has already filled blueprint data
$db = Database::getInstance();
$userManager = new UserManagement($db->getConnection());
$user = $userManager->getUserById($_SESSION['user_id']);

// Check if user has blueprint data (age, country, etc.)
$hasBlueprintData = !empty($user['age']) && !empty($user['country']) && !empty($user['occupation']) && !empty($user['gender']) && !empty($user['motivation']);

// Page-specific variables
$pageTitle = 'unSpend | Blueprint';
$content = '';
ob_start();
include 'includes/blueprint.php';
$content = ob_get_clean();

// Custom scripts for blueprint page
$customScripts = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="js/blueprint.js"></script>
';

// Include the common layout
include 'layouts/app.php';
?>