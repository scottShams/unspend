<?php
session_start();

echo json_encode([
    'email' => $_SESSION['temp_email'] ?? '',
    'name' => $_SESSION['temp_name'] ?? '',
    'income' => $_SESSION['temp_income'] ?? ''
]);
?>