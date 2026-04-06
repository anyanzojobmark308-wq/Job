<?php
// ============================================================
//  FraudGuard – Admin Withdrawal Management
//  Save as: C:\xampp\htdocs\fraudguard\admin_withdrawals.php
// ============================================================
require_once 'php/config.php';
startSession();
requireLogin();
if (!isAdmin()) { header('Location: dashboard.php'); exit; }
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Withdrawal Requests – FraudGuard Admin</title>
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
      <div class="avatar" style="background:linear-gradient(135deg,#ff6b35,#ff4444)">
        <?= strtoupper(substr($user['full_name'],0,1)) ?>
      </div>
      <div class="uname"><?= htmlspecialchars($user['full_name']) ?></div>
      <div class="urole" style="color:var(--danger)"><?= ucfirst(str_replace('_',' ',$user['role'])) ?></div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section-title">Overview</div>
      <a href="admin_dashboard.php"  class="nav-link"><span class="icon">📊</span> Dashboard</a>
      <a href="transactions.php"     class="nav-link"><span class="icon">💳</span> Transactions</a>
      <a href="admin_alerts.php"     class="nav-link"><span class="icon">⚠️</span> Fraud Alerts</a>
      <div class="nav-section-title">Management</div>
      <a href="admin_cases.php"      class="nav-link"><span class="icon">📁</span> Refund Cases</a>
      <a href="admin_deposits.php"   class="nav-link"><span class="icon">💰</span> Deposit Requests</a>
      <a href="admin_withdrawals.php" class="nav-link active"><span class="icon">💵</span> Withdrawal Requests</a>
      <a href="admin_users.php"      class="nav-link"><span class="icon">👥</span> Users</a>
      <div class="nav-section-title">Account</div>
      <a href="notifications.php"    class="nav-link"><span class="icon">🔔</span> Notifications</a>
    </nav>
    <div class="sidebar-footer">
      <a href="php/auth.php?action=logout" class="nav-link"><span class="icon">🚪</span> Logout</a>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">💵 Withdrawal Requests</div>
      <div class="topbar-actions">
        <select class="form-select" id="filter-status" style="width:160px" onchange="loadWithdrawals()">
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <button class="btn btn-secondary btn-sm" onclick="loadWithdrawals()">🔄 Refresh</button>
      </div>
    </div>

    <div class="page-content">
      <div id="toast-container"></div>

      <!-- Summary Stats -->
      <div class="stats-grid" style="margin-bottom:24px">
        <div class="stat-card orange">
          <div class="stat-icon">⏳</div>
          <div class="stat-value" id="st-pending">—</div>
          <div class="stat-label">Pending Approval</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon">✅</div>
          <div class="stat-value" id="st-approved">—</div>
          <div class="stat-label">Approved Total</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-icon">💵</div>
          <div class="stat-value" id="st-amount">—</div>
          <div class="stat-label">Total Paid Out (UGX)</div>
        </div>
        <div class="stat-card red">
          <div class="stat-icon">❌</div>
          <div class="stat-value" id="st-rejected">—</div>
          <div class="stat-label">Rejected</div>
        </div>
      </div>

      <!-- Withdrawals Table -->
      <div class="data-card">
        <div class="data-card-header">
          <div class="data-card-title">📋 All Withdrawal Requests</div>
          <span id="wd-count" style="font-size:13px;color:var(--muted)"></span>
        </div>
        <div style="overflow-x:auto">
          <table class="data-table">
            <thead>
              <tr>
                <th>Reference</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Send To</th>
                <th>Account Name</th>
                <th>Status</th>
                <th>Requested</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="wd-body">
              <tr>
                <td colspan="9" style="text-align:center;color:var(--muted);padding:40px">
                  Loading...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- PROCESS MODAL -->
<div class="modal-overlay" id="wdModal">
  <div class="modal" style="max-width:540px">
    <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:20px">
      <div>
        <div class="modal-title" id="modal-wd-ref">Process Withdrawal</div>
        <div class="modal-sub" id="modal-wd-sub"></div>
      </div>
      <button onclick="closeModal('wdModal')"
        style="background:none;border:none;color:var(--muted);font-size:24px;cursor:pointer">×</button>
    </div>

    <!-- Customer & payment info panel -->
    <div style="background:var(--surface2);border-radius:12px;padding:18px;margin-bottom:20px">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:14px">
        <div>
          <span style="color:var(--muted);font-size:12px">Customer</span><br>
          <strong id="md-name"></strong>
        </div>
        <div>
          <span style="color:var(--muted);font-size:12px">Phone</span><br>
          <span id="md-phone"></span>
        </div>
        <div>
          <span style="color:var(--muted);font-size:12px">Withdrawal Amount</span><br>
          <strong id="md-amount" style="color:var(--warning);font-size:18px"></strong>
        </div>
        <div>
          <span style="color:var(--muted);font-size:12px">After 1% Fee</span><br>
          <strong id="md-payout" style="color:var(--success)"></strong>
        </div>
        <div>
          <span style="color:var(--muted);font-size:12px">Method</span><br>
          <span id="md-method" style="font-weight:600"></span>
        </div>
        <div>
          <span style="color:var(--muted);font-size:12px">Account / Phone</span><br>
          <span id="md-accnum" style="font-family:monospace;color:var(--accent)"></span>
        </div>
        <div style="grid-column:1/-1">
          <span style="color:var(--muted);font-size:12px">Account Holder Name</span><br>
          <span id="md-accname"></span>
        </div>
      </div>

      <!-- Action reminder -->
      <div style="margin-top:14px;padding:10px 12px;background:rgba(255,179,0,0.08);border-radius:8px;font-size:12px;color:var(--warning)">
        ⚠️ Before approving: verify the payment was sent to the customer's account, then click Approve.
      </div>
    </div>

    <div id="modal-alert"></div>

    <div class="form-group">
      <label class="form-label">Admin Notes (sent to customer)</label>
      <textarea class="form-textarea" id="modal-notes"
        placeholder="e.g. Payment sent via MTN. Transaction ID: MTN2026XXXXXX"
        style="min-height:80px"></textarea>
    </div>

    <input type="hidden" id="modal-wd-id">

    <div style="display:flex;gap:12px;margin-top:8px">
      <button class="btn btn-secondary" onclick="closeModal('wdModal')" style="flex:1">
        Cancel
      </button>
      <button class="btn btn-danger" onclick="processWithdrawal('reject')" style="flex:1">
        ❌ Reject & Refund
      </button>
      <button class="btn btn-primary" onclick="processWithdrawal('approve')" style="flex:2">
        ✅ Approve — Send Money
      </button>
    </div>
  </div>
</div>

<script>
let allWDs = [];
const FEE_RATE = 0.01;

function fmtUGX(n) { return 'UGX ' + Number(n).toLocaleString(); }
function fmtDate(s) {
  return new Date(s).toLocaleString('en-UG',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
}
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function toast(msg, type='success') {
  const t = document.createElement('div');
  t.className = `toast toast-${type}`;
  t.textContent = msg;
  document.getElementById('toast-container').appendChild(t);
  setTimeout(() => t.remove(), 4000);
}

// ── Load withdrawals ──────────────────────────────────────
async function loadWithdrawals() {
  const res  = await fetch('php/withdraw.php?action=admin_get_withdrawals');
  const json = await res.json();
  if (!json.success) return;

  allWDs = json.data;
  const filter = document.getElementById('filter-status').value;
  const wds    = filter ? allWDs.filter(w => w.status === filter) : allWDs;

  // Stats
  const pending  = allWDs.filter(w => w.status === 'pending').length;
  const approved = allWDs.filter(w => w.status === 'approved').length;
  const rejected = allWDs.filter(w => w.status === 'rejected').length;
  const totalAmt = allWDs
    .filter(w => w.status === 'approved')
    .reduce((s,w) => s + parseFloat(w.amount||0), 0);

  document.getElementById('st-pending').textContent  = pending;
  document.getElementById('st-approved').textContent = approved;
  document.getElementById('st-rejected').textContent = rejected;
  document.getElementById('st-amount').textContent   = fmtUGX(totalAmt);
  document.getElementById('wd-count').textContent    = wds.length + ' request(s)';

  const tbody = document.getElementById('wd-body');
  if (!wds.length) {
    tbody.innerHTML = `
      <tr>
        <td colspan="9" style="text-align:center;padding:50px">
          <div class="empty-state">
            <div class="icon">💵</div>
            <p>No withdrawal requests found.</p>
          </div>
        </td>
      </tr>`;
    return;
  }

  const methodIcon = m =>
    m.includes('MTN')    ? '📱' :
    m.includes('Airtel') ? '📱' :
    m.includes('Bank')   ? '🏦' : '🏪';

  tbody.innerHTML = wds.map(w => {
    const sc = w.status === 'approved'  ? 'completed'
             : w.status === 'rejected'  ? 'blocked'
             : w.status === 'cancelled' ? 'pending'
             : 'flagged';
    const si = w.status === 'approved'  ? '✅'
             : w.status === 'rejected'  ? '❌'
             : w.status === 'cancelled' ? '🚫' : '⏳';
    const fee    = Math.round(parseFloat(w.amount) * FEE_RATE);
    const payout = parseFloat(w.amount) - fee;

    return `
      <tr>
        <td><span style="font-family:monospace;color:var(--accent);font-size:12px">${w.withdrawal_reference}</span></td>
        <td>
          <strong>${w.full_name}</strong><br>
          <span style="font-size:12px;color:var(--muted)">${w.phone}</span>
        </td>
        <td>
          <div style="font-weight:700;color:var(--warning)">${fmtUGX(w.amount)}</div>
          <div style="font-size:11px;color:var(--success)">Payout: ${fmtUGX(payout)}</div>
        </td>
        <td style="font-size:13px">${methodIcon(w.method)} ${w.method}</td>
        <td style="font-family:monospace;color:var(--accent);font-size:13px">${w.account_number}</td>
        <td style="font-size:13px">${w.account_name}</td>
        <td><span class="badge badge-${sc}">${si} ${w.status.charAt(0).toUpperCase()+w.status.slice(1)}</span></td>
        <td style="font-size:12px;color:var(--muted)">${fmtDate(w.created_at)}</td>
        <td>
          ${w.status === 'pending'
            ? `<button class="btn btn-secondary btn-sm" onclick="openWithdrawal(${w.withdrawal_id})">Review →</button>`
            : `<span style="font-size:11px;color:var(--muted)">${w.admin_notes ? w.admin_notes.substring(0,25)+'...' : 'Processed'}</span>`
          }
        </td>
      </tr>`;
  }).join('');
}

// ── Open review modal ─────────────────────────────────────
function openWithdrawal(id) {
  const w = allWDs.find(x => x.withdrawal_id == id);
  if (!w) return;

  const fee    = Math.round(parseFloat(w.amount) * FEE_RATE);
  const payout = parseFloat(w.amount) - fee;

  document.getElementById('modal-wd-id').value       = w.withdrawal_id;
  document.getElementById('modal-wd-ref').textContent = '💵 ' + w.withdrawal_reference;
  document.getElementById('modal-wd-sub').textContent = w.method + ' — ' + fmtUGX(w.amount);
  document.getElementById('md-name').textContent      = w.full_name;
  document.getElementById('md-phone').textContent     = w.phone;
  document.getElementById('md-amount').textContent    = fmtUGX(w.amount);
  document.getElementById('md-payout').textContent    = fmtUGX(payout);
  document.getElementById('md-method').textContent    = w.method;
  document.getElementById('md-accnum').textContent    = w.account_number;
  document.getElementById('md-accname').textContent   = w.account_name;
  document.getElementById('modal-notes').value        = '';
  document.getElementById('modal-alert').innerHTML    = '';

  openModal('wdModal');
}

// ── Process withdrawal ────────────────────────────────────
async function processWithdrawal(action) {
  const wdId  = document.getElementById('modal-wd-id').value;
  const notes = document.getElementById('modal-notes').value.trim();
  const alertEl = document.getElementById('modal-alert');

  if (action === 'reject' && !notes) {
    alertEl.innerHTML = '<div class="alert alert-error">⚠️ Please enter a reason for rejection.</div>';
    return;
  }

  const body = new FormData();
  body.append('action',         action === 'approve' ? 'approve_withdrawal' : 'reject_withdrawal');
  body.append('withdrawal_id',  wdId);
  body.append('admin_notes',    notes);

  const res  = await fetch('php/withdraw.php', { method:'POST', body });
  const json = await res.json();

  if (json.success) {
    alertEl.innerHTML = `<div class="alert alert-success">✅ ${json.message}</div>`;
    setTimeout(() => { closeModal('wdModal'); loadWithdrawals(); }, 1800);
  } else {
    alertEl.innerHTML = `<div class="alert alert-error">⚠️ ${json.message}</div>`;
  }
}

// ── Init ──────────────────────────────────────────────────
loadWithdrawals();
setInterval(loadWithdrawals, 30000); // auto-refresh every 30s
</script>
</body>
</html>
