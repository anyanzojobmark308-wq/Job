<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup - AI Fraud Detector</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--primary:#00E5A0;--dark:#080C14;--dark2:#0E1520;--dark3:#151E2D;--text:#E0EAF5;--text-muted:#6B8299;}
*{margin:0;padding:0;box-sizing:border-box;}
body{background:var(--dark);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(0,229,160,0.04) 1px,transparent 1px),linear-gradient(90deg,rgba(0,229,160,0.04) 1px,transparent 1px);background-size:40px 40px;pointer-events:none;}
.card{background:var(--dark2);border:1px solid rgba(255,255,255,0.08);border-radius:20px;padding:40px;max-width:640px;width:100%;position:relative;z-index:1;}
h1{font-family:'Syne',sans-serif;font-size:28px;font-weight:800;margin-bottom:6px;}
h1 span{color:var(--primary);}
.subtitle{color:var(--text-muted);font-size:14px;margin-bottom:32px;}
.step{display:flex;gap:16px;padding:16px;background:var(--dark3);border-radius:12px;margin-bottom:12px;border:1px solid rgba(255,255,255,0.05);}
.step-num{width:32px;height:32px;border-radius:8px;background:rgba(0,229,160,0.1);color:var(--primary);display:grid;place-items:center;font-family:'Syne',sans-serif;font-weight:800;font-size:14px;flex-shrink:0;}
.step-content h3{font-size:14px;font-weight:600;margin-bottom:4px;}
.step-content p{font-size:13px;color:var(--text-muted);line-height:1.6;}
.step-content code{background:var(--dark);padding:2px 6px;border-radius:4px;font-size:12px;color:var(--primary);font-family:monospace;}
.btn{display:inline-flex;align-items:center;gap:8px;margin-top:24px;padding:13px 24px;background:var(--primary);border:none;border-radius:10px;color:var(--dark);font-family:'Syne',sans-serif;font-weight:700;font-size:14px;cursor:pointer;text-decoration:none;transition:all 0.2s;}
.btn:hover{background:#00B87C;transform:translateY(-1px);}
.sql-box{background:var(--dark);border:1px solid rgba(0,229,160,0.15);border-radius:10px;padding:16px;font-family:monospace;font-size:12px;color:var(--primary);margin-top:8px;max-height:100px;overflow-y:auto;line-height:1.6;}
.alert-box{padding:14px 16px;border-radius:10px;margin-bottom:20px;border:1px solid rgba(0,229,160,0.2);background:rgba(0,229,160,0.05);font-size:13px;line-height:1.6;}
.creds-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:20px;}
.cred-box{background:var(--dark3);border-radius:12px;padding:16px;border:1px solid rgba(255,255,255,0.06);}
.cred-label{font-size:11px;color:var(--text-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px;}
.cred-item{display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;}
.cred-val{font-family:monospace;color:var(--primary);}
</style>
</head>
<body>
<div class="card">
  <h1>🛡️ AI <span>Fraud Detector</span> Setup</h1>
  <p class="subtitle">Follow these steps to get the system running on XAMPP</p>

  <div class="alert-box">
    <i class="fas fa-info-circle" style="color:var(--primary)"></i>
    <strong style="color:var(--primary)"> Prerequisites:</strong> XAMPP must be running with <strong>Apache</strong> and <strong>MySQL</strong> services started.
  </div>

  <div class="step">
    <div class="step-num">1</div>
    <div class="step-content">
      <h3>Place project in XAMPP folder</h3>
      <p>Copy the entire <code>ai_fraud_detector</code> folder to your XAMPP htdocs directory:<br>
      <code>C:\xampp\htdocs\ai_fraud_detector\</code> (Windows)<br>
      <code>/Applications/XAMPP/htdocs/ai_fraud_detector/</code> (Mac)</p>
    </div>
  </div>

  <div class="step">
    <div class="step-num">2</div>
    <div class="step-content">
      <h3>Create the database</h3>
      <p>Open <code>phpMyAdmin</code> at <code>http://localhost/phpmyadmin</code>, go to <strong>Import</strong>, and upload the file <code>database.sql</code> from the project root. Or run:</p>
      <div class="sql-box">mysql -u root -p &lt; database.sql</div>
    </div>
  </div>

  <div class="step">
    <div class="step-num">3</div>
    <div class="step-content">
      <h3>Configure database (if needed)</h3>
      <p>If your XAMPP MySQL uses a password, edit <code>includes/config.php</code> and update <code>DB_PASS</code>. Default XAMPP uses empty password.</p>
    </div>
  </div>

  <div class="step">
    <div class="step-num">4</div>
    <div class="step-content">
      <h3>Open the application</h3>
      <p>Navigate to <code>http://localhost/ai_fraud_detector/</code> in your browser.</p>
    </div>
  </div>

  <div class="creds-grid">
    <div class="cred-box">
      <div class="cred-label">👤 Admin Login</div>
      <div class="cred-item"><span>Email</span><span class="cred-val">admin@frauddetector.ug</span></div>
      <div class="cred-item"><span>Password</span><span class="cred-val">password</span></div>
      <div style="font-size:11px;color:#FF3B5C;margin-top:8px">⚠️ Change password after first login!</div>
    </div>
    <div class="cred-box">
      <div class="cred-label">🔗 Quick Links</div>
      <div class="cred-item" style="flex-direction:column;gap:4px">
        <a href="index.php" style="color:var(--primary);font-size:13px;text-decoration:none">→ Main Login Page</a>
        <a href="admin/dashboard.php" style="color:var(--primary);font-size:13px;text-decoration:none">→ Admin Dashboard</a>
        <a href="user/dashboard.php" style="color:var(--primary);font-size:13px;text-decoration:none">→ User Dashboard</a>
      </div>
    </div>
  </div>

  <a href="index.php" class="btn"><i class="fas fa-rocket"></i> Launch Application</a>
</div>
</body>
</html>
