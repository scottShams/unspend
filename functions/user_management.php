<?php

class UserManagement {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function createOrGetUser($email, $name, $income, $password = null, $referrerId = null) {
        try {
            // First, try to find existing user
            $stmt = $this->pdo->prepare("SELECT id, name, email, income, referral_token, email_verified FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Update last login and return existing user
                return $this->updateLastLogin($user['id'], $name, $income);
            } else {
                // Generate referral token and verification token
                $referralToken = $this->generateReferralToken();
                $verificationToken = $this->generateVerificationToken();

                // Create new user
                $stmt = $this->pdo->prepare("INSERT INTO users (email, name, password, income, referral_token, verification_token) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$email, $name, $password ?: '', $income, $referralToken, $verificationToken]);
                $userId = $this->pdo->lastInsertId();

                // If referrer exists, create referral relationship
                if ($referrerId) {
                    $this->createReferral($referrerId, $userId);
                }

                return [
                    'id' => $userId,
                    'name' => $name,
                    'email' => $email,
                    'income' => $income,
                    'referral_token' => $referralToken,
                    'verification_token' => $verificationToken,
                    'email_verified' => 0
                ];
            }
        } catch (PDOException $e) {
            throw new Exception("Error creating/getting user: " . $e->getMessage());
        }
    }

    public function updateLastLogin($userId, $name, $income) {
        $stmt = $this->pdo->prepare("UPDATE users SET name = ?, income = ?, last_login = NOW() WHERE id = ?");
        $stmt->execute([$name, $income, $userId]);
        
        // Fetch and return the updated user data
        $stmt = $this->pdo->prepare("SELECT id, name, email, income FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function incrementAnalysisCount($userId) {
        $stmt = $this->pdo->prepare("UPDATE users SET analysis_count = analysis_count + 1 WHERE id = ?");
        $stmt->execute([$userId]);
    }

    public function getUserByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserById($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserAnalysisHistory($userId, $limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT id, filename, upload_date, analysis_result
            FROM uploads
            WHERE user_id = :userId
            ORDER BY upload_date DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLatestAnalysis($userId) {
        $stmt = $this->pdo->prepare("
            SELECT id, filename, upload_date, analysis_result, blueprint_result
            FROM uploads
            WHERE user_id = ?
            ORDER BY upload_date DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAnalysisById($analysisId, $userId = null) {
        $query = "
            SELECT id, filename, upload_date, analysis_result, blueprint_result
            FROM uploads
            WHERE id = ?
        ";
        $params = [$analysisId];

        if ($userId !== null) {
            $query .= " AND user_id = ?";
            $params[] = $userId;
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function generateReferralToken() {
        return bin2hex(random_bytes(16)); // Generates a 32-character hex string
    }

    private function generateVerificationToken() {
        return bin2hex(random_bytes(32)); // Generates a 64-character hex string
    }

    public function getUserByReferralToken($token) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE referral_token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserByVerificationToken($token) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE verification_token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function verifyUserEmail($userId) {
        $stmt = $this->pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    public function createReferral($referrerId, $referredUserId) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO referrals (referrer_id, referred_user_id) VALUES (?, ?)");
            return $stmt->execute([$referrerId, $referredUserId]);
        } catch (PDOException $e) {
            // Handle duplicate referral (if user tries to register again)
            return false;
        }
    }

    public function completeReferral($referredUserId) {
        $stmt = $this->pdo->prepare("UPDATE referrals SET status = 'completed', completed_at = NOW() WHERE referred_user_id = ? AND status = 'pending'");
        return $stmt->execute([$referredUserId]);
    }

    public function getReferralStats($userId) {
        // Get total referrals (clicks)
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total_clicks FROM referrals WHERE referrer_id = ?");
        $stmt->execute([$userId]);
        $totalClicks = $stmt->fetch(PDO::FETCH_ASSOC)['total_clicks'];

        // Get pending referrals
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as pending FROM referrals WHERE referrer_id = ? AND status = 'pending'");
        $stmt->execute([$userId]);
        $pending = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];

        // Get completed referrals
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as completed FROM referrals WHERE referrer_id = ? AND status = 'completed'");
        $stmt->execute([$userId]);
        $completed = $stmt->fetch(PDO::FETCH_ASSOC)['completed'];

        return [
            'total_clicks' => $totalClicks,
            'pending' => $pending,
            'completed' => $completed,
            'earnings' => $completed * 10.00 // $10 per completed referral
        ];
    }

    public function getReferralList($userId) {
        $stmt = $this->pdo->prepare("
            SELECT r.status, r.created_at, r.completed_at, u.name, u.email
            FROM referrals r
            JOIN users u ON r.referred_user_id = u.id
            WHERE r.referrer_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function authenticateUser($email, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
}

?>