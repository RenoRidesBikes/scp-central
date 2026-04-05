<?php
// ============================================================
// SCP Central — Password reset request
// ============================================================

require_once __DIR__ . '/../includes/db.php';

define('RESET_TOKEN_EXPIRY', 60 * 60); // 1 hour

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

$submitted = false;
$error     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');

    if (empty($identifier)) {
        $error = 'Please enter your username or email address.';
    } else {
        // Always show success — never confirm whether an account exists
        $submitted = true;

        $db   = getDB();
        $stmt = $db->prepare("
            SELECT id, email, name FROM users
            WHERE (username = :id OR email = :id) AND is_active = true
            LIMIT 1
        ");
        $stmt->execute([':id' => $identifier]);
        $user = $stmt->fetch();

        if ($user) {
            // Invalidate any existing reset tokens for this user
            $db->prepare("DELETE FROM password_resets WHERE user_id = :uid")
               ->execute([':uid' => $user['id']]);

            // Generate token
            $token     = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expires   = time() + RESET_TOKEN_EXPIRY;

            $db->prepare("
                INSERT INTO password_resets (user_id, token_hash, expires_at)
                VALUES (:uid, :hash, TO_TIMESTAMP(:exp))
            ")->execute([':uid' => $user['id'], ':hash' => $tokenHash, ':exp' => $expires]);

            $resetUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/reset_password.php?token=' . $token;

            // ── TODO: SMTP ──────────────────────────────────────
            // Replace this block with your SMTP mailer when ready.
            // Send to: $user['email']
            // Subject: "SCP Central — Password Reset"
            // Body should include $resetUrl
            // Token expires in 1 hour.
            // ────────────────────────────────────────────────────
            error_log("PASSWORD RESET TOKEN for user {$user['id']} ({$user['email']}): {$resetUrl}");

            authLog('password_reset_requested', $user['id'], [
                'identifier' => $identifier,
                'expires_at' => date('Y-m-d H:i:s', $expires),
            ]);
        }
        // If user not found — we still show success (don't leak account existence)
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SCP Central — Reset Password</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #F4F2EE; --surface: #FFFFFF; --border: #E0DDD8;
    --text: #1A1A18; --text-muted: #6B6860;
    --accent: #1A4E8F; --accent-hover: #163F6E;
    --danger: #A32D2D; --danger-bg: #FCEBEB; --danger-border: #F7C1C1;
    --success: #1A6B3C; --success-bg: #EDFAF3; --success-border: #A3E6C3;
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
  .subtitle { font-size: 14px; color: var(--text-muted); margin-top: 6px; line-height: 1.5; }
  .card { background: var(--surface); border: 0.5px solid var(--border); border-radius: var(--radius); padding: 28px; }
  .alert { border-radius: var(--radius-sm); padding: 11px 14px; font-size: 13px; margin-bottom: 20px; line-height: 1.5; }
  .alert-error   { background: var(--danger-bg);  border: 0.5px solid var(--danger-border); color: var(--danger); }
  .alert-success { background: var(--success-bg); border: 0.5px solid var(--success-border); color: var(--success); }
  .field { margin-bottom: 20px; }
  .field label { display: block; font-size: 12px; font-weight: 500; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
  .field input {
    width: 100%; padding: 10px 12px; border: 0.5px solid var(--border); border-radius: var(--radius-sm);
    font-family: var(--sans); font-size: 14px; background: #FAFAF8; color: var(--text);
    outline: none; transition: border-color 0.15s, box-shadow 0.15s;
  }
  .field input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(26,78,143,0.1); background: #fff; }
  .btn-submit {
    width: 100%; padding: 11px; background: var(--accent); color: #fff; border: none;
    border-radius: var(--radius-sm); font-family: var(--sans); font-size: 14px;
    font-weight: 500; cursor: pointer; transition: background 0.15s;
  }
  .btn-submit:hover { background: var(--accent-hover); }
  .back-link { display: block; text-align: center; margin-top: 20px; font-size: 13px; color: var(--accent); text-decoration: none; }
  .back-link:hover { text-decoration: underline; }
  .success-icon { width: 48px; height: 48px; background: var(--success-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
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
    <h1>Reset password</h1>
    <?php if (!$submitted): ?>
      <p class="subtitle">Enter your username or email and we'll send you a reset link.</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <?php if ($submitted): ?>

      <div style="text-align:center;padding:8px 0 4px;">
        <div class="success-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1A6B3C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
          </svg>
        </div>
        <div style="font-size:16px;font-weight:600;margin-bottom:8px;">Check your email</div>
        <div style="font-size:14px;color:var(--text-muted);line-height:1.6;">
          If that username or email is in our system, a reset link is on its way.<br>
          The link expires in <strong>1 hour</strong>.
        </div>
      </div>

    <?php else: ?>

      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="field">
          <label for="identifier">Username or email</label>
          <input type="text"
                 id="identifier"
                 name="identifier"
                 value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
                 autocomplete="username"
                 autocapitalize="none"
                 spellcheck="false"
                 autofocus
                 required>
        </div>
        <button type="submit" class="btn-submit">Send reset link</button>
      </form>

    <?php endif; ?>
  </div>

  <a href="/login.php" class="back-link">← Back to sign in</a>
  <div class="page-footer">SCP Central &mdash; Still Creek Press &copy; <?= date('Y') ?></div>

</div>
</body>
</html>
