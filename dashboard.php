<?php
// ============================================================
//  FraudGuard – Customer Dashboard
// ============================================================
require_once 'php/config.php';
startSession(); requireLogin();
$user = currentUser();
if (isAdmin()) { header('Location: admin_dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard – FraudGuard</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="app-layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="icon">🛡️</div>
      <div class="name">Fraud<span>Guard</span></div>
    </div>
    <div class="sidebar-user">
      <div class="avatar"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
      <div class="uname"><?= htmlspecialchars($user['full_name']) ?></div>
      <div class="urole"><?= ucfirst($user['role']) ?></div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section-title">Main</div>
      <a href="dashboard.php"   class="nav-link active"><span class="icon">📊</span> Dashboard</a>
      <a href="deposit.php"     class="nav-link"><span class="icon">💰</span> Deposit Money</a>
      <a href="withdraw.php"    class="nav-link"><span class="icon">💵</span> Withdraw Money</a>
      <a href="#" class="nav-link" onclick="openModal('transferModal')"><span class="icon">💸</span> Make Transfer</a>
      <a href="transactions.php" class="nav-link"><span class="icon">📋</span> Transactions</a>

      <div class="nav-section-title">Support</div>
      <a href="refunds.php" class="nav-link"><span class="icon">🔄</span> My Refund Cases</a>
      <a href="#" class="nav-link" onclick="openModal('refundModal')"><span class="icon">📝</span> Report Fraud</a>
      <a href="notifications.php" class="nav-link"><span class="icon">🔔</span> Notifications <span class="nav-badge" id="notif-count" style="display:none">!</span></a>
    </nav>
    <div class="sidebar-footer">
      <a href="php/auth.php?action=logout" class="nav-link"><span class="icon">🚪</span> Logout</a>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">Dashboard</div>
      <div class="topbar-actions">
        <button class="notif-btn" onclick="location.href='notifications.php'" id="notif-bell">🔔<span class="notif-dot" id="notif-dot" style="display:none"></span></button>
        <button class="btn btn-primary" onclick="openModal('transferModal')">+ New Transfer</button>
      </div>
    </div>

    <div class="page-content">
      <div id="toast-container"></div>

      <!-- Welcome Banner -->
      <div style="background: linear-gradient(135deg,rgba(0,212,255,0.08),rgba(0,102,255,0.05)); border:1px solid rgba(0,212,255,0.15); border-radius:16px; padding:24px 28px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center;">
        <div>
          <div style="font-size:22px; font-weight:700;">Good day, <?= htmlspecialchars(explode(' ',$user['full_name'])[0]) ?>! 👋</div>
          <div style="color:var(--muted); margin-top:4px; font-size:14px;">Your account is protected and monitored 24/7.</div>
        </div>
        <div style="text-align:right">
          <div style="font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px;">Account Number</div>
          <div style="font-size:18px; font-weight:700; color:var(--accent); font-family:monospace;" id="acc-no">Loading...</div>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-grid" id="stats-grid">
        <div class="stat-card blue">
          <div class="stat-icon">💰</div>
          <div class="stat-value" id="stat-balance">—</div>
          <div class="stat-label">Account Balance</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon">✅</div>
          <div class="stat-value" id="stat-txn">—</div>
          <div class="stat-label">Completed Transactions</div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon">📁</div>
          <div class="stat-value" id="stat-cases">—</div>
          <div class="stat-label">Refund Cases</div>
        </div>
        <div class="stat-card purple">
          <div class="stat-icon">🔔</div>
          <div class="stat-value" id="stat-notif">—</div>
          <div class="stat-label">Unread Alerts</div>
        </div>
      </div>

      <!-- Recent Transactions -->
      <div class="data-card">
        <div class="data-card-header">
          <div class="data-card-title">Recent Transactions</div>
          <a href="transactions.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div style="overflow-x:auto">
          <table class="data-table" id="recent-txn-table">
            <thead>
              <tr>
                <th>Reference</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Fraud Score</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody id="recent-txn-body">
              <tr><td colspan="6" style="text-align:center; color:var(--muted); padding:30px">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- TRANSFER MODAL -->
<div class="modal-overlay" id="transferModal">
  <div class="modal">
    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:20px">
      <div>
        <div class="modal-title">💸 New Transfer</div>
        <div class="modal-sub">Send money securely via FraudGuard</div>
      </div>
      <button onclick="closeModal('transferModal')" style="background:none;border:none;color:var(--muted);font-size:22px;cursor:pointer">×</button>
    </div>
    <div id="transfer-alert"></div>
    <div class="form-group">
      <label class="form-label">Receiver Account Number</label>
      <input type="text" class="form-input" id="receiver-acc" placeholder="e.g. FG-2025-0002">
    </div>
    <div class="form-group">
      <label class="form-label">Amount (UGX)</label>
      <input type="number" class="form-input" id="transfer-amount" placeholder="Enter amount" min="100">
    </div>
    <div class="form-group">
      <label class="form-label">Channel</label>
      <select class="form-select" id="transfer-channel">
        <option value="mobile_money">Mobile Money</option>
        <option value="online_banking">Online Banking</option>
        <option value="agent">Agent</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Location (optional)</label>
      <input type="text" class="form-input" id="transfer-location" placeholder="e.g. Kampala, UG">
    </div>
    <div style="display:flex; gap:12px; margin-top:8px">
      <button class="btn btn-secondary" onclick="closeModal('transferModal')" style="flex:1">Cancel</button>
      <button class="btn btn-primary" onclick="submitTransfer()" style="flex:1" id="transfer-btn">Send Money</button>
    </div>
  </div>
</div>

<!-- REPORT FRAUD MODAL -->
<div class="modal-overlay" id="refundModal">
  <div class="modal">
    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:20px">
      <div>
        <div class="modal-title">📝 Report Fraud / Request Refund</div>
        <div class="modal-sub">Submit a dispute for an unauthorized transaction</div>
      </div>
      <button onclick="closeModal('refundModal')" style="background:none;border:none;color:var(--muted);font-size:22px;cursor:pointer">×</button>
    </div>
    <div id="refund-alert"></div>
    <div class="form-group">
      <label class="form-label">Transaction Reference</label>
      <input type="text" class="form-input" id="case-txn-ref" placeholder="TXN-2025-XXXXXX">
    </div>
    <div class="form-group">
      <label class="form-label">Fraud Type</label>
      <select class="form-select" id="case-fraud-type">
        <option value="Unauthorized Transfer">Unauthorized Transfer</option>
        <option value="SIM Swap Fraud">SIM Swap Fraud</option>
        <option value="Phishing/Scam">Phishing / Scam</option>
        <option value="Account Takeover">Account Takeover</option>
        <option value="Fake Payment Confirmation">Fake Payment Confirmation</option>
        <option value="Other">Other</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Amount Claimed (UGX)</label>
      <input type="number" class="form-input" id="case-amount" placeholder="Amount you want refunded">
    </div>
    <div class="form-group">
      <label class="form-label">Description</label>
      <textarea class="form-textarea" id="case-desc" placeholder="Describe what happened in detail..."></textarea>
    </div>
    <div style="display:flex; gap:12px; margin-top:8px">
      <button class="btn btn-secondary" onclick="closeModal('refundModal')" style="flex:1">Cancel</button>
      <button class="btn btn-primary" onclick="submitRefundCase()" style="flex:1">Submit Case</button>
    </div>
  </div>
</div>

<script>
// ── Helpers ──────────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function toast(msg, type='success') {
  const t = document.createElement('div');
  t.className = `toast toast-${type}`;
  t.textContent = msg;
  document.getElementById('toast-container').appendChild(t);
  setTimeout(() => t.remove(), 4000);
}

function fmtUGX(n) { return 'UGX ' + Number(n).toLocaleString(); }
function fmtDate(s) { return new Date(s).toLocaleDateString('en-UG',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}); }

function scoreClass(s) {
  s = parseFloat(s);
  if (s >= 80) return 'critical';
  if (s >= 60) return 'high';
  if (s >= 35) return 'medium';
  return 'low';
}

// ── Load Dashboard Stats ──────────────────────────────────
async function loadDashboard() {
  const res  = await fetch('php/analytics.php?action=dashboard_stats');
  const json = await res.json();
  if (!json.success) return;
  const d = json.data;

  document.getElementById('acc-no').textContent        = d.account_no  || '—';
  document.getElementById('stat-balance').textContent  = fmtUGX(d.balance);
  document.getElementById('stat-txn').textContent      = d.txn_count   || 0;
  document.getElementById('stat-cases').textContent    = d.open_cases  || 0;
  document.getElementById('stat-notif').textContent    = d.unread_notif || 0;

  if (d.unread_notif > 0) {
    document.getElementById('notif-dot').style.display = 'block';
    document.getElementById('notif-count').style.display = 'inline';
    document.getElementById('notif-count').textContent = d.unread_notif;
  }

  // Recent transactions
  const tbody = document.getElementById('recent-txn-body');
  if (!d.recent_transactions || d.recent_transactions.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:30px">No transactions yet.</td></tr>';
    return;
  }
  tbody.innerHTML = d.recent_transactions.map(t => `
    <tr>
      <td><span style="font-family:monospace;color:var(--accent)">${t.txn_reference}</span></td>
      <td style="text-transform:capitalize">${t.txn_type}</td>
      <td style="font-weight:600">${fmtUGX(t.amount)}</td>
      <td><span class="badge badge-${t.status}">${t.status}</span></td>
      <td>
        <div style="display:flex; align-items:center; gap:8px">
          <span>${t.fraud_score}</span>
          <div class="score-bar" style="width:60px"><div class="score-fill score-${scoreClass(t.fraud_score)}" style="width:${t.fraud_score}%"></div></div>
        </div>
      </td>
      <td style="color:var(--muted); font-size:13px">${fmtDate(t.created_at)}</td>
    </tr>
  `).join('');
}

// ── Submit Transfer ───────────────────────────────────────
async function submitTransfer() {
  const btn = document.getElementById('transfer-btn');
  btn.innerHTML = '<div class="spinner"></div> Processing...';
  btn.disabled = true;

  const body = new FormData();
  body.append('action', 'transfer');
  body.append('receiver_acc',       document.getElementById('receiver-acc').value);
  body.append('amount',             document.getElementById('transfer-amount').value);
  body.append('channel',            document.getElementById('transfer-channel').value);
  body.append('location',           document.getElementById('transfer-location').value || 'Kampala, UG');

  const res  = await fetch('php/transaction.php', { method:'POST', body });
  const json = await res.json();

  const alertEl = document.getElementById('transfer-alert');
  if (json.success) {
    alertEl.innerHTML = `<div class="alert alert-success">✅ ${json.message}</div>`;
    setTimeout(() => { closeModal('transferModal'); loadDashboard(); }, 2000);
  } else {
    alertEl.innerHTML = `<div class="alert alert-error">⚠️ ${json.message}</div>`;
  }

  btn.innerHTML = 'Send Money';
  btn.disabled = false;
}

// ── Submit Refund Case ────────────────────────────────────
async function submitRefundCase() {
  const body = new FormData();
  body.append('action',         'submit_case');
  body.append('txn_reference',  document.getElementById('case-txn-ref').value);
  body.append('fraud_type',     document.getElementById('case-fraud-type').value);
  body.append('amount_claimed', document.getElementById('case-amount').value);
  body.append('description',    document.getElementById('case-desc').value);

  const res  = await fetch('php/refund.php', { method:'POST', body });
  const json = await res.json();

  const alertEl = document.getElementById('refund-alert');
  if (json.success) {
    alertEl.innerHTML = `<div class="alert alert-success">✅ ${json.message}</div>`;
    setTimeout(() => closeModal('refundModal'), 2500);
  } else {
    alertEl.innerHTML = `<div class="alert alert-error">⚠️ ${json.message}</div>`;
  }
}

loadDashboard();
</script>
</body>
</html>
