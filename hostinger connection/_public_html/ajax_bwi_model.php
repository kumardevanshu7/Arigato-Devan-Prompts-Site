<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once __DIR__ . '/includes/bwi_helper.php';

header('Content-Type: application/json; charset=utf-8');

// Admin only
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

if ($action === 'add') {
    $name = trim((string)($_POST['name'] ?? ''));
    $type = trim((string)($_POST['type'] ?? 'gemini'));

    if ($name === '') {
        echo json_encode(['success' => false, 'message' => 'Model name is required.']);
        exit;
    }

    $res = add_custom_bwi_model($name, $type);
    echo json_encode($res);
    exit;
}

if ($action === 'delete') {
    $id = trim((string)($_POST['id'] ?? ''));
    if ($id === '') {
        echo json_encode(['success' => false, 'message' => 'Model ID is required.']);
        exit;
    }
    $ok = delete_custom_bwi_model($id);
    echo json_encode(['success' => $ok, 'message' => $ok ? 'Model deleted' : 'Cannot delete default model']);
    exit;
}

if ($action === 'list') {
    $models = get_bwi_models();
    echo json_encode(['success' => true, 'models' => $models]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
exit;
