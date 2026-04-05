<?php
// ============================================================
// SCP Central — Shared DB connection
// Include: require_once __DIR__ . '/../../includes/db.php';
// ============================================================

require_once '/var/www/secrets.php';

define('DB_HOST', 'postgres');
define('DB_PORT', '5432');
define('DB_NAME', 'scp_central');
define('DB_USER', 'scpadmin');

/**
 * Returns a singleton PDO connection.
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
