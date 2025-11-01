<main>
    <!-- LOGIN PAGE CONTENT CONTAINER -->
    <div id="loginPage" class="py-16 md:py-32 bg-gray-100 min-h-screen flex items-center justify-center">
        <div class="w-full max-w-md">
            <div class="bg-white p-8 sm:p-10 rounded-xl shadow-2xl border-t-8 border-violet-700">
                <?php if ($userExistsInDB && $userNeedsPassword): ?>
                <!-- SET PASSWORD FORM -->
                <h2 class="text-3xl font-extrabold text-gray-900 text-center mb-6">
                    Set Your Password
                </h2>
                <p class="text-center text-gray-500 mb-8">
                    We found your account. Please set a password to continue.
                </p>
                <form id="setPasswordForm" class="space-y-6" onsubmit="setUserPassword(event)" action="login_process.php" method="POST">
                    <div>
                        <label for="set-password" class="block text-sm font-medium text-gray-700">Create Password</label>
                        <input type="password" name="password" id="set-password" required autocomplete="new-password" class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700" placeholder="••••••••">
                    </div>
                    <div>
                        <label for="confirm-password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm-password" required autocomplete="new-password" class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700" placeholder="••••••••">
                    </div>
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['temp_email']); ?>">
                    <input type="hidden" name="action" value="set_password">

                    <div id="set-password-status" class="text-center font-medium h-5"></div>

                    <button type="submit" class="flat-cta w-full text-white py-3 rounded-lg font-bold text-lg uppercase shadow-xl">
                        Set Password & Continue
                    </button>
                </form>
                <?php else: ?>
                <!-- REGULAR LOGIN FORM -->
                <h2 class="text-3xl font-extrabold text-gray-900 text-center mb-6">
                    Referral Dashboard Login
                </h2>
                <p class="text-center text-gray-500 mb-8">
                    Sign in to view your commissions and referral links.
                </p>
                <form id="loginForm" class="space-y-6" onsubmit="loginUser(event)">
                    <input type="hidden" name="action" value="login">
                    <div>
                        <label for="login-email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" name="email" id="login-email" required autocomplete="email" value="<?php echo htmlspecialchars($_SESSION['temp_email'] ?? ''); ?>" class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700" placeholder="your.email@example.com">
                    </div>
                    <div>
                        <label for="login-password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" id="login-password" required autocomplete="current-password" class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-violet-700 focus:border-violet-700" placeholder="••••••••">
                    </div>
                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($_SESSION['temp_name'] ?? ''); ?>">
                    <input type="hidden" name="income" value="<?php echo htmlspecialchars($_SESSION['temp_income'] ?? ''); ?>">

                    <div id="login-status" class="text-center font-medium h-5"></div>

                    <button type="submit" class="flat-cta w-full text-white py-3 rounded-lg font-bold text-lg uppercase shadow-xl">
                        Log In to Dashboard
                    </button>
                </form>
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600 mb-2">Don't have an account?</p>
                    <a href="register.php" class="flat-cta text-white py-2 px-4 rounded-lg font-bold text-sm inline-block">
                        Create Account
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>