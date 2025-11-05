<?php
function checkAdminAuth() {
    session_start();
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function logout() {
    session_start();
    session_destroy();
    header('Location: login.php');
    exit;
}
?>