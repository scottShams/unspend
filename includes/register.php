<main>
    <!-- REGISTER PAGE CONTENT CONTAINER -->
    <div id="registerPage" class="py-16 md:py-32 bg-gray-100 min-h-screen flex items-center justify-center">
        <div class="w-full max-w-md">
            <div class="bg-white p-8 sm:p-10 rounded-xl shadow-2xl border-t-8 border-violet-700">
                <h2 class="text-3xl font-extrabold text-gray-900 text-center mb-6">
                    Create Your Account
                </h2>
                <p class="text-center text-gray-500 mb-8">
                    Join unSpend to access referral links and track your earnings.
                </p>
                <form id="registerForm" class="space-y-6" onsubmit="registerUser(event)" action="login_process.php" method="POST">
                    <input type="hidden" name="action" value="register">
                    <div>
                        <label for="register-name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="name" id="register-name" required autocomplete="name" class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700" placeholder="John Doe">
                    </div>
                    <div>
                        <label for="register-email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" name="email" id="register-email" required autocomplete="email" class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700" placeholder="your.email@example.com">
                    </div>
                    <div>
                        <label for="register-income" class="block text-sm font-medium text-gray-700">Monthly Income</label>
                        <input type="number" name="income" id="register-income" required min="0" step="0.01" class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700" placeholder="5000.00">
                    </div>
                    <div>
                        <label for="register-password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" id="register-password" required autocomplete="new-password" class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700" placeholder="••••••••">
                    </div>
                    <div>
                        <label for="register-confirm-password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input type="password" name="confirm_password" id="register-confirm-password" required autocomplete="new-password" class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700" placeholder="••••••••">
                    </div>

                    <div id="register-status" class="text-center font-medium h-5"></div>

                    <button type="submit" class="flat-cta w-full text-white py-3 rounded-lg font-bold text-lg uppercase shadow-xl">
                        Create Account
                    </button>
                </form>
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600 mb-2">Already have an account?</p>
                    <a href="login.php" class="flat-cta text-white py-2 px-4 rounded-lg font-bold text-sm inline-block">
                        Sign In
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>