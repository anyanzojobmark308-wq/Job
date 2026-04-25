# 🛡️ AI Mobile Money Fraud Detector
### Uganda Mobile Money Social Engineering Detection System

---

## 📋 System Overview

This system uses AI behavioral analysis to detect **"anomalous transaction bursts"** following calls from **unverified numbers**, temporarily pausing transactions and issuing **security prompts** to protect Ugandan users from social engineering fraud.

### Key Features
- 🤖 **AI Fraud Detection Engine** - Multi-factor risk scoring (0-100)
- 📱 **Anomalous Burst Detection** - Flags rapid consecutive transactions
- 📞 **Caller Verification** - Checks if caller is registered/blacklisted
- 🔐 **Security Prompts** - Pauses suspicious transactions for user review
- 👤 **User Dashboard** - Send money with real-time AI protection
- 🛡️ **Admin Panel** - Full oversight, alert management, blacklist control
- 🚫 **Blacklist Manager** - Block known fraud numbers

---

## 🚀 Quick Start (XAMPP)

### Requirements
- XAMPP (Apache + MySQL + PHP 7.4+)
- Web browser

### Steps

1. **Copy project** to `C:\xampp\htdocs\ai_fraud_detector\`

2. **Start XAMPP** - Run Apache and MySQL

3. **Setup Database**:
   - Open `http://localhost/phpmyadmin`
   - Click **Import** → Select `database.sql` → Click **Go**

4. **Launch**: Open `http://localhost/ai_fraud_detector/setup.php`

---

## 🔑 Default Credentials

| Role  | Email                     | Password  |
|-------|---------------------------|-----------|
| Admin | admin@frauddetector.ug   | password  |

⚠️ **Change the admin password immediately in production!**

---

## 📁 Project Structure

```
ai_fraud_detector/
├── index.php              ← Login/Register page
├── setup.php              ← Setup guide page
├── database.sql           ← Database schema + seed data
├── includes/
│   ├── config.php         ← DB config & connection
│   ├── auth.php           ← Login/register/session helpers
│   └── fraud_detector.php ← AI detection engine
├── api/
│   ├── auth.php           ← Auth API endpoint
│   ├── transactions.php   ← Transaction API endpoint
│   └── users.php          ← Admin users API
├── user/
│   └── dashboard.php      ← User dashboard (send money, history)
└── admin/
    └── dashboard.php      ← Admin control panel
```

---

## 🤖 How the AI Detection Works

The fraud detector scores each transaction on 5 factors:

| Factor | Max Score | Description |
|--------|-----------|-------------|
| Caller Verification | 60 pts | Unknown/blacklisted caller phone |
| Transaction Burst | 40 pts | 3+ transactions in 10 minutes |
| Amount Risk | 25 pts | High-value transactions (>500K UGX) |
| Receiver History | 10 pts | First-time recipient |
| Blacklist Check | 50 pts | Phone in blacklisted database |

**Verdict:**
- 0-34: ✅ **Safe** — Transaction approved
- 35-69: ⚠️ **Suspicious** — Security prompt shown
- 70-100: 🚫 **Fraud** — Transaction blocked

---

## 💻 VS Code Development

Open the folder in VS Code:
```bash
code C:\xampp\htdocs\ai_fraud_detector
```

Recommended extensions:
- PHP Intelephense
- MySQL (by Jun Han) for DB browsing
- Live Server (for static files)

---

## 🔧 Configuration

Edit `includes/config.php` to adjust:
- `BURST_WINDOW_MINUTES` — Time window for burst detection (default: 10)
- `BURST_THRESHOLD` — Transaction count to trigger flag (default: 3)
- `HIGH_RISK_AMOUNT` — UGX threshold for large amount alerts (default: 500,000)

---

*Built for Uganda Mobile Money Security — 2026*
