<?php
// ============================================================
//  FraudGuard – Withdrawal Handler (withdraw.php)
//  Save as: C:\xampp\htdocs\fraudguard\php\withdraw.php
// ============================================================
require_once __DIR__ . '/config.php';
startSession();
requireLogin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user   = currentUser();
$db     = getDB();

// ─── CUSTOMER: Submit withdrawal request ──────────────────
if ($action === 'request_withdrawal') {
    $amount    = (float)($_POST['amount'] ?? 0);
    $method    = sanitize($_POST['method'] ?? '');
    $accName   = sanitize($_POST['account_name'] ?? '');
    $accNumber = sanitize($_POST['account_number'] ?? '');
    $notes     = sanitize($_POST['notes'] ?? '');

    // Validations
    if ($amount < 5000) {
        echo json_encode(['success'=>false,'message'=>'Minimum withdrawal is UGX 5,000.']); exit;
    }
    if (!$method) {
        echo json_encode(['success'=>false,'message'=>'Please select a withdrawal method.']); exit;
    }
    if (!$accNumber) {
        echo json_encode(['success'=>false,'message'=>'Please enter your account/phone number.']); exit;
    }
    if (!$accName) {
        echo json_encode(['success'=>false,'message'=>'Please enter the account holder name.']); exit;
    }

    // Check balance
    $balStmt = $db->prepare("SELECT balance FROM users WHERE user_id = ?");
    $balStmt->execute([$user['user_id']]);
    $balance = (float)$balStmt->fetchColumn();

    if ($balance < $amount) {
        echo json_encode(['success'=>false,
            'message'=>'Insufficient balance. Your balance is ' . formatUGX($balance)]); exit;
    }

    // Check for pending withdrawal already
    $pendingStmt = $db->prepare("
        SELECT COUNT(*) FROM withdrawal_requests
        WHERE user_id = ? AND status = 'pending'
    ");
    $pendingStmt->execute([$user['user_id']]);
    if ((int)$pendingStmt->fetchColumn() >= 3) {
        echo json_encode(['success'=>false,
            'message'=>'You have too many pending withdrawals. Please wait for them to be processed.']); exit;
    }

    // Generate reference
    $wdRef = generateRef('WD');

    // Hold the funds (deduct immediately, refund if rejected)
    $db->prepare("UPDATE users SET balance = balance - ? WHERE user_id = ?")
       ->execute([$amount, $user['user_id']]);

    // Save withdrawal request
    $stmt = $db->prepare("
        INSERT INTO withdrawal_requests
          (user_id, withdrawal_reference, amount, method,
           account_name, account_number, notes, status)
        VALUES (?,?,?,?,?,?,?,'pending')
    ");
    $stmt->execute([
        $user['user_id'], $wdRef, $amount, $method,
        $accName, $accNumber, $notes
    ]);
    $wdId = (int)$db->lastInsertId();

    // Record as a pending transaction
    $txnRef = generateRef('TXN');
    $db->prepare("
        INSERT INTO transactions
          (sender_id, txn_reference, txn_type, amount, channel, status, fraud_score)
        VALUES (?,'$txnRef','withdrawal',?,'mobile_money','pending',0)
    ")->execute([$user['user_id'], $amount]);

    // Notify admins
    $admins = $db->query(
        "SELECT user_id FROM users WHERE role IN ('admin','fraud_officer')"
    )->fetchAll();
    foreach ($admins as $admin) {
        $db->prepare("INSERT INTO notifications (user_id,title,message,type) VALUES (?,?,?,?)")
           ->execute([
               $admin['user_id'],
               'New Withdrawal Request',
               "{$user['full_name']} requested withdrawal of " . formatUGX($amount) . " to $method ($accNumber). Ref: $wdRef",
               'info'
           ]);
    }

    logAudit($user['user_id'], "Withdrawal request $wdRef – " . formatUGX($amount), 'withdrawal');

    echo json_encode([
        'success'   => true,
        'message'   => "Withdrawal request submitted! Reference: $wdRef. Funds will be sent to your $method account within 24 hours.",
        'reference' => $wdRef,
        'new_balance'=> $balance - $amount,
    ]);
    exit;
}

// ─── CUSTOMER: Get my withdrawal requests ─────────────────
if ($action === 'my_withdrawals') {
    $stmt = $db->prepare("
        SELECT * FROM withdrawal_requests
        WHERE user_id = ? ORDER BY created_at DESC
    ");
    $stmt->execute([$user['user_id']]);
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
    exit;
}

// ─── ADMIN: Get all withdrawal requests ───────────────────
if ($action === 'admin_get_withdrawals' && isAdmin()) {
    $stmt = $db->query("
        SELECT wr.*, u.full_name, u.email, u.phone, u.account_no, u.balance
        FROM withdrawal_requests wr
        JOIN users u ON wr.user_id = u.user_id
        ORDER BY wr.created_at DESC
    ");
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
    exit;
}

// ─── ADMIN: Approve withdrawal ────────────────────────────
if ($action === 'approve_withdrawal' && isAdmin()) {
    $wdId      = (int)($_POST['withdrawal_id'] ?? 0);
    $adminNotes= sanitize($_POST['admin_notes'] ?? '');

    if (!$wdId) {
        echo json_encode(['success'=>false,'message'=>'Invalid withdrawal ID.']); exit;
    }

    $wdStmt = $db->prepare("
        SELECT * FROM withdrawal_requests WHERE withdrawal_id=? AND status='pending'
    ");
    $wdStmt->execute([$wdId]);
    $wd = $wdStmt->fetch();

    if (!$wd) {
        echo json_encode(['success'=>false,'message'=>'Withdrawal not found or already processed.']); exit;
    }

    // Approve — funds already deducted at request time
    $db->prepare("
        UPDATE withdrawal_requests
        SET status='approved', admin_notes=?, processed_by=?, processed_at=NOW()
        WHERE withdrawal_id=?
    ")->execute([$adminNotes, $user['user_id'], $wdId]);

    // Update transaction status to completed
    $db->prepare("
        UPDATE transactions SET status='completed'
        WHERE sender_id=? AND txn_type='withdrawal' AND status='pending'
        ORDER BY created_at DESC LIMIT 1
    ")->execute([$wd['user_id']]);

    // Notify user
    $db->prepare("INSERT INTO notifications (user_id,title,message,type) VALUES (?,?,?,?)")
       ->execute([
           $wd['user_id'],
           'Withdrawal Approved ✅',
           formatUGX($wd['amount']) . " has been sent to your {$wd['method']} account ({$wd['account_number']}). Ref: {$wd['withdrawal_reference']}.",
           'success'
       ]);

    logAudit($user['user_id'], "Withdrawal {$wd['withdrawal_reference']} approved", 'withdrawal');

    echo json_encode([
        'success' => true,
        'message' => "Withdrawal approved. " . formatUGX($wd['amount']) . " will be sent to {$wd['account_number']}."
    ]);
    exit;
}

// ─── ADMIN: Reject withdrawal (refund balance) ────────────
if ($action === 'reject_withdrawal' && isAdmin()) {
    $wdId      = (int)($_POST['withdrawal_id'] ?? 0);
    $adminNotes= sanitize($_POST['admin_notes'] ?? '');

    if (!$wdId) {
        echo json_encode(['success'=>false,'message'=>'Invalid withdrawal ID.']); exit;
    }
    if (!$adminNotes) {
        echo json_encode(['success'=>false,'message'=>'Please provide a reason for rejection.']); exit;
    }

    $wdStmt = $db->prepare("
        SELECT * FROM withdrawal_requests WHERE withdrawal_id=? AND status='pending'
    ");
    $wdStmt->execute([$wdId]);
    $wd = $wdStmt->fetch();

    if (!$wd) {
        echo json_encode(['success'=>false,'message'=>'Withdrawal not found or already processed.']); exit;
    }

    // Reject — refund the held funds back to user
    $db->prepare("
        UPDATE withdrawal_requests
        SET status='rejected', admin_notes=?, processed_by=?, processed_at=NOW()
        WHERE withdrawal_id=?
    ")->execute([$adminNotes, $user['user_id'], $wdId]);

    // Refund balance
    $db->prepare("UPDATE users SET balance = balance + ? WHERE user_id=?")
       ->execute([$wd['amount'], $wd['user_id']]);

    // Update transaction status
    $db->prepare("
        UPDATE transactions SET status='flagged'
        WHERE sender_id=? AND txn_type='withdrawal' AND status='pending'
        ORDER BY created_at DESC LIMIT 1
    ")->execute([$wd['user_id']]);

    // Notify user
    $db->prepare("INSERT INTO notifications (user_id,title,message,type) VALUES (?,?,?,?)")
       ->execute([
           $wd['user_id'],
           'Withdrawal Rejected',
           "Your withdrawal request (Ref: {$wd['withdrawal_reference']}) of " . formatUGX($wd['amount']) . " was rejected. Reason: $adminNotes. Your funds have been refunded to your account.",
           'warning'
       ]);

    logAudit($user['user_id'], "Withdrawal {$wd['withdrawal_reference']} rejected – refunded", 'withdrawal');

    echo json_encode([
        'success' => true,
        'message' => "Withdrawal rejected. " . formatUGX($wd['amount']) . " has been refunded to the user's account."
    ]);
    exit;
}

// ─── CUSTOMER: Cancel own pending withdrawal ──────────────
if ($action === 'cancel_withdrawal') {
    $wdId = (int)($_POST['withdrawal_id'] ?? 0);

    $wdStmt = $db->prepare("
        SELECT * FROM withdrawal_requests
        WHERE withdrawal_id=? AND user_id=? AND status='pending'
    ");
    $wdStmt->execute([$wdId, $user['user_id']]);
    $wd = $wdStmt->fetch();

    if (!$wd) {
        echo json_encode(['success'=>false,'message'=>'Withdrawal not found or cannot be cancelled.']); exit;
    }

    // Cancel and refund
    $db->prepare("
        UPDATE withdrawal_requests SET status='cancelled' WHERE withdrawal_id=?
    ")->execute([$wdId]);

    $db->prepare("UPDATE users SET balance = balance + ? WHERE user_id=?")
       ->execute([$wd['amount'], $user['user_id']]);

    logAudit($user['user_id'], "Withdrawal {$wd['withdrawal_reference']} cancelled by user", 'withdrawal');

    echo json_encode([
        'success' => true,
        'message' => "Withdrawal cancelled. " . formatUGX($wd['amount']) . " has been returned to your account."
    ]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid action.']);
