<?php
// ============================================================
// SCP Central — Logout
// ============================================================

require_once __DIR__ . '/../includes/db.php';

define('REMEMBER_COOKIE', 'scp_remember');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'secure' => true,
        'httponly' => true, 'samesite' => 'Lax',
    ]);
    session_start();
}

$userId = $_SESSION['user_id'] ?? null;
$reason = $_GET['reason'] ?? 'manual';

// Log the event
if ($userId) {
    try {
        $db   = getDB();
        $stmt = $db->prepare("
            INSERT INTO auth_log (user_id, event, ip, user_agent, details)
            VALUES (:uid, :event, :ip, :ua, :details)
        ");
        $stmt->execute([
            ':uid'     => $userId,
            ':event'   => 'logout',
            ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
            ':ua'      => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ':details' => json_encode(['reason' => $reason]),
        ]);
    } catch (Exception $e) {
        error_log('logout log failed: ' . $e->getMessage());
    }
}

// Clear remember token from DB
if ($userId) {
    try {
        $db = getDB();
        $db->prepare("DELETE FROM remember_tokens WHERE user_id = :uid")
           ->execute([':uid' => $userId]);
    } catch (Exception $e) {
        error_log('remember token clear failed: ' . $e->getMessage());
    }
}

// Destroy session
session_unset();
session_destroy();

// Clear remember cookie
setcookie(REMEMBER_COOKIE, '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);

$msg = $reason === 'timeout' ? '?reason=timeout' : '';
header('Location: /login.php' . $msg);
exit;
