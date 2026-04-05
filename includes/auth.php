<?php
// ============================================================
// SCP Central — Auth middleware
// Include at the top of every protected page:
//   require_once __DIR__ . '/../includes/auth.php';
// Then call sessionTimeoutScript() inside your <head> tag.
// ============================================================

require_once __DIR__ . '/db.php';

// ── SESSION CONFIG ───────────────────────────────────────────
define('SESSION_DURATION',    8 * 60 * 60);   // 8 hours in seconds
define('SESSION_WARN_BEFORE', 15 * 60);        // warn 15 min before expiry
define('REMEMBER_DURATION',   30 * 24 * 60 * 60); // 30 days
define('REMEMBER_COOKIE',     'scp_remember');
define('MAX_FAILED_ATTEMPTS', 5);
define('LOCKOUT_DURATION',    30 * 60);        // 30 minutes

// ── START SESSION ────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
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
        // Non-fatal — log to error_log but don't break the page
        error_log('auth_log write failed: ' . $e->getMessage());
    }
}

function getUserById(int $id): ?array {
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.username, u.email, u.role_id,
               u.force_password_change, u.is_active,
               r.name AS role
        FROM users u
        JOIN roles r ON r.id = u.role_id
        WHERE u.id = :id
    ");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

function clearRememberCookie(): void {
    setcookie(REMEMBER_COOKIE, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function loginFromRememberToken(): ?array {
    $cookie = $_COOKIE[REMEMBER_COOKIE] ?? '';
    if (empty($cookie)) return null;

    // Cookie format: userId:token
    $parts = explode(':', $cookie, 2);
    if (count($parts) !== 2) return null;

    [$userId, $token] = $parts;
    $userId = (int) $userId;
    if ($userId < 1 || empty($token)) return null;

    $tokenHash = hash('sha256', $token);

    $db   = getDB();
    $stmt = $db->prepare("
        SELECT id FROM remember_tokens
        WHERE user_id = :uid
          AND token_hash = :hash
          AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([':uid' => $userId, ':hash' => $tokenHash]);
    $row = $stmt->fetch();
    if (!$row) return null;

    $user = getUserById($userId);
    if (!$user || !$user['is_active']) return null;

    return $user;
}

function startAuthSession(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']      = $user['id'];
    $_SESSION['user_name']    = $user['name'];
    $_SESSION['user_role']    = $user['role'];
    $_SESSION['role_id']      = $user['role_id'];
    $_SESSION['last_active']  = time();
    $_SESSION['session_start'] = time();
}

// ── ENFORCE AUTH ─────────────────────────────────────────────

$_AUTH_USER = null;

// Check active session
if (!empty($_SESSION['user_id'])) {
    $lastActive = $_SESSION['last_active'] ?? 0;
    if ((time() - $lastActive) > SESSION_DURATION) {
        // Session expired
        $uid = $_SESSION['user_id'];
        session_unset();
        session_destroy();
        clearRememberCookie();
        authLog('session_expired', $uid);
        header('Location: /login.php?reason=timeout');
        exit;
    }
    // Refresh activity timestamp
    $_SESSION['last_active'] = time();
    $_AUTH_USER = getUserById((int) $_SESSION['user_id']);

} else {
    // No session — try remember me cookie
    $rememberedUser = loginFromRememberToken();
    if ($rememberedUser) {
        startAuthSession($rememberedUser);
        $_AUTH_USER = $rememberedUser;
        authLog('login_remember', $rememberedUser['id']);
    } else {
        clearRememberCookie();
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: /login.php?redirect=' . $redirect);
        exit;
    }
}

// Guard — user was deleted or deactivated mid-session
if (!$_AUTH_USER || !$_AUTH_USER['is_active']) {
    session_unset();
    session_destroy();
    clearRememberCookie();
    header('Location: /login.php?reason=inactive');
    exit;
}

// Force password change
if ($_AUTH_USER['force_password_change'] &&
    !str_ends_with($_SERVER['PHP_SELF'], 'change_password.php')) {
    header('Location: /change_password.php');
    exit;
}

// ── SESSION TIMEOUT SCRIPT ───────────────────────────────────
// Call sessionTimeoutScript() inside your page <head> tag.

function sessionTimeoutScript(): void {
    $warnAt   = SESSION_DURATION - SESSION_WARN_BEFORE; // seconds until warning
    $expireAt = SESSION_DURATION;                        // seconds until hard logout
    echo <<<HTML
<script>
(function() {
    const WARN_MS    = {$warnAt}000;
    const EXPIRE_MS  = {$expireAt}000;
    let warnTimer, expireTimer, countdown;
    let lastActivity = Date.now();

    function resetTimers() {
        clearTimeout(warnTimer);
        clearTimeout(expireTimer);
        clearInterval(countdown);
        hideModal();
        lastActivity = Date.now();
        warnTimer   = setTimeout(showWarning, WARN_MS);
        expireTimer = setTimeout(forceLogout, EXPIRE_MS);
    }

    function showWarning() {
        const modal = document.getElementById('scp-session-modal');
        const counter = document.getElementById('scp-session-counter');
        if (!modal) return;
        let secs = Math.floor((EXPIRE_MS - (Date.now() - lastActivity)) / 1000);
        modal.style.display = 'flex';
        counter.textContent = formatTime(secs);
        countdown = setInterval(function() {
            secs--;
            if (secs <= 0) { clearInterval(countdown); return; }
            counter.textContent = formatTime(secs);
        }, 1000);
    }

    function hideModal() {
        const modal = document.getElementById('scp-session-modal');
        if (modal) modal.style.display = 'none';
    }

    function formatTime(s) {
        const m = Math.floor(s / 60);
        const r = s % 60;
        return m + ':' + String(r).padStart(2, '0');
    }

    function keepAlive() {
        fetch('/api/ping.php', { method: 'POST', credentials: 'same-origin' })
            .catch(function(){});
        resetTimers();
    }

    function autoSaveAndLogout() {
        if (typeof scpAutoSave === 'function') {
            scpAutoSave(function() { window.location = '/logout.php?reason=timeout'; });
        } else {
            window.location = '/logout.php?reason=timeout';
        }
    }

    function forceLogout() {
        clearInterval(countdown);
        autoSaveAndLogout();
    }

    // Expose keep-alive for "Stay logged in" button
    window.scpKeepAlive = keepAlive;
    window.scpAutoSaveAndLogout = autoSaveAndLogout;

    // Reset on user activity
    ['click','keydown','mousemove','touchstart'].forEach(function(e) {
        document.addEventListener(e, function() {
            if (Date.now() - lastActivity > 60000) resetTimers(); // only ping if idle >1min
        }, { passive: true });
    });

    resetTimers();
})();
</script>
HTML;
}

// ── SESSION MODAL HTML ───────────────────────────────────────
// Call sessionModal() just before </body> on every protected page.

function sessionModal(): void {
    echo <<<HTML
<!-- SCP Session Timeout Modal -->
<div id="scp-session-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;font-family:'IBM Plex Sans',sans-serif;">
  <div style="background:#fff;border-radius:10px;padding:32px 36px;max-width:400px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,0.2);text-align:center;">
    <div style="width:48px;height:48px;background:#FFF8EC;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#854F0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
      </svg>
    </div>
    <div style="font-size:17px;font-weight:600;color:#1a1a18;margin-bottom:8px;">Still there?</div>
    <div style="font-size:14px;color:#6B6860;margin-bottom:6px;">Your session expires in</div>
    <div id="scp-session-counter" style="font-family:'IBM Plex Mono',monospace;font-size:32px;font-weight:600;color:#1B4F8A;margin-bottom:20px;">15:00</div>
    <div style="display:flex;gap:10px;justify-content:center;">
      <button onclick="window.scpAutoSaveAndLogout()"
              style="padding:9px 20px;border-radius:6px;border:1px solid #E2DDD6;background:#fff;font-size:14px;cursor:pointer;font-family:'IBM Plex Sans',sans-serif;color:#6B6860;">
        Save &amp; Log out
      </button>
      <button onclick="window.scpKeepAlive()"
              style="padding:9px 20px;border-radius:6px;border:none;background:#1B4F8A;color:#fff;font-size:14px;font-weight:500;cursor:pointer;font-family:'IBM Plex Sans',sans-serif;">
        Keep me logged in
      </button>
    </div>
  </div>
</div>
HTML;
}
