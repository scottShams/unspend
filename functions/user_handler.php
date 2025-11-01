<?php

function getUserName($pdo, $email) {
    try {
        if($email) {
            $stmt = $pdo->prepare("SELECT user_name FROM uploads WHERE user_email = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$email]);
            return $stmt->fetchColumn() ?: 'User';
        }
    } catch (PDOException $e) {
        // Log error if needed
    }
    return 'User';
}

?>