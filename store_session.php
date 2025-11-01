<?php
session_start();

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $_SESSION['temp_email'] = $data['email'] ?? '';
    $_SESSION['temp_name'] = $data['name'] ?? '';
    $_SESSION['temp_income'] = $data['income'] ?? '';
}

echo json_encode(['success' => true]);
?>