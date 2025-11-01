CREATE TABLE uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    user_id INT NULL,
    user_email VARCHAR(255),
    user_name VARCHAR(255),
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    analysis_result TEXT,
    blueprint_result TEXT,
    INDEX idx_user_id (user_id),
    INDEX idx_user_email (user_email),
    INDEX idx_upload_date (upload_date)
);