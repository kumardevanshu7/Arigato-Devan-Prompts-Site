<?php
session_start();
require_once "db.php";
header('Content-Type: application/json');
$action    = $_POST['action'] ?? '';
$prompt_id = (int)($_POST['prompt_id'] ?? 0);
if (!$prompt_id) { echo json_encode(['ok' => false]); exit; }

function auto_like_on_copy(PDO $pdo, int $prompt_id): void
{
    if (empty($_SESSION["user_id"])) {
        return;
    }
    try {
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO likes (user_id, prompt_id) VALUES (?, ?)",
        );
        $stmt->execute([(int) $_SESSION["user_id"], $prompt_id]);
        if ($stmt->rowCount() > 0) {
            $pdo->prepare(
                "UPDATE prompts SET likes_count = likes_count + 1 WHERE id = ?",
            )->execute([$prompt_id]);
        }
    } catch (Exception $e) {
    }
}
if ($action === 'view') {
    $pdo->prepare("UPDATE prompts SET view_count = view_count + 1 WHERE id = ?")->execute([$prompt_id]);
    echo json_encode(['ok' => true]);
} elseif ($action === 'copy') {
    $pdo->prepare("UPDATE prompts SET copy_count = copy_count + 1 WHERE id = ?")->execute([$prompt_id]);
    auto_like_on_copy($pdo, $prompt_id);
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false]);
}
