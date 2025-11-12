<?php
session_start();
require 'vendor/autoload.php';
require 'config/database.php';
require_once 'functions/user_management.php';

$message = '';
$messageType = '';

if (isset($_GET['token'])) {
    $token = trim($_GET['token']);

    $db = Database::getInstance();
    $userManager = new UserManagement($db->getConnection());

    $user = $userManager->getUserByVerificationToken($token);

    if ($user) {
        // Verify the user
        if ($userManager->verifyUserEmail($user['id'])) {
            // Set session for the verified user
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_authorized'] = isset($_SESSION['redirect_after_verify']) && $_SESSION['redirect_after_verify'] === 'summary' ? false : true;
            
            $message = 'Your email has been successfully verified! You are now logged in.';
            $messageType = 'success';

            // Check if user came from summary page
            $redirectUrl = isset($_SESSION['redirect_after_verify']) && $_SESSION['redirect_after_verify'] === 'summary'
                ? 'blueprint.php'
                : 'dashboard.php';

            unset($_SESSION['redirect_after_verify']);

            // Redirect after 3 seconds
            header("refresh:3;url=$redirectUrl");
        } else {
            $message = 'There was an error verifying your email. Please try again.';
            $messageType = 'error';
        }
    } else {
        $message = 'Invalid or expired verification link. Please request a new verification email.';
        $messageType = 'error';
    }
} else {
    $message = 'No verification token provided.';
    $messageType = 'error';
}

// Page-specific variables
$pageTitle = 'unSpend | Email Verification';
$content = '';
ob_start();
?>

<main class="py-16 md:py-32 bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white p-8 sm:p-10 rounded-xl shadow-2xl border-t-8 border-violet-700">
            <div class="text-center">
                <?php if ($messageType === 'success'): ?>
                    <div class="mb-6">
                        <svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Email Verified!</h2>
                    <p class="text-green-600 mb-4"><?php echo htmlspecialchars($message); ?></p>
                    <p class="text-sm text-gray-500 mb-6">Redirecting...</p>
                    
                    <!-- Preloader -->
                    <div class="flex justify-center items-center">
                        <div class="relative">
                            <div class="w-16 h-16 border-4 border-violet-200 border-t-violet-600 rounded-full animate-spin"></div>
                            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                                <svg class="w-6 h-6 text-violet-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mb-6">
                        <svg class="mx-auto h-16 w-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Verification Failed</h2>
                    <p class="text-red-600 mb-4"><?php echo htmlspecialchars($message); ?></p>
                    <a href="login.php" class="flat-cta text-white py-2 px-4 rounded-lg font-bold text-sm inline-block">
                        Back to Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php
$content = ob_get_clean();

// Include the common layout
include 'layouts/app.php';
?>

<?php if ($messageType === 'success'): ?>
<style>
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .animate-spin {
        animation: spin 1s linear infinite;
    }
</style>
<?php endif; ?>