<?php
require_once '../includes/auth.php';
requireLogin('../index.php');
if ($_SESSION['role'] === 'admin') { header('Location: ../admin/dashboard.php'); exit(); }
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - AI Fraud Detector</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
  --primary: #00E5A0; --primary-dark: #00B87C;
  --danger: #FF3B5C; --warning: #FFB800; --info: #3B82F6;
  --dark: #080C14; --dark2: #0E1520; --dark3: #151E2D; --dark4: #1C2738;
  --border: rgba(0,229,160,0.12); --text: #E0EAF5; --text-muted: #6B8299;
  --font-head: 'Syne', sans-serif; --font-body: 'DM Sans', sans-serif;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { background:var(--dark); color:var(--text); font-family:var(--font-body); display:flex; min-height:100vh; }
body::before {
  content:''; position:fixed; inset:0;
  background-image: linear-gradient(rgba(0,229,160,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0,229,160,0.03) 1px, transparent 1px);
  background-size:40px 40px; pointer-events:none; z-index:0;
}

/* Sidebar */
.sidebar {
  width:260px; background:var(--dark2); border-right:1px solid rgba(255,255,255,0.06);
  display:flex; flex-direction:column; position:fixed; top:0; left:0; bottom:0; z-index:100;
  transition: transform 0.3s;
}
.sidebar-logo { padding:28px 24px; border-bottom:1px solid rgba(255,255,255,0.06); display:flex; align-items:center; gap:12px; }
.logo-icon { width:40px; height:40px; background:var(--primary); border-radius:10px; display:grid; place-items:center; color:var(--dark); font-size:18px; font-weight:900; }
.logo-text { font-family:var(--font-head); font-size:15px; font-weight:700; }
.logo-text span { color:var(--primary); }

.nav-section { padding:20px 16px 8px; font-size:11px; color:var(--text-muted); font-weight:600; letter-spacing:1px; text-transform:uppercase; }
.nav-item { display:flex; align-items:center; gap:12px; padding:12px 16px; border-radius:10px; cursor:pointer; color:var(--text-muted); transition:all 0.2s; margin:2px 8px; font-size:14px; text-decoration:none; }
.nav-item:hover { background:rgba(255,255,255,0.04); color:var(--text); }
.nav-item.active { background:rgba(0,229,160,0.1); color:var(--primary); }
.nav-item i { width:20px; text-align:center; }
.nav-badge { margin-left:auto; background:var(--danger); color:white; font-size:10px; padding:2px 7px; border-radius:50px; font-weight:700; }

.sidebar-footer { margin-top:auto; padding:16px; border-top:1px solid rgba(255,255,255,0.06); }
.user-card { display:flex; align-items:center; gap:12px; padding:12px; background:var(--dark3); border-radius:12px; }
.user-avatar { width:38px; height:38px; background:linear-gradient(135deg,var(--primary),var(--primary-dark)); border-radius:10px; display:grid; place-items:center; color:var(--dark); font-weight:800; font-size:16px; flex-shrink:0; }
.user-name { font-size:13px; font-weight:600; }
.user-role { font-size:11px; color:var(--primary); }
.logout-btn { margin-top:10px; width:100%; padding:10px; background:rgba(255,59,92,0.1); border:1px solid rgba(255,59,92,0.2); border-radius:10px; color:#FF3B5C; font-family:var(--font-head); font-size:13px; cursor:pointer; transition:all 0.2s; }
.logout-btn:hover { background:rgba(255,59,92,0.2); }

/* Main Content */
.main { margin-left:260px; flex:1; padding:32px; position:relative; z-index:1; }
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:32px; }
.page-title { font-family:var(--font-head); font-size:26px; font-weight:800; }
.page-title span { color:var(--primary); }
.btn-new-txn { display:flex; align-items:center; gap:8px; padding:12px 20px; background:var(--primary); border:none; border-radius:10px; color:var(--dark); font-family:var(--font-head); font-size:14px; font-weight:700; cursor:pointer; transition:all 0.2s; box-shadow:0 4px 15px rgba(0,229,160,0.3); }
.btn-new-txn:hover { background:var(--primary-dark); transform:translateY(-1px); }

/* Stats Grid */
.stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin-bottom:32px; }
.stat-card { background:var(--dark2); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:24px; position:relative; overflow:hidden; transition:transform 0.2s; }
.stat-card:hover { transform:translateY(-2px); }
.stat-card::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; }
.stat-card.green::before { background:var(--primary); }
.stat-card.red::before { background:var(--danger); }
.stat-card.yellow::before { background:var(--warning); }
.stat-card.blue::before { background:var(--info); }
.stat-icon { width:44px; height:44px; border-radius:12px; display:grid; place-items:center; font-size:18px; margin-bottom:16px; }
.stat-card.green .stat-icon { background:rgba(0,229,160,0.1); color:var(--primary); }
.stat-card.red .stat-icon { background:rgba(255,59,92,0.1); color:var(--danger); }
.stat-card.yellow .stat-icon { background:rgba(255,184,0,0.1); color:var(--warning); }
.stat-card.blue .stat-icon { background:rgba(59,130,246,0.1); color:var(--info); }
.stat-value { font-family:var(--font-head); font-size:30px; font-weight:800; margin-bottom:4px; }
.stat-label { font-size:13px; color:var(--text-muted); }

/* Panels */
.grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px; }
.panel { background:var(--dark2); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:24px; }
.panel-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; }
.panel-title { font-family:var(--font-head); font-size:16px; font-weight:700; display:flex; align-items:center; gap:10px; }
.panel-title i { color:var(--primary); }

/* Transaction form */
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.fg { margin-bottom:14px; }
.fg label { font-size:12px; color:var(--text-muted); font-weight:500; display:block; margin-bottom:6px; }
.fg input, .fg select {
  width:100%; padding:11px 14px; background:var(--dark3);
  border:1px solid rgba(255,255,255,0.08); border-radius:10px;
  color:var(--text); font-family:var(--font-body); font-size:13px; outline:none; transition:all 0.2s;
}
.fg input:focus, .fg select:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,229,160,0.1); }

.analyze-btn { width:100%; padding:12px; background:var(--dark4); border:1px solid var(--border); border-radius:10px; color:var(--primary); font-family:var(--font-head); font-weight:700; font-size:14px; cursor:pointer; transition:all 0.2s; margin-bottom:10px; }
.analyze-btn:hover { background:rgba(0,229,160,0.08); }
.submit-btn { width:100%; padding:13px; background:var(--primary); border:none; border-radius:10px; color:var(--dark); font-family:var(--font-head); font-weight:700; font-size:14px; cursor:pointer; transition:all 0.2s; box-shadow:0 4px 15px rgba(0,229,160,0.3); }
.submit-btn:hover { background:var(--primary-dark); }
.submit-btn:disabled { opacity:0.5; cursor:not-allowed; }

/* Risk indicator */
.risk-meter { margin:16px 0; }
.risk-label { display:flex; justify-content:space-between; font-size:12px; margin-bottom:6px; }
.risk-bar { height:8px; background:var(--dark4); border-radius:50px; overflow:hidden; }
.risk-fill { height:100%; border-radius:50px; transition:width 1s ease, background 0.5s; }

.verdict-box { padding:14px 16px; border-radius:12px; margin:12px 0; font-size:13px; line-height:1.6; }
.verdict-safe { background:rgba(0,229,160,0.08); border:1px solid rgba(0,229,160,0.2); color:var(--primary); }
.verdict-suspicious { background:rgba(255,184,0,0.08); border:1px solid rgba(255,184,0,0.2); color:var(--warning); }
.verdict-fraud { background:rgba(255,59,92,0.08); border:1px solid rgba(255,59,92,0.2); color:var(--danger); }

.decision-btns { display:flex; gap:10px; margin-top:10px; }
.btn-proceed { flex:1; padding:11px; background:var(--primary); border:none; border-radius:10px; color:var(--dark); font-family:var(--font-head); font-weight:700; font-size:13px; cursor:pointer; }
.btn-cancel { flex:1; padding:11px; background:rgba(255,59,92,0.1); border:1px solid rgba(255,59,92,0.2); border-radius:10px; color:var(--danger); font-family:var(--font-head); font-weight:700; font-size:13px; cursor:pointer; }

/* Transactions list */
.txn-item { display:flex; align-items:center; gap:14px; padding:14px 0; border-bottom:1px solid rgba(255,255,255,0.04); }
.txn-item:last-child { border-bottom:none; }
.txn-icon { width:40px; height:40px; border-radius:12px; display:grid; place-items:center; flex-shrink:0; }
.txn-icon.send { background:rgba(59,130,246,0.1); color:var(--info); }
.txn-icon.safe { background:rgba(0,229,160,0.1); color:var(--primary); }
.txn-icon.fraud { background:rgba(255,59,92,0.1); color:var(--danger); }
.txn-icon.suspicious { background:rgba(255,184,0,0.1); color:var(--warning); }
.txn-details { flex:1; }
.txn-ref { font-size:13px; font-weight:600; }
.txn-meta { font-size:12px; color:var(--text-muted); margin-top:2px; }
.txn-amount { font-family:var(--font-head); font-weight:700; font-size:15px; text-align:right; }
.txn-amount.blocked { color:var(--danger); }
.txn-amount.safe { color:var(--primary); }
.status-badge { font-size:10px; padding:3px 8px; border-radius:50px; font-weight:700; display:inline-block; margin-top:3px; }
.badge-safe, .badge-approved, .badge-completed { background:rgba(0,229,160,0.1); color:var(--primary); }
.badge-suspicious, .badge-flagged { background:rgba(255,184,0,0.1); color:var(--warning); }
.badge-fraud, .badge-blocked { background:rgba(255,59,92,0.1); color:var(--danger); }
.badge-pending { background:rgba(107,130,153,0.2); color:var(--text-muted); }

/* Alert items */
.alert-item { display:flex; gap:12px; padding:12px; border-radius:10px; margin-bottom:10px; }
.alert-item.critical { background:rgba(255,59,92,0.07); border:1px solid rgba(255,59,92,0.15); }
.alert-item.high { background:rgba(255,184,0,0.07); border:1px solid rgba(255,184,0,0.15); }
.alert-item.medium { background:rgba(59,130,246,0.07); border:1px solid rgba(59,130,246,0.15); }
.alert-item.low { background:rgba(107,130,153,0.07); border:1px solid rgba(107,130,153,0.15); }
.alert-icon { font-size:18px; }
.alert-item.critical .alert-icon { color:var(--danger); }
.alert-item.high .alert-icon { color:var(--warning); }
.alert-item.medium .alert-icon { color:var(--info); }
.alert-content { flex:1; }
.alert-title { font-size:13px; font-weight:600; margin-bottom:2px; }
.alert-desc { font-size:12px; color:var(--text-muted); }
.alert-time { font-size:11px; color:var(--text-muted); margin-top:4px; }

.empty-state { text-align:center; padding:30px; color:var(--text-muted); }
.empty-state i { font-size:32px; display:block; margin-bottom:12px; opacity:0.3; }

.spinner-sm { display:inline-block; width:14px; height:14px; border:2px solid rgba(255,255,255,0.2); border-top-color:currentColor; border-radius:50%; animation:spin 0.8s linear infinite; vertical-align:middle; }
@keyframes spin { to{transform:rotate(360deg)} }

.toast { position:fixed; bottom:24px; right:24px; background:var(--dark2); border:1px solid var(--border); padding:14px 20px; border-radius:12px; font-size:13px; z-index:9999; transform:translateY(80px); opacity:0; transition:all 0.4s; max-width:320px; }
.toast.show { transform:translateY(0); opacity:1; }
.toast.success { border-color:rgba(0,229,160,0.3); color:var(--primary); }
.toast.error { border-color:rgba(255,59,92,0.3); color:var(--danger); }

@media(max-width:1200px) { .stats-grid { grid-template-columns:repeat(2,1fr); } }
@media(max-width:900px) { .grid-2 { grid-template-columns:1fr; } .form-row { grid-template-columns:1fr; } }
</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">🛡</div>
    <div class="logo-text">AI<span>Fraud</span>Det.</div>
  </div>

  <div class="nav-section">Main Menu</div>
  <a class="nav-item active" onclick="showPage('dashboard')"><i class="fas fa-home"></i> Dashboard</a>
  <a class="nav-item" onclick="showPage('transactions')"><i class="fas fa-exchange-alt"></i> Transactions</a>
  <a class="nav-item" onclick="showPage('alerts')"><i class="fas fa-bell"></i> Alerts <span class="nav-badge" id="alertCount">0</span></a>
  <a class="nav-item" onclick="showPage('profile')"><i class="fas fa-user"></i> My Profile</a>

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
        <div class="user-role">Protected User</div>
      </div>
    </div>
    <button class="logout-btn" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Sign Out</button>
  </div>
</aside>

<!-- Main Content -->
<main class="main">

  <!-- Dashboard Page -->
  <div id="page-dashboard">
    <div class="page-header">
      <div>
        <div class="page-title">Good <?= date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening') ?>, <span><?= htmlspecialchars(explode(' ',$user['full_name'])[0]) ?>!</span></div>
        <div style="font-size:13px;color:var(--text-muted);margin-top:4px">Your mobile money is protected 24/7 by AI</div>
      </div>
      <button class="btn-new-txn" onclick="showPage('send')"><i class="fas fa-paper-plane"></i> Send Money</button>
    </div>

    <div class="stats-grid" id="statsGrid">
      <div class="stat-card green"><div class="stat-icon"><i class="fas fa-exchange-alt"></i></div><div class="stat-value" id="s-total">—</div><div class="stat-label">Total Transactions</div></div>
      <div class="stat-card red"><div class="stat-icon"><i class="fas fa-ban"></i></div><div class="stat-value" id="s-fraud">—</div><div class="stat-label">Fraud Blocked</div></div>
      <div class="stat-card yellow"><div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-value" id="s-susp">—</div><div class="stat-label">Suspicious Flagged</div></div>
      <div class="stat-card blue"><div class="stat-icon"><i class="fas fa-bell"></i></div><div class="stat-value" id="s-alerts">—</div><div class="stat-label">Open Alerts</div></div>
    </div>

    <div class="grid-2">
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title"><i class="fas fa-history"></i> Recent Transactions</div>
          <a onclick="showPage('transactions')" style="font-size:12px;color:var(--primary);cursor:pointer">View All</a>
        </div>
        <div id="recentTxns"><div class="empty-state"><i class="fas fa-inbox"></i>Loading...</div></div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <div class="panel-title"><i class="fas fa-shield-alt"></i> Active Alerts</div>
        </div>
        <div id="recentAlerts"><div class="empty-state"><i class="fas fa-check-circle"></i>Loading...</div></div>
      </div>
    </div>
  </div>

  <!-- Send Money Page -->
  <div id="page-send" style="display:none">
    <div class="page-header">
      <div class="page-title"><i class="fas fa-paper-plane" style="color:var(--primary);font-size:22px;margin-right:10px"></i>Send <span>Money</span></div>
    </div>
    <div style="max-width:560px">
      <div class="panel">
        <div class="panel-title" style="margin-bottom:20px"><i class="fas fa-robot"></i> AI-Protected Transaction</div>

        <div class="form-row">
          <div class="fg">
            <label>Recipient Phone *</label>
            <input type="text" id="txnReceiver" placeholder="+256700000000">
          </div>
          <div class="fg">
            <label>Amount (UGX) *</label>
            <input type="number" id="txnAmount" placeholder="e.g. 50000">
          </div>
        </div>

        <div class="fg">
          <label>Caller's Phone (if someone called you)</label>
          <input type="text" id="txnCaller" placeholder="Enter if prompted by a caller (+256...)">
        </div>

        <div class="fg">
          <label>Transaction Type</label>
          <select id="txnType">
            <option value="send">Send Money</option>
            <option value="withdraw">Withdraw</option>
            <option value="deposit">Deposit</option>
          </select>
        </div>

        <button class="analyze-btn" onclick="analyzeTransaction()">
          <i class="fas fa-brain"></i> Analyze with AI First
        </button>

        <div id="analysisResult" style="display:none">
          <div class="risk-meter">
            <div class="risk-label"><span>Risk Score</span><span id="riskPct">0%</span></div>
            <div class="risk-bar"><div class="risk-fill" id="riskBar" style="width:0%;background:var(--primary)"></div></div>
          </div>
          <div class="verdict-box" id="verdictBox"></div>
          <div id="decisionArea"></div>
        </div>

        <button class="submit-btn" onclick="submitTransaction()" id="submitBtn" style="display:none">
          <i class="fas fa-check"></i> Confirm & Send
        </button>
      </div>
    </div>
  </div>

  <!-- Transactions Page -->
  <div id="page-transactions" style="display:none">
    <div class="page-header">
      <div class="page-title">Transaction <span>History</span></div>
    </div>
    <div class="panel">
      <div id="allTransactions"><div class="empty-state"><i class="fas fa-spinner fa-spin"></i> Loading...</div></div>
    </div>
  </div>

  <!-- Alerts Page -->
  <div id="page-alerts" style="display:none">
    <div class="page-header">
      <div class="page-title">Security <span>Alerts</span></div>
    </div>
    <div class="panel">
      <div id="allAlerts"><div class="empty-state"><i class="fas fa-spinner fa-spin"></i> Loading...</div></div>
    </div>
  </div>

  <!-- Profile Page -->
  <div id="page-profile" style="display:none">
    <div class="page-header">
      <div class="page-title">My <span>Profile</span></div>
    </div>
    <div class="panel" style="max-width:500px">
      <div style="text-align:center;margin-bottom:28px">
        <div style="width:72px;height:72px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-radius:20px;display:grid;place-items:center;color:var(--dark);font-weight:800;font-size:28px;margin:0 auto 14px">
          <?= strtoupper(substr($user['full_name'],0,1)) ?>
        </div>
        <div style="font-family:var(--font-head);font-size:20px;font-weight:700"><?= htmlspecialchars($user['full_name']) ?></div>
        <div style="font-size:13px;color:var(--primary);margin-top:4px">Protected User</div>
      </div>
      <div style="display:grid;gap:12px">
        <?php foreach([['fas fa-envelope','Email',$user['email']], ['fas fa-phone','Phone',$user['phone']], ['fas fa-calendar','Member Since',date('F Y',strtotime($user['created_at']))]] as $row): ?>
        <div style="display:flex;align-items:center;gap:14px;padding:14px;background:var(--dark3);border-radius:12px">
          <i class="<?=$row[0]?>" style="color:var(--primary);width:20px"></i>
          <div><div style="font-size:11px;color:var(--text-muted)"><?=$row[1]?></div><div style="font-size:14px;font-weight:600"><?=htmlspecialchars($row[2])?></div></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</main>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
let currentAnalysis = null;
let currentPage = 'dashboard';

function showPage(page) {
  document.querySelectorAll('[id^="page-"]').forEach(p => p.style.display='none');
  const el = document.getElementById('page-'+page);
  if (el) el.style.display='block';
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  
  currentPage = page;
  if (page === 'dashboard') { loadStats(); loadRecentTxns(); loadAlerts(); }
  if (page === 'transactions') loadAllTransactions();
  if (page === 'alerts') loadAllAlerts();
}

function showToast(msg, type='success') {
  const t = document.getElementById('toast');
  t.textContent = msg; t.className = 'toast show ' + type;
  setTimeout(() => t.classList.remove('show'), 3500);
}

async function loadStats() {
  try {
    const r = await fetch('../api/transactions.php?action=stats');
    const d = await r.json();
    if (d.success) {
      const s = d.stats;
      document.getElementById('s-total').textContent = s.total?.total || 0;
      document.getElementById('s-fraud').textContent = s.fraud || 0;
      document.getElementById('s-susp').textContent = s.suspicious || 0;
      document.getElementById('s-alerts').textContent = s.open_alerts || 0;
      document.getElementById('alertCount').textContent = s.open_alerts || 0;
    }
  } catch(e) {}
}

async function loadRecentTxns() {
  try {
    const r = await fetch('../api/transactions.php?action=history&page=1');
    const d = await r.json();
    const el = document.getElementById('recentTxns');
    if (!d.success || !d.transactions.length) {
      el.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i>No transactions yet</div>'; return;
    }
    el.innerHTML = d.transactions.slice(0,5).map(txn => txnHTML(txn)).join('');
  } catch(e) {}
}

function txnHTML(t) {
  const cls = t.ai_verdict === 'fraud' ? 'fraud' : t.ai_verdict === 'suspicious' ? 'suspicious' : 'safe';
  const icon = t.ai_verdict === 'fraud' ? 'fa-ban' : t.ai_verdict === 'suspicious' ? 'fa-exclamation-triangle' : 'fa-check';
  const amt = parseFloat(t.amount).toLocaleString('en-UG');
  const date = new Date(t.created_at).toLocaleDateString('en-UG',{day:'numeric',month:'short',hour:'2-digit',minute:'2-digit'});
  return `<div class="txn-item">
    <div class="txn-icon ${cls}"><i class="fas ${icon}"></i></div>
    <div class="txn-details">
      <div class="txn-ref">${t.transaction_ref || 'N/A'}</div>
      <div class="txn-meta">To: ${t.receiver_phone} &bull; ${date}</div>
      <span class="status-badge badge-${t.status}">${t.status.toUpperCase()}</span>
    </div>
    <div class="txn-amount ${t.status==='blocked'?'blocked':'safe'}">UGX ${amt}</div>
  </div>`;
}

async function loadAlerts() {
  try {
    const r = await fetch('../api/transactions.php?action=alerts');
    const d = await r.json();
    const el = document.getElementById('recentAlerts');
    if (!d.success || !d.alerts.length) {
      el.innerHTML = '<div class="empty-state"><i class="fas fa-check-circle"></i>No active alerts 🎉</div>'; return;
    }
    el.innerHTML = d.alerts.slice(0,4).map(a => alertHTML(a)).join('');
  } catch(e) {}
}

function alertHTML(a) {
  const icons = {critical:'fa-radiation',high:'fa-exclamation-circle',medium:'fa-exclamation-triangle',low:'fa-info-circle'};
  const date = new Date(a.created_at).toLocaleDateString('en-UG',{day:'numeric',month:'short'});
  return `<div class="alert-item ${a.severity}">
    <i class="fas ${icons[a.severity]||'fa-bell'} alert-icon"></i>
    <div class="alert-content">
      <div class="alert-title">${a.alert_type.replace(/_/g,' ').toUpperCase()}</div>
      <div class="alert-desc">${a.description}</div>
      <div class="alert-time">${date} &bull; Ref: ${a.transaction_ref||'N/A'}</div>
    </div>
  </div>`;
}

async function loadAllTransactions() {
  const el = document.getElementById('allTransactions');
  el.innerHTML = '<div class="empty-state"><i class="fas fa-spinner fa-spin"></i></div>';
  try {
    const r = await fetch('../api/transactions.php?action=history&page=1');
    const d = await r.json();
    if (!d.success || !d.transactions.length) {
      el.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i>No transactions yet. Try sending money!</div>'; return;
    }
    el.innerHTML = d.transactions.map(t => txnHTML(t)).join('');
  } catch(e) { el.innerHTML = '<div class="empty-state">Error loading transactions</div>'; }
}

async function loadAllAlerts() {
  const el = document.getElementById('allAlerts');
  try {
    const r = await fetch('../api/transactions.php?action=alerts');
    const d = await r.json();
    if (!d.success || !d.alerts.length) {
      el.innerHTML = '<div class="empty-state"><i class="fas fa-check-circle"></i>No alerts! Your account is safe 🎉</div>'; return;
    }
    el.innerHTML = d.alerts.map(a => alertHTML(a)).join('');
  } catch(e) {}
}

async function analyzeTransaction() {
  const receiver = document.getElementById('txnReceiver').value.trim();
  const amount   = document.getElementById('txnAmount').value;
  const caller   = document.getElementById('txnCaller').value.trim();
  
  if (!receiver || !amount) { showToast('Please enter recipient and amount','error'); return; }
  
  document.getElementById('analysisResult').style.display='none';
  document.getElementById('submitBtn').style.display='none';
  
  try {
    const r = await fetch('../api/transactions.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({action:'analyze', receiver_phone:receiver, amount:parseFloat(amount), caller_phone:caller, type:document.getElementById('txnType').value})
    });
    const d = await r.json();
    if (!d.success) { showToast(d.message,'error'); return; }
    
    currentAnalysis = d.analysis;
    showAnalysis(d.analysis);
  } catch(e) { showToast('Analysis failed. Is XAMPP running?','error'); }
}

function showAnalysis(a) {
  const score = Math.round(a.risk_score);
  document.getElementById('riskPct').textContent = score + '%';
  const bar = document.getElementById('riskBar');
  bar.style.width = score + '%';
  bar.style.background = score >= 70 ? '#FF3B5C' : score >= 35 ? '#FFB800' : '#00E5A0';
  
  const cls = a.verdict === 'fraud' ? 'verdict-fraud' : a.verdict === 'suspicious' ? 'verdict-suspicious' : 'verdict-safe';
  document.getElementById('verdictBox').className = 'verdict-box ' + cls;
  document.getElementById('verdictBox').innerHTML = `<strong>${a.security_message}</strong>`;
  
  const decArea = document.getElementById('decisionArea');
  if (a.verdict === 'fraud') {
    decArea.innerHTML = `<div class="decision-btns"><button class="btn-cancel" onclick="cancelTransaction()"><i class="fas fa-times"></i> Transaction Blocked</button></div>`;
  } else if (a.verdict === 'suspicious') {
    decArea.innerHTML = `<div class="decision-btns">
      <button class="btn-cancel" onclick="cancelTransaction()"><i class="fas fa-times"></i> Cancel (Safer)</button>
      <button class="btn-proceed" onclick="proceedWithCaution()"><i class="fas fa-check"></i> Proceed Anyway</button>
    </div>`;
  } else {
    decArea.innerHTML = '';
    document.getElementById('submitBtn').style.display='block';
  }
  
  document.getElementById('analysisResult').style.display='block';
}

function cancelTransaction() {
  document.getElementById('txnReceiver').value='';
  document.getElementById('txnAmount').value='';
  document.getElementById('txnCaller').value='';
  document.getElementById('analysisResult').style.display='none';
  document.getElementById('submitBtn').style.display='none';
  showToast('Transaction cancelled. You are protected! ✅','success');
}

function proceedWithCaution() {
  document.getElementById('submitBtn').style.display='block';
  document.getElementById('submitBtn').textContent = '⚠️ Proceed Despite Risk';
  document.getElementById('submitBtn').style.background='#FFB800';
  showToast('Proceeding with flagged transaction. Stay safe!','error');
}

async function submitTransaction() {
  const receiver = document.getElementById('txnReceiver').value.trim();
  const amount   = document.getElementById('txnAmount').value;
  const caller   = document.getElementById('txnCaller').value.trim();
  
  document.getElementById('submitBtn').disabled = true;
  document.getElementById('submitBtn').innerHTML = '<span class="spinner-sm"></span> Processing...';
  
  try {
    const r = await fetch('../api/transactions.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({action:'submit', receiver_phone:receiver, amount:parseFloat(amount), caller_phone:caller, type:document.getElementById('txnType').value, decision:'proceed'})
    });
    const d = await r.json();
    if (d.success) {
      const a = d.analysis;
      if (a.verdict === 'fraud') {
        showToast('⛔ Transaction blocked! Fraud detected.','error');
      } else if (a.verdict === 'suspicious') {
        showToast('⚠️ Transaction submitted with flag. Stay alert!','error');
      } else {
        showToast('✅ Transaction successful! UGX ' + parseFloat(amount).toLocaleString(),'success');
      }
      cancelTransaction();
      loadStats();
      loadRecentTxns();
    } else {
      showToast(d.message,'error');
    }
  } catch(e) { showToast('Submission failed','error'); }
  
  document.getElementById('submitBtn').disabled = false;
  document.getElementById('submitBtn').innerHTML = '<i class="fas fa-check"></i> Confirm & Send';
}

function logout() {
  fetch('../api/auth.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'logout'})})
    .then(() => window.location.href = '../index.php');
}

// Init
showPage('dashboard');
</script>
</body>
</html>
