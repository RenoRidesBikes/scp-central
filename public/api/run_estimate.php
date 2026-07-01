<?php
/*
 * /api/run_estimate.php
 *
 * Sandbox compute for the press-cost vertical slice. NO DB writes.
 * Reads temp_press + estimating_formula live, runs the formula engine
 * per component per quantity break, returns the cost build as JSON.
 *
 * Request (POST JSON):
 *   {
 *     "press_number": "3",           // temp_press.press_number (what the UI knows)
 *     "quantities": [5000, 10000, 25000]
 *   }
 *
 * Response:
 *   {
 *     "press": { "id":1, "number":"3", "name":"..." },
 *     "breaks": [
 *       { "quantity":10000,
 *         "components":[
 *           {"formula_key":"makeready","label":"Makeready","cost":330,
 *            "markup_pct":35,"price":445.5,"rate_values":{...}},
 *           {"formula_key":"press_run","label":"Press run","cost":55, ...}
 *         ],
 *         "cost_total":385, "price_total":519.75 }
 *     ]
 *   }
 *
 * Immutability note: this is the SANDBOX path. Nothing is stamped here.
 * A future save/lock endpoint copies formula_json + rate_values + cost
 * onto estimate_component rows.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/formula_engine.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Which formulas make up press cost, in display order.
// TODO: hardcoded - move to a per-job-type component list in DB config.
$PRESS_COST_FORMULAS = ['makeready', 'press_run'];

// Parse body.
$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request body']);
    exit;
}

$pressNumber = isset($payload['press_number']) ? trim((string) $payload['press_number']) : '';
$quantities = $payload['quantities'] ?? [];

if ($pressNumber === '' || !is_array($quantities) || count($quantities) === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'press_number and quantities are required']);
    exit;
}

// Normalise quantities to positive ints.
$quantities = array_values(array_filter(array_map('intval', $quantities), function ($q) {
    return $q > 0;
}));
if (count($quantities) === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid quantities']);
    exit;
}

try {
    $db = getDB();

    // Load the press row (the source of the rate reads).
    $stmt = $db->prepare('SELECT * FROM temp_press WHERE press_number = :n AND is_active = TRUE');
    $stmt->execute([':n' => $pressNumber]);
    $press = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$press) {
        http_response_code(404);
        echo json_encode(['error' => 'Press not found']);
        exit;
    }

    // Load the current version of each press-cost formula.
    $formulas = [];
    $fstmt = $db->prepare(
        'SELECT formula_key, label, steps_json, default_markup_pct
         FROM estimating_formula
         WHERE formula_key = :k AND is_current = TRUE
         ORDER BY version DESC LIMIT 1'
    );
    foreach ($PRESS_COST_FORMULAS as $key) {
        $fstmt->execute([':k' => $key]);
        $row = $fstmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            error_log("run_estimate.php: missing current formula '$key'");
            http_response_code(500);
            echo json_encode(['error' => 'Estimating formula not configured']);
            exit;
        }
        $steps = json_decode((string) $row['steps_json'], true);
        if (!is_array($steps)) {
            error_log("run_estimate.php: bad steps_json for '$key'");
            http_response_code(500);
            echo json_encode(['error' => 'Estimating formula is invalid']);
            exit;
        }
        $formulas[$key] = ['label' => $row['label'], 'steps' => $steps,
                           'markup' => (float) $row['default_markup_pct']];
    }

    // Compute each break.
    $breaks = [];
    foreach ($quantities as $qty) {
        $components = [];
        $costTotal  = 0.0;
        $priceTotal = 0.0;

        foreach ($PRESS_COST_FORMULAS as $key) {
            $f = $formulas[$key];

            // Build the read set from the press row for the columns this formula names.
            $reads = [];
            foreach (($f['steps']['reads'] ?? []) as $col) {
                if (!array_key_exists($col, $press)) {
                    error_log("run_estimate.php: formula '$key' reads missing column '$col'");
                    http_response_code(500);
                    echo json_encode(['error' => 'Estimating formula references a missing rate']);
                    exit;
                }
                $reads[$col] = (float) $press[$col];
            }

            $inputs = ['quantity' => $qty];

            $result = evaluateFormula($f['steps'], $inputs, $reads);
            $cost   = $result['cost'];
            $markup = $f['markup'];
            $price  = $cost * (1 + $markup / 100);

            $components[] = [
                'formula_key' => $key,
                'label'       => $f['label'],
                'cost'        => round($cost, 4),
                'markup_pct'  => $markup,
                'price'       => round($price, 4),
                'rate_values' => $result['values'],
            ];

            $costTotal  += $cost;
            $priceTotal += $price;
        }

        $breaks[] = [
            'quantity'    => $qty,
            'components'  => $components,
            'cost_total'  => round($costTotal, 4),
            'price_total' => round($priceTotal, 4),
        ];
    }

} catch (RuntimeException $e) {
    error_log('run_estimate.php: formula error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Could not compute estimate']);
    exit;
} catch (Exception $e) {
    error_log('run_estimate.php: error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Could not compute estimate']);
    exit;
}

echo json_encode([
    'press' => [
        'id'     => (int) $press['id'],
        'number' => $press['press_number'],
        'name'   => $press['name'],
    ],
    'breaks' => $breaks,
]);
