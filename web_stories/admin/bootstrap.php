<?php
require_once dirname(__DIR__) . '/bootstrap.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../index.php');
    exit();
}

define('WS_ADMIN', true);
