CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    password VARCHAR(255) NULL,
    income DECIMAL(10, 2) NOT NULL,
    referral_token VARCHAR(255) UNIQUE NULL,
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255) UNIQUE NULL,
    age INT NULL,
    country VARCHAR(10) NULL,
    occupation VARCHAR(255) NULL,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say') NULL,
    motivation VARCHAR(100) NULL,
    blueprint_unlocked TINYINT(1) DEFAULT 0,
    blueprint_unlocked_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    analysis_count INT DEFAULT 0,
    additional_credits INT DEFAULT 0
);

-- Update uploads table to reference users
ALTER TABLE uploads 
ADD COLUMN user_id INT NULL AFTER filename;
ALTER TABLE uploads 
ADD CONSTRAINT fk_user_id 
FOREIGN KEY (user_id) REFERENCES users(id) 
ON DELETE CASCADE;

ALTER TABLE users ADD COLUMN additional_credits INT DEFAULT 0;

-- Create referral_clicks table to track anonymous clicks
CREATE TABLE referral_clicks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    session_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(id),
    INDEX idx_referrer_created (referrer_id, created_at)
);

-- Create referrals table to track referral relationships
CREATE TABLE referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL,
    referred_user_id INT NOT NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (referrer_id) REFERENCES users(id),
    FOREIGN KEY (referred_user_id) REFERENCES users(id),
    UNIQUE KEY unique_referral (referrer_id, referred_user_id)
);