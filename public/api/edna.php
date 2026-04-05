<?php
/**
 * /api/edna.php
 *
 * Edna AI orchestrator.
 * - Loads system prompt layers from edna_prompts table
 * - Composes: base + module + job_type (when known)
 * - Forwards to Anthropic API
 * - Returns structured JSON response + prompt_version_ids used
 *
 * Does NOT write to DB — that is save_quote.php's job.
 */

declare(strict_types=1);

require_once '/var/www/secrets.php';
require_once __DIR__ . '/../../includes/db.php';

// ── CORS ──────────────────────────────────────────────────────────────────────
// TODO: hardcoded — move allowed origins to DB config table
$allowed_origins = [
    'https://scp.stepsolutionsai.online',
    'http://localhost',
    'http://localhost:8080',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// No origin header = same-origin or direct server request — always allow
if ($origin === '') {
    // same-origin, no header needed
} elseif (in_array($origin, $allowed_origins)) {
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

// ── REQUEST ───────────────────────────────────────────────────────────────────
$body    = file_get_contents('php://input');
$payload = json_decode($body, true);

if (!$payload || !isset($payload['messages'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request body']);
    exit;
}

$module   = $payload['module']   ?? null;   // e.g. forms_estimating
$job_type = $payload['job_type'] ?? null;   // e.g. forms_snap_set — optional on first parse

if (!$module) {
    http_response_code(400);
    echo json_encode(['error' => 'module is required']);
    exit;
}

// ── LOAD PROMPT LAYERS FROM DB ────────────────────────────────────────────────
try {
    $pdo = getDB();

    // Always load: base (global) + module layer
    $layers_to_load = [
        ['layer' => 'base',     'module' => 'global'],
        ['layer' => 'module',   'module' => $module],
    ];

    // Add job_type layer if we already know it (second pass / clarification round)
    if ($job_type) {
        $layers_to_load[] = ['layer' => 'job_type', 'module' => $job_type];
    }

    // Build IN clause dynamically
    $placeholders = [];
    $params       = [];
    foreach ($layers_to_load as $i => $l) {
        $placeholders[] = "(:layer_{$i}, :module_{$i})";
        $params["layer_{$i}"]  = $l['layer'];
        $params["module_{$i}"] = $l['module'];
    }

    $in_clause = implode(', ', $placeholders);

    $stmt = $pdo->prepare("
        SELECT id, layer, module, content
        FROM edna_prompts
        WHERE (layer, module) IN ($in_clause)
          AND is_active = true
        ORDER BY
            CASE layer
                WHEN 'base'     THEN 1
                WHEN 'module'   THEN 2
                WHEN 'job_type' THEN 3
            END
    ");
    $stmt->execute($params);
    $prompt_rows = $stmt->fetchAll();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error loading prompts', 'detail' => $e->getMessage()]);
    exit;
}

if (empty($prompt_rows)) {
    http_response_code(500);
    echo json_encode(['error' => 'No active prompts found for module: ' . $module]);
    exit;
}

// ── COMPOSE SYSTEM PROMPT ─────────────────────────────────────────────────────
$system_parts       = [];
$prompt_version_ids = [];

foreach ($prompt_rows as $row) {
    $system_parts[]       = $row['content'];
    $prompt_version_ids[] = (int) $row['id'];
}

// Append the JSON output contract — always last
// TODO: hardcoded JSON structure — move field definitions to DB per job type
$system_parts[] = '
Return ONLY valid JSON, no markdown, no explanation.

Known customers — ONLY return a customer value if the job description clearly matches one of these names exactly. If the customer is not in this list or is unclear, return null:
- BCAA
- Telus
- BC Hydro
- City of Burnaby
- TransLink
- ICBC
- WorkSafeBC
- Fortis BC
- Vancouver Coastal Health
- Fraser Health

Press reference — Still Creek Press equipment:
Press 1: MVP Memjet, 11" cutoff — rarely used for forms work
Press 2: Didde, 17" web — 1-2 colour narrow web
Press 3: Didde, 22" web, 5 colour — primary forms press, best for most snap set and continuous work
Press 4: MVP, 14" cutoff — short run specialist
Press 5: Didde, 17" web — backup to Press 2, narrow web 1-2 colour
Press 11: Didde, 22" web, 8 colour — full colour jobs only

Return this exact structure:
{
  "customer": "string or null",
  "job_name": "string",
  "job_type": "continuous | snap_set | sheetfed",
  "job_type_confidence": "confirmed | suggested",
  "width": "string or null",
  "width_confidence": "confirmed | suggested | missing",
  "depth": "string or null",
  "depth_confidence": "confirmed | suggested | missing",
  "parts": "string or null",
  "parts_confidence": "confirmed | suggested | missing",
  "ncr_type": "string or null",
  "ncr_type_confidence": "confirmed | suggested | missing",
  "stock": "string or null",
  "stock_confidence": "confirmed | suggested | missing",
  "ink_front": "string or null",
  "ink_front_confidence": "confirmed | suggested | missing",
  "ink_back": "string or null",
  "ink_back_confidence": "confirmed | suggested | missing",
  "perforation": "string or null",
  "perforation_confidence": "confirmed | suggested | missing",
  "finishing": "string or null",
  "finishing_confidence": "confirmed | suggested | missing",
  "press": "number as string e.g. '3', or null",
  "press_reason": "string — one sentence why this press, or null",
  "quantities": [1000, 2500, 5000],
  "edna_note": "string — one sentence, plain language, flag anything unusual or missing"
}';

$system_prompt = implode("\n\n", $system_parts);

// ── CALL ANTHROPIC ────────────────────────────────────────────────────────────
$anthropic_payload = [
    'model'      => ANTHROPIC_MODEL,
    'max_tokens' => min((int)($payload['max_tokens'] ?? 1500), 2000),
    'system'     => $system_prompt,
    'messages'   => $payload['messages'],
];

$ch = curl_init(ANTHROPIC_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($anthropic_payload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: '          . ANTHROPIC_API_KEY,
        'anthropic-version: '  . ANTHROPIC_VERSION,
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

// ── RETURN RESPONSE + PROMPT VERSION IDS ─────────────────────────────────────
// Decode Anthropic response, inject prompt_version_ids, re-encode
$anthropic_data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid JSON from Anthropic']);
    exit;
}

// Attach prompt_version_ids so frontend can pass them to save_quote.php
$anthropic_data['prompt_version_ids'] = $prompt_version_ids;

http_response_code($http_code);
echo json_encode($anthropic_data);
