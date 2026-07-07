<?php
/**
 * Web Stories module bootstrap — isolated environment under /web_stories/
 */
define('WS_ROOT', __DIR__);
define('SITE_ROOT', dirname(__DIR__));

require_once SITE_ROOT . '/includes/session_bootstrap.php';
require_once SITE_ROOT . '/db.php';
require_once WS_ROOT . '/includes/helpers.php';
ws_ensure_schema($pdo);
