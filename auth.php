<?php
require_once __DIR__ . '/config.php';

function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        session_start();
    }
}

function isLoggedIn() {
    startSecureSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function requireLogin($redirect = '../index.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit();
    }
}

function requireAdmin($redirect = '../index.php') {
    if (!isAdmin()) {
        header("Location: $redirect");
        exit();
    }
}

function registerUser($fullName, $email, $phone, $password, $role = 'user') {
    $pdo = getDBConnection();
    
    // Check duplicates
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
    $stmt->execute([$email, $phone]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email or phone number already registered.'];
    }
    
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$fullName, $email, $phone, $hashedPassword, $role]);
    
    logAction($pdo->lastInsertId(), 'REGISTER', "New $role registered: $email");
    return ['success' => true, 'message' => 'Registration successful!', 'user_id' => $pdo->lastInsertId()];
}

function loginUser($emailOrPhone, $password) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (email = ? OR phone = ?) AND is_active = 1");
    $stmt->execute([$emailOrPhone, $emailOrPhone]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid credentials. Please try again.'];
    }
    
    startSecureSession();
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email']     = $user['email'];
    $_SESSION['phone']     = $user['phone'];
    $_SESSION['role']      = $user['role'];
    
    logAction($user['id'], 'LOGIN', "User logged in: {$user['email']}");
    return ['success' => true, 'message' => 'Login successful!', 'role' => $user['role']];
}

function logoutUser() {
    startSecureSession();
    if (isset($_SESSION['user_id'])) {
        logAction($_SESSION['user_id'], 'LOGOUT', 'User logged out');
    }
    session_destroy();
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, full_name, email, phone, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
?>
