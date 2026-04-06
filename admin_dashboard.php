<?php
// ============================================================
//  FraudGuard – Admin Dashboard
// ============================================================
require_once 'php/config.php';
startSession(); requireLogin();
if (!isAdmin()) { header('Location: dashboard.php'); exit; }
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard – FraudGuard</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
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
      <div class="avatar" style="background: linear-gradient(135deg,#ff6b35,#ff4444)"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
      <div class="uname"><?= htmlspecialchars($user['full_name']) ?></div>
      <div class="urole" style="color:var(--danger)"><?= ucfirst(str_replace('_',' ',$user['role'])) ?></div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section-title">Overview</div>
      <a href="admin_dashboard.php" class="nav-link active"><span class="icon">📊</span> Dashboard</a>
      <a href="admin_transactions.php" class="nav-link"><span class="icon">💳</span> All Transactions</a>
      <a href="admin_alerts.php" class="nav-link"><span class="icon">⚠️</span> Fraud Alerts <span class="nav-badge" id="alert-badge">—</span></a>

      <div class="nav-section-title">Case Management</div>
      <a href="admin_cases.php" class="nav-link"><span class="icon">📁</span> Refund Cases <span class="nav-badge" id="cases-badge">—</span></a>
      <a href="admin_deposits.php" class="nav-link"><span class="icon">💰</span> Deposit Requests <span class="nav-badge" id="dep-badge" style="display:none"></span></a>
      <a href="admin_withdrawals.php" class="nav-link"><span class="icon">💵</span> Withdrawal Requests</a>

      <div class="nav-section-title">Administration</div>
      <a href="admin_users.php" class="nav-link"><span class="icon">👥</span> Users</a>
      <a href="notifications.php" class="nav-link"><span class="icon">🔔</span> Notifications</a>
    </nav>
    <div class="sidebar-footer">
      <a href="php/auth.php?action=logout" class="nav-link"><span class="icon">🚪</span> Logout</a>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">Admin Dashboard</div>
      <div class="topbar-actions">
        <span style="color:var(--muted); font-size:13px" id="last-updated"></span>
        <button class="btn btn-secondary btn-sm" onclick="loadAll()">🔄 Refresh</button>
      </div>
    </div>

    <div class="page-content">
      <div id="toast-container"></div>

      <!-- Stats Row -->
      <div class="stats-grid">
        <div class="stat-card blue">
          <div class="stat-icon">💳</div>
          <div class="stat-value" id="st-total">—</div>
          <div class="stat-label">Total Transactions</div>
        </div>
        <div class="stat-card red">
          <div class="stat-icon">🚨</div>
          <div class="stat-value" id="st-flagged">—</div>
          <div class="stat-label">Flagged Today</div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon">🔒</div>
          <div class="stat-value" id="st-blocked">—</div>
          <div class="stat-label">Blocked Today</div>
        </div>
        <div class="stat-card purple">
          <div class="stat-icon">📁</div>
          <div class="stat-value" id="st-cases">—</div>
          <div class="stat-label">Open Cases</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon">👥</div>
          <div class="stat-value" id="st-users">—</div>
          <div class="stat-label">Registered Users</div>
        </div>
        <div class="stat-card orange">
          <div class="stat-icon">⚠️</div>
          <div class="stat-value" id="st-alerts">—</div>
          <div class="stat-label">Unresolved Alerts</div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid-2" style="margin-bottom:24px">
        <div class="data-card">
          <div class="data-card-header"><div class="data-card-title">Transaction Volume – Last 7 Days</div></div>
          <div style="padding:20px"><canvas id="volumeChart" height="200"></canvas></div>
        </div>
        <div class="data-card">
          <div class="data-card-header"><div class="data-card-title">Fraud Alert Types</div></div>
          <div style="padding:20px; display:flex; align-items:center; justify-content:center"><canvas id="typeChart" height="200"></canvas></div>
        </div>
      </div>

      <!-- Recent Alerts Table -->
      <div class="data-card">
        <div class="data-card-header">
          <div class="data-card-title">Recent Fraud Alerts</div>
          <a href="admin_alerts.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div style="overflow-x:auto">
          <table class="data-table">
            <thead><tr>
              <th>User</th><th>Alert Type</th><th>Severity</th>
              <th>Transaction</th><th>Amount</th><th>Description</th><th>Time</th><th>Action</th>
            </tr></thead>
            <tbody id="alerts-body">
              <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:30px">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let volumeChart, typeChart;

function fmtUGX(n) { return 'UGX ' + Number(n).toLocaleString(); }
function fmtDate(s) { return new Date(s).toLocaleString('en-UG',{day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'}); }

async function loadAll() {
  document.getElementById('last-updated').textContent = 'Updating...';
  const res  = await fetch('php/analytics.php?action=dashboard_stats');
  const json = await res.json();
  if (!json.success) return;
  const d = json.data;

  document.getElementById('st-total').textContent   = d.total_transactions?.toLocaleString() || 0;
  document.getElementById('st-flagged').textContent = d.flagged_today || 0;
  document.getElementById('st-blocked').textContent = d.blocked_today || 0;
  document.getElementById('st-cases').textContent   = d.open_cases    || 0;
  document.getElementById('st-users').textContent   = d.total_users   || 0;
  document.getElementById('st-alerts').textContent  = d.total_alerts  || 0;
  document.getElementById('alert-badge').textContent = d.total_alerts || 0;
  document.getElementById('cases-badge').textContent = d.open_cases   || 0;
  document.getElementById('last-updated').textContent = 'Updated ' + new Date().toLocaleTimeString();

  // Volume chart
  if (d.volume_chart) {
    const labels  = d.volume_chart.map(r => r.txn_date);
    const counts  = d.volume_chart.map(r => r.count);
    if (volumeChart) volumeChart.destroy();
    volumeChart = new Chart(document.getElementById('volumeChart'), {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Transactions',
          data: counts,
          backgroundColor: 'rgba(0,212,255,0.3)',
          borderColor: 'rgba(0,212,255,0.8)',
          borderWidth: 2,
          borderRadius: 6,
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7fa3' } },
          x: { grid: { display: false }, ticks: { color: '#6b7fa3' } }
        }
      }
    });
  }

  // Type chart
  if (d.fraud_by_type) {
    const colors = ['#00d4ff','#ff6b35','#ff4444','#ffb300','#00e676','#7c4dff'];
    if (typeChart) typeChart.destroy();
    typeChart = new Chart(document.getElementById('typeChart'), {
      type: 'doughnut',
      data: {
        labels: d.fraud_by_type.map(r => r.alert_type.replace(/_/g,' ')),
        datasets: [{ data: d.fraud_by_type.map(r => r.count), backgroundColor: colors, borderWidth: 0 }]
      },
      options: { plugins: { legend: { position:'right', labels:{ color:'#6b7fa3', padding:14 } } }, cutout:'65%' }
    });
  }

  // Alerts
  const tbody = document.getElementById('alerts-body');
  if (!d.recent_alerts || !d.recent_alerts.length) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--muted);padding:30px">No alerts found.</td></tr>';
    return;
  }
  tbody.innerHTML = d.recent_alerts.map(a => `
    <tr>
      <td><strong>${a.full_name}</strong></td>
      <td><span style="color:var(--warning)">${a.alert_type.replace(/_/g,' ')}</span></td>
      <td><span class="badge badge-${a.severity}">${a.severity}</span></td>
      <td style="font-family:monospace;color:var(--accent);font-size:12px">${a.txn_reference}</td>
      <td>${fmtUGX(a.amount)}</td>
      <td style="max-width:240px;font-size:12px;color:var(--muted)">${a.description}</td>
      <td style="color:var(--muted);font-size:12px">${fmtDate(a.created_at)}</td>
      <td>
        <button class="btn btn-success btn-xs" onclick="resolveAlert(${a.alert_id})">Resolve</button>
      </td>
    </tr>
  `).join('');
}

async function resolveAlert(id) {
  const body = new FormData(); body.append('alert_id', id);
  const res  = await fetch('php/analytics.php?action=resolve_alert', { method:'POST', body });
  const json = await res.json();
  if (json.success) { loadAll(); }
}

loadAll();
setInterval(loadAll, 30000); // auto-refresh every 30s
</script>
</body>
</html>
