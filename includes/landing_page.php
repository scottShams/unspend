<main>
    <!-- LANDING PAGE CONTENT CONTAINER (Hidden after analysis is complete) -->
    <div id="landingPage">
        <!-- Hero Section -->
        <section class="py-16 md:py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <?php if (isset($user)): ?>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 leading-tight mb-4">
                        Welcome back <?= htmlspecialchars($user['name']) ?> <br>
                        You have analysed <?= (int)$analysisCount ?> bank statements last time you were here.<br>
                        You have <?= (int)$remaining ?> credits available.
                    </h1>
                <?php else: ?>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 leading-tight mb-4">
                        STOP Overspending. Analyse your Expense <br>and Start Growing Wealth.
                    </h1>
                <?php endif; ?>
				<br />
                <br />

 				<!-- Primary CTA -->
                <div id="cta">
                    <?php
                    // Determine button label, link, and optional message
                    if ($userHasAccount) {
                        if (isset($remaining) && $remaining <= 0) {
                            // No credits left
                            $ctaText = 'Buy Credit $1.99';
                            $ctaLink = 'pricing.php';
                            $ctaClass = '';
                            $ctaMessage = 'You’ve used all your credits. Get more to continue analyzing statements.';
                        } else {
                            // Credits available
                            $ctaText = 'Upload Another Statement';
                            $ctaLink = '#'; // keep existing modal or upload link
                            $ctaClass = 'cta-trigger';
                            $ctaMessage = 'Go ahead and analyse another bank statement by clicking the big button below.';
                        }
                    } else {
                        // New user
                        $ctaText = 'Start Your 3 Months FREE Analysis Now!!';
                        $ctaLink = '#'; // signup or upload modal
                        $ctaClass = 'cta-trigger';
                        $ctaMessage = 'No credit card required for your first 90 days of clarity.';
                    }
                    ?>

                    <!-- Main CTA Button -->
                    <a href="<?= htmlspecialchars($ctaLink) ?>"
                    class="flat-cta text-lg sm:text-xl md:text-2xl lg:text-3xl text-white px-6 sm:px-8 md:px-10 py-3 sm:py-4 rounded-xl font-bold uppercase shadow-2xl tracking-wider <?= htmlspecialchars($ctaClass) ?> block text-center"
                    data-user-has-account="<?= $userHasAccount ? 'true' : 'false'; ?>">
                    <?= htmlspecialchars($ctaText) ?>
                    </a>

                    <!-- Message under button -->
                    <p class="mt-4 text-sm text-gray-500">
                        <?= htmlspecialchars($ctaMessage) ?>
                    </p>
                </div>

                
                <!-- Key Highlights & Benefits -->
                <div class="mb-10 flex flex-wrap justify-center gap-4 sm:gap-8 max-w-4xl mx-auto">
                        
                    <span class="flex items-center text-lg text-gray-700 bg-violet-100 rounded-full px-4 py-2 shadow-sm font-medium">
                        <svg class="w-6 h-6 mr-2 text-violet-800" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        AI-Driven Expense Categorization
                    </span>
                    <span class="flex items-center text-lg text-gray-700 bg-violet-100 rounded-full px-4 py-2 shadow-sm font-medium">
                        <svg class="w-6 h-6 mr-2 text-violet-800" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        Pattern Detection (The First Step)
                    </span>
                    <span class="flex items-center text-lg text-gray-700 bg-violet-100 rounded-full px-4 py-2 shadow-sm font-medium">
                        <svg class="w-6 h-6 mr-2 text-violet-800" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2.32 0-4 1.57-4 3.5 0 2.22 2.67 4.5 4 4.5s4-2.28 4-4.5c0-1.93-1.68-3.5-4-3.5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18s-4-3-4-5c0-2.21 1.79-4 4-4s4 1.79 4 4c0 2-4 5-4 5z"></path></svg>
                        Wealth Blueprint Tailored to YOU - Get Rich!
                    </span>
                </div>

 				<p class="text-xl sm:text-2xl text-red-600 font-semibold mb-8">
                    <span class="bg-yellow-100 px-3 py-1 rounded-full inline-block">Go Ahead and Upload your Bank Statement.</span> Are you struggling to Save and Get Richer, but don't know why you're not progressing? Most people Fail to manage their Expenses properly. Transform your life, Start Today with our AI Driven Expense Analysis to get you on the Right Track.
                </p>
            </div>
        </section>

        <!-- Scaremongering / Urgency Section -->
        <section class="py-16 bg-red-50" id="urgency">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 fomo-box bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-3xl font-bold text-red-700 mb-4">The Truth You Can't Afford to Ignore.</h2>
                <p class="text-xl text-gray-700 mb-4">
                    If you don't know exactly where every dollar is going, you're not saving—you're, <strong>leaking money</strong>.
                    Most people overspend by 15-20% <strong>every single month</strong> on easily avoidable expenses.
                    Without an immediate, clear analysis, you're guaranteeing you'll stay on the treadmill of living paycheck-to-paycheck.
                    <strong>Stop hoping to get rich. Start planning it.</strong>
                </p>
                <div class="flex justify-center mt-6">
                    <a href="#" class="flat-cta text-lg sm:text-xl text-white px-6 sm:px-8 py-3 rounded-lg font-semibold uppercase cta-trigger block text-center" data-user-has-account="<?php echo $userHasAccount ? 'true' : 'false'; ?>">
                        <?php echo $userHasAccount ? 'Upload Another Statement' : 'Don\'t Wait, Get your Tailored Action Plan to get Richer'; ?>
                    </a>
                </div>
            </div>
        </section>

        <!-- Features & AI Analysis Section (Content Omitted for brevity, retaining structure) -->
        <section class="py-20" id="features">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-4xl font-extrabold text-gray-900 mb-12 text-center">How UnSpend Reveals Your Financial Blind Spots</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-10">

                    <!-- Feature 1: Upload & AI Categorization -->
                    <div class="bg-white p-6 rounded-xl shadow-xl hover:shadow-2xl transition duration-300 transform hover:-translate-y-1 border-b-4 border-amber-500">
                        <div class="text-5xl text-violet-800 mb-4">📁</div>
                        <h3 class="text-2xl font-bold mb-3 text-gray-800">1. Effortless Statement Upload</h3>
                        <p class="text-gray-600">Securely upload your bank statement (PDF or CSV). Our <strong>cutting-edge AI</strong> instantly processes thousands of raw transactions, turning messy data into clean, understandable information.</p>
                    </div>

                    <!-- Feature 2: Summary & Pattern Display -->
                    <div class="bg-white p-6 rounded-xl shadow-xl hover:shadow-2xl transition duration-300 transform hover:-translate-y-1 border-b-4 border-amber-500">
                        <div class="text-5xl text-violet-800 mb-4">📊</div>
                        <h3 class="text-2xl font-bold mb-3 text-gray-800">2. Instant Category Summary</h3>
                        <p class="text-gray-600">See a clear, visual breakdown. We display the major categories of your spending (e.g., Dining, Subscriptions, Groceries) and show the exact <strong>portion of your monthly income</strong> they consume.</p>
                    </div>

                    <!-- Feature 3: Pattern Identification -->
                    <div class="bg-white p-6 rounded-xl shadow-xl hover:shadow-2xl transition duration-300 transform hover:-translate-y-1 border-b-4 border-amber-500">
                        <div class="text-5xl text-violet-800 mb-4">🔍</div>
                        <h3 class="text-2xl font-bold mb-3 text-gray-800">3. Identify Hidden Spending Patterns</h3>
                        <p class="text-gray-600">Unlock the complex data patterns showing <strong>when</strong> and <strong>why</strong> your money leaks. See the truth behind your habits, giving you the power to target problems.</p>
                        <!-- <p class="mt-4 text-sm text-red-500 font-semibold"> (Full personalized suggestions require the **$57 Wealth Growth Blueprint upgrade**.)</p> -->
                    </div>
                </div>
            </div>
        </section>

        <!-- Wealth Growth Blueprint Upgrade Section -->
        <section class="py-16 bg-red-600" id="blueprint-upgrade">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center bg-white p-8 rounded-xl shadow-2xl border-4 border-yellow-400">
                <h2 class="text-4xl font-extrabold text-gray-900 mb-4">The Missing Piece: Stop Living Paycheck-to-Paycheck</h2>
                <p class="text-xl text-gray-700 mb-6">You have the data. Now, get the <strong>AI-Driven Action Plan</strong> to Help you know what to do to Transform your Spending Habits and Start Building Wealth. You are <strong>ONE STEP Away </strong>from the start of Transformation.</p>
                <p class="text-5xl font-extrabold text-amber-500 mb-8">
                    Unlock the Blueprint <span class="text-red-600">|</span> Only $37 <span class="text-lg font-medium text-gray-500 block sm:inline"><br />(One-Time Payment)</span>
                </p>

                <a href="#" class="bg-red-600 hover:bg-red-700 text-white px-6 sm:px-8 md:px-10 py-3 sm:py-4 rounded-xl font-bold text-lg sm:text-xl uppercase shadow-2xl tracking-wider cta-trigger block text-center">
                    YES, Unlock My Blueprint
                </a>
                <p class="mt-3 text-sm text-gray-500">Available to all users, regardless of your current plan (Free, Monthly, or Annual).</p>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section class="py-20 bg-gray-100" id="testimonials">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-4xl font-extrabold text-gray-900 mb-12 text-center">Real Results. Real Change.</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                    <!-- Testimonial 1 -->
                    <div class="bg-white p-6 rounded-xl shadow-lg border-t-4 border-violet-700">
                        <p class="text-2xl text-gray-700 italic mb-4">
                            "I thought I was 'good' with money. UnSpend showed me I was losing $300 a month on subscription creep and impulsive Amazon buys. After 3 months, I'm already investing that money instead!"
                        </p>
                        <p class="font-bold text-gray-900">— Sarah K., Marketing Manager</p>
                        <p class="text-sm text-violet-700">Identified $3,600/year in unnecessary spending.</p>
                    </div>

                    <!-- Testimonial 2 -->
                    <div class="bg-white p-6 rounded-xl shadow-lg border-t-4 border-violet-700">
                        <p class="text-2xl text-gray-700 italic mb-4">
                            "The AI grouped my spending in ways I never would have done manually. The suggestions were shockingly simple but effective. I've cut my dining-out budget by 40% without feeling deprived."
                        </p>
                        <p class="font-bold text-gray-900">— David M., Small Business Owner</p>
                        <p class="text-sm text-violet-700">Achieved a 40% reduction in discretionary expenses.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section class="py-20 bg-gray-900" id="pricing">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-4xl font-extrabold text-white mb-12">Choose Your Path to Clarity</h2>
                <p class="text-2xl text-amber-300 mb-10">Start with <strong>3 MONTHS FREE</strong> — Zero obligation, pure insight.</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                    <!-- Plan 1: Free Trial -->
                    <div class="bg-white p-8 rounded-xl shadow-2xl border-4 border-yellow-400">
                        <h3 class="text-3xl font-bold mb-4 text-yellow-600">3 FREE Credits</h3>
                        <p class="text-5xl font-extrabold mb-6">$0</p>
                        <p class="text-gray-600 mb-8 font-medium">Analyze your first 3 months of bank statements to get a taste of true financial control.</p>
                        <ul class="text-left space-y-3 text-gray-700 mb-8">
                            <li class="flex items-center"><svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg> 3 Months of Analysis</li>
                            <li class="flex items-center"><svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg> AI Categorization & Summary</li>
                            <li class="flex items-center text-gray-400"><svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg> Monthly Upload Reminder (X)</li>
                            <li class="flex items-center mt-4 text-sm font-semibold text-red-600">**+ Optional  <a href="what-is-the-blueprint"> Blueprint </a> Upgrade**</li>
                        </ul>
                        <a href="#" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 px-8 rounded-lg shadow-md block cta-trigger" data-user-has-account="<?php echo $userHasAccount ? 'true' : 'false'; ?>"><?php echo $userHasAccount ? 'Upload Statement' : 'Get Started FREE'; ?></a>
                    </div>

                    <!-- Plan 2: Monthly Subscription (Featured) -->
                    <div class="bg-purple-900 p-8 rounded-xl shadow-2xl transform scale-105 border-4 border-amber-400">
                        <h3 class="text-3xl font-bold mb-4 text-amber-400">Monthly Insights</h3>
                        <p class="text-5xl font-extrabold mb-6 text-white"><span class="text-base font-normal block">$4.99/1 credit</span>$1.99</p>
                        <p class="text-purple-200 mb-8 font-medium">Continuous, up-to-date analysis to ensure your spending habits stay optimized month after month.</p>
                        <ul class="text-left space-y-3 text-white mb-8">
                            <li class="flex items-center"><svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg> Unlimited Monthly Analyses</li>
                            <li class="flex items-center"><svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg> AI Categorization & Summary</li>
                            <li class="flex items-center"><svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg> Pattern Detection</li>
                            <li class="flex items-center"><svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg> Monthly Upload Reminder</li>
                            <li class="flex items-center mt-4 text-sm font-semibold text-amber-300">**+ Optional <a href="what-is-the-blueprint"> Blueprint </a> Upgrade**</li>
                        </ul>
                        <a href="#" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-8 rounded-lg shadow-md block cta-trigger" data-user-has-account="<?php echo $userHasAccount ? 'true' : 'false'; ?>"><?php echo $userHasAccount ? 'Upload Statement' : 'Subscribe Now'; ?></a>
                    </div>

                    <!-- Plan 3: Annual Subscription -->
                    <div class="bg-white p-8 rounded-xl shadow-2xl border-4 border-yellow-400">
                        <h3 class="text-3xl font-bold mb-4 text-yellow-600">Annual Clarity </h3>
                        <p class="text-5xl font-extrabold mb-6">
                       <p class="text-5xl font-extrabold mb-6 text-dark"><span class="text-base font-normal block">$19.99/12 credit</span>$49.99</p>
                        <p class="text-gray-600 mb-8 font-medium">Commit to a year of financial mastery and save big—get two months free!</p>
                        <ul class="text-left space-y-3 text-gray-700 mb-8">
                            <li class="flex items-center"><svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg> Unlimited Analyses</li>
                            <li class="flex items-center"><svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg> AI Categorization & Summary</li>
                            <li class="flex items-center"><svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg> Pattern Detection</li>
                            <li class="flex items-center"><svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg> Monthly Upload Reminder</li>
                            <li class="flex items-center"><svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg> Priority Support</li>
                            <li class="flex items-center mt-4 text-sm font-semibold text-red-600">**+ Optional  <a href="what-is-the-blueprint"> Blueprint </a> Upgrade**</li>
                        </ul>
                        <a href="#" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 px-8 rounded-lg shadow-md block cta-trigger" data-user-has-account="<?php echo $userHasAccount ? 'true' : 'false'; ?>"><?php echo $userHasAccount ? 'Upload Statement' : 'Subscribe Annual & Save'; ?></a>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- SUMMARY PAGE CONTENT CONTAINER (Hidden by default, shown after AI analysis) -->
    <div id="summaryPage" class="hidden py-16 md:py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-5xl font-extrabold text-gray-900 text-center mb-4">Your AI Expense Summary</h1>
            <p class="text-xl text-gray-600 text-center mb-12" id="summaryIntroText">Analysis for the uploaded statement is complete. See your initial spending breakdown below.</p>

            <!-- 1. STATS CARDS (Top Row) -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-16">
                <!-- Stat Card 1: Total Spent (Dynamic) -->
                <div class="bg-violet-700 text-white p-6 rounded-xl shadow-2xl border-b-4 border-amber-500">
                    <p class="text-sm font-semibold opacity-80 uppercase">Total Spent (1 Month)</p>
                    <p class="text-4xl font-extrabold mt-1" id="statTotalSpent">$0.00</p>
                    <p class="text-xs mt-2 opacity-70">Based on AI-categorized debits.</p>
                </div>

                <!-- Stat Card 2: Total Income (Dynamic from Credits) -->
                <div class="bg-white p-6 rounded-xl shadow-2xl border-b-4 border-violet-500">
                    <p class="text-sm font-semibold text-gray-500 uppercase">Total Income/Credit Analyzed</p>
                    <p class="text-4xl font-extrabold mt-1 text-violet-700" id="statMonthlyIncome">$0.00</p>
                    <p class="text-xs mt-2 text-gray-500">From statement credits.</p>
                </div>

                <!-- Stat Card 3: Share of Income Spent (Dynamic) -->
                <div class="bg-white p-6 rounded-xl shadow-2xl border-b-4 border-red-500">
                    <p class="text-sm font-semibold text-gray-500 uppercase">Total Share of Income Spent</p>
                    <p class="text-4xl font-extrabold mt-1 text-red-600" id="statIncomeShare">0.00%</p>
                    <p class="text-xs mt-2 text-gray-500">The crucial financial health metric.</p>
                </div>

                <!-- Stat Card 4: Discretionary Leaks Found (Dynamic) -->
                <div class="bg-white p-6 rounded-xl shadow-2xl border-b-4 border-green-500">
                    <p class="text-sm font-semibold text-gray-500 uppercase">AI-Identified Discretionary Leaks</p>
                    <p class="text-4xl font-extrabold mt-1 text-green-600" id="statLeaksFound">$0.00</p>
                    <p class="text-xs mt-2 text-gray-500">Immediate, recoverable savings identified.</p>
                </div>
            </div>

            <!-- 2. EXPENSE CHART (PROMINENT - Above the Fold) -->
            <div class="bg-white p-4 sm:p-8 rounded-xl shadow-2xl mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-6 text-center">Your Monthly Spending Distribution (AI-Categorized)</h2>
                <div class="chart-container">
                    <!-- Canvas for Chart.js -->
                    <canvas id="expenseDoughnutChart"></canvas>
                </div>
            </div>

            <!-- 3. CATEGORY DETAIL LIST (Below Chart) -->
            <div class="bg-white p-4 sm:p-8 rounded-xl shadow-2xl">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Detailed Category Breakdown</h2>
                <!-- Dynamic List / Table for Categories -->
                <div id="categoryDetailList" class="space-y-4">
                    <!-- Details will be populated by JavaScript -->
                </div>
            </div>

            <!-- Call to Action for Blueprint -->
            <div class="mt-16 text-center bg-purple-50 p-8 rounded-xl shadow-2xl border-4 border-violet-700">
                <h3 class="text-3xl font-bold text-violet-800 mb-4">Ready for the Action Plan?</h3>
                <p class="text-xl text-gray-700 mb-6">Your data is ready, but a basic summary won't save you money. Unlock the **Wealth Blueprint** for personalized, step-by-step instructions on eliminating those spending leaks.</p>
                <a href="#" class="flat-cta text-white py-3 px-8 rounded-lg font-bold text-lg uppercase shadow-xl">
                    Unlock the $57 Blueprint Now
                </a>
            </div>
        </div>
    </div>
</main>