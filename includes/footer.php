<!-- --- MODAL STEP 1: CONTACT INFO --- -->
<div id="contactModal" class="hidden fixed inset-0 z-[99] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('contactModal')"></div>
        <div class="inline-block align-middle bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border-t-8 border-violet-700">
            <div class="bg-white p-8">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-2xl font-bold text-gray-900" id="modal-title">
                        <?php echo $userHasAccount ? 'Upload Another Statement' : 'Step 1: Secure Your Spot'; ?>
                    </h3>
                    <button onclick="closeModal('contactModal')" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <p class="text-gray-600 mb-6">
                    <?php echo $userHasAccount ? 'Upload another bank statement for analysis.' : 'Enter your details to proceed with the secure AI analysis.'; ?>
                </p>
                <form id="contactForm" class="space-y-4">
                    <?php if (!$userHasAccount): ?>
                    <div>
                        <label for="modal-name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" id="modal-name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700">
                    </div>
                    <div>
                        <label for="modal-email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" id="modal-email" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700">
                    </div>
                    <div>
                        <label for="modal-income" class="block text-sm font-medium text-gray-700">Estimated Monthly Income ($)</label>
                        <input type="number" id="modal-income" required min="1" step="0.01" placeholder="e.g., 6000" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700">
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="flat-cta w-full text-white py-3 mt-6 rounded-lg font-bold text-lg uppercase shadow-xl">
                        <?php echo $userHasAccount ? 'Upload Statement' : 'Continue to Upload'; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- --- MODAL STEP 2: UPLOAD INSTRUCTIONS AND AI ANALYSIS --- -->
<div id="uploadModal" class="hidden fixed inset-0 z-[99] overflow-y-auto" aria-labelledby="modal-title-upload" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('uploadModal')"></div>
        <div class="inline-block align-middle bg-gray-800 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border-t-8 border-amber-500">
            <div class="p-8">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-2xl font-bold text-amber-400" id="modal-title-upload">Step 2:  Start AI Analysis</h3>
                    <button onclick="closeModal('uploadModal')" class="text-gray-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <p class="text-gray-300 mb-6">
                    <?php 
                        $userName = $_COOKIE['user_name'] ?? ($_SESSION['user_name'] ?? $_SESSION['temp_name'] ?? '');
                        echo htmlspecialchars($userName ? $userName . ',' : '');
                    ?> 
                    Select a file below to begin the analysis. Our AI will securely process the content to categorize and summarize your spending.</p>

                <div class="bg-gray-700 p-5 rounded-lg space-y-4">
                    <div class="flex items-start">
                        <span class="text-amber-400 text-xl mr-3 font-extrabold">1.</span>
                        <div>
                            <h4 class="font-bold text-white">Required Format</h4>
                            <p class="text-gray-400">Your file <strong>must</strong> be a <strong>PDF</strong> or <strong>CSV</strong> export from your bank or credit card provider.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <span class="text-amber-400 text-xl mr-3 font-extrabold">2.</span>
                        <div>
                            <h4 class="font-bold text-white">One Month, Strict Dates</h4>
                            <p class="text-gray-400">The statement <strong>must cover only one full calendar month</strong>. If the dates are more than 1 month we will automatically analyse only the first 30 days on your statement. Ideally, all transaction dates inside the file should fall within that specific month (e.g., January 1st to January 31st). This isolates habits for precise pattern detection.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <span class="text-amber-400 text-xl mr-3 font-extrabold">3.</span>
                        <div>
                            <h4 class="font-bold text-white">Upload Method</h4>
                            <p class="text-gray-400">Click the button below to browse for your file. Once uploaded, our secure AI will begin the analysis!</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <!-- File Upload Form -->
                    <form id="uploadForm" method="POST" enctype="multipart/form-data" action="functions/process_upload.php">
                        <!-- Agreement Checkbox -->
                        <div class="mb-4">
                            <label class="flex items-start text-sm text-gray-300">
                                <input type="checkbox" id="agreeTerms" name="agreeTerms" required class="mr-2 h-4 w-4 text-amber-500 bg-gray-700 border-gray-600 rounded focus:ring-amber-500 focus:ring-2 mt-0.5">
                                <span>
                                    I agree to the
                                    <a href="terms.php" target="_blank" class="text-amber-400 hover:text-amber-300 underline">Terms and Conditions</a>
                                    and
                                    <a href="privacy.php" target="_blank" class="text-amber-400 hover:text-amber-300 underline">Privacy Policy</a>,
                                    and consent to the AI analysis of my bank statement data.
                                </span>
                            </label>
                        </div>
                        
                        <!-- Hidden email will be added by JS -->
                        <label id="uploadLabel" class="flat-cta w-full flex justify-center items-center text-white py-3 rounded-lg font-bold text-lg uppercase shadow-xl cursor-pointer">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            Select Bank Statement (PDF/CSV)
                            <input type="file" id="bankStatementFile" name="bankStatementFile" accept=".pdf,.csv" class="hidden">
                        </label>
                        <button type="submit" id="uploadSubmit" class="flat-cta w-full text-white py-3 mt-4 rounded-lg font-bold text-lg uppercase shadow-xl hidden">
                            Upload and Analyze
                        </button>

                        <!-- NEW: Progress Bar Container -->
                        <div id="progressBarContainer" class="hidden mt-4">
                            <div class="text-sm font-semibold text-white mb-1 flex justify-between">
                                <span>Processing Data...</span>
                                <span id="progressPercent">0%</span>
                            </div>
                            <div class="w-full bg-gray-600 rounded-full h-2.5">
                                <div id="progressBar" class="bg-amber-500 h-2.5 rounded-full transition-all duration-300 ease-out" style="width: 0%"></div>
                            </div>
                        </div>

                        <p id="uploadStatus" class="mt-3 text-sm text-gray-400 text-center"></p>
                    </form>

                    <!-- Preloader Animation -->
                    <div id="preloader" class="hidden mt-4 text-center">
                        <div class="inline-block w-8 h-8 border-4 border-amber-500 border-t-transparent rounded-full animate-spin"></div>
                        <p id="preloaderText" class="text-white mt-2">Processing your file...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Initialize analysisData from PHP session if available
    <?php
    if (!empty($_SESSION['analysisData'])) {
        $analysis = $_SESSION['analysisData'];

        // Decode JSON if stored as a string
        if (is_string($analysis)) {
            $decoded = json_decode($analysis, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $analysis = $decoded;
            } else {
                $analysis = [];
            }
        }

        // Safely get nested values (avoid warnings)
        $totalSpent  = $analysis['summary']['totalSpent'] ?? 0;
        $totalCredit = $analysis['summary']['totalCredit'] ?? 0;
        $totalLeaks  = $analysis['summary']['totalDiscretionaryLeaks'] ?? 0;
        $expenses    = $analysis['categorizedExpenses'] ?? [];

        // Output to JS safely
        echo "analysisData.totalSpent = {$totalSpent};";
        echo "analysisData.monthlyIncome = {$totalCredit};";
        echo "analysisData.totalLeaks = {$totalLeaks};";
        echo "analysisData.expenses = " . json_encode($expenses) . ";";

        unset($_SESSION['analysisData']);
    }
    ?>

    // Make userHasAccount available to JavaScript
    window.userHasAccount = <?php echo $userHasAccount ? 'true' : 'false'; ?>;

    // Contact Form Handler
    document.addEventListener('DOMContentLoaded', function() {
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const name = document.getElementById('modal-name')?.value;
                const email = document.getElementById('modal-email')?.value;
                const income = document.getElementById('modal-income')?.value;
                
                if (name && email && income) {
                    // Save user data to cookies (expires in 15 days)
                    setCookie('user_name', name, 15);
                    setCookie('user_email', email, 15);
                    setCookie('user_income', income, 15);
                    
                    // Close contact modal and open upload modal
                    closeModal('contactModal');
                    
                    // Small delay to ensure contact modal is fully closed
                    setTimeout(() => {
                        openModal('uploadModal');
                    }, 100);
                }
            });
        }
    });
</script>