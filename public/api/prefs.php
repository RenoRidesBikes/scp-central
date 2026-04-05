<?php
/**
 * SCP Central — Dashboard widget prefs endpoint
 * POST /api/prefs.php
 *
 * Body (JSON): { "hidden": { "my_queue": true, "win_rate": false, ... } }
 * Response:    { "ok": true } | { "error": "..." }
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$userId = $_SESSION['user']['id'];
$body   = json_decode(file_get_contents('php://input'), true);

if (!isset($body['hidden']) || !is_array($body['hidden'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload — expected { hidden: { widgetKey: bool } }']);
    exit;
}

// Allowlist — only known widget keys accepted, never trust client input
$allowed = ['new_quote', 'my_queue', 'aged_quotes', 'team_pipeline', 'win_rate', 'revenue'];
$hidden  = [];
foreach ($allowed as $key) {
    $hidden[$key] = (bool) ($body['hidden'][$key] ?? false);
}

$prefs = json_encode(['hidden' => $hidden]);

$stmt = $pdo->prepare('UPDATE users SET dashboard_prefs = :prefs WHERE id = :id');
$stmt->execute([':prefs' => $prefs, ':id' => $userId]);

echo json_encode(['ok' => true]);
