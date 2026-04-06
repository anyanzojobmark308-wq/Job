<?php
// ============================================================
//  FraudGuard – Customer Withdrawal Page
//  Save as: C:\xampp\htdocs\fraudguard\withdraw.php
// ============================================================
require_once 'php/config.php';
startSession();
requireLogin();
$user = currentUser();
if (isAdmin()) { header('Location: admin_withdrawals.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Withdraw Money – FraudGuard</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<style>
.method-card {
  padding: 16px 18px;
  background: var(--surface2);
  border: 1.5px solid var(--border);
  border-radius: 14px;
  margin-bottom: 12px;
  cursor: pointer;
  transition: all .2s;
  display: flex;
  align-items: center;
  gap: 16px;
}
.method-card:hover  { border-color: var(--accent); background: rgba(0,212,255,0.04); }
.method-card.active { border-color: var(--accent); background: rgba(0,212,255,0.08); }
.method-icon { font-size: 28px; width: 40px; text-align: center; flex-shrink: 0; }
.method-title  { font-size: 15px; font-weight: 600; }
.method-sub    { font-size: 12px; color: var(--muted); margin-top: 3px; }
.method-check  {
  margin-left: auto;
  width: 20px; height: 20px;
  border-radius: 50%;
  border: 2px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  transition: all .2s;
}
.method-card.active .method-check {
  background: var(--accent);
  border-color: var(--accent);
}
.method-card.active .method-check::after {
  content: '✓';
  font-size: 11px;
  color: #000;
  font-weight: 700;
}

.charges-box {
  background: rgba(255,179,0,0.07);
  border: 1px solid rgba(255,179,0,0.2);
  border-radius: 10px;
  padding: 14px 16px;
  font-size: 13px;
  margin: 14px 0;
  display: none;
}
.charges-box.show { display: block; }
.charges-row {
  display: flex;
  justify-content: space-between;
  padding: 4px 0;
  border-bottom: 1px dashed rgba(255,255,255,0.06);
}
.charges-row:last-child { border-bottom: none; font-weight: 700; }
.charges-row span:first-child { color: var(--muted); }
.charges-row span:last-child  { color: var(--text); }
.charges-row.total span { color: var(--warning); }

.balance-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(0,230,118,0.1);
  border: 1px solid rgba(0,230,118,0.25);
  color: var(--success);
  border-radius: 20px;
  padding: 6px 14px;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 20px;
}

.history-item {
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
  transition: background .15s;
}
.history-item:hover { background: rgba(255,255,255,0.02); }
.history-item:last-child { border-bottom: none; }

.quick-btn {
  padding: 7px 14px;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--text);
  font-family: 'Space Grotesk', sans-serif;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all .2s;
}
.quick-btn:hover { border-color: var(--accent); color: var(--accent); }
</style>
</head>
<body>
<div class="app-layout">

  <!-- ── SIDEBAR ──────────────────────────────────────────── -->
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
      <a href="dashboard.php"    class="nav-link"><span class="icon">📊</span> Dashboard</a>
      <a href="deposit.php"      class="nav-link"><span class="icon">💰</span> Deposit Money</a>
      <a href="withdraw.php"     class="nav-link active"><span class="icon">💵</span> Withdraw Money</a>
      <a href="transactions.php" class="nav-link"><span class="icon">📋</span> Transactions</a>
      <div class="nav-section-title">Support</div>
      <a href="refunds.php"       class="nav-link"><span class="icon">🔄</span> Refund Cases</a>
      <a href="notifications.php" class="nav-link"><span class="icon">🔔</span> Notifications</a>
    </nav>
    <div class="sidebar-footer">
      <a href="php/auth.php?action=logout" class="nav-link"><span class="icon">🚪</span> Logout</a>
    </div>
  </aside>

  <!-- ── MAIN ─────────────────────────────────────────────── -->
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">💵 Withdraw Money</div>
      <div class="topbar-actions">
        <div style="font-size:14px; color:var(--muted)">
          Available: <strong style="color:var(--success)" id="topbar-balance">Loading...</strong>
        </div>
      </div>
    </div>

    <div class="page-content">
      <div id="toast-container"></div>

      <div class="grid-2" style="align-items:start; gap:24px">

        <!-- ── LEFT: Withdrawal Form ────────────────────────── -->
        <div>

          <!-- Balance + Quick Amounts -->
          <div class="data-card" style="margin-bottom:20px">
            <div style="padding:22px">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px">
                <div>
                  <div style="font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px">Available Balance</div>
                  <div style="font-size:34px;font-weight:700;color:var(--white)" id="balance-big">—</div>
                  <div style="font-size:12px;color:var(--muted);margin-top:4px" id="acc-no-display"></div>
                </div>
                <div style="font-size:50px;opacity:0.15">💵</div>
              </div>
              <div style="font-size:12px;color:var(--muted);margin-bottom:10px">Quick amounts:</div>
              <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button class="quick-btn" onclick="setAmount(10000)">10K</button>
                <button class="quick-btn" onclick="setAmount(50000)">50K</button>
                <button class="quick-btn" onclick="setAmount(100000)">100K</button>
                <button class="quick-btn" onclick="setAmount(500000)">500K</button>
                <button class="quick-btn" onclick="setAmount(1000000)">1M</button>
                <button class="quick-btn" id="all-btn" onclick="setAllBalance()">All</button>
              </div>
            </div>
          </div>

          <!-- Withdrawal Form -->
          <div class="data-card">
            <div class="data-card-header">
              <div class="data-card-title">📤 New Withdrawal Request</div>
            </div>
            <div style="padding:22px">

              <div id="form-alert"></div>

              <!-- Step 1: Amount -->
              <div style="font-size:12px;color:var(--accent);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:12px">Step 1 — Enter Amount</div>
              <div class="form-group">
                <label class="form-label">Amount to Withdraw (UGX)</label>
                <input type="number" class="form-input" id="wd-amount"
                  placeholder="Minimum UGX 5,000"
                  min="5000" oninput="updateCharges()">
                <div id="amount-preview" style="font-size:12px;margin-top:6px;color:var(--muted)"></div>
              </div>

              <!-- Charges breakdown -->
              <div class="charges-box" id="charges-box">
                <div style="font-size:12px;font-weight:600;color:var(--warning);margin-bottom:8px">💸 Breakdown</div>
                <div class="charges-row">
                  <span>Amount requested</span>
                  <span id="ch-amount">—</span>
                </div>
                <div class="charges-row">
                  <span>Processing fee (1%)</span>
                  <span id="ch-fee" style="color:var(--danger)">—</span>
                </div>
                <div class="charges-row total">
                  <span>You will receive</span>
                  <span id="ch-receive" style="color:var(--success)">—</span>
                </div>
              </div>

              <!-- Step 2: Method -->
              <div style="font-size:12px;color:var(--accent);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin:20px 0 12px">Step 2 — Choose Withdrawal Method</div>

              <div class="method-card" id="mc-mtn" onclick="selectMethod('MTN Mobile Money','mc-mtn')">
                <div class="method-icon">📱</div>
                <div>
                  <div class="method-title" style="color:#FFB300">MTN Mobile Money</div>
                  <div class="method-sub">Receive instantly on your MTN number</div>
                </div>
                <div class="method-check"></div>
              </div>

              <div class="method-card" id="mc-airtel" onclick="selectMethod('Airtel Money','mc-airtel')">
                <div class="method-icon">📱</div>
                <div>
                  <div class="method-title" style="color:#E53935">Airtel Money</div>
                  <div class="method-sub">Receive instantly on your Airtel number</div>
                </div>
                <div class="method-check"></div>
              </div>

              <div class="method-card" id="mc-bank" onclick="selectMethod('Bank Transfer','mc-bank')">
                <div class="method-icon">🏦</div>
                <div>
                  <div class="method-title" style="color:var(--accent)">Bank Transfer</div>
                  <div class="method-sub">Sent to your bank account (1–2 business days)</div>
                </div>
                <div class="method-check"></div>
              </div>

              <div class="method-card" id="mc-agent" onclick="selectMethod('Agent Pickup','mc-agent')">
                <div class="method-icon">🏪</div>
                <div>
                  <div class="method-title" style="color:var(--success)">Agent Pickup (Cash)</div>
                  <div class="method-sub">Collect cash from a FraudGuard agent</div>
                </div>
                <div class="method-check"></div>
              </div>

              <!-- Step 3: Account Details (shown after method selected) -->
              <div id="account-fields" style="display:none; margin-top:20px">
                <div style="font-size:12px;color:var(--accent);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:12px">
                  Step 3 — Enter Your Account Details
                </div>

                <div id="fields-mobile" style="display:none">
                  <div class="form-group">
                    <label class="form-label" id="phone-label">MTN Phone Number</label>
                    <input type="text" class="form-input" id="wd-acc-number"
                      placeholder="e.g. 0701234567" maxlength="15">
                    <div style="font-size:12px;color:var(--muted);margin-top:5px">
                      Enter the phone number registered for mobile money
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Account Holder Name</label>
                    <input type="text" class="form-input" id="wd-acc-name"
                      placeholder="Name as registered on mobile money">
                  </div>
                </div>

                <div id="fields-bank" style="display:none">
                  <div class="form-group">
                    <label class="form-label">Bank Name</label>
                    <select class="form-select" id="bank-name">
                      <option value="">-- Select your bank --</option>
                      <option>Stanbic Bank Uganda</option>
                      <option>Centenary Bank</option>
                      <option>DFCU Bank</option>
                      <option>Equity Bank Uganda</option>
                      <option>Absa Bank Uganda</option>
                      <option>Bank of Africa Uganda</option>
                      <option>Cairo Bank Uganda</option>
                      <option>Diamond Trust Bank</option>
                      <option>Housing Finance Bank</option>
                      <option>KCB Bank Uganda</option>
                      <option>NC Bank Uganda</option>
                      <option>Orient Bank Uganda</option>
                      <option>PostBank Uganda</option>
                      <option>Pride Microfinance</option>
                      <option>Other</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Bank Account Number</label>
                    <input type="text" class="form-input" id="wd-acc-number"
                      placeholder="e.g. 9030123456789">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Account Holder Name</label>
                    <input type="text" class="form-input" id="wd-acc-name"
                      placeholder="Name exactly as on your bank account">
                  </div>
                </div>

                <div id="fields-agent" style="display:none">
                  <div style="background:rgba(0,230,118,0.06);border:1px solid rgba(0,230,118,0.2);border-radius:10px;padding:14px;margin-bottom:16px;font-size:13px;line-height:1.8">
                    <div style="font-weight:600;color:var(--success);margin-bottom:6px">🏪 Agent Pickup Instructions</div>
                    <div style="color:var(--muted)">
                      1. Submit this form<br>
                      2. Admin approves and sends you a <strong style="color:var(--text)">pickup code</strong><br>
                      3. Visit any FraudGuard agent<br>
                      4. Show your <strong style="color:var(--text)">national ID + pickup code</strong><br>
                      5. Collect your cash
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Your Phone Number (for pickup code)</label>
                    <input type="text" class="form-input" id="wd-acc-number"
                      placeholder="e.g. 0701234567">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Your Full Name (must match National ID)</label>
                    <input type="text" class="form-input" id="wd-acc-name"
                      placeholder="As on your National ID">
                  </div>
                </div>
              </div>

              <!-- Notes -->
              <div class="form-group" id="notes-group" style="display:none; margin-top:8px">
                <label class="form-label">Additional Notes <span style="color:var(--muted);font-weight:400">(optional)</span></label>
                <textarea class="form-textarea" id="wd-notes"
                  placeholder="Any extra info e.g. preferred agent location"
                  style="min-height:60px"></textarea>
              </div>

              <!-- Submit -->
              <button class="btn btn-primary" id="wd-submit-btn"
                onclick="submitWithdrawal()"
                style="width:100%;padding:14px;font-size:15px;margin-top:12px;display:none">
                💵 Submit Withdrawal Request
              </button>

            </div>
          </div>
        </div>

        <!-- ── RIGHT: History + Info ─────────────────────────── -->
        <div>

          <!-- Processing Times -->
          <div class="data-card" style="margin-bottom:20px">
            <div class="data-card-header"><div class="data-card-title">⏱️ Processing Times</div></div>
            <div style="padding:16px">
              <div style="display:flex;flex-direction:column;gap:10px">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;background:var(--surface2);border-radius:10px">
                  <div style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:20px">📱</span>
                    <div>
                      <div style="font-size:13px;font-weight:600;color:#FFB300">MTN Mobile Money</div>
                      <div style="font-size:12px;color:var(--muted)">Instant after approval</div>
                    </div>
                  </div>
                  <span class="badge badge-completed">~5 min</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;background:var(--surface2);border-radius:10px">
                  <div style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:20px">📱</span>
                    <div>
                      <div style="font-size:13px;font-weight:600;color:#E53935">Airtel Money</div>
                      <div style="font-size:12px;color:var(--muted)">Instant after approval</div>
                    </div>
                  </div>
                  <span class="badge badge-completed">~5 min</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;background:var(--surface2);border-radius:10px">
                  <div style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:20px">🏦</span>
                    <div>
                      <div style="font-size:13px;font-weight:600;color:var(--accent)">Bank Transfer</div>
                      <div style="font-size:12px;color:var(--muted)">Business days only</div>
                    </div>
                  </div>
                  <span class="badge badge-pending">1–2 days</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;background:var(--surface2);border-radius:10px">
                  <div style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:20px">🏪</span>
                    <div>
                      <div style="font-size:13px;font-weight:600;color:var(--success)">Agent Pickup</div>
                      <div style="font-size:12px;color:var(--muted)">After code confirmation</div>
                    </div>
                  </div>
                  <span class="badge badge-pending">~1 hour</span>
                </div>
              </div>
              <div style="margin-top:14px;padding:10px 12px;background:rgba(255,179,0,0.06);border-radius:8px;font-size:12px;color:var(--muted)">
                ⚠️ Funds are held from your balance immediately when you submit. If rejected, they are returned automatically.
              </div>
            </div>
          </div>

          <!-- Withdrawal History -->
          <div class="data-card">
            <div class="data-card-header">
              <div class="data-card-title">📜 Withdrawal History</div>
              <button class="btn btn-secondary btn-sm" onclick="loadHistory()">🔄 Refresh</button>
            </div>
            <div id="history-area">
              <div style="text-align:center;padding:40px;color:var(--muted)">Loading...</div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
// ── Globals ───────────────────────────────────────────────
let selectedMethod = '';
let currentBalance = 0;
const FEE_RATE     = 0.01; // 1% processing fee

// ── Helpers ───────────────────────────────────────────────
function fmtUGX(n) {
  return 'UGX ' + Number(n).toLocaleString();
}
function fmtDate(s) {
  return new Date(s).toLocaleString('en-UG', {
    day:'2-digit', month:'short', year:'numeric',
    hour:'2-digit', minute:'2-digit'
  });
}
function toast(msg, type = 'success') {
  const t = document.createElement('div');
  t.className = `toast toast-${type}`;
  t.textContent = msg;
  document.getElementById('toast-container').appendChild(t);
  setTimeout(() => t.remove(), 5000);
}

// ── Quick amount buttons ──────────────────────────────────
function setAmount(val) {
  document.getElementById('wd-amount').value = val;
  updateCharges();
}
function setAllBalance() {
  document.getElementById('wd-amount').value = Math.floor(currentBalance);
  updateCharges();
}

// ── Update charges preview ────────────────────────────────
function updateCharges() {
  const val     = parseFloat(document.getElementById('wd-amount').value) || 0;
  const preview = document.getElementById('amount-preview');
  const box     = document.getElementById('charges-box');

  if (val < 5000) {
    preview.innerHTML = val > 0
      ? `<span style="color:var(--danger)">⚠️ Minimum withdrawal is UGX 5,000</span>`
      : '';
    box.classList.remove('show');
    return;
  }

  if (val > currentBalance) {
    preview.innerHTML = `<span style="color:var(--danger)">⚠️ Amount exceeds your balance of ${fmtUGX(currentBalance)}</span>`;
    box.classList.remove('show');
    return;
  }

  const fee     = Math.round(val * FEE_RATE);
  const receive = val - fee;

  preview.innerHTML = `<span style="color:var(--success)">✅ Valid amount</span>`;
  document.getElementById('ch-amount').textContent  = fmtUGX(val);
  document.getElementById('ch-fee').textContent     = '− ' + fmtUGX(fee);
  document.getElementById('ch-receive').textContent = fmtUGX(receive);
  box.classList.add('show');
}

// ── Select withdrawal method ──────────────────────────────
function selectMethod(method, cardId) {
  selectedMethod = method;

  // Reset all cards
  document.querySelectorAll('.method-card').forEach(c => c.classList.remove('active'));
  document.getElementById(cardId).classList.add('active');

  // Show account fields
  document.getElementById('account-fields').style.display = 'block';
  document.getElementById('fields-mobile').style.display  = 'none';
  document.getElementById('fields-bank').style.display    = 'none';
  document.getElementById('fields-agent').style.display   = 'none';

  // Clear previous inputs
  document.querySelectorAll('#wd-acc-number').forEach(el => el.value = '');
  document.querySelectorAll('#wd-acc-name').forEach(el => el.value = '');

  if (method === 'MTN Mobile Money') {
    document.getElementById('fields-mobile').style.display = 'block';
    document.getElementById('phone-label').textContent     = 'MTN Phone Number';
  } else if (method === 'Airtel Money') {
    document.getElementById('fields-mobile').style.display = 'block';
    document.getElementById('phone-label').textContent     = 'Airtel Phone Number';
  } else if (method === 'Bank Transfer') {
    document.getElementById('fields-bank').style.display   = 'block';
  } else if (method === 'Agent Pickup') {
    document.getElementById('fields-agent').style.display  = 'block';
  }

  document.getElementById('notes-group').style.display    = 'block';
  document.getElementById('wd-submit-btn').style.display  = 'block';

  // Scroll to fields smoothly
  setTimeout(() => {
    document.getElementById('account-fields').scrollIntoView({ behavior:'smooth', block:'nearest' });
  }, 100);
}

// ── Load balance ──────────────────────────────────────────
async function loadBalance() {
  const res  = await fetch('php/analytics.php?action=dashboard_stats');
  const json = await res.json();
  if (!json.success) return;

  currentBalance = parseFloat(json.data.balance) || 0;

  document.getElementById('balance-big').textContent      = fmtUGX(currentBalance);
  document.getElementById('topbar-balance').textContent   = fmtUGX(currentBalance);
  document.getElementById('acc-no-display').textContent   = 'Account: ' + (json.data.account_no || '—');
}

// ── Load withdrawal history ───────────────────────────────
async function loadHistory() {
  const area = document.getElementById('history-area');
  area.innerHTML = '<div style="text-align:center;padding:30px;color:var(--muted)">Loading...</div>';

  const res  = await fetch('php/withdraw.php?action=my_withdrawals');
  const json = await res.json();

  if (!json.success || !json.data.length) {
    area.innerHTML = `
      <div class="empty-state" style="padding:50px 20px">
        <div class="icon">💵</div>
        <p>No withdrawal requests yet.<br>Use the form to make your first withdrawal.</p>
      </div>`;
    return;
  }

  area.innerHTML = json.data.map(w => {
    const statusClass = w.status === 'approved'  ? 'completed'
                      : w.status === 'rejected'  ? 'blocked'
                      : w.status === 'cancelled' ? 'pending'
                      : 'pending';
    const statusIcon  = w.status === 'approved'  ? '✅'
                      : w.status === 'rejected'  ? '❌'
                      : w.status === 'cancelled' ? '🚫'
                      : '⏳';
    const fee    = Math.round(parseFloat(w.amount) * FEE_RATE);
    const payout = parseFloat(w.amount) - fee;

    return `
      <div class="history-item">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:10px">
          <div>
            <span style="font-family:monospace;color:var(--accent);font-size:12px">
              ${w.withdrawal_reference}
            </span>
            <span class="badge badge-${statusClass}" style="margin-left:8px">
              ${statusIcon} ${w.status.charAt(0).toUpperCase() + w.status.slice(1)}
            </span>
          </div>
          <div style="font-size:12px;color:var(--muted)">${fmtDate(w.created_at)}</div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:start">
          <div>
            <div style="font-size:20px;font-weight:700;color:var(--warning)">${fmtUGX(w.amount)}</div>
            <div style="font-size:12px;color:var(--muted);margin-top:3px">
              ${w.method === 'MTN Mobile Money' ? '📱' :
                w.method === 'Airtel Money'     ? '📱' :
                w.method === 'Bank Transfer'    ? '🏦' : '🏪'}
              ${w.method} → <strong style="color:var(--text)">${w.account_number}</strong>
            </div>
            <div style="font-size:12px;color:var(--muted);margin-top:2px">
              Payout: <span style="color:var(--success)">${fmtUGX(payout)}</span>
              <span style="color:var(--danger)">(fee: ${fmtUGX(fee)})</span>
            </div>
          </div>

          <div style="text-align:right">
            ${w.admin_notes
              ? `<div style="font-size:12px;color:${w.status==='rejected'?'var(--danger)':'var(--muted)'};max-width:160px">
                   ${w.admin_notes}
                 </div>`
              : ''}
            ${w.status === 'pending'
              ? `<button class="btn btn-danger btn-xs" style="margin-top:6px"
                   onclick="cancelWithdrawal(${w.withdrawal_id})">
                   Cancel
                 </button>`
              : ''}
          </div>
        </div>
      </div>`;
  }).join('');
}

// ── Submit withdrawal ─────────────────────────────────────
async function submitWithdrawal() {
  const alertEl = document.getElementById('form-alert');
  const btn     = document.getElementById('wd-submit-btn');
  const amount  = parseFloat(document.getElementById('wd-amount').value) || 0;
  const notes   = document.getElementById('wd-notes').value.trim();

  // Get account number and name based on active method
  let accNumber = '';
  let accName   = '';

  if (selectedMethod === 'Bank Transfer') {
    const bankName = document.getElementById('bank-name')?.value || '';
    accNumber = (bankName ? bankName + ' – ' : '') +
                (document.getElementById('wd-acc-number')?.value?.trim() || '');
    accName   = document.getElementById('wd-acc-name')?.value?.trim() || '';
  } else {
    const numEls  = document.querySelectorAll('#wd-acc-number');
    const nameEls = document.querySelectorAll('#wd-acc-name');
    numEls.forEach(el => { if (el.offsetParent !== null) accNumber = el.value.trim(); });
    nameEls.forEach(el => { if (el.offsetParent !== null) accName  = el.value.trim(); });
  }

  // Validate
  alertEl.innerHTML = '';
  if (amount < 5000) {
    alertEl.innerHTML = '<div class="alert alert-error">⚠️ Minimum withdrawal amount is UGX 5,000.</div>';
    return;
  }
  if (amount > currentBalance) {
    alertEl.innerHTML = '<div class="alert alert-error">⚠️ Amount exceeds your available balance.</div>';
    return;
  }
  if (!selectedMethod) {
    alertEl.innerHTML = '<div class="alert alert-error">⚠️ Please select a withdrawal method.</div>';
    return;
  }
  if (!accNumber) {
    alertEl.innerHTML = '<div class="alert alert-error">⚠️ Please enter your account / phone number.</div>';
    return;
  }
  if (!accName) {
    alertEl.innerHTML = '<div class="alert alert-error">⚠️ Please enter the account holder name.</div>';
    return;
  }

  btn.innerHTML = '<div class="spinner"></div>&nbsp; Submitting...';
  btn.disabled  = true;

  const body = new FormData();
  body.append('action',         'request_withdrawal');
  body.append('amount',         amount);
  body.append('method',         selectedMethod);
  body.append('account_name',   accName);
  body.append('account_number', accNumber);
  body.append('notes',          notes);

  try {
    const res  = await fetch('php/withdraw.php', { method:'POST', body });
    const json = await res.json();

    if (json.success) {
      alertEl.innerHTML = `<div class="alert alert-success">✅ ${json.message}</div>`;

      // Update balance display immediately
      if (json.new_balance !== undefined) {
        currentBalance = parseFloat(json.new_balance);
        document.getElementById('balance-big').textContent    = fmtUGX(currentBalance);
        document.getElementById('topbar-balance').textContent = fmtUGX(currentBalance);
      }

      // Reset form
      document.getElementById('wd-amount').value   = '';
      document.getElementById('wd-notes').value    = '';
      document.getElementById('amount-preview').textContent = '';
      document.getElementById('charges-box').classList.remove('show');
      document.querySelectorAll('.method-card').forEach(c => c.classList.remove('active'));
      document.getElementById('account-fields').style.display   = 'none';
      document.getElementById('notes-group').style.display      = 'none';
      document.getElementById('wd-submit-btn').style.display    = 'none';
      selectedMethod = '';

      // Reload history
      loadHistory();
      toast('Withdrawal request submitted successfully!', 'success');

    } else {
      alertEl.innerHTML = `<div class="alert alert-error">⚠️ ${json.message}</div>`;
    }

  } catch (err) {
    alertEl.innerHTML = '<div class="alert alert-error">⚠️ Network error. Please check your connection and try again.</div>';
  }

  btn.innerHTML = '💵 Submit Withdrawal Request';
  btn.disabled  = false;
}

// ── Cancel withdrawal ─────────────────────────────────────
async function cancelWithdrawal(wdId) {
  if (!confirm('Cancel this withdrawal? Your funds will be returned to your account.')) return;

  const body = new FormData();
  body.append('action',         'cancel_withdrawal');
  body.append('withdrawal_id',  wdId);

  const res  = await fetch('php/withdraw.php', { method:'POST', body });
  const json = await res.json();

  if (json.success) {
    toast(json.message, 'success');
    loadBalance();
    loadHistory();
  } else {
    toast(json.message, 'error');
  }
}

// ── Init ──────────────────────────────────────────────────
loadBalance();
loadHistory();
</script>
</body>
</html>
