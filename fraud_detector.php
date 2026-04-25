<?php
require_once __DIR__ . '/config.php';

// =============================================
// AI Fraud Detection Engine
// Uganda Mobile Money Social Engineering Detector
// =============================================

class FraudDetector {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Main fraud analysis - analyses a transaction before processing
     */
    public function analyzeTransaction($userId, $senderPhone, $receiverPhone, $amount, $callerPhone = null) {
        $riskScore = 0;
        $alerts = [];
        $reasons = [];
        
        // 1. Check caller verification
        $callerRisk = $this->checkCallerRisk($callerPhone);
        $riskScore += $callerRisk['score'];
        if ($callerRisk['alert']) {
            $alerts[] = $callerRisk;
            $reasons[] = $callerRisk['reason'];
        }
        
        // 2. Check transaction burst (anomalous burst detection)
        $burstRisk = $this->checkTransactionBurst($userId);
        $riskScore += $burstRisk['score'];
        if ($burstRisk['alert']) {
            $alerts[] = $burstRisk;
            $reasons[] = $burstRisk['reason'];
        }
        
        // 3. Check amount risk
        $amountRisk = $this->checkAmountRisk($amount);
        $riskScore += $amountRisk['score'];
        if ($amountRisk['alert']) {
            $alerts[] = $amountRisk;
            $reasons[] = $amountRisk['reason'];
        }
        
        // 4. Check receiver history
        $receiverRisk = $this->checkReceiverRisk($receiverPhone, $userId);
        $riskScore += $receiverRisk['score'];
        if ($receiverRisk['alert']) {
            $alerts[] = $receiverRisk;
            $reasons[] = $receiverRisk['reason'];
        }
        
        // 5. Check blacklisted numbers
        $blacklistRisk = $this->checkBlacklist($callerPhone, $receiverPhone);
        $riskScore += $blacklistRisk['score'];
        if ($blacklistRisk['alert']) {
            $alerts[] = $blacklistRisk;
            $reasons[] = $blacklistRisk['reason'];
        }
        
        // Cap risk score at 100
        $riskScore = min(100, $riskScore);
        
        // Determine verdict
        $verdict = 'safe';
        if ($riskScore >= 70) $verdict = 'fraud';
        elseif ($riskScore >= 35) $verdict = 'suspicious';
        
        // Determine status
        $status = 'pending';
        if ($verdict === 'fraud') $status = 'blocked';
        elseif ($verdict === 'suspicious') $status = 'flagged';
        else $status = 'approved';
        
        $aiReason = !empty($reasons) ? implode(' | ', $reasons) : 'Transaction appears normal based on behavioral analysis.';
        
        return [
            'risk_score' => $riskScore,
            'verdict'    => $verdict,
            'status'     => $status,
            'alerts'     => $alerts,
            'ai_reason'  => $aiReason,
            'needs_prompt' => ($verdict === 'suspicious' || $verdict === 'fraud'),
            'security_message' => $this->generateSecurityPrompt($verdict, $riskScore, $reasons)
        ];
    }
    
    private function checkCallerRisk($callerPhone) {
        if (!$callerPhone) {
            return ['score' => 0, 'alert' => false, 'type' => null, 'reason' => ''];
        }
        
        $stmt = $this->pdo->prepare("SELECT * FROM caller_registry WHERE phone_number = ?");
        $stmt->execute([$callerPhone]);
        $caller = $stmt->fetch();
        
        if (!$caller) {
            // Unknown caller prompted you to send money
            return [
                'score' => 30,
                'alert' => true,
                'type' => 'unverified_caller',
                'severity' => 'high',
                'reason' => "Unverified caller ($callerPhone) not in registry - Social engineering risk detected",
                'description' => "You received a call from an unregistered number before this transaction"
            ];
        }
        
        if ($caller['is_blacklisted']) {
            return [
                'score' => 60,
                'alert' => true,
                'type' => 'social_engineering',
                'severity' => 'critical',
                'reason' => "BLACKLISTED number ($callerPhone) with {$caller['report_count']} fraud reports",
                'description' => "This caller has been reported for fraud {$caller['report_count']} times"
            ];
        }
        
        return ['score' => 0, 'alert' => false, 'type' => null, 'reason' => ''];
    }
    
    private function checkTransactionBurst($userId) {
        $window = BURST_WINDOW_MINUTES;
        $threshold = BURST_THRESHOLD;
        
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as cnt FROM transactions 
             WHERE user_id = ? AND created_at >= NOW() - INTERVAL ? MINUTE 
             AND status NOT IN ('blocked')"
        );
        $stmt->execute([$userId, $window]);
        $result = $stmt->fetch();
        $count = (int)$result['cnt'];
        
        if ($count >= $threshold) {
            return [
                'score' => 40,
                'alert' => true,
                'type' => 'anomalous_burst',
                'severity' => 'high',
                'reason' => "Anomalous burst: $count transactions in last {$window} minutes (threshold: {$threshold})",
                'description' => "Multiple rapid transactions detected - possible social engineering in progress"
            ];
        }
        
        if ($count >= 2) {
            return [
                'score' => 15,
                'alert' => true,
                'type' => 'rapid_repeat',
                'severity' => 'medium',
                'reason' => "Rapid repeat: $count transactions in last {$window} minutes",
                'description' => "Multiple transactions in a short window - monitoring closely"
            ];
        }
        
        return ['score' => 0, 'alert' => false, 'type' => null, 'reason' => ''];
    }
    
    private function checkAmountRisk($amount) {
        if ($amount >= HIGH_RISK_AMOUNT) {
            return [
                'score' => 25,
                'alert' => true,
                'type' => 'suspicious_amount',
                'severity' => 'high',
                'reason' => "High-value transaction: UGX " . number_format($amount) . " - requires extra verification",
                'description' => "Amount exceeds high-risk threshold of UGX " . number_format(HIGH_RISK_AMOUNT)
            ];
        }
        if ($amount >= MEDIUM_RISK_AMOUNT) {
            return [
                'score' => 10,
                'alert' => true,
                'type' => 'suspicious_amount',
                'severity' => 'medium',
                'reason' => "Medium-value transaction: UGX " . number_format($amount),
                'description' => "Amount flagged for additional monitoring"
            ];
        }
        return ['score' => 0, 'alert' => false, 'type' => null, 'reason' => ''];
    }
    
    private function checkReceiverRisk($receiverPhone, $userId) {
        // Check if user has sent to this number before
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as cnt FROM transactions WHERE user_id = ? AND receiver_phone = ? AND status = 'completed'"
        );
        $stmt->execute([$userId, $receiverPhone]);
        $result = $stmt->fetch();
        
        if ((int)$result['cnt'] === 0) {
            return [
                'score' => 10,
                'alert' => true,
                'type' => 'suspicious_amount',
                'severity' => 'low',
                'reason' => "First-time recipient: $receiverPhone - verify this is correct",
                'description' => "You have never sent money to this number before"
            ];
        }
        return ['score' => 0, 'alert' => false, 'type' => null, 'reason' => ''];
    }
    
    private function checkBlacklist($callerPhone, $receiverPhone) {
        $phones = array_filter([$callerPhone, $receiverPhone]);
        if (empty($phones)) return ['score' => 0, 'alert' => false, 'type' => null, 'reason' => ''];
        
        $placeholders = implode(',', array_fill(0, count($phones), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT phone_number FROM caller_registry WHERE phone_number IN ($placeholders) AND is_blacklisted = 1"
        );
        $stmt->execute($phones);
        $found = $stmt->fetchAll();
        
        if (!empty($found)) {
            $nums = implode(', ', array_column($found, 'phone_number'));
            return [
                'score' => 50,
                'alert' => true,
                'type' => 'social_engineering',
                'severity' => 'critical',
                'reason' => "Blacklisted number(s) involved: $nums",
                'description' => "One or more phone numbers linked to known fraud cases"
            ];
        }
        return ['score' => 0, 'alert' => false, 'type' => null, 'reason' => ''];
    }
    
    private function generateSecurityPrompt($verdict, $riskScore, $reasons) {
        if ($verdict === 'fraud') {
            return "⚠️ TRANSACTION BLOCKED: Our AI detected high fraud risk ({$riskScore}% risk score). " .
                   "This transaction matches known social engineering patterns used by scammers in Uganda. " .
                   "DO NOT proceed. If someone called asking you to send money, this is likely a SCAM.";
        }
        if ($verdict === 'suspicious') {
            return "🔐 SECURITY PAUSE: This transaction has been flagged (Risk: {$riskScore}%). " .
                   "Before proceeding, ask yourself: Did someone call you and ask you to send this money? " .
                   "Is this recipient someone you know personally? Are you being pressured?";
        }
        return "✅ Transaction analysed - no significant fraud indicators detected.";
    }
    
    public function saveTransaction($userId, $data, $analysis) {
        $ref = 'TXN' . strtoupper(bin2hex(random_bytes(5)));
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO transactions (user_id, transaction_ref, sender_phone, receiver_phone, amount, 
             transaction_type, caller_phone, caller_verified, status, risk_score, ai_verdict, ai_reason)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $callerVerified = 0;
        if ($data['caller_phone']) {
            $stmt2 = $this->pdo->prepare("SELECT is_verified FROM caller_registry WHERE phone_number = ?");
            $stmt2->execute([$data['caller_phone']]);
            $cr = $stmt2->fetch();
            $callerVerified = $cr ? (int)$cr['is_verified'] : 0;
        }
        
        $stmt->execute([
            $userId, $ref, $data['sender_phone'], $data['receiver_phone'],
            $data['amount'], $data['type'], $data['caller_phone'] ?? null,
            $callerVerified, $analysis['status'], $analysis['risk_score'],
            $analysis['verdict'], $analysis['ai_reason']
        ]);
        
        $txnId = $this->pdo->lastInsertId();
        
        // Save fraud alerts
        foreach ($analysis['alerts'] as $alert) {
            if ($alert['alert']) {
                $stmt3 = $this->pdo->prepare(
                    "INSERT INTO fraud_alerts (transaction_id, user_id, alert_type, severity, description) VALUES (?, ?, ?, ?, ?)"
                );
                $stmt3->execute([$txnId, $userId, $alert['type'], $alert['severity'], $alert['description']]);
            }
        }
        
        // Save security prompt if needed
        if ($analysis['needs_prompt']) {
            $stmt4 = $this->pdo->prepare(
                "INSERT INTO security_prompts (transaction_id, user_id, prompt_message) VALUES (?, ?, ?)"
            );
            $stmt4->execute([$txnId, $userId, $analysis['security_message']]);
        }
        
        return ['transaction_id' => $txnId, 'ref' => $ref];
    }
    
    public function getUserStats($userId) {
        $pdo = $this->pdo;
        $stats = [];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(amount) as total_amount FROM transactions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['total'] = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM transactions WHERE user_id = ? AND ai_verdict = 'fraud'");
        $stmt->execute([$userId]);
        $stats['fraud'] = $stmt->fetch()['cnt'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM transactions WHERE user_id = ? AND ai_verdict = 'suspicious'");
        $stmt->execute([$userId]);
        $stats['suspicious'] = $stmt->fetch()['cnt'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM fraud_alerts WHERE user_id = ? AND is_resolved = 0");
        $stmt->execute([$userId]);
        $stats['open_alerts'] = $stmt->fetch()['cnt'];
        
        return $stats;
    }
    
    public function getAdminStats() {
        $pdo = $this->pdo;
        $stats = [];
        
        $stats['total_users']        = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
        $stats['total_transactions'] = $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
        $stats['fraud_blocked']      = $pdo->query("SELECT COUNT(*) FROM transactions WHERE ai_verdict='fraud'")->fetchColumn();
        $stats['suspicious']         = $pdo->query("SELECT COUNT(*) FROM transactions WHERE ai_verdict='suspicious'")->fetchColumn();
        $stats['open_alerts']        = $pdo->query("SELECT COUNT(*) FROM fraud_alerts WHERE is_resolved=0")->fetchColumn();
        $stats['total_amount']       = $pdo->query("SELECT SUM(amount) FROM transactions WHERE status='completed'")->fetchColumn();
        $stats['blacklisted']        = $pdo->query("SELECT COUNT(*) FROM caller_registry WHERE is_blacklisted=1")->fetchColumn();
        
        return $stats;
    }
}
?>
