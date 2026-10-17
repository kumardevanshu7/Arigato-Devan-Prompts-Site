<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/step_pics_helper.php';

header('Content-Type: application/json; charset=utf-8');

// Admin only
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

if ($action === 'upload') {
    if (empty($_FILES['image'])) {
        echo json_encode(['success' => false, 'message' => 'No image uploaded.']);
        exit;
    }
    $point_text = trim((string)($_POST['point_text'] ?? ''));
    $res = save_step_pic_from_upload($_FILES['image'], $point_text, $pdo);
    echo json_encode($res);
    exit;
}

if ($action === 'list') {
    // If table has no text-only steps yet, run sync once to populate existing prompt sentences
    try {
        $hasTextSteps = (int)$pdo->query("SELECT COUNT(*) FROM step_small_pics WHERE image_path IS NULL OR image_path = ''")->fetchColumn();
        if ($hasTextSteps === 0) {
            sync_steps_to_library($pdo);
        }
    } catch (Exception $e) {}

    $q = trim((string)($_GET['q'] ?? ''));
    $pics = get_library_step_pics($pdo, $q);
    echo json_encode(['success' => true, 'pics' => $pics]);
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM step_small_pics WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'sync') {
    $count = sync_steps_to_library($pdo);
    echo json_encode(['success' => true, 'synced' => $count]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
exit;
