<?php
/**
 * /public/api/save_quote.php
 *
 * Creates a new quote record on CSR save.
 * - Writes quote fields to quotes table
 * - Writes edna_analysis JSONB (Edna's original parse)
 * - Compares Edna's suggestions vs CSR's saved values
 * - Writes deltas to edna_field_corrections
 *
 * Returns: { quote_id: int }
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/secrets.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ── REQUEST ───────────────────────────────────────────────────────────────────
$body    = file_get_contents('php://input');
$payload = json_decode($body, true);

if (!$payload) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request body']);
    exit;
}

// Required fields
$required = ['module', 'job_type', 'edna_analysis', 'prompt_version_ids', 'saved_fields'];
foreach ($required as $field) {
    if (!isset($payload[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: {$field}"]);
        exit;
    }
}

$module              = $payload['module'];
$job_type            = $payload['job_type'];
$edna_analysis       = $payload['edna_analysis'];       // Edna's original parse — full JSON object
$prompt_version_ids  = $payload['prompt_version_ids'];  // array of int — which prompts fired
$saved_fields        = $payload['saved_fields'];         // what the CSR actually saved
$customer_code       = $payload['customer_code']  ?? null;
$customer_name       = $payload['customer_name']  ?? null;
$job_name            = $payload['job_name']        ?? null;

// Use primary prompt version id (first in array = base, last = most specific)
$prompt_version_id   = !empty($prompt_version_ids) ? (int) end($prompt_version_ids) : null;

// ── INSERT QUOTE ──────────────────────────────────────────────────────────────
try {
    $pdo = getDB();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO quotes (
            module,
            job_type,
            customer_code,
            customer_name,
            job_name,
            status,
            edna_analysis,
            prompt_version_id,
            created_by,
            created_at,
            updated_at
        ) VALUES (
            :module,
            :job_type,
            :customer_code,
            :customer_name,
            :job_name,
            'draft',
            :edna_analysis,
            :prompt_version_id,
            :created_by,
            NOW(),
            NOW()
        )
        RETURNING id
    ");

    $stmt->execute([
        'module'            => $module,
        'job_type'          => $job_type,
        'customer_code'     => $customer_code,
        'customer_name'     => $customer_name,
        'job_name'          => $job_name,
        'edna_analysis'     => json_encode($edna_analysis),
        'prompt_version_id' => $prompt_version_id,
        'created_by'        => $_SESSION['user_id'],
    ]);

    $quote_id = (int) $stmt->fetchColumn();

    // ── CORRECTION TRACKING ───────────────────────────────────────────────────
    // Compare what Edna suggested vs what the CSR actually saved
    // Trackable fields — Edna's parse key => label
    // TODO: hardcoded field list — move to DB per job type
    $trackable_fields = [
        'width', 'depth', 'parts', 'ncr_type', 'stock',
        'ink_front', 'ink_back', 'perforation', 'finishing', 'job_type',
    ];

    $correction_stmt = $pdo->prepare("
        INSERT INTO edna_field_corrections (
            quote_id,
            prompt_version_id,
            field_name,
            edna_suggested,
            user_saved,
            confidence_state,
            created_at
        ) VALUES (
            :quote_id,
            :prompt_version_id,
            :field_name,
            :edna_suggested,
            :user_saved,
            :confidence_state,
            NOW()
        )
    ");

    foreach ($trackable_fields as $field) {
        $edna_value  = $edna_analysis[$field]              ?? null;
        $user_value  = $saved_fields[$field]               ?? null;
        $confidence  = $edna_analysis[$field . '_confidence'] ?? null;

        // Only write a correction record if Edna had a value AND the CSR changed it
        if ($edna_value !== null && $edna_value !== $user_value) {
            $correction_stmt->execute([
                'quote_id'          => $quote_id,
                'prompt_version_id' => $prompt_version_id,
                'field_name'        => $field,
                'edna_suggested'    => $edna_value,
                'user_saved'        => $user_value,
                'confidence_state'  => $confidence,
            ]);
        }
    }

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'DB error saving quote', 'detail' => $e->getMessage()]);
    exit;
}

// ── RETURN ────────────────────────────────────────────────────────────────────
echo json_encode([
    'success'  => true,
    'quote_id' => $quote_id,
]);
