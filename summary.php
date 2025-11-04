<?php
// Summary Page
session_start();
require 'config/database.php';
require 'functions/user_handler.php';
require 'functions/user_management.php';

// Get database connection and user info
$pdo = Database::getInstance()->getConnection();
$userManager = new UserManagement($pdo);

$user = $userManager->getUserByEmail($_SESSION['user_email']);

// Get user info from session
$userId = $user['id'] ?? null;
$userEmail = $user['email'] ?? null;
$userName = $user['name'] ?? 'User';
$userIncome = number_format($user['income'], 2) ?? 0;


// If we have user ID, get their analysis history
$userHistory = [];
$latestAnalysis = null;
if ($userId) {
    $userHistory = $userManager->getUserAnalysisHistory($userId, 5); // Get last 5 analyses
    $latestAnalysis = $userManager->getLatestAnalysis($userId);
}

// Page-specific variables
$pageTitle = 'unSpend | Expense Summary';
// $headerInclude = 'includes/summary_header.php';
$content = '';
ob_start();
include 'includes/summary_content.php';
$content = ob_get_clean();

// Get currency from latest analysis if available
$currency = 'USD'; // default
if ($latestAnalysis && !empty($latestAnalysis['analysis_result'])) {
    $analysisData = json_decode($latestAnalysis['analysis_result'], true);
    $currency = $analysisData['summary']['currency'] ?? 'USD';
}

// Custom scripts for summary page
$customScripts = '<script>
// Initialize analysisData from PHP session if available
' . (isset($_SESSION['analysisData']) ? '
if (typeof window.analysisData !== "undefined") {
    window.analysisData.totalSpent = ' . ($_SESSION['analysisData']['summary']['totalSpent'] ?? 0) . ';
    window.analysisData.monthlyIncome = ' . ($_SESSION['analysisData']['summary']['totalCredit'] ?? 0) . ';
    window.analysisData.totalLeaks = ' . ($_SESSION['analysisData']['summary']['totalDiscretionaryLeaks'] ?? 0) . ';
    window.analysisData.expenses = combineExpenses(' . json_encode($_SESSION['analysisData']['categorizedExpenses'] ?? []) . ');
    window.analysisData.statementPeriod = ' . json_encode($_SESSION['analysisData']['summary']['statementPeriod'] ?? null) . ';
    window.analysisData.currentAnalysisId = ' . ($latestAnalysis['id'] ?? 'null') . ';
    window.analysisData.currency = "' . ($_SESSION['analysisData']['summary']['currency'] ?? 'USD') . '";
}
' : '') . '
// Set latest analysis ID for auto-loading and currency
window.latestAnalysisId = ' . ($latestAnalysis['id'] ?? 'null') . ';
window.currency = "' . $currency . '";
</script>';

// Override JavaScript file
$jsInclude = 'js/summary.js';

// Include Chart.js and common functions for the summary page
$customScripts .= '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

// Include the common layout
include 'layouts/app.php';

// Clean up session data after use
if (isset($_SESSION['analysisData'])) {
    unset($_SESSION['analysisData']);
}
?>