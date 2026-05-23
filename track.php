<?php
session_start();
require_once "db.php";
header('Content-Type: application/json');
$action    = $_POST['action'] ?? '';
$prompt_id = (int)($_POST['prompt_id'] ?? 0);
if (!$prompt_id) { echo json_encode(['ok' => false]); exit; }
if ($action === 'view') {
    $pdo->prepare("UPDATE prompts SET view_count = view_count + 1 WHERE id = ?")->execute([$prompt_id]);
    echo json_encode(['ok' => true]);
} elseif ($action === 'copy') {
    $pdo->prepare("UPDATE prompts SET copy_count = copy_count + 1 WHERE id = ?")->execute([$prompt_id]);
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false]);
}
