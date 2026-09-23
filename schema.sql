CREATE DATABASE IF NOT EXISTS empress3_db;
USE empress3_db;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS epins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    epin_code VARCHAR(50) NOT NULL UNIQUE,
    package_type VARCHAR(20) NOT NULL DEFAULT 'Starter_1000',
    status VARCHAR(20) NOT NULL DEFAULT 'Unused',
    generated_by_admin_id INT DEFAULT NULL,
    used_by_member_id VARCHAR(50) DEFAULT NULL,
    assigned_to VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(20) NOT NULL UNIQUE,
    sponsor_id VARCHAR(20) DEFAULT NULL,
    placement_parent_id VARCHAR(20) DEFAULT NULL,
    matrix_position INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    used_epin VARCHAR(50) NOT NULL,
    package_type VARCHAR(20) NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Active',
    address_line VARCHAR(255) DEFAULT NULL,
    place VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    pincode VARCHAR(20) DEFAULT NULL,
    state VARCHAR(100) DEFAULT NULL,
    pan_number VARCHAR(20) DEFAULT NULL,
    aadhaar_number VARCHAR(20) DEFAULT NULL,
    bank_name VARCHAR(100) DEFAULT NULL,
    bank_account_number VARCHAR(50) DEFAULT NULL,
    ifsc_code VARCHAR(20) DEFAULT NULL,
    crypto_wallet_address VARCHAR(255) DEFAULT NULL,
    wallet_network VARCHAR(50) DEFAULT 'USDT (TRC20)',
    kyc_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sponsor (sponsor_id),
    INDEX idx_parent (placement_parent_id)
);

CREATE TABLE IF NOT EXISTS wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(20) NOT NULL UNIQUE,
    balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    user_wallet_60 DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    company_wallet_40 DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(50) NOT NULL,
    type VARCHAR(50) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    wallet_type VARCHAR(20) NOT NULL DEFAULT 'Main',
    status VARCHAR(20) NOT NULL DEFAULT 'Credit',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_member (member_id)
);

CREATE TABLE IF NOT EXISTS withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(20) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_date TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type VARCHAR(20) NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token)
);

CREATE TABLE IF NOT EXISTS member_rebirths (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(20) NOT NULL,
    completed_level INT NOT NULL,
    rebirth_count INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rebirth_member (member_id)
);
