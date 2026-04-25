-- =============================================
-- AI Mobile Money Fraud Detector - Database
-- Uganda Mobile Money Security System
-- =============================================

CREATE DATABASE IF NOT EXISTS ai_fraud_detector CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ai_fraud_detector;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    profile_pic VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Transactions Table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    transaction_ref VARCHAR(50) UNIQUE NOT NULL,
    sender_phone VARCHAR(20) NOT NULL,
    receiver_phone VARCHAR(20) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    transaction_type ENUM('send','receive','withdraw','deposit') NOT NULL,
    caller_phone VARCHAR(20) DEFAULT NULL,
    caller_verified TINYINT(1) DEFAULT 0,
    status ENUM('pending','approved','flagged','blocked','completed') DEFAULT 'pending',
    risk_score DECIMAL(5,2) DEFAULT 0.00,
    ai_verdict ENUM('safe','suspicious','fraud') DEFAULT 'safe',
    ai_reason TEXT DEFAULT NULL,
    flagged_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Fraud Alerts Table
CREATE TABLE IF NOT EXISTS fraud_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    user_id INT NOT NULL,
    alert_type ENUM('anomalous_burst','unverified_caller','social_engineering','suspicious_amount','rapid_repeat') NOT NULL,
    severity ENUM('low','medium','high','critical') NOT NULL,
    description TEXT NOT NULL,
    is_resolved TINYINT(1) DEFAULT 0,
    resolved_by INT DEFAULT NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Security Prompts Table
CREATE TABLE IF NOT EXISTS security_prompts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    user_id INT NOT NULL,
    prompt_message TEXT NOT NULL,
    user_response ENUM('proceed','cancel','report') DEFAULT NULL,
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE
);

-- Caller Registry (Known/Unknown callers)
CREATE TABLE IF NOT EXISTS caller_registry (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) UNIQUE NOT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    is_blacklisted TINYINT(1) DEFAULT 0,
    telecom_provider VARCHAR(50) DEFAULT NULL,
    report_count INT DEFAULT 0,
    added_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- System Logs
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Default Admin
INSERT INTO users (full_name, email, phone, password, role) VALUES
('System Admin', 'admin@frauddetector.ug', '+256700000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert Sample Blacklisted Numbers
INSERT INTO caller_registry (phone_number, is_verified, is_blacklisted, telecom_provider, report_count) VALUES
('+256701234567', 0, 1, 'MTN Uganda', 15),
('+256782345678', 0, 1, 'Airtel Uganda', 8),
('+256703456789', 1, 0, 'MTN Uganda', 0),
('+256784567890', 1, 0, 'Airtel Uganda', 0);

-- Note: Default admin password is 'password' - CHANGE IMMEDIATELY in production
