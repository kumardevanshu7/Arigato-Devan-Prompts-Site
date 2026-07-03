<?php
/**
 * Long-lived sessions (365 days) — use instead of session_start().
 */
require_once __DIR__ . '/session_config.php';

if (session_status() === PHP_SESSION_ACTIVE) {
    return;
}

$lifetime = session_lifetime_seconds();

session_set_cookie_params([
    'lifetime' => $lifetime,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);

ini_set('session.gc_maxlifetime', (string) $lifetime);
ini_set('session.cookie_lifetime', (string) $lifetime);

session_start();

if (session_id() !== '') {
    setcookie(session_name(), session_id(), [
        'expires'  => time() + $lifetime,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
