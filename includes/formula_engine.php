<?php
/*
 * includes/formula_engine.php
 *
 * Safe step-list evaluator for the estimating formula engine.
 * No eval. A formula is a JSON step-list stored in estimating_formula.steps_json:
 *
 *   {
 *     "inputs": ["quantity"],
 *     "reads":  ["impr_per_hr", "rate_per_hr"],
 *     "steps": [
 *       {"op":"div", "a":"quantity",  "b":"impr_per_hr", "into":"run_hours"},
 *       {"op":"mul", "a":"run_hours", "b":"rate_per_hr",  "into":"cost"}
 *     ]
 *   }
 *
 * inputs  names read from the spec (quantity, parts, ...)
 * reads   column names read off the component source row (a temp_press row, etc.)
 * steps   ordered ops writing intermediates; the final result must be named "cost".
 *
 * Each step operand (a, b) is a number literal, or the name of an input,
 * a read value, or a prior intermediate. Supported ops: add sub mul div min max round.
 */

declare(strict_types=1);

/*
 * Evaluate one formula.
 *
 * $steps   decoded steps_json (associative array)
 * $inputs  map of input name  => number (from the spec)
 * $reads   map of column name => number (from the source row)
 *
 * Returns ['cost' => float, 'values' => array of every resolved name => number].
 * Throws RuntimeException on any bad reference, unknown op, or divide-by-zero.
 */
function evaluateFormula(array $steps, array $inputs, array $reads): array
{
    // Seed the value namespace with inputs + reads, all cast to float.
    $values = [];
    foreach ($inputs as $k => $v) { $values[$k] = (float) $v; }
    foreach ($reads as $k => $v)  { $values[$k] = (float) $v; }

    $stepList = $steps['steps'] ?? null;
    if (!is_array($stepList) || count($stepList) === 0) {
        throw new RuntimeException('Formula has no steps');
    }

    foreach ($stepList as $i => $step) {
        $op   = $step['op']   ?? null;
        $into = $step['into'] ?? null;
        if (!is_string($op) || !is_string($into) || $into === '') {
            throw new RuntimeException("Step $i missing op or into");
        }

        $a = resolveOperand($step['a'] ?? null, $values, $i);
        $b = resolveOperand($step['b'] ?? null, $values, $i);

        switch ($op) {
            case 'add': $r = $a + $b; break;
            case 'sub': $r = $a - $b; break;
            case 'mul': $r = $a * $b; break;
            case 'div':
                if ($b == 0.0) { throw new RuntimeException("Step $i divide by zero"); }
                $r = $a / $b;
                break;
            case 'min': $r = min($a, $b); break;
            case 'max': $r = max($a, $b); break;
            case 'round':
                // round uses $a as the value and $b as decimal places
                $r = round($a, (int) $b);
                break;
            default:
                throw new RuntimeException("Step $i unknown op: $op");
        }

        $values[$into] = $r;
    }

    if (!array_key_exists('cost', $values)) {
        throw new RuntimeException('Formula never produced a cost');
    }

    return ['cost' => $values['cost'], 'values' => $values];
}

/*
 * Resolve one operand to a number: a numeric literal, or a name already
 * present in the value namespace. Anything else is an error.
 */
function resolveOperand($operand, array $values, int $stepIndex): float
{
    if (is_int($operand) || is_float($operand)) {
        return (float) $operand;
    }
    if (is_string($operand)) {
        // A numeric string literal is allowed.
        if (is_numeric($operand)) {
            return (float) $operand;
        }
        if (array_key_exists($operand, $values)) {
            return $values[$operand];
        }
        throw new RuntimeException("Step $stepIndex unknown operand: $operand");
    }
    throw new RuntimeException("Step $stepIndex invalid operand");
}
