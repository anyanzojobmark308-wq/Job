<?php
require_once 'includes/auth.php';
startSecureSession();
if (isLoggedIn()) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI Fraud Detector - Uganda Mobile Money Security</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  :root {
    --primary: #00E5A0;
    --primary-dark: #00B87C;
    --danger: #FF3B5C;
    --warning: #FFB800;
    --dark: #080C14;
    --dark2: #0E1520;
    --dark3: #151E2D;
    --border: rgba(0,229,160,0.15);
    --text: #E0EAF5;
    --text-muted: #6B8299;
    --font-head: 'Syne', sans-serif;
    --font-body: 'DM Sans', sans-serif;
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  body {
    background: var(--dark);
    color: var(--text);
    font-family: var(--font-body);
    min-height: 100vh;
    overflow-x: hidden;
  }
  /* Animated background grid */
  body::before {
    content:'';
    position:fixed; inset:0;
    background-image:
      linear-gradient(rgba(0,229,160,0.04) 1px, transparent 1px),
      linear-gradient(90deg, rgba(0,229,160,0.04) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events:none; z-index:0;
  }
  .layout { display:flex; min-height:100vh; position:relative; z-index:1; }

  /* === LEFT HERO PANEL === */
  .hero-panel {
    flex:1; display:flex; flex-direction:column; justify-content:center;
    padding:60px 80px; position:relative; overflow:hidden;
  }
  .hero-panel::before {
    content:'';
    position:absolute; top:-100px; left:-100px;
    width:500px; height:500px;
    background: radial-gradient(circle, rgba(0,229,160,0.12) 0%, transparent 70%);
    pointer-events:none;
  }
  .logo { display:flex; align-items:center; gap:14px; margin-bottom:60px; }
  .logo-icon {
    width:50px; height:50px; background:var(--primary);
    border-radius:14px; display:grid; place-items:center;
    font-size:22px; color:var(--dark); font-weight:900;
    box-shadow: 0 0 30px rgba(0,229,160,0.4);
  }
  .logo-text { font-family:var(--font-head); font-size:18px; font-weight:700; }
  .logo-text span { color:var(--primary); }

  .hero-badge {
    display:inline-flex; align-items:center; gap:8px;
    background:rgba(0,229,160,0.08); border:1px solid var(--border);
    padding:8px 16px; border-radius:50px; font-size:12px;
    color:var(--primary); margin-bottom:30px; width:fit-content;
  }
  .hero-badge .dot { width:6px; height:6px; background:var(--primary); border-radius:50%; animation:pulse 2s infinite; }
  
  h1 {
    font-family:var(--font-head); font-size:clamp(36px,4vw,56px);
    font-weight:800; line-height:1.1; margin-bottom:24px;
  }
  h1 .accent { color:var(--primary); }
  .hero-desc { color:var(--text-muted); font-size:16px; line-height:1.7; max-width:440px; margin-bottom:40px; }

  .stats-row { display:flex; gap:32px; }
  .stat-item { text-align:center; }
  .stat-num { font-family:var(--font-head); font-size:28px; font-weight:800; color:var(--primary); }
  .stat-label { font-size:12px; color:var(--text-muted); margin-top:4px; }

  /* Animated shield */
  .shield-visual {
    position:absolute; right:40px; bottom:40px;
    width:200px; height:200px; opacity:0.15;
    animation: float 4s ease-in-out infinite;
  }
  @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-15px)} }
  @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }

  /* === RIGHT AUTH PANEL === */
  .auth-panel {
    width:480px; background:var(--dark2);
    border-left:1px solid rgba(255,255,255,0.06);
    display:flex; flex-direction:column; justify-content:center;
    padding:60px 50px;
  }
  .tabs { display:flex; gap:0; margin-bottom:36px; background:var(--dark3); border-radius:12px; padding:4px; }
  .tab-btn {
    flex:1; padding:12px; border:none; background:transparent;
    color:var(--text-muted); font-family:var(--font-head); font-size:14px;
    font-weight:600; cursor:pointer; border-radius:10px; transition:all 0.3s;
  }
  .tab-btn.active { background:var(--primary); color:var(--dark); }

  .auth-form { display:none; }
  .auth-form.active { display:block; animation:fadeIn 0.4s ease; }
  @keyframes fadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }

  .form-title { font-family:var(--font-head); font-size:22px; font-weight:700; margin-bottom:6px; }
  .form-sub { color:var(--text-muted); font-size:14px; margin-bottom:28px; }

  .form-group { margin-bottom:18px; position:relative; }
  .form-group label { display:block; font-size:13px; font-weight:500; margin-bottom:8px; color:var(--text-muted); }
  .form-group input, .form-group select {
    width:100%; padding:13px 16px 13px 44px;
    background:var(--dark3); border:1px solid rgba(255,255,255,0.08);
    border-radius:10px; color:var(--text); font-family:var(--font-body); font-size:14px;
    transition:all 0.3s; outline:none;
  }
  .form-group input:focus, .form-group select:focus {
    border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,229,160,0.1);
  }
  .form-group .icon {
    position:absolute; left:14px; bottom:13px;
    color:var(--text-muted); font-size:16px; pointer-events:none;
  }
  .form-group select { cursor:pointer; }

  .role-selector { display:flex; gap:10px; margin-bottom:20px; }
  .role-btn {
    flex:1; padding:10px; border:1px solid rgba(255,255,255,0.1);
    background:var(--dark3); border-radius:10px; color:var(--text-muted);
    font-size:13px; cursor:pointer; transition:all 0.3s; text-align:center;
  }
  .role-btn.selected { border-color:var(--primary); color:var(--primary); background:rgba(0,229,160,0.08); }
  .role-btn i { display:block; font-size:20px; margin-bottom:6px; }

  .btn-primary {
    width:100%; padding:14px; background:var(--primary);
    border:none; border-radius:10px; color:var(--dark);
    font-family:var(--font-head); font-size:15px; font-weight:700;
    cursor:pointer; transition:all 0.3s; margin-top:8px;
    box-shadow: 0 4px 20px rgba(0,229,160,0.3);
  }
  .btn-primary:hover { background:var(--primary-dark); transform:translateY(-1px); box-shadow:0 6px 25px rgba(0,229,160,0.4); }
  .btn-primary:active { transform:translateY(0); }
  .btn-primary:disabled { opacity:0.6; cursor:not-allowed; transform:none; }

  .alert {
    padding:12px 16px; border-radius:10px; font-size:13px;
    margin-bottom:16px; display:flex; align-items:center; gap:10px;
  }
  .alert-error { background:rgba(255,59,92,0.1); border:1px solid rgba(255,59,92,0.3); color:#FF3B5C; }
  .alert-success { background:rgba(0,229,160,0.1); border:1px solid rgba(0,229,160,0.3); color:var(--primary); }
  .alert { display:none; }
  .alert.show { display:flex; }

  .spinner { display:inline-block; width:16px; height:16px; border:2px solid var(--dark); border-top-color:transparent; border-radius:50%; animation:spin 0.8s linear infinite; }
  @keyframes spin { to{transform:rotate(360deg)} }

  /* Responsive */
  @media(max-width:900px) {
    .hero-panel { display:none; }
    .auth-panel { width:100%; padding:40px 24px; }
  }
</style>
</head>
<body>
<div class="layout">
  <!-- Hero Panel -->
  <div class="hero-panel">
    <div class="logo">
      <div class="logo-icon">🛡</div>
      <div class="logo-text">AI<span>Fraud</span>Detector</div>
    </div>

    <div class="hero-badge"><span class="dot"></span> Live Monitoring Active</div>

    <h1>Stop Mobile Money<br><span class="accent">Scams in Uganda</span><br>With AI</h1>

    <p class="hero-desc">
      Our AI engine detects "anomalous transaction bursts" following calls from unverified numbers — 
      temporarily pausing transactions and issuing security prompts to protect Ugandan users from 
      social engineering fraud.
    </p>

    <div class="stats-row">
      <div class="stat-item">
        <div class="stat-num">94%</div>
        <div class="stat-label">Detection Rate</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">2.3s</div>
        <div class="stat-label">Avg Analysis</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">12K+</div>
        <div class="stat-label">Frauds Blocked</div>
      </div>
    </div>

    <!-- Shield SVG -->
    <svg class="shield-visual" viewBox="0 0 200 200" fill="none">
      <path d="M100 10L180 45V100C180 145 145 180 100 195C55 180 20 145 20 100V45L100 10Z" fill="none" stroke="#00E5A0" stroke-width="3"/>
      <path d="M75 100L90 115L125 80" stroke="#00E5A0" stroke-width="4" stroke-linecap="round"/>
    </svg>
  </div>

  <!-- Auth Panel -->
  <div class="auth-panel">
    <div class="tabs">
      <button class="tab-btn active" onclick="switchTab('login')">Sign In</button>
      <button class="tab-btn" onclick="switchTab('register')">Register</button>
    </div>

    <!-- Login Form -->
    <div id="loginForm" class="auth-form active">
      <h2 class="form-title">Welcome back</h2>
      <p class="form-sub">Sign in to your fraud protection dashboard</p>

      <div id="loginAlert" class="alert alert-error"><i class="fas fa-exclamation-circle"></i><span></span></div>
      <div id="loginSuccess" class="alert alert-success"><i class="fas fa-check-circle"></i><span></span></div>

      <div class="form-group">
        <label>Email or Phone</label>
        <i class="fas fa-user icon"></i>
        <input type="text" id="loginIdentifier" placeholder="Enter email or phone" autocomplete="username">
      </div>

      <div class="form-group">
        <label>Password</label>
        <i class="fas fa-lock icon"></i>
        <input type="password" id="loginPassword" placeholder="Enter password" autocomplete="current-password">
      </div>

      <button class="btn-primary" onclick="handleLogin()" id="loginBtn">
        Sign In
      </button>
    </div>

    <!-- Register Form -->
    <div id="registerForm" class="auth-form">
      <h2 class="form-title">Create Account</h2>
      <p class="form-sub">Join the mobile money fraud protection network</p>

      <div id="regAlert" class="alert alert-error"><i class="fas fa-exclamation-circle"></i><span></span></div>
      <div id="regSuccess" class="alert alert-success"><i class="fas fa-check-circle"></i><span></span></div>

      <div style="margin-bottom:18px;">
        <label style="font-size:13px;color:var(--text-muted);display:block;margin-bottom:10px;">Account Type</label>
        <div class="role-selector">
          <div class="role-btn selected" onclick="selectRole('user',this)" id="roleUser">
            <i class="fas fa-user"></i>User
          </div>
          <div class="role-btn" onclick="selectRole('admin',this)" id="roleAdmin">
            <i class="fas fa-shield-alt"></i>Admin
          </div>
        </div>
        <input type="hidden" id="selectedRole" value="user">
      </div>

      <div class="form-group">
        <label>Full Name</label>
        <i class="fas fa-id-card icon"></i>
        <input type="text" id="regName" placeholder="Enter your full name">
      </div>

      <div class="form-group">
        <label>Email Address</label>
        <i class="fas fa-envelope icon"></i>
        <input type="email" id="regEmail" placeholder="name@example.com">
      </div>

      <div class="form-group">
        <label>Phone Number</label>
        <i class="fas fa-phone icon"></i>
        <input type="text" id="regPhone" placeholder="+256700000000">
      </div>

      <div class="form-group">
        <label>Password</label>
        <i class="fas fa-lock icon"></i>
        <input type="password" id="regPassword" placeholder="Min 8 characters">
      </div>

      <button class="btn-primary" onclick="handleRegister()" id="regBtn">
        Create Account
      </button>
    </div>
  </div>
</div>

<script>
function switchTab(tab) {
  document.querySelectorAll('.tab-btn').forEach((b,i) => b.classList.toggle('active', (tab==='login'&&i===0)||(tab==='register'&&i===1)));
  document.getElementById('loginForm').classList.toggle('active', tab==='login');
  document.getElementById('registerForm').classList.toggle('active', tab==='register');
}

function selectRole(role, el) {
  document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('selectedRole').value = role;
}

function showAlert(id, msg, type='error') {
  const el = document.getElementById(id);
  el.className = `alert alert-${type} show`;
  el.querySelector('span').textContent = msg;
  setTimeout(() => el.classList.remove('show'), 5000);
}

async function handleLogin() {
  const identifier = document.getElementById('loginIdentifier').value.trim();
  const password   = document.getElementById('loginPassword').value;
  const btn        = document.getElementById('loginBtn');
  
  if (!identifier || !password) { showAlert('loginAlert','Please fill in all fields'); return; }
  
  btn.innerHTML = '<span class="spinner"></span> Signing in...';
  btn.disabled = true;
  
  try {
    const res = await fetch('api/auth.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({action:'login', identifier, password})
    });
    const data = await res.json();
    
    if (data.success) {
      showAlert('loginSuccess', data.message, 'success');
      setTimeout(() => { window.location.href = data.redirect; }, 800);
    } else {
      showAlert('loginAlert', data.message);
      btn.innerHTML = 'Sign In'; btn.disabled = false;
    }
  } catch(e) {
    showAlert('loginAlert', 'Server error. Is XAMPP running?');
    btn.innerHTML = 'Sign In'; btn.disabled = false;
  }
}

async function handleRegister() {
  const name     = document.getElementById('regName').value.trim();
  const email    = document.getElementById('regEmail').value.trim();
  const phone    = document.getElementById('regPhone').value.trim();
  const password = document.getElementById('regPassword').value;
  const role     = document.getElementById('selectedRole').value;
  const btn      = document.getElementById('regBtn');
  
  if (!name||!email||!phone||!password) { showAlert('regAlert','Please fill in all fields'); return; }
  if (password.length < 8) { showAlert('regAlert','Password must be at least 8 characters'); return; }
  
  btn.innerHTML = '<span class="spinner"></span> Creating account...';
  btn.disabled = true;
  
  try {
    const res = await fetch('api/auth.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({action:'register', name, email, phone, password, role})
    });
    const data = await res.json();
    
    if (data.success) {
      showAlert('regSuccess', data.message + ' Please sign in.', 'success');
      setTimeout(() => switchTab('login'), 1500);
    } else {
      showAlert('regAlert', data.message);
    }
    btn.innerHTML = 'Create Account'; btn.disabled = false;
  } catch(e) {
    showAlert('regAlert', 'Server error. Is XAMPP running?');
    btn.innerHTML = 'Create Account'; btn.disabled = false;
  }
}

// Enter key support
document.addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    if (document.getElementById('loginForm').classList.contains('active')) handleLogin();
    else handleRegister();
  }
});
</script>
</body>
</html>
