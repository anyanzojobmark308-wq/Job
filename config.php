<?php
// =============================================
// Database Configuration - XAMPP Ready
// =============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // XAMPP default
define('DB_PASS', '');           // XAMPP default (empty)
define('DB_NAME', 'ai_fraud_detector');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'AI Fraud Detector');
define('APP_URL', 'http://localhost/ai_fraud_detector');
define('APP_VERSION', '1.0.0');

// Session config
define('SESSION_LIFETIME', 3600); // 1 hour

// Fraud detection thresholds
define('BURST_WINDOW_MINUTES', 10);   // Check transactions in last N minutes
define('BURST_THRESHOLD', 3);          // Flag if N+ transactions in window
define('HIGH_RISK_AMOUNT', 500000);    // UGX - flag large amounts
define('MEDIUM_RISK_AMOUNT', 200000);  // UGX

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

function logAction($userId, $action, $details = null) {
    try {
        $pdo = getDBConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $details, $ip]);
    } catch (Exception $e) {
        // Silent fail for logs
    }
}
?>
