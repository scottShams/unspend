<?php
session_start();

// Unset all session variables
$_SESSION = [];

// Delete the session cookie (if it exists)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session completely
session_destroy();

// Delete your custom cookies
setcookie('user_email', '', time() - 3600, '/');
setcookie('user_name', '', time() - 3600, '/');
setcookie('user_income', '', time() - 3600, '/');


// Optional: redirect to login or homepage
header("Location: index.php");
exit;
?>
