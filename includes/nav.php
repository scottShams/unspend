<!-- Mobile Menu Button -->
<button class="md:hidden text-gray-600 hover:text-amber-500 focus:outline-none" id="mobileMenuButton">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
</button>

<!-- Navigation Menu -->
<nav class="hidden md:flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-8 absolute md:relative top-full md:top-auto left-0 md:left-auto right-0 md:right-auto bg-white md:bg-transparent shadow-lg md:shadow-none p-4 md:p-0 z-40" id="headerNav">
    <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'text-amber-500' : 'text-gray-600'; ?> hover:text-amber-500 transition duration-150 font-medium block md:inline">Home</a>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="summary.php" class="text-gray-600 hover:text-amber-500 transition duration-150 font-medium block md:inline">Summary</a>
    <?php endif; ?>
    <a href="refer.php" class="<?php echo ($current_page == 'refer.php') ? 'text-amber-500' : 'text-gray-600'; ?> hover:text-amber-500 transition duration-150 font-medium block md:inline">Refer & Earn!</a>
    <?php if (isset($_SESSION['user_authorized']) && $_SESSION['user_authorized'] === true): ?>
        <!-- Show when logged in -->
        <a href="dashboard.php"
        class="<?php echo ($current_page == 'dashboard.php') ? 'text-amber-500' : 'text-gray-600'; ?> hover:text-amber-500 transition duration-150 font-medium block md:inline">
            Dashboard
        </a>
        <a href="logout.php"
        class="<?php echo ($current_page == 'logout.php') ? 'text-amber-500' : 'text-gray-600'; ?> hover:text-amber-500 transition duration-150 font-medium block md:inline">
            Logout
        </a>
    <?php else: ?>
        <!-- Show when logged out -->
        <a href="login.php"
        class="<?php echo ($current_page == 'login.php') ? 'text-amber-500' : 'text-gray-600'; ?> hover:text-amber-500 transition duration-150 font-medium block md:inline">
            Dashboard Login
        </a>
    <?php endif; ?>

</nav>

<!-- CTA Button - Hidden on mobile, shown on larger screens -->
<a href="#" class="flat-cta text-white px-2 sm:px-4 py-2 rounded-lg font-semibold shadow-lg hidden sm:block cta-trigger text-center" id="headerCta" data-user-has-account="<?php echo $userHasAccount ? 'true' : 'false'; ?>">Start Free Analysis</a>

<script>
// Mobile menu toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuButton = document.getElementById('mobileMenuButton');
    const headerNav = document.getElementById('headerNav');

    if (mobileMenuButton && headerNav) {
        mobileMenuButton.addEventListener('click', function() {
            headerNav.classList.toggle('hidden');

            // Toggle hamburger icon to X
            if (headerNav.classList.contains('hidden')) {
                // Change back to hamburger icon
                mobileMenuButton.innerHTML = `
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                `;
            } else {
                // Change to X icon
                mobileMenuButton.innerHTML = `
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                `;
            }
        });
    }
});
</script>
