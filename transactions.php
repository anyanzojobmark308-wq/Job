<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../includes/fraud_detector.php';

requireLogin('../index.php');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$action = $input['action'] ?? $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];
$detector = new FraudDetector();

switch ($action) {
    case 'analyze':
        $senderPhone   = trim($input['sender_phone'] ?? $_SESSION['phone']);
        $receiverPhone = trim($input['receiver_phone'] ?? '');
        $amount        = floatval($input['amount'] ?? 0);
        $callerPhone   = trim($input['caller_phone'] ?? '');
        $type          = $input['type'] ?? 'send';
        
        if (!$receiverPhone || $amount <= 0) {
            echo json_encode(['success'=>false,'message'=>'Invalid transaction details']);
            exit;
        }
        
        // AI Analysis
        $analysis = $detector->analyzeTransaction($userId, $senderPhone, $receiverPhone, $amount, $callerPhone ?: null);
        
        echo json_encode(['success'=>true, 'analysis'=>$analysis]);
        break;

    case 'submit':
        $senderPhone   = trim($input['sender_phone'] ?? $_SESSION['phone']);
        $receiverPhone = trim($input['receiver_phone'] ?? '');
        $amount        = floatval($input['amount'] ?? 0);
        $callerPhone   = trim($input['caller_phone'] ?? '');
        $type          = $input['type'] ?? 'send';
        $userDecision  = $input['decision'] ?? 'proceed'; // proceed or cancel
        
        if (!$receiverPhone || $amount <= 0) {
            echo json_encode(['success'=>false,'message'=>'Invalid transaction details']);
            exit;
        }
        
        $analysis = $detector->analyzeTransaction($userId, $senderPhone, $receiverPhone, $amount, $callerPhone ?: null);
        
        // If user cancels on suspicious
        if ($userDecision === 'cancel') {
            $analysis['status'] = 'blocked';
            $analysis['verdict'] = 'suspicious';
            $analysis['ai_reason'] = '[User cancelled after security prompt] ' . $analysis['ai_reason'];
        }
        
        $txn = $detector->saveTransaction($userId, [
            'sender_phone'   => $senderPhone,
            'receiver_phone' => $receiverPhone,
            'amount'         => $amount,
            'caller_phone'   => $callerPhone ?: null,
            'type'           => $type,
        ], $analysis);
        
        // Update prompt response
        if ($analysis['needs_prompt']) {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("UPDATE security_prompts SET user_response = ?, responded_at = NOW() WHERE transaction_id = ?");
            $stmt->execute([$userDecision === 'cancel' ? 'cancel' : 'proceed', $txn['transaction_id']]);
        }
        
        logAction($userId, 'TRANSACTION', "Ref: {$txn['ref']} | Verdict: {$analysis['verdict']} | Score: {$analysis['risk_score']}");
        
        echo json_encode(['success'=>true, 'transaction'=>$txn, 'analysis'=>$analysis]);
        break;

    case 'history':
        $pdo = getDBConnection();
        $page  = max(1, intval($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page-1)*$limit;
        
        $isAdmin = $_SESSION['role'] === 'admin';
        if ($isAdmin) {
            $stmt = $pdo->prepare("SELECT t.*, u.full_name, u.phone as user_phone FROM transactions t JOIN users u ON t.user_id=u.id ORDER BY t.created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute([$limit, $offset]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id=? ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute([$userId, $limit, $offset]);
        }
        $transactions = $stmt->fetchAll();
        echo json_encode(['success'=>true,'transactions'=>$transactions]);
        break;

    case 'stats':
        if ($_SESSION['role'] === 'admin') {
            echo json_encode(['success'=>true,'stats'=>$detector->getAdminStats()]);
        } else {
            echo json_encode(['success'=>true,'stats'=>$detector->getUserStats($userId)]);
        }
        break;

    case 'alerts':
        $pdo = getDBConnection();
        if ($_SESSION['role'] === 'admin') {
            $stmt = $pdo->query("SELECT fa.*, t.transaction_ref, t.amount, u.full_name FROM fraud_alerts fa JOIN transactions t ON fa.transaction_id=t.id JOIN users u ON fa.user_id=u.id ORDER BY fa.created_at DESC LIMIT 50");
        } else {
            $stmt = $pdo->prepare("SELECT fa.*, t.transaction_ref, t.amount FROM fraud_alerts fa JOIN transactions t ON fa.transaction_id=t.id WHERE fa.user_id=? ORDER BY fa.created_at DESC LIMIT 20");
            $stmt->execute([$userId]);
        }
        echo json_encode(['success'=>true,'alerts'=>$stmt->fetchAll()]);
        break;

    case 'resolve_alert':
        if ($_SESSION['role'] !== 'admin') { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
        $alertId = intval($input['alert_id'] ?? 0);
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE fraud_alerts SET is_resolved=1, resolved_by=?, resolved_at=NOW() WHERE id=?");
        $stmt->execute([$userId, $alertId]);
        echo json_encode(['success'=>true]);
        break;

    case 'blacklist':
        if ($_SESSION['role'] !== 'admin') { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
        $phone    = trim($input['phone'] ?? '');
        $action2  = $input['bl_action'] ?? 'add';
        $provider = trim($input['provider'] ?? '');
        
        if (!$phone) { echo json_encode(['success'=>false,'message'=>'Phone required']); exit; }
        $pdo = getDBConnection();
        if ($action2 === 'add') {
            $stmt = $pdo->prepare("INSERT INTO caller_registry (phone_number, is_blacklisted, telecom_provider, added_by) VALUES (?,1,?,?) ON DUPLICATE KEY UPDATE is_blacklisted=1");
            $stmt->execute([$phone, $provider, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE caller_registry SET is_blacklisted=0 WHERE phone_number=?");
            $stmt->execute([$phone]);
        }
        echo json_encode(['success'=>true]);
        break;

    default:
        echo json_encode(['success'=>false,'message'=>'Unknown action']);
}
?>
