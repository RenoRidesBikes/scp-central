<?php
/**
 * SCP Central — Permission constants and hasPermission() gate
 *
 * Include AFTER db.php (requires $pdo in scope).
 * This is the ONLY place permission bits are defined.
 * hasPermission() is the ONLY permitted gate check in the codebase —
 * never write raw bitwise checks inline.
 *
 * Usage:
 *   require_once __DIR__ . '/permissions.php';
 *   if (hasPermission($userId, 'widget:my_queue', PERM_READ)) { ... }
 *
 * Resolution order: user override → role default → deny (0)
 * Bit positions are scoped per resource_key — each resource has its own
 * 64-bit space, so overlapping values across resources are intentional.
 */

// ── Universal page-level bits ─────────────────────────────────────────────────
const PERM_READ   = 1 << 0;   //  1
const PERM_WRITE  = 1 << 1;   //  2
const PERM_DELETE = 1 << 2;   //  4
const PERM_EXPORT = 1 << 3;   //  8
const PERM_APPROVE = 1 << 4;  // 16

// ── Pricing card bits (resource_key: 'card:pricing') ─────────────────────────
const PERM_PRICE_VIEW_MARGIN    = 1 << 3;  //  8
const PERM_PRICE_VIEW_COST      = 1 << 4;  // 16
const PERM_PRICE_APPROVE_CEIL   = 1 << 5;  // 32
const PERM_PRICE_OVERRIDE_FLOOR = 1 << 6;  // 64

// ── Edna confidence bits (resource_key: 'card:edna_confidence') ──────────────
const PERM_EDNA_OVERRIDE_CONFIRMED = 1 << 2;  //  4
const PERM_EDNA_DISMISS_WARNING    = 1 << 3;  //  8
const PERM_EDNA_RETRIGGER          = 1 << 4;  // 16

// ── Per-request cache (avoids redundant DB hits for same user+resource) ───────
$_permCache = [];

/**
 * Check whether a user has a specific permission bit on a resource.
 *
 * @param int    $userId      users.id
 * @param string $resourceKey e.g. 'widget:my_queue', 'page:estimating', 'card:pricing'
 * @param int    $bit         PERM_* constant
 * @return bool
 */
function hasPermission(int $userId, string $resourceKey, int $bit): bool
{
    global $_permCache;

    $cacheKey = "{$userId}:{$resourceKey}";

    if (!array_key_exists($cacheKey, $_permCache)) {
        $db = getDB();

        // 1. User-level override wins unconditionally
        $stmt = $db->prepare('
            SELECT permission_bits
            FROM   user_permissions
            WHERE  user_id      = :uid
              AND  resource_key = :rk
        ');
        $stmt->execute([':uid' => $userId, ':rk' => $resourceKey]);
        $override = $stmt->fetchColumn();

        if ($override !== false) {
            $_permCache[$cacheKey] = (int) $override;
        } else {
            // 2. Fall back to role default
            $stmt = $db->prepare('
                SELECT rp.permission_bits
                FROM   role_permissions rp
                JOIN   users u ON u.role_id = rp.role_id
                WHERE  u.id           = :uid
                  AND  rp.resource_key = :rk
            ');
            $stmt->execute([':uid' => $userId, ':rk' => $resourceKey]);
            $bits = $stmt->fetchColumn();
            $_permCache[$cacheKey] = $bits !== false ? (int) $bits : 0;
        }
    }

    return ($_permCache[$cacheKey] & $bit) !== 0;
}

/**
 * Flush the per-request cache.
 * Call after writing to user_permissions or role_permissions mid-request.
 */
function flushPermCache(): void
{
    global $_permCache;
    $_permCache = [];
}
