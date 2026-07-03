<?php
/**
 * Optional auto_prepend — sets session lifetime before session_start() on any page.
 */
require_once __DIR__ . '/session_config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $lifetime = session_lifetime_seconds();
    ini_set('session.gc_maxlifetime', (string) $lifetime);
    ini_set('session.cookie_lifetime', (string) $lifetime);
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
