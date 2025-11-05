<?php
// Get user data and referral link
if (isset($_SESSION['user_id'])) {
    require_once 'functions/user_management.php';
    $db = Database::getInstance();
    $userManager = new UserManagement($db->getConnection());
    $user = $userManager->getUserById($_SESSION['user_id']);

    if ($user) {
        $referralLink = "https://unspend.me/referral/" . $user['referral_token'];
    }
}
?>

<main>
    <!-- DASHBOARD PAGE CONTENT CONTAINER (NEW) -->
    <div id="dashboardPage" class="py-16 md:py-24 bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-extrabold text-gray-900 text-center mb-4">
                Your Referral Success Dashboard
            </h1>
            <p class="text-xl text-gray-600 text-center mb-12">
                Track your earnings and the status of every friend you've helped.
            </p>

            <!-- 1. KEY METRICS CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16">
                
                <!-- Earnings Card -->
                <div class="bg-violet-700 text-white p-6 rounded-xl shadow-2xl border-b-4 border-amber-400">
                    <p class="text-sm font-semibold opacity-80 uppercase">Total Confirmed Earnings</p>
                    <p class="text-4xl font-extrabold mt-1" id="dashTotalEarnings">$0.00</p>
                    <p class="text-xs mt-2 opacity-70">Payouts from completed Blueprint purchases.</p>
                </div>
                
                <!-- Total Referrals Card -->
                <div class="bg-white p-6 rounded-xl shadow-2xl border-b-4 border-violet-500">
                    <p class="text-sm font-semibold text-gray-500 uppercase">Total Referrals (Clicks)</p>
                    <p class="text-4xl font-extrabold mt-1 text-violet-700" id="dashTotalReferrals">0</p>
                    <p class="text-xs mt-2 text-gray-500">Friends who visited the site with your link.</p>
                </div>

                <!-- Pending Card -->
                <div class="bg-white p-6 rounded-xl shadow-2xl border-b-4 border-red-500">
                    <p class="text-sm font-semibold text-gray-500 uppercase">Referrals Still Pending</p>
                    <p class="text-4xl font-extrabold mt-1 text-red-600" id="dashPendingReferrals">0</p>
                    <p class="text-xs mt-2 text-gray-500">Sign-ups who have not yet purchased the Blueprint.</p>
                </div>
            </div>

            <!-- 2. GRAPHICAL SUMMARY & REFERRAL LINK -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-16">
                
                <!-- Chart Column -->
                <div class="lg:col-span-2 bg-white p-4 sm:p-8 rounded-xl shadow-2xl">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Referral Conversion Rate</h2>
                    <div class="chart-container h-[350px]">
                        <!-- Canvas for Chart.js -->
                        <canvas id="referralDoughnutChart"></canvas>
                    </div>
                </div>
                
                <!-- Link & Payout Column -->
                <div class="lg:col-span-1 bg-violet-700 text-white p-6 rounded-xl shadow-2xl flex flex-col justify-center">
                    <h3 class="text-2xl font-bold mb-4">Your Secret Agent Link</h3>
                    <p class="text-sm opacity-80 mb-4">
                        Share this link to earn commissions on every successful Blueprint download.
                    </p>

                    <input type="text" id="dashboardReferralLink"
                        value="<?php echo htmlspecialchars($referralLink ?? ''); ?>"
                        readonly
                        class="flex-grow px-4 py-3 mb-4 border border-violet-400 rounded-md shadow-sm bg-violet-600 text-white font-mono text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">

                    <button onclick="copyReferralLink()"
                        class="bg-amber-400 text-violet-900 py-3 px-6 rounded-lg font-bold shadow-md hover:bg-amber-300 transition duration-150 mb-6">
                        Copy Link to Share
                    </button>

                    <div class="flex justify-center gap-4">
                        <button onclick="shareVia('whatsapp')" class="w-12 h-12 flex items-center justify-center rounded-full bg-green-500 hover:bg-green-400 shadow-md transition">
                            <i class="fab fa-whatsapp text-xl"></i>
                        </button>

                        <button onclick="shareVia('messenger')" class="w-12 h-12 flex items-center justify-center rounded-full bg-blue-500 hover:bg-blue-400 shadow-md transition">
                            <i class="fab fa-facebook-messenger text-xl"></i>
                        </button>

                        <button onclick="shareVia('email')" class="w-12 h-12 flex items-center justify-center rounded-full bg-amber-500 hover:bg-amber-400 shadow-md transition">
                            <i class="fas fa-envelope text-xl"></i>
                        </button>

                        <button onclick="shareVia('twitter')" class="w-12 h-12 flex items-center justify-center rounded-full bg-sky-500 hover:bg-sky-400 shadow-md transition">
                            <i class="fab fa-twitter text-xl"></i>
                        </button>

                        <button onclick="shareVia('linkedin')" title="Share on LinkedIn"
                            class="w-12 h-12 flex items-center justify-center rounded-full bg-blue-700 hover:bg-blue-600 shadow-md transition">
                            <i class="fab fa-linkedin-in text-xl"></i>
                        </button>

                        <button onclick="nativeShare()" class="w-12 h-12 flex items-center justify-center rounded-full bg-gray-200 hover:bg-gray-300 text-violet-700 shadow-md transition">
                            <i class="fas fa-share-alt text-xl"></i>
                        </button>
                    </div>
                </div>

            </div>

            <!-- 3. DETAILED REFERRAL LIST -->
            <div class="bg-white p-4 sm:p-8 rounded-xl shadow-2xl">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Individual Referral Status</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referral Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Referred</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Commission</th>
                            </tr>
                        </thead>
                        <tbody id="referralTableBody" class="bg-white divide-y divide-gray-200">
                            <!-- Data populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</main>