-- ============================================================
--  FraudGuard – Create withdrawal_requests table
--  HOW TO USE:
--  1. Open http://localhost/phpmyadmin
--  2. Click fraudguard_db in left sidebar
--  3. Click the SQL tab at the top
--  4. Paste this entire code and click Go
-- ============================================================

USE fraudguard_db;

CREATE TABLE IF NOT EXISTS withdrawal_requests (
    withdrawal_id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id              INT              NOT NULL,
    withdrawal_reference VARCHAR(50) UNIQUE NOT NULL,
    amount               DECIMAL(15,2)    NOT NULL,
    method               VARCHAR(100)     NOT NULL,
    account_name         VARCHAR(150)     NOT NULL,
    account_number       VARCHAR(150)     NOT NULL,
    notes                TEXT,
    status               ENUM('pending','approved','rejected','cancelled') DEFAULT 'pending',
    admin_notes          TEXT,
    processed_by         INT,
    processed_at         DATETIME,
    created_at           DATETIME         DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)      REFERENCES users(user_id),
    FOREIGN KEY (processed_by) REFERENCES users(user_id)
);

-- Confirm
SELECT 'withdrawal_requests table created successfully!' AS Result;
