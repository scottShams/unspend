<?php
// Check if user is logged in and exists in database
$isLoggedIn = false;
$referralLink = '';

if (isset($_SESSION['user_id'])) {
    require_once 'functions/user_management.php';
    $db = Database::getInstance();
    $userManager = new UserManagement($db->getConnection());
    $user = $userManager->getUserById($_SESSION['user_id']);

    if ($user) {
        $isLoggedIn = true;
        $referralLink = "https://unspend.me/referral/" . $user['referral_token'];
    }
}
?>

<main>
    <div id="referralPage" class="py-16 md:py-24 bg-violet-50 min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-5xl font-extrabold text-violet-900 mb-4">
                The $10 Secret Agent Program: Share the Power!
            </h1>
            <h2 class="text-2xl text-violet-700 font-semibold mb-8 max-w-2xl mx-auto">
                Help your friends stop their financial leaks, and we'll reward your vigilance with a crisp **$10** for every Blueprint they download.
            </h2>
            
            <div class="bg-white p-8 sm:p-12 rounded-2xl shadow-2xl border-t-8 border-amber-500">
                <p class="text-lg text-gray-600 mb-6">
                    You've seen the truth in your own bank statements. Now, imagine your friend Bob or Aunt Carol finally conquering their "accidental" daily latte budget. You get $10, and they get financial freedom. It's a win-win for everyone (except Bob's local coffee shop).
                </p>

                <div class="space-y-6">
                    <!-- Step 1: Get Your Link -->
                    <div class="text-left bg-gray-50 p-5 rounded-lg border-l-4 border-violet-500">
                        <h3 class="text-xl font-bold text-violet-800 mb-3">1. Grab Your Secret Link:</h3>
                        <?php if ($isLoggedIn): ?>
                        <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3">
                            <input type="text" id="referralLink"
                                value="<?php echo htmlspecialchars($referralLink); ?>"
                                readonly
                                class="flex-grow px-4 py-3 border border-gray-300 rounded-md shadow-sm bg-white text-gray-600 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                            <button onclick="copyReferralLink()" class="flat-cta text-white py-3 px-6 rounded-lg font-bold shadow-md whitespace-nowrap">
                                <span id="copyButtonText">Copy Link</span>
                            </button>
                        </div>
                        <p class="text-sm text-gray-500 mt-2" id="copyStatus"></p>
                        <?php else: ?>
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                            <p class="text-amber-800 font-medium mb-3">Please log in to access your referral link</p>
                            <a href="login.php" class="flat-cta text-white py-2 px-4 rounded-lg font-bold text-sm inline-block">
                                Go to Login
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- NEW SELLING POINTS SECTION -->
                    <div class="text-left bg-gray-50 p-5 rounded-lg border-l-4 border-amber-500">
                        <h3 class="text-xl font-bold text-amber-800 mb-3">2. Why Share? The Rewards Are Unlimited:</h3>
                        <ul class="list-disc list-inside text-gray-700 space-y-3">
                            <li class="font-semibold">
                                Unlimited Earnings Potential: <span class="font-normal">There is **no cap** on how much you can earn. Share with 10 friends and get $100. Share with 100, and get $1,000!</span>
                            </li>
                            <li class="font-semibold">
                                Instant Cash Rewards: <span class="font-normal">Earn a crisp **$10** deposited directly to your account for every friend who runs the analysis and downloads the Wealth Blueprint.</span>
                            </li>
                            <li class="font-semibold">
                                Be the Financial Hero: <span class="font-normal">You're giving them the ultimate tool to expose their hidden spending habits and finally gain control. You're the friend who actually helps them save money!</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-10">
                    <p class="text-xl font-bold text-violet-700 mb-3">💰 Current Earnings: $120.00</p>
                    <p class="text-sm text-gray-500">Keep those wallets healthy!</p>
                </div>

            </div>
        </div>
    </div>
</main>