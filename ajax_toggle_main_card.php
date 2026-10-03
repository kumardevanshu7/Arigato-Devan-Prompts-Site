<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$id = (int)($_POST['prompt_id'] ?? 0);
$source = trim($_POST['source'] ?? 'regular');

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit();
}

try {
    if ($source === 'curated') {
        $cur = $pdo->prepare("SELECT is_main_card FROM curated_prompts WHERE id = ?");
        $cur->execute([$id]);
        $val = $cur->fetchColumn();
        if ($val === false) {
            echo json_encode(['success' => false, 'error' => 'Curated prompt not found']);
            exit();
        }
        $new_val = ((int)$val === 1) ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE curated_prompts SET is_main_card = ? WHERE id = ?");
        $stmt->execute([$new_val, $id]);
        echo json_encode(['success' => true, 'is_main_card' => $new_val, 'source' => 'curated']);
        exit();
    } else {
        $cur = $pdo->prepare("SELECT is_main_card FROM prompts WHERE id = ?");
        $cur->execute([$id]);
        $val = $cur->fetchColumn();
        if ($val === false) {
            echo json_encode(['success' => false, 'error' => 'Prompt not found']);
            exit();
        }
        $new_val = ((int)$val === 1) ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE prompts SET is_main_card = ? WHERE id = ?");
        $stmt->execute([$new_val, $id]);
        echo json_encode(['success' => true, 'is_main_card' => $new_val, 'source' => 'regular']);
        exit();
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit();
}
