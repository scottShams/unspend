CREATE TABLE uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    user_id INT NULL,
    user_email VARCHAR(255),
    user_name VARCHAR(255),
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    analysis_result TEXT,
    blueprint_result TEXT,
    
    -- Table-level constraint for Foreign Key (requires 'users' table to exist)
    CONSTRAINT fk_user_id 
        FOREIGN KEY (user_id) REFERENCES users(id) 
        ON DELETE CASCADE,
        
    -- Creating an index on user_id as part of CREATE TABLE (often automatically done by FK)
    INDEX idx_user_id (user_id);

    -- Index on user_email
    CREATE INDEX idx_user_email ON uploads (user_email);

    -- Index on upload_date
    CREATE INDEX idx_upload_date ON uploads (upload_date);
);