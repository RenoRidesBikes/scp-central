<?php
// ============================================================
// SCP Central — Login
// ============================================================

require_once __DIR__ . '/../../includes/db.php';

define('BCRYPT_COST',        12);
define('MAX_FAILED_ATTEMPTS', 5);
define('LOCKOUT_DURATION',    30 * 60);
define('REMEMBER_DURATION',   30 * 24 * 60 * 60);
define('REMEMBER_COOKIE',     'scp_remember');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'secure' => true,
        'httponly' => true, 'samesite' => 'Lax',
    ]);
    session_start();
}

// Already logged in — redirect home
if (!empty($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

// ── HELPERS ─────────────────────────────────────────────────

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

function findUser(string $identifier): ?array {
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.username, u.email, u.password_hash,
               u.failed_attempts, u.locked_until, u.is_active,
               u.force_password_change, u.role_id,
               r.name AS role
        FROM users u
        JOIN roles r ON r.id = u.role_id
        WHERE u.username = :id OR u.email = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $identifier]);
    return $stmt->fetch() ?: null;
}

function setRememberCookie(int $userId): void {
    $token     = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expires   = time() + REMEMBER_DURATION;

    $db   = getDB();
    // Clean up old tokens for this user
    $db->prepare("DELETE FROM remember_tokens WHERE user_id = :uid")
       ->execute([':uid' => $userId]);

    $stmt = $db->prepare("
        INSERT INTO remember_tokens (user_id, token_hash, expires_at)
        VALUES (:uid, :hash, TO_TIMESTAMP(:exp))
    ");
    $stmt->execute([':uid' => $userId, ':hash' => $tokenHash, ':exp' => $expires]);

    setcookie(REMEMBER_COOKIE, $userId . ':' . $token, [
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// ── HANDLE POST ──────────────────────────────────────────────
$error    = '';
$redirect = $_GET['redirect'] ?? '/index.php';
$reason   = $_GET['reason']   ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password']   ?? '';
    $remember   = !empty($_POST['remember']);

    if (empty($identifier) || empty($password)) {
        $error = 'Please enter your username and password.';
    } else {
        $user = findUser($identifier);

        // Generic error — never tell them which field was wrong
        $genericError = 'Incorrect username or password.';

        if (!$user || !$user['is_active']) {
            // Still log the attempt even if user not found
            authLog('login_failed', null, ['identifier' => $identifier, 'reason' => 'user_not_found']);
            $error = $genericError;

        } elseif ($user['locked_until'] !== null &&
                  strtotime($user['locked_until']) > time()) {
            $unlockTime = date('g:i a', strtotime($user['locked_until']));
            authLog('login_blocked', $user['id'], ['reason' => 'locked']);
            $error = "This account is temporarily locked. Try again after {$unlockTime}.";

        } elseif (!password_verify($password, $user['password_hash'])) {
            $attempts = (int)$user['failed_attempts'] + 1;
            $db = getDB();

            if ($attempts >= MAX_FAILED_ATTEMPTS) {
                $lockedUntil = date('Y-m-d H:i:s', time() + LOCKOUT_DURATION);
                $db->prepare("
                    UPDATE users SET failed_attempts = :a, locked_until = :lu WHERE id = :id
                ")->execute([':a' => $attempts, ':lu' => $lockedUntil, ':id' => $user['id']]);
                authLog('account_locked', $user['id'], ['attempts' => $attempts]);
                $unlockTime = date('g:i a', time() + LOCKOUT_DURATION);
                $error = "Too many failed attempts. Account locked until {$unlockTime}.";
            } else {
                $db->prepare("
                    UPDATE users SET failed_attempts = :a WHERE id = :id
                ")->execute([':a' => $attempts, ':id' => $user['id']]);
                authLog('login_failed', $user['id'], ['attempts' => $attempts]);
                $remaining = MAX_FAILED_ATTEMPTS - $attempts;
                $error = $genericError .
                    ($remaining === 1 ? ' 1 attempt remaining before lockout.' :
                    " {$remaining} attempts remaining before lockout.");
            }

        } else {
            // ✅ Successful login
            $db = getDB();
            $db->prepare("
                UPDATE users SET failed_attempts = 0, locked_until = NULL, last_login = NOW()
                WHERE id = :id
            ")->execute([':id' => $user['id']]);

            session_regenerate_id(true);
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['user_name']     = $user['name'];
            $_SESSION['user_role']     = $user['role'];
            $_SESSION['role_id']       = $user['role_id'];
            $_SESSION['last_active']   = time();
            $_SESSION['session_start'] = time();

            if ($remember) {
                setRememberCookie($user['id']);
            }

            authLog('login_success', $user['id']);

            // Force password change if flagged
            if ($user['force_password_change']) {
                header('Location: /change_password.php');
                exit;
            }

            // Safe redirect — only allow relative paths
            $dest = filter_var($redirect, FILTER_VALIDATE_URL) ? '/index.php' : $redirect;
            header('Location: ' . $dest);
            exit;
        }
    }
}

$reasonMessages = [
    'timeout'  => 'Your session expired. Please log in again.',
    'inactive' => 'Your account has been deactivated. Contact your administrator.',
];
$infoMessage = $reasonMessages[$reason] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SCP Central — Sign In</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:           #F4F2EE;
    --surface:      #FFFFFF;
    --border:       #E0DDD8;
    --text:         #1A1A18;
    --text-muted:   #6B6860;
    --accent:       #1A4E8F;
    --accent-hover: #163F6E;
    --accent-light: #E6F1FB;
    --danger:       #A32D2D;
    --danger-bg:    #FCEBEB;
    --danger-border:#F7C1C1;
    --warning:      #854F0B;
    --warning-bg:   #FAEEDA;
    --info-bg:      #EBF1F9;
    --info-border:  #B5D4F4;
    --mono:         'IBM Plex Mono', monospace;
    --sans:         'IBM Plex Sans', sans-serif;
    --radius:       10px;
    --radius-sm:    6px;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: var(--sans);
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px 16px;
  }

  .wrap {
    width: 100%;
    max-width: 400px;
  }

  /* ── HEADER ── */
  .header {
    text-align: center;
    margin-bottom: 32px;
  }

  .logo-mark {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: #1c1c1e;
    border-radius: 12px;
    margin-bottom: 16px;
  }

  .wordmark {
    font-family: var(--mono);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 6px;
  }

  h1 {
    font-size: 22px;
    font-weight: 600;
    color: var(--text);
  }

  /* ── CARD ── */
  .card {
    background: var(--surface);
    border: 0.5px solid var(--border);
    border-radius: var(--radius);
    padding: 28px 28px 24px;
  }

  /* ── ALERTS ── */
  .alert {
    border-radius: var(--radius-sm);
    padding: 11px 14px;
    font-size: 13px;
    margin-bottom: 20px;
    line-height: 1.5;
  }
  .alert-error   { background: var(--danger-bg);  border: 0.5px solid var(--danger-border); color: var(--danger); }
  .alert-info    { background: var(--info-bg);    border: 0.5px solid var(--info-border);   color: var(--accent); }
  .alert-warning { background: var(--warning-bg); border: 0.5px solid #FAC775;              color: var(--warning);}

  /* ── FIELDS ── */
  .field {
    margin-bottom: 16px;
  }

  .field label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 6px;
  }

  .input-wrap {
    position: relative;
    display: flex;
    align-items: center;
  }

  .input-wrap input {
    width: 100%;
    padding: 10px 12px;
    border: 0.5px solid var(--border);
    border-radius: var(--radius-sm);
    font-family: var(--sans);
    font-size: 14px;
    background: #FAFAF8;
    color: var(--text);
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
  }

  .input-wrap input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(26,78,143,0.1);
    background: #fff;
  }

  .input-wrap input.has-toggle { padding-right: 38px; }

  .eye-btn {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    transition: color 0.15s;
  }
  .eye-btn:hover { color: var(--accent); }

  /* ── REMEMBER / FORGOT ROW ── */
  .meta-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    margin-top: -4px;
  }

  .remember-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--text-muted);
    cursor: pointer;
    user-select: none;
  }

  .remember-label input[type="checkbox"] {
    width: 15px;
    height: 15px;
    accent-color: var(--accent);
    cursor: pointer;
  }

  .forgot-link {
    font-size: 13px;
    color: var(--accent);
    text-decoration: none;
  }
  .forgot-link:hover { text-decoration: underline; }

  /* ── SUBMIT ── */
  .btn-submit {
    width: 100%;
    padding: 11px;
    background: var(--accent);
    color: #fff;
    border: none;
    border-radius: var(--radius-sm);
    font-family: var(--sans);
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.15s, transform 0.1s;
  }
  .btn-submit:hover  { background: var(--accent-hover); }
  .btn-submit:active { transform: translateY(1px); }

  /* ── FOOTER ── */
  .page-footer {
    text-align: center;
    margin-top: 24px;
    font-size: 12px;
    color: var(--text-muted);
  }
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
    <h1>Sign in</h1>
  </div>

  <div class="card">

    <?php if ($infoMessage): ?>
      <div class="alert alert-warning"><?= htmlspecialchars($infoMessage) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/login.php<?= $redirect !== '/index.php' ? '?redirect=' . urlencode($redirect) : '' ?>" novalidate>

      <div class="field">
        <label for="identifier">Username or email</label>
        <div class="input-wrap">
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
      </div>

      <div class="field">
        <label for="password">Password</label>
        <div class="input-wrap">
          <input type="password"
                 id="password"
                 name="password"
                 class="has-toggle"
                 autocomplete="current-password"
                 required>
          <button type="button" class="eye-btn" onclick="togglePw()" title="Show/hide password">
            <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>

      <div class="meta-row">
        <label class="remember-label">
          <input type="checkbox" name="remember" <?= !empty($_POST['remember']) ? 'checked' : '' ?>>
          Keep me signed in
        </label>
        <a href="/reset_request.php" class="forgot-link">Forgot password?</a>
      </div>

      <button type="submit" class="btn-submit">Sign in</button>

    </form>
  </div>

  <div class="page-footer">SCP Central &mdash; Still Creek Press &copy; <?= date('Y') ?></div>

</div>

<script>
function togglePw() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eye-icon');
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    icon.innerHTML = show
        ? '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>'
        : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
}
</script>
</body>
</html>
