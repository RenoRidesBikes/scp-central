<?php
/**
 * edna.php — Anthropic API proxy for SCP Central
 * Place at: /home/ssaiadmin/scp-stack/php/api/edna.php
 *
 * SECURITY: API key lives at /var/config/secrets.php
 * That file is outside the web root and never touches Git.
 */

// ── SECRETS ─────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../config/secrets.php';

define('ANTHROPIC_API_URL', 'https://api.anthropic.com/v1/messages');
define('ANTHROPIC_VERSION', '2023-06-01');

// ── ALLOWED ORIGINS ──────────────────────────────────────────────────────────
$allowed_origins = [
    'https://scp.stepsolutionsai.online',
    'http://localhost',
    'http://127.0.0.1',
    'null',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    http_response_code(403);
    echo json_encode(['error' => 'Origin not allowed']);
    exit;
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body    = file_get_contents('php://input');
$payload = json_decode($body, true);

if (!$payload || !isset($payload['messages'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request body']);
    exit;
}

$payload['model']      = 'claude-sonnet-4-20250514';
$payload['max_tokens'] = min((int)($payload['max_tokens'] ?? 1500), 2000);

$ch = curl_init(ANTHROPIC_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . ANTHROPIC_API_KEY,
        'anthropic-version: ' . ANTHROPIC_VERSION,
    ],
    CURLOPT_TIMEOUT => 30,
]);

$response   = curl_exec($ch);
$http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    http_response_code(502);
    echo json_encode(['error' => 'Could not reach Anthropic API', 'detail' => $curl_error]);
    exit;
}

http_response_code($http_code);
echo $response;
