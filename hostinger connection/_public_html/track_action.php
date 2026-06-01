<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'msg' => 'not_logged_in']);
    exit();
}
require_once 'db.php';

$action    = $_POST['action']    ?? '';
$prompt_id = (int)($_POST['prompt_id'] ?? 0);

$allowed = ['view', 'copy', 'share'];
if (!$prompt_id || !in_array($action, $allowed)) {
    echo json_encode(['ok' => false, 'msg' => 'invalid']);
    exit();
}

$col = $action . '_count';
try {
    $pdo->prepare("UPDATE prompts SET `{$col}` = COALESCE(`{$col}`, 0) + 1 WHERE id = ?")
        ->execute([$prompt_id]);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => 'db_error']);
}
