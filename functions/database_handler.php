<?php

// Load environment variables
require_once __DIR__ . '/../config/env.php';

function getPDO() {
    $dsn = 'mysql:host=' . Env::get('DB_HOST', 'localhost') . ';dbname=' . Env::get('DB_NAME', 'unSpend');
    $username = Env::get('DB_USER', 'root');
    $password = Env::get('DB_PASS', '');
    $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
    return new PDO($dsn, $username, $password, $options);
}

function saveAnalysisToDatabase($pdo, $targetFile, $userId, $analysisJson) {
    try {
        if (!$pdo || !$pdo->query('SELECT 1')) {
            $pdo = getPDO();
        }
    } catch (Exception $e) {
        $pdo = getPDO();
    }
    require_once 'user_management.php';
    $userManager = new UserManagement($pdo);

    // Get user details for backward compatibility
    $user = $userManager->getUserById($userId);
    if (!$user) {
        throw new Exception("User not found");
    }

    // Increment analysis count for user
    $userManager->incrementAnalysisCount($userId);

    // Insert with all required fields based on uploads.sql schema
    $stmt = $pdo->prepare("
        INSERT INTO uploads (
            filename,
            user_id,
            user_email,
            user_name,
            analysis_result,
            upload_date
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $targetFile,
        $userId,
        $user['email'],
        $user['name'],
        $analysisJson
    ]);
}

function getUserAnalysisHistory($pdo, $userId, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT
            id,
            filename,
            user_email,
            user_name,
            upload_date,
            analysis_result
        FROM uploads
        WHERE user_id = ?
        ORDER BY upload_date DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLatestUserAnalysis($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT
            id,
            filename,
            user_email,
            user_name,
            upload_date,
            analysis_result
        FROM uploads
        WHERE user_id = ?
        ORDER BY upload_date DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAnalysisById($pdo, $analysisId, $userId = null) {
    $query = "
        SELECT
            id,
            filename,
            user_id,
            user_email,
            user_name,
            upload_date,
            analysis_result
        FROM uploads
        WHERE id = ?
    ";
    $params = [$analysisId];

    if ($userId !== null) {
        $query .= " AND user_id = ?";
        $params[] = $userId;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUserAnalysisCount($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM uploads WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}


?>