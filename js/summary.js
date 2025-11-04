// Global data structure to hold analysis results and user input
window.analysisData = {
    monthlyIncome: 0,
    totalSpent: 0,
    totalLeaks: 0,
    expenses: [],
    chartInstance: null,
    currentAnalysisId: null
};
// Function to get currency symbol
function getCurrencySymbol(currency) {
    const symbols = {
        'USD': '$',
        'EUR': '€',
        'GBP': '£',
        'BDT': '৳',
        'AED': 'د.إ',
        'SAR': '﷼',
        'INR': '₹',
        'JPY': '¥',
        'CNY': '¥',
        'KRW': '₩',
        'THB': '฿',
        'VND': '₫',
        'MYR': 'RM',
        'SGD': 'S$',
        'PHP': '₱',
        'IDR': 'Rp',
        'PKR': '₨',
        'LKR': '₨',
        'NPR': '₨',
        'MMK': 'K',
        'LAK': '₭',
        'KHR': '៛',
        'BND': 'B$'
    };
    return symbols[currency] || '$';
}


// Function to generate distinct colors for categories
function generateCategoryColors(count) {
    const baseColors = [
        '#4F46E5', '#7C3AED', '#EC4899', '#F59E0B', '#10B981', '#3B82F6',
        '#EF4444', '#8B5CF6', '#06B6D4', '#F97316', '#14B8A6', '#6366F1'
    ];

    const result = [...baseColors];
    while (result.length < count) {
        const h = Math.floor(Math.random() * 360);
        result.push(`hsl(${h}, 75%, 50%)`);
    }
    return result.slice(0, count);
}

// Combine duplicate categories into a single value
function combineExpenses(expenses) {
    const map = new Map();
    expenses.forEach(e => {
        const cat = e.category || 'Unknown';
        const amount = Number(e.amount || 0);

        if (map.has(cat)) {
            const data = map.get(cat);
            data.amount += amount;
            data.isLeak = data.isLeak || e.isLeak;
        } else {
            map.set(cat, { ...e, amount });
        }
    });
    return Array.from(map.values());
}

// Ensure analysis section is visible before chart rendering
function ensureSectionVisible(callback) {
    requestAnimationFrame(() => {
        requestAnimationFrame(callback);
    });
}

// Chart Rendering Function
function renderChart() {
    const chartElement = document.getElementById('expenseDoughnutChart');
    if (!chartElement) return console.error('Canvas missing!');

    const ctx = chartElement.getContext('2d');
    if (!ctx) return console.error('No 2D context found!');

    if (window.analysisData.chartInstance) {
        window.analysisData.chartInstance.destroy();
        window.analysisData.chartInstance = null;
    }

    if (!window.analysisData.expenses.length) {
        chartElement.style.display = 'none';
        return;
    }

    chartElement.style.display = 'block';

    const colors = generateCategoryColors(window.analysisData.expenses.length);

    window.analysisData.chartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: window.analysisData.expenses.map(e => e.category),
            datasets: [{
                data: window.analysisData.expenses.map(e => e.amount),
                backgroundColor: colors,
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

// Page Rendering Function
function renderSummaryPage() {
    const income = window.analysisData.monthlyIncome;
    const spent = window.analysisData.totalSpent;
    const leaks = window.analysisData.totalLeaks;

    // Update summary intro text with statement period
    const summaryIntroText = document.getElementById('summaryIntroText');
    if (window.analysisData.statementPeriod) {
        const startDate = new Date(window.analysisData.statementPeriod.startDate);
        const endDate = new Date(window.analysisData.statementPeriod.endDate);
        const formattedStart = startDate.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        const formattedEnd = endDate.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        summaryIntroText.innerHTML = `<span class="text-violet-600 font-semibold">📊 Expense Analysis Summary</span> for the period <span class="font-bold text-gray-800">${formattedStart} to ${formattedEnd}</span>`;
    } else {
        summaryIntroText.innerHTML = '<span class="text-violet-600 font-semibold">📊 Expense Analysis Summary</span> for your selected statement';
    }

    // Update user income display with currency
    const userIncomeDisplay = document.getElementById('userIncomeDisplay');
    if (userIncomeDisplay) {
        const currencySymbol = getCurrencySymbol(window.currency || 'USD');
        userIncomeDisplay.textContent = `${currencySymbol}${parseFloat(userIncomeDisplay.textContent.replace(/[^\d.-]/g, '')).toFixed(2)}`;
    }

    const currencySymbol = getCurrencySymbol(window.currency || 'USD');
    document.getElementById('statTotalSpent').textContent = `${currencySymbol}${spent.toFixed(2)}`;
    document.getElementById('statMonthlyIncome').textContent = `${currencySymbol}${income.toFixed(2)}`;
    document.getElementById('statIncomeShare').textContent = `${((spent / income) * 100 || 0).toFixed(2)}%`;
    document.getElementById('statLeaksFound').textContent = `${currencySymbol}${leaks.toFixed(2)}`;

    const listContainer = document.getElementById('categoryDetailList');
    listContainer.innerHTML = '';

    if (!window.analysisData.expenses.length) {
        listContainer.innerHTML = '<p class="text-center text-gray-500">No categorized expenses available.</p>';
    } else {
        window.analysisData.expenses.forEach(e => {
            const percentage = (e.amount / spent) * 100 || 0;
            const incomePortion = (e.amount / income) * 100 || 0;
            const currencySymbol = getCurrencySymbol(window.currency || 'USD');

            listContainer.insertAdjacentHTML('beforeend', `
                <div class="p-4 bg-gray-50 rounded-lg shadow-sm border-l-4" style="border-left-color: ${e.color || '#374151'};">
                    <div class="flex justify-between items-center">
                        <span class="text-lg text-gray-900">${e.category}</span>
                        <span class="text-xl font-extrabold">${currencySymbol}${e.amount.toFixed(2)}</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-500 mt-1">
                        <span>% of Total Spent: ${percentage.toFixed(2)}%</span>
                        <span>% of Your Income: <span class="font-semibold text-violet-700">${incomePortion.toFixed(2)}%</span></span>
                    </div>
                </div>
            `);
        });
    }

    ensureSectionVisible(() => renderChart());
}

// Include common functions
// Modal and upload functions are now in js/common.js

// Tab switching
document.addEventListener('DOMContentLoaded', () => {
    const historyTab = document.getElementById('historyTab');
    const analysisTab = document.getElementById('analysisTab');
    const historySection = document.getElementById('historySection');
    const analysisSection = document.getElementById('analysisSection');
    const backToHistory = document.getElementById('backToHistory');
    const unlockBtn = document.getElementById('unlockBlueprintBtn');

    // Initialize file upload functionality for summary page
    initializeFileUpload();

    // Auto-load latest analysis if available
    if (window.analysisData && window.analysisData.expenses && window.analysisData.expenses.length > 0) {
        renderSummaryPage();
    } else if (window.latestAnalysisId) {
        // Load the latest analysis from database
        loadAnalysis(window.latestAnalysisId);
    }

    if (historyTab) {
        historyTab.addEventListener('click', () => {
            if (historySection) historySection.classList.remove('hidden');
            if (analysisSection) analysisSection.classList.add('hidden');
            // Hide unlock button when showing history
            if (unlockBtn) unlockBtn.style.display = 'none';
        });
    }

    if (analysisTab) {
        analysisTab.addEventListener('click', () => {
            if (historySection) historySection.classList.add('hidden');
            if (analysisSection) analysisSection.classList.remove('hidden');
            // Show unlock button when showing single analysis
            if (unlockBtn) unlockBtn.style.display = 'inline-block';
            ensureSectionVisible(renderSummaryPage);
        });
    }

    if (backToHistory) {
        backToHistory.addEventListener('click', () => {
            if (historyTab) historyTab.click();
        });
    }

    // Blueprint unlock button event listener
    if (unlockBtn) {
        unlockBtn.addEventListener('click', unlockBlueprint);
    }
});

// Load selected analysis
function loadAnalysis(analysisId) {
    const button = event.target;
    const original = button.textContent;
    button.textContent = 'Loading...';
    button.disabled = true;

    fetch(`functions/get_analysis.php?id=${analysisId}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) return alert(data.message);

            window.analysisData = {
                monthlyIncome: data.analysis.summary.totalCredit,
                totalSpent: data.analysis.summary.totalSpent,
                totalLeaks: data.analysis.summary.totalDiscretionaryLeaks,
                expenses: combineExpenses(data.analysis.categorizedExpenses),
                statementPeriod: data.statementPeriod,
                currentAnalysisId: analysisId,
                currency: data.analysis.summary.currency || 'USD'
            };

            // Update global currency
            window.currency = window.analysisData.currency;

            // Show analysis section and unlock button
            const historySection = document.getElementById('historySection');
            const analysisSection = document.getElementById('analysisSection');
            const unlockBtn = document.getElementById('unlockBlueprintBtn');

            if (historySection) historySection.classList.add('hidden');
            if (analysisSection) analysisSection.classList.remove('hidden');
            if (unlockBtn) unlockBtn.style.display = 'inline-block';

            renderSummaryPage();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        })
        .catch(err => console.error(err))
        .finally(() => {
            button.textContent = original;
            button.disabled = false;
        });
}

// Blueprint unlock function
async function unlockBlueprint(event) {
    event.preventDefault();

    try {
        const response = await fetch('check_verification.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'check_verification' })
        });

        const result = await response.json();

        if (result.verified) {
            // User is verified, redirect to blueprint with current analysis id and currency
            const analysisId = window.analysisData.currentAnalysisId || window.latestAnalysisId;
            const currency = window.currency || 'USD';
            window.location.replace(`blueprint.php?id=${analysisId}&currency=${currency}`);
        } else {
            // User needs verification, send email
            const emailResponse = await fetch('send_verification.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'send_verification', source: 'summary' })
            });

            const emailResult = await emailResponse.json();

            if (emailResult.success) {
                Swal.fire({
                    icon: 'info',
                    title: 'Email Verification Required',
                    text: 'Please check your email and click the verification link before accessing the blueprint.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#5b21b6'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to send verification email. Please try again.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#5b21b6'
                });
            }
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred. Please try again.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#5b21b6'
        });
    }
}

function showBlueprintModal() {
    // Create modal HTML
    const modalHTML = `
        <div id="blueprintModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <div class="inline-block align-middle bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border-t-8 border-violet-700">
                    <div class="bg-white px-4 pt-5 pb-4 p-6">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-2xl font-bold text-gray-900" id="modal-title">Unlock Your Wealth Blueprint</h3>
                            <button onclick="closeBlueprintModal()" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <form id="blueprintForm" onsubmit="submitBlueprintForm(event)" class="space-y-4">
                            <div>
                                <label for="user_age" class="block text-sm font-medium text-gray-700">Age</label>
                                <input type="number" id="user_age" name="age" required min="18" max="120" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700">
                            </div>

                            <div>
                                <label for="user_country" class="block text-sm font-medium text-gray-700">Country</label>
                                <select id="user_country" name="country" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700">
                                    <option value="">Select Country</option>
                                    <option value="US">United States</option>
                                    <option value="CA">Canada</option>
                                    <option value="UK">United Kingdom</option>
                                    <option value="AU">Australia</option>
                                    <option value="DE">Germany</option>
                                    <option value="FR">France</option>
                                    <option value="BD">Bangladesh</option>
                                    <!-- Add more countries as needed -->
                                </select>
                            </div>

                            <div>
                                <label for="user_occupation" class="block text-sm font-medium text-gray-700">Occupation</label>
                                <input type="text" id="user_occupation" name="occupation" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700" placeholder="e.g., Software Engineer, Teacher, Business Owner">
                            </div>

                            <div>
                                <label for="user_gender" class="block text-sm font-medium text-gray-700">Gender</label>
                                <select id="user_gender" name="gender" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                    <option value="prefer_not_to_say">Prefer not to say</option>
                                </select>
                            </div>

                            <div>
                                <label for="user_motivation" class="block text-sm font-medium text-gray-700">What drives you to do this?</label>
                                <select id="user_motivation" name="motivation" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700">
                                    <option value="">Select your primary motivation</option>
                                    <option value="manage_expense">Need help to manage expenses</option>
                                    <option value="build_wealth">Build wealth and stop the rat race</option>
                                    <option value="check_wife_spending">Check my wife's spending habits</option>
                                    <option value="check_husband_spending">Check my husband's spending habits</option>
                                </select>
                            </div>

                            <div class="flex justify-end space-x-3 pt-4">
                                <button type="button" onclick="closeBlueprintModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                                    Cancel
                                </button>
                                <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200">
                                    Unlock Blueprint
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Auto-detect country (you can enhance this with a geolocation API)
    detectUserCountry();
}

function closeBlueprintModal() {
    const modal = document.getElementById('blueprintModal');
    if (modal) {
        modal.remove();
    }
}

async function submitBlueprintForm(event) {
    event.preventDefault();

    const formData = new FormData(event.target);

    try {
        const response = await fetch('process_blueprint.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            closeBlueprintModal();
            // Show blueprint content instead of redirecting
            const blueprintPage = document.getElementById('blueprintPage');
            if (blueprintPage) {
                blueprintPage.classList.remove('hidden');
            } else {
                // If we're not on blueprint page, redirect
                window.location.href = 'blueprint.php';
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message || 'Failed to process your request.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#5b21b6'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred. Please try again.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#5b21b6'
        });
    }
}

function detectUserCountry() {
    // Simple country detection based on timezone
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

    // Basic mapping - you can enhance this
    const countryMap = {
        'Asia/Dhaka': 'BD',
        'America/New_York': 'US',
        'America/Toronto': 'CA',
        'Europe/London': 'UK',
        'Australia/Sydney': 'AU',
        'Europe/Berlin': 'DE',
        'Europe/Paris': 'FR'
    };

    const detectedCountry = countryMap[timezone] || 'US';
    const countrySelect = document.getElementById('user_country');
    if (countrySelect) {
        countrySelect.value = detectedCountry;
    }
}
window.loadAnalysis = loadAnalysis;
