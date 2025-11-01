<?php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Common layout file for all pages
// This file provides the HTML structure and includes common elements

// Set default values that can be overridden by child pages
$pageTitle = $pageTitle ?? 'Unspend | Elite Financial Analysis';
$pageDescription = $pageDescription ?? '';
$customHead = $customHead ?? '';
$bodyClass = $bodyClass ?? '';
$customScripts = $customScripts ?? '';

// Global user account check for modal functionality
$userHasAccount = false;
if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    require_once 'functions/user_management.php';
    $db = Database::getInstance();
    $userManager = new UserManagement($db->getConnection());
    $user = $userManager->getUserById($_SESSION['user_id']);
    if ($user && !empty($user['name']) && !empty($user['email']) && !empty($user['income'])) {
        $userHasAccount = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <?php if ($pageDescription): ?>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <?php endif; ?>

    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Load Chart.js for visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <!-- Load SweetAlert2 for beautiful alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Use Inter font -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f9fb; /* Very light, modern background */
        }
        /* Custom styles for the new deep violet (classy purple) CTA */
        .flat-cta {
            background-color: #5b21b6; /* Deep Violet 700 - Darker, richer purple */
            transition: all 0.2s ease-in-out;
            /* Deeper, more sophisticated shadow */
            box-shadow: 0 6px 10px -3px rgba(91, 33, 182, 0.4);
        }
        .flat-cta:hover {
            background-color: #6d28d9; /* Violet 800 */
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(91, 33, 182, 0.6);
        }
        /* Red border retained for urgent messaging */
        .fomo-box {
            border-left: 5px solid #ef4444;
        }
        /* Modified chart-container for a more prominent, centered view */
        .chart-container {
            position: relative;
            max-width: 700px; /* Increased max-width for prominence */
            margin: auto;
            height: 500px; /* Ensure chart has enough vertical space */
        }
    </style>

    <?php echo $customHead; ?>
</head>
<body class="<?php echo htmlspecialchars($bodyClass); ?>">

    <?php
    // Include header - can be overridden by setting $headerInclude variable
    $headerFile = $headerInclude ?? 'includes/header.php';
    if (file_exists($headerFile)) {
        include $headerFile;
    }
    ?>

    <!-- Main Content Area -->
    <main>
        <?php
        // This is where the page-specific content will be rendered
        // Content should be defined in the $content variable or included files
        echo $content ?? '';
        ?>
    </main>

    <?php
    // Include footer - can be overridden by setting $footerInclude variable
    $footerFile = $footerInclude ?? 'includes/footer.php';
    if (file_exists($footerFile)) {
        include $footerFile;
    }
    ?>

    <?php echo $customScripts; ?>

    <?php
    // Include default JavaScript - can be overridden by setting $jsInclude variable
    $jsFile = $jsInclude ?? 'js/script.js';
    if (file_exists($jsFile)): ?>
    
    <script src="<?php echo htmlspecialchars($jsFile); ?>"></script>
    <?php endif; ?>
    <script src="js/common.js"></script>
</body>
</html>