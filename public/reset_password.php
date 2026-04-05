<?php
// ============================================================
// SCP Central — Password reset (token handler)
// ============================================================

require_once __DIR__ . '/../includes/db.php';

define('BCRYPT_COST',       12);
define('MIN_PASSWORD_LENGTH', 12);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'secure' => true,
        'httponly' => true, 'samesite' => 'Lax',
    ]);
    session_start();
}

// Already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

function authLog(string $event, ?int $userId = null, array $details = []): void {
    try {
        $db   = getDB();
        $stmt = $db->prepare("
            INSERT INTO auth_log (user_id, event, ip, user_agent, details)
            VALUES (:user_id, :event, :ip, :ua, :details)
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':event'   => $event,
            ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
            ':ua'      => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ':details' => $details ? json_encode($details) : null,
        ]);
    } catch (Exception $e) {
        error_log('auth_log write failed: ' . $e->getMessage());
    }
}

// ── VALIDATE TOKEN ───────────────────────────────────────────
$rawToken  = $_GET['token'] ?? '';
$tokenHash = hash('sha256', $rawToken);
$resetRow  = null;
$tokenUser = null;
$tokenError = '';

if (empty($rawToken)) {
    $tokenError = 'Invalid or missing reset token.';
} else {
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT pr.id, pr.user_id, pr.expires_at, pr.used_at,
               u.name, u.username, u.email, u.is_active
        FROM password_resets pr
        JOIN users u ON u.id = pr.user_id
        WHERE pr.token_hash = :hash
        LIMIT 1
    ");
    $stmt->execute([':hash' => $tokenHash]);
    $resetRow = $stmt->fetch();

    if (!$resetRow) {
        $tokenError = 'This reset link is invalid.';
    } elseif ($resetRow['used_at'] !== null) {
        $tokenError = 'This reset link has already been used.';
    } elseif (strtotime($resetRow['expires_at']) < time()) {
        $tokenError = 'This reset link has expired. Please request a new one.';
    } elseif (!$resetRow['is_active']) {
        $tokenError = 'This account is inactive. Contact your administrator.';
    } else {
        $tokenUser = $resetRow;
    }
}

// ── HANDLE SUBMIT ────────────────────────────────────────────
$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenUser) {
    $pw  = $_POST['password']         ?? '';
    $cfm = $_POST['password_confirm'] ?? '';

    if (strlen($pw) < MIN_PASSWORD_LENGTH) {
        $errors[] = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters.';
    }
    if ($pw !== $cfm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $db   = getDB();

        // Update password, clear lockout, clear force_password_change
        $db->prepare("
            UPDATE users
            SET password_hash = :hash,
                failed_attempts = 0,
                locked_until = NULL,
                force_password_change = false
            WHERE id = :id
        ")->execute([':hash' => $hash, ':id' => $tokenUser['user_id']]);

        // Mark token used
        $db->prepare("
            UPDATE password_resets SET used_at = NOW() WHERE id = :id
        ")->execute([':id' => $tokenUser['id']]);

        authLog('password_reset_completed', $tokenUser['user_id']);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SCP Central — Set New Password</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #F4F2EE; --surface: #FFFFFF; --border: #E0DDD8;
    --text: #1A1A18; --text-muted: #6B6860;
    --accent: #1A4E8F; --accent-hover: #163F6E;
    --danger: #A32D2D; --danger-bg: #FCEBEB; --danger-border: #F7C1C1;
    --success: #1A6B3C; --success-bg: #EDFAF3; --success-border: #A3E6C3;
    --warning: #854F0B; --warning-bg: #FAEEDA;
    --mono: 'IBM Plex Mono', monospace; --sans: 'IBM Plex Sans', sans-serif;
    --radius: 10px; --radius-sm: 6px;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: var(--sans); background: var(--bg); color: var(--text);
    min-height: 100vh; display: flex; flex-direction: column;
    align-items: center; justify-content: center; padding: 24px 16px;
  }
  .wrap { width: 100%; max-width: 400px; }
  .header { text-align: center; margin-bottom: 32px; }
  .logo-mark {
    display: inline-flex; align-items: center; justify-content: center;
    width: 48px; height: 48px; background: #1c1c1e; border-radius: 12px; margin-bottom: 16px;
  }
  .wordmark { font-family: var(--mono); font-size: 11px; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; }
  h1 { font-size: 22px; font-weight: 600; }
  .subtitle { font-size: 14px; color: var(--text-muted); margin-top: 6px; }
  .card { background: var(--surface); border: 0.5px solid var(--border); border-radius: var(--radius); padding: 28px; }
  .alert { border-radius: var(--radius-sm); padding: 11px 14px; font-size: 13px; margin-bottom: 20px; line-height: 1.5; }
  .alert-error   { background: var(--danger-bg);  border: 0.5px solid var(--danger-border); color: var(--danger); }
  .alert-warning { background: var(--warning-bg); border: 0.5px solid #FAC775; color: var(--warning); }
  .alert ul { margin-top: 6px; padding-left: 16px; }
  .alert li { margin-top: 4px; }
  .field { margin-bottom: 16px; }
  .field label { display: block; font-size: 12px; font-weight: 500; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
  .input-wrap { position: relative; display: flex; align-items: center; }
  .input-wrap input {
    width: 100%; padding: 10px 38px 10px 12px; border: 0.5px solid var(--border);
    border-radius: var(--radius-sm); font-family: var(--mono); font-size: 13px;
    background: #FAFAF8; color: var(--text); outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
  }
  .input-wrap input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(26,78,143,0.1); background: #fff; }
  .eye-btn { position: absolute; right: 10px; background: none; border: none; cursor: pointer; padding: 0; color: var(--text-muted); display: flex; align-items: center; transition: color 0.15s; }
  .eye-btn:hover { color: var(--accent); }
  .strength-bar { height: 3px; border-radius: 2px; background: var(--border); margin-top: 6px; overflow: hidden; }
  .strength-fill { height: 100%; border-radius: 2px; transition: width 0.2s, background 0.2s; width: 0%; }
  .btn-submit {
    width: 100%; padding: 11px; background: var(--accent); color: #fff; border: none;
    border-radius: var(--radius-sm); font-family: var(--sans); font-size: 14px;
    font-weight: 500; cursor: pointer; transition: background 0.15s; margin-top: 4px;
  }
  .btn-submit:hover { background: var(--accent-hover); }
  .success-icon { width: 48px; height: 48px; background: var(--success-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
  .back-link { display: block; text-align: center; margin-top: 20px; font-size: 13px; color: var(--accent); text-decoration: none; }
  .back-link:hover { text-decoration: underline; }
  .page-footer { text-align: center; margin-top: 24px; font-size: 12px; color: var(--text-muted); }
</style>
</head>
<body>
<div class="wrap">

  <div class="header">
    <div class="logo-mark">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
        <rect x="3" y="3" width="8" height="8" rx="1.5" fill="#fff" opacity="0.9"/>
        <rect x="13" y="3" width="8" height="8" rx="1.5" fill="#fff" opacity="0.5"/>
        <rect x="3" y="13" width="8" height="8" rx="1.5" fill="#fff" opacity="0.5"/>
        <rect x="13" y="13" width="8" height="8" rx="1.5" fill="#D94032" opacity="0.9"/>
      </svg>
    </div>
    <div class="wordmark">SCP Central</div>
    <h1>Set new password</h1>
  </div>

  <div class="card">

    <?php if ($tokenError): ?>

      <div class="alert alert-warning"><?= htmlspecialchars($tokenError) ?></div>
      <a href="/reset_request.php" style="display:block;text-align:center;margin-top:4px;">
        <button type="button" class="btn-submit">Request a new link</button>
      </a>

    <?php elseif ($success): ?>

      <div style="text-align:center;padding:8px 0 4px;">
        <div class="success-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1A6B3C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <div style="font-size:16px;font-weight:600;margin-bottom:8px;">Password updated!</div>
        <div style="font-size:14px;color:var(--text-muted);margin-bottom:20px;">You can now sign in with your new password.</div>
        <a href="/login.php"><button type="button" class="btn-submit">Sign in</button></a>
      </div>

    <?php else: ?>

      <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px;">
        Setting password for <strong><?= htmlspecialchars($tokenUser['name']) ?></strong>
      </p>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <strong>Please fix the following:</strong>
          <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="/reset_password.php?token=<?= urlencode($rawToken) ?>" novalidate>

        <div class="field">
          <label>New password</label>
          <div class="input-wrap">
            <input type="password" id="pw" name="password"
                   autocomplete="new-password"
                   placeholder="Min <?= MIN_PASSWORD_LENGTH ?> characters"
                   oninput="updateStrength(this)">
            <button type="button" class="eye-btn" onclick="toggleSingle('pw','eye-pw')" title="Show/hide">
              <svg id="eye-pw" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
          <div class="strength-bar"><div class="strength-fill" id="strength-bar"></div></div>
        </div>

        <div class="field">
          <label>Confirm password</label>
          <div class="input-wrap">
            <input type="password" id="cfm" name="password_confirm"
                   autocomplete="new-password"
                   placeholder="Repeat password"
                   oninput="checkMatch()">
            <button type="button" class="eye-btn" onclick="toggleSingle('cfm','eye-cfm')" title="Show/hide">
              <svg id="eye-cfm" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-submit">Set new password</button>
      </form>

    <?php endif; ?>
  </div>

  <a href="/login.php" class="back-link">← Back to sign in</a>
  <div class="page-footer">SCP Central &mdash; Still Creek Press &copy; <?= date('Y') ?></div>

</div>

<script>
function updateStrength(input) {
    const pw  = input.value;
    const bar = document.getElementById('strength-bar');
    let score = 0;
    if (pw.length >= 12) score++;
    if (pw.length >= 16) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    const colours = ['#E55','#E55','#F5A623','#F5A623','#1A6B3C','#1A6B3C'];
    const widths  = ['0%','20%','40%','60%','80%','100%'];
    bar.style.width      = pw.length ? widths[score] : '0%';
    bar.style.background = colours[score];
}
function checkMatch() {
    const cfm = document.getElementById('cfm');
    const pw  = document.getElementById('pw');
    cfm.style.borderColor = (cfm.value.length > 0 && cfm.value !== pw.value) ? '#A32D2D' : '';
}
function toggleSingle(inputId, eyeId) {
    const input = document.getElementById(inputId);
    const eye   = document.getElementById(eyeId);
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    eye.innerHTML = show
        ? '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>'
        : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
}
</script>
</body>
</html>
