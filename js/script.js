// Data structure to hold analysis results and user input
let analysisData = {
    monthlyIncome: 0,
    totalSpent: 0,
    totalLeaks: 0,
    expenses: [],
    // Chart instance reference
    chartInstance: null
};

document.addEventListener('DOMContentLoaded', () => {
    const contactModal = document.getElementById('contactModal');
    const uploadModal = document.getElementById('uploadModal');
    const contactForm = document.getElementById('contactForm');

    // --- Chart Rendering Function ---
    function renderChart() {
        const ctx = document.getElementById('expenseDoughnutChart').getContext('2d');

        // Destroy existing chart instance if it exists
        if (analysisData.chartInstance) {
            analysisData.chartInstance.destroy();
        }

        const data = {
            labels: analysisData.expenses.map(e => e.category),
            datasets: [{
                data: analysisData.expenses.map(e => e.amount),
                backgroundColor: analysisData.expenses.map(e => e.color || '#374151'),
                hoverOffset: 15,
            }]
        };

        const config = {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false, // Allows chart to take the full height of the container
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: {
                                size: 14,
                                family: 'Inter'
                            }
                        }
                    },
                    title: {
                        display: false, // Title is now in the main H2 tag
                    }
                }
            }
        };

        analysisData.chartInstance = new Chart(ctx, config);
    }

    // --- Page Redirection Simulation Fix ---
    function renderSummaryPage() {
        // 1. Calculations
        const income = analysisData.monthlyIncome;
        const spent = analysisData.totalSpent;
        const incomeShare = (spent / income) * 100;

        // 2. Update Stats Section
        document.getElementById('statTotalSpent').textContent = `$${spent.toFixed(2)}`;
        document.getElementById('statMonthlyIncome').textContent = `$${income.toFixed(2)}`;
        document.getElementById('statIncomeShare').textContent = `${incomeShare.toFixed(2)}%`;
        document.getElementById('statLeaksFound').textContent = `$${analysisData.totalLeaks.toFixed(2)}`;

        // 3. Update Category Detail List
        const listContainer = document.getElementById('categoryDetailList');
        listContainer.innerHTML = '';

        analysisData.expenses.forEach(e => {
            const percentage = (e.amount / spent) * 100;
            const incomePortion = (e.amount / income) * 100;
            const isLeakClass = e.isLeak ? 'text-red-600 font-bold' : 'text-gray-900';
            const leakLabel = e.isLeak ? '<span class="text-xs text-red-500 bg-red-100 px-2 py-0.5 rounded-full ml-2">LEAK</span>' : '';

            listContainer.innerHTML += `
                <div class="p-4 bg-gray-50 rounded-lg shadow-sm border-l-4" style="border-left-color: ${e.color || '#374151'};">
                    <div class="flex justify-between items-center">
                        <span class="text-lg ${isLeakClass} flex items-center">${e.category} ${leakLabel}</span>
                        <span class="text-xl font-extrabold text-gray-900">$${e.amount.toFixed(2)}</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-500 mt-1">
                        <span>% of Total Spent: ${percentage.toFixed(2)}%</span>
                        <span>% of Your Income: <span class="font-semibold text-violet-700">${incomePortion.toFixed(2)}%</span></span>
                    </div>
                </div>
            `;
        });

        // 4. Render Chart
        renderChart();

        // 5. Switch UI View
        const landingPage = document.getElementById('landingPage');
        const summaryPage = document.getElementById('summaryPage');
        if (landingPage && summaryPage) {
            landingPage.classList.add('hidden');
            summaryPage.classList.remove('hidden');

            // Update header for a cleaner look
            const headerLink = document.querySelector('header a.text-2xl');
            if (headerLink) headerLink.textContent = 'AxiomSpend | Expense Summary';

            const headerNav = document.getElementById('headerNav');
            const headerCta = document.getElementById('headerCta');
            if (headerNav) headerNav.classList.add('hidden');
            if (headerCta) headerCta.classList.add('hidden');
        }

        closeModal('uploadModal');
        window.scrollTo(0, 0); // Scroll to top of the new content
    }

    // --- CTA Trigger Logic ---
    document.querySelectorAll('.cta-trigger').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();

            // Check if user has account from data attribute
            const hasAccount = button.getAttribute('data-user-has-account') === 'true';

            if (hasAccount) {
                // Existing user - go directly to upload modal
                openModal('uploadModal');
            } else {
                // New user - start with contact modal
                openModal('contactModal');
            }
        });
    });

    // --- Form Submission (Step 1 -> Step 2) ---
    if (contactForm) {
        contactForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const email = document.getElementById('modal-email').value;
            const name = document.getElementById('modal-name').value;
            const income = document.getElementById('modal-income').value;

            // Store in session for login page
            fetch('store_session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, name, income })
            }).then(() => {
                // Close Step 1, Open Step 2
                closeModal('contactModal');
                openModal('uploadModal');
            });
        });
    }

    // Initialize file upload functionality
    initializeFileUpload();

    // Render summary if data exists
    if (analysisData.expenses && analysisData.expenses.length > 0) {
        renderSummaryPage();
    }

    // --- Login Function ---
    window.loginUser = async function (e) {
        e.preventDefault();
        const form = document.getElementById('loginForm');
        const statusMessage = document.getElementById('login-status');

        const formData = new FormData(form);

        try {
            const response = await fetch('login_process.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                statusMessage.textContent = 'Login successful! Redirecting...';
                statusMessage.classList.remove('text-red-600');
                statusMessage.classList.add('text-green-600');

                // Redirect after success
                setTimeout(() => {
                    window.location.href = result.redirect;
                }, 1000);
            } else if (result.needs_password) {
                // Redirect to set password
                window.location.href = 'login.php';
            } else {
                statusMessage.textContent = result.message;
                statusMessage.classList.remove('text-green-600');
                statusMessage.classList.add('text-red-600');
            }
        } catch (error) {
            statusMessage.textContent = 'An error occurred. Please try again.';
            statusMessage.classList.remove('text-green-600');
            statusMessage.classList.add('text-red-600');
        }
    }

    // --- Set Password Function ---
    window.setUserPassword = async function (e) {
        e.preventDefault();
        const form = document.getElementById('setPasswordForm');
        const statusMessage = document.getElementById('set-password-status');

        const formData = new FormData(form);

        try {
            const response = await fetch('login_process.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                if (result.needs_verification) {
                    statusMessage.textContent = result.message;
                    statusMessage.classList.remove('text-red-600');
                    statusMessage.classList.add('text-blue-600');

                    // Show SweetAlert message
                    Swal.fire({
                        icon: 'success',
                        title: 'Verification Email Sent!',
                        text: 'Please check your email to verify your account before logging in.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#5b21b6'
                    });

                    // Stay on current page - no redirect
                } else {
                    statusMessage.textContent = result.message || 'Password set successfully! Redirecting...';
                    statusMessage.classList.remove('text-red-600');
                    statusMessage.classList.add('text-green-600');

                    // Redirect after success
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1000);
                }
            } else {
                statusMessage.textContent = result.message;
                statusMessage.classList.remove('text-green-600', 'text-blue-600');
                statusMessage.classList.add('text-red-600');
            }
        } catch (error) {
            statusMessage.textContent = 'An error occurred. Please try again.';
            statusMessage.classList.remove('text-green-600', 'text-blue-600');
            statusMessage.classList.add('text-red-600');
        }
    }


    // --- Register Function ---
    window.registerUser = async function (e) {
        e.preventDefault();
        const form = document.getElementById('registerForm');
        const statusMessage = document.getElementById('register-status');

        const formData = new FormData(form);

        try {
            const response = await fetch('login_process.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                if (result.needs_verification) {
                    statusMessage.textContent = result.message;
                    statusMessage.classList.remove('text-red-600');
                    statusMessage.classList.add('text-blue-600');

                    // Show SweetAlert message
                    Swal.fire({
                        icon: 'success',
                        title: 'Verification Email Sent!',
                        text: 'Please check your email to verify your account before logging in.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#5b21b6'
                    });

                    // Stay on current page - no redirect
                } else {
                    statusMessage.textContent = 'Account created successfully! Redirecting...';
                    statusMessage.classList.remove('text-red-600');
                    statusMessage.classList.add('text-green-600');

                    // Redirect after success
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1000);
                }
            } else {
                statusMessage.textContent = result.message;
                statusMessage.classList.remove('text-green-600', 'text-blue-600');
                statusMessage.classList.add('text-red-600');
            }
        } catch (error) {
            statusMessage.textContent = 'An error occurred. Please try again.';
            statusMessage.classList.remove('text-green-600', 'text-blue-600');
            statusMessage.classList.add('text-red-600');
        }
    }
});