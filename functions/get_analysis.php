<?php
session_start();
require '../config/database.php';
require 'user_management.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$analysisId = $_GET['id'] ?? null;

if (!$analysisId) {
    echo json_encode(['success' => false, 'message' => 'Analysis ID required']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    $userManager = new UserManagement($pdo);

    // Get the specific analysis for this user
    $stmt = $pdo->prepare("
        SELECT id, filename, upload_date, analysis_result
        FROM uploads
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$analysisId, $userId]);
    $analysis = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$analysis) {
        echo json_encode(['success' => false, 'message' => 'Analysis not found']);
        exit;
    }

    // Get user name
    $user = $userManager->getUserByEmail($_SESSION['user_email']);
    $userName = $user ? $user['name'] : 'User';

    // Parse the analysis result
    $analysisData = json_decode($analysis['analysis_result'], true);

    echo json_encode([
        'success' => true,
        'analysis' => $analysisData,
        'userName' => $userName,
        'date' => date('M j, Y', strtotime($analysis['upload_date'])),
        'filename' => $analysis['filename'],
        'statementPeriod' => $analysisData['summary']['statementPeriod'] ?? null
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error loading analysis: ' . $e->getMessage()]);
}
?>