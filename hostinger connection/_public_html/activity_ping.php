<?php
session_start();
require_once "db.php";
if (!isset($_SESSION['user_id'])) { http_response_code(204); exit; }
// Throttle: only update if last_active is NULL or older than 2 minutes
$row = $pdo->prepare("SELECT last_active FROM users WHERE id = ? LIMIT 1");
$row->execute([$_SESSION['user_id']]);
$la = $row->fetchColumn();
if (!$la || (time() - strtotime($la)) > 120) {
    $pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?")->execute([$_SESSION['user_id']]);
}
http_response_code(204);
