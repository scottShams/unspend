    <main>
        <div class="py-16 md:py-24 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Pass user account status to JavaScript -->
                <script>window.userHasAccount = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;</script>

                <!-- Include footer modals for upload functionality -->
                <?php include 'footer.php'; ?>
                <h1 class="text-5xl font-extrabold text-gray-900 text-center mb-4"><?php echo htmlspecialchars($userName); ?>'s Expense Summary</h1>
                <p class="text-center mb-4"><?php echo htmlspecialchars($userName); ?>'s Estimated Monthly Income: <span id="userIncomeDisplay"><?php echo htmlspecialchars($userIncome); ?></span></p>
                <!-- Navigation Tabs -->
                <div class="flex justify-center mb-8 hidden">
                    <div class="bg-white rounded-lg shadow-md p-1">
                        <button id="historyTab" class="px-6 py-3 rounded-md font-medium transition-colors duration-200 bg-violet-600 text-white">
                            Analysis History
                        </button>
                        <button id="analysisTab" class="px-6 py-3 rounded-md font-medium transition-colors duration-200 text-gray-600 hover:text-gray-900">
                            Current Analysis
                        </button>
                    </div>
                </div>

                <!-- Analysis History Section (Default View) -->
                <div id="historySection" class="bg-white p-8 rounded-xl shadow-2xl hidden">
                    <h2 class="text-3xl font-bold text-gray-900 mb-6 text-center">Your Analysis History</h2>

                    <?php if (!empty($userHistory)): ?>
                        <div class="space-y-4">
                            <?php foreach ($userHistory as $index => $analysis): ?>
                            <div class="bg-gray-50 p-6 rounded-lg shadow-sm border-l-4 <?php echo $index === 0 ? 'border-green-500' : 'border-gray-300'; ?>">
                                <div class="flex justify-between items-center">
                                    <div class="flex-1">
                                        <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                            <?php echo htmlspecialchars(basename($analysis['filename'])); ?>
                                            <?php if ($index === 0): ?>
                                            <span class="text-sm text-green-600 bg-green-100 px-3 py-1 rounded-full ml-2">Latest Analysis</span>
                                            <?php endif; ?>
                                        </h3>
                                        <p class="text-gray-600 mb-3">
                                            Analyzed on <?php echo date('F j, Y \a\t g:i A', strtotime($analysis['upload_date'])); ?>
                                        </p>
                                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                                            <span>📊 Complete Analysis Available</span>
                                            <span>📈 Charts & Insights Ready</span>
                                        </div>
                                    </div>
                                    <div class="ml-6">
                                        <button onclick="loadAnalysis(<?php echo $analysis['id']; ?>)"
                                                class="bg-violet-600 hover:bg-violet-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 shadow-md">
                                            View Analysis
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">📊</div>
                            <h3 class="text-2xl font-semibold text-gray-900 mb-2">No Analysis History Yet</h3>
                            <p class="text-gray-600 mb-6">Upload your first bank statement to get started with expense analysis.</p>
                            <a href="index.php" class="bg-violet-600 hover:bg-violet-700 text-white px-8 py-4 rounded-lg font-semibold transition-colors duration-200 shadow-md">
                                Upload Statement
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Current Analysis Section (Default View) -->
                <div id="analysisSection">
                    <p class="text-xl text-gray-600 text-center mb-12" id="summaryIntroText">Analysis details for your selected statement.</p>

                    <!-- 1. STATS CARDS (Top Row) -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-16">
                        <!-- Stat Card 1: Total Spent (Dynamic) -->
                        <div class="bg-violet-700 text-white p-6 rounded-xl shadow-2xl border-b-4 border-amber-500">
                            <p class="text-sm font-semibold opacity-80 uppercase">Total Spent (1 Month)</p>
                            <p class="text-4xl font-extrabold mt-1" id="statTotalSpent">$0.00</p>
                            <p class="text-xs mt-2 opacity-70">Based on categorized debits.</p>
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
                            <p class="text-sm font-semibold text-gray-500 uppercase">Identified Discretionary Leaks</p>
                            <p class="text-4xl font-extrabold mt-1 text-green-600" id="statLeaksFound">$0.00</p>
                            <p class="text-xs mt-2 text-gray-500">Immediate, recoverable savings identified.</p>
                        </div>
                    </div>

                    <!-- 2. EXPENSE CHART (PROMINENT - Above the Fold) -->
                    <div class="bg-white p-4 sm:p-8 rounded-xl shadow-2xl mb-16">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6 text-center">Your Monthly Spending Distribution</h2>
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

                    <!-- Back to History Button -->
                    <div class="mt-8 text-center">
                        <button id="backToHistory" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 shadow-md">
                            ← Back to History
                        </button>
                    </div>
                </div>

                <!-- Call to Action for Blueprint -->
                <div id="blueprintCTA" class="mt-16 text-center bg-purple-50 p-8 rounded-xl shadow-2xl border-4 border-violet-700">
                    <h3 class="text-3xl font-bold text-violet-800 mb-4">Ready for the Action Plan?</h3>
                    <p class="text-xl text-gray-700 mb-6">Your data is ready, but a basic summary won't save you money. Unlock the **Wealth Blueprint** for personalized, step-by-step instructions on eliminating those spending leaks.</p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                        <a href="#" id="unlockBlueprintBtn" class="flat-cta text-white py-3 px-8 rounded-lg font-bold text-lg uppercase shadow-xl">
                            Unlock the $57 Blueprint Now
                        </a>
                        <button onclick="openUploadModal()" class="px-8 py-4 bg-green-600 text-white font-bold text-lg rounded-xl shadow-lg hover:bg-green-700 transition duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-green-300">
                            Analyze Another PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>