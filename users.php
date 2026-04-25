<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
requireAdmin('../index.php');

$action = $_GET['action'] ?? '';
$pdo = getDBConnection();

switch($action) {
    case 'list':
        $stmt = $pdo->query("SELECT id, full_name, email, phone, role, is_active, created_at FROM users WHERE role='user' ORDER BY created_at DESC");
        echo json_encode(['success'=>true,'users'=>$stmt->fetchAll()]);
        break;
    
    case 'blacklist':
        $stmt = $pdo->query("SELECT * FROM caller_registry WHERE is_blacklisted=1 ORDER BY created_at DESC");
        echo json_encode(['success'=>true,'numbers'=>$stmt->fetchAll()]);
        break;
    
    case 'toggle_user':
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = intval($input['user_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE users SET is_active = 1 - is_active WHERE id = ?");
        $stmt->execute([$userId]);
        echo json_encode(['success'=>true]);
        break;
    
    default:
        echo json_encode(['success'=>false,'message'=>'Unknown action']);
}
?>
