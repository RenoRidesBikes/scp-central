<?php
/**
 * /api/save_settings.php
 *
 * Persists admin settings to the app_settings table.
 * Gated by RBAC: requires page:settings WRITE (super_admin via '*' wildcard).
 *
 * Currently handles: anthropic_model (validated against anthropic_models).
 * Add new settings here as additional validated branches.
 *
 * Validation note: the chosen model MUST exist and be active in
 * anthropic_models — this is the guardrail that stops a retired/invalid
 * model string from ever being saved (the not_found_error class of bug).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permissions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$userId = (int) $_AUTH_USER['id'];

if (!hasPermission($userId, 'page:settings', PERM_WRITE)) {
    http_response_code(403);
    echo json_encode(['error' => 'You don\'t have permission to change settings']);
    exit;
}

// ── Parse body ────────────────────────────────────────────────────────────────
$body    = file_get_contents('php://input');
$payload = json_decode($body, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request body']);
    exit;
}

// ── Handle: anthropic_model ───────────────────────────────────────────────────
if (!array_key_exists('anthropic_model', $payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'No recognised setting in request']);
    exit;
}

$model = trim((string) $payload['anthropic_model']);

try {
    $db = getDB();

    // Guardrail: only allow models that are active in the curated list.
    $stmt = $db->prepare('SELECT 1 FROM anthropic_models WHERE model_id = :m AND is_active = true');
    $stmt->execute([':m' => $model]);
    if (!$stmt->fetchColumn()) {
        http_response_code(422);
        echo json_encode(['error' => 'That model is not an available option']);
        exit;
    }

    // Upsert the setting, stamping who changed it and when.
    $stmt = $db->prepare("
        INSERT INTO app_settings (key, value, type, updated_at, updated_by)
        VALUES ('anthropic_model', :val, 'enum', NOW(), :uid)
        ON CONFLICT (key) DO UPDATE
            SET value = EXCLUDED.value,
                updated_at = NOW(),
                updated_by = EXCLUDED.updated_by
    ");
    $stmt->execute([':val' => $model, ':uid' => $userId]);

} catch (Exception $e) {
    error_log('save_settings.php: DB error saving anthropic_model: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Could not save setting']);
    exit;
}

echo json_encode(['saved' => true, 'anthropic_model' => $model]);
