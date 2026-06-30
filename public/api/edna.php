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
    error_log('edna.php: DB error loading prompts: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'DB error loading prompts']);
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

NCR type reference — use these exact strings for ncr_type:
- CB / CF (2-part)
- CB / CFB / CF (3-part)
- CB / CFB / CFB / CF (4-part)
- N/A — for non-NCR jobs (continuous forms, sheetfed, single-part)
Always set ncr_type to N/A for continuous forms jobs.
Infer NCR type from part count if not explicitly stated: 2-part = CB/CF, 3-part = CB/CFB/CF, 4-part = CB/CFB/CFB/CF.

Finishing operations reference — these are the only valid finishing operations:
- perforation: cuts in paper for tearing. Common on snap sets and continuous forms. Always specify location e.g. top, bottom, left, right, centre.
- padding: sets glued at the head. Common on snap sets. Specify set size e.g. 25, 50, 100. Treat "padded", "pads of", "glued in sets", "books of" as padding.
- collating: interleaving NCR plies in correct order. ALWAYS include for any multi-part NCR job — it is automatic and non-negotiable.
- numbering: sequential number printed on each set. Include only if mentioned or clearly implied.
- drilling: hole punching. Include only if mentioned.
- shrink wrap: individual pad wrapping. Include only if mentioned.

For the finishing field return a plain English string listing all applicable operations with relevant detail e.g. "perforation top, padding sets of 50, collating (3-part NCR)".

Quantity break defaults — use these if not specified in the description:
- snap set: 5000, 10000, 25000
- continuous: 10000, 25000, 50000
- sheetfed: 500, 1000, 2500

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
  "press": "number as string e.g. 3, or null",
  "press_reason": "string — one sentence why this press, or null",
  "quantities": [1000, 2500, 5000],
  "edna_note": "string — one sentence, plain language, flag anything unusual or missing"
}';

$system_prompt = implode("\n\n", $system_parts);

// ── RESOLVE ACTIVE MODEL ──────────────────────────────────────────────────────
// Model is admin-configurable via the Settings page (app_settings table).
// Fall back to the ANTHROPIC_MODEL constant from secrets.php if the row is
// missing/empty — degrade to last-known-good rather than break Edna.
// API key/URL/version stay in secrets.php; only the model name lives in the DB.
$model = ANTHROPIC_MODEL;
try {
    $stmt = $pdo->prepare("SELECT value FROM app_settings WHERE key = 'anthropic_model'");
    $stmt->execute();
    $dbModel = $stmt->fetchColumn();
    if (is_string($dbModel) && $dbModel !== '') {
        $model = $dbModel;
    }
} catch (Exception $e) {
    // Non-fatal — log and keep the constant fallback.
    error_log('edna.php: could not read anthropic_model setting, using fallback: ' . $e->getMessage());
}

// ── CALL ANTHROPIC ────────────────────────────────────────────────────────────
$anthropic_payload = [
    'model'      => $model,
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
    error_log('edna.php: cURL error reaching Anthropic API: ' . $curl_error);
    http_response_code(502);
    echo json_encode(['error' => 'Could not reach Anthropic API']);
    exit;
}

// ── RETURN RESPONSE + PROMPT VERSION IDS ─────────────────────────────────────
// Decode Anthropic response, inject prompt_version_ids, re-encode
$anthropic_data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log('edna.php: invalid JSON from Anthropic (HTTP ' . $http_code . '): ' . substr((string) $response, 0, 500));
    http_response_code(502);
    echo json_encode(['error' => 'Invalid JSON from Anthropic']);
    exit;
}

// Anthropic returns a 4xx/5xx with a JSON error body for things like a retired
// model string (404 not_found_error) or a bad key (401). Without this check the
// error body would be passed straight through to the client, surfacing as a
// confusing client-side failure rather than a logged server-side one.
// Log the real detail; return a generic message so internals don't leak.
if ($http_code >= 400 || isset($anthropic_data['error'])) {
    $detail = $anthropic_data['error']['message']
        ?? $anthropic_data['error']['type']
        ?? ('HTTP ' . $http_code);
    error_log('edna.php: Anthropic API error (HTTP ' . $http_code . '): ' . $detail);
    http_response_code(502);
    echo json_encode(['error' => 'Edna could not process this request']);
    exit;
}

// Attach prompt_version_ids so frontend can pass them to save_quote.php
$anthropic_data['prompt_version_ids'] = $prompt_version_ids;

http_response_code($http_code);
echo json_encode($anthropic_data);
