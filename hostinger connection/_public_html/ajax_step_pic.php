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
    $q = trim((string)($_GET['q'] ?? ''));
    $pics = get_library_step_pics($pdo, $q);
    echo json_encode(['success' => true, 'pics' => $pics]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
exit;
