<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Admin access required.']);
    exit();
}

$prompt_id = (int)($_POST['prompt_id'] ?? 0);
$status = trim((string)($_POST['status'] ?? ''));

if ($prompt_id <= 0 || !in_array($status, ['already_indexed', 'indexed_now'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid prompt ID or status.']);
    exit();
}

try {
    // Check existing status
    $stmt = $pdo->prepare("SELECT id, gsc_status, gsc_indexed_at FROM prompts WHERE id = ?");
    $stmt->execute([$prompt_id]);
    $prompt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prompt) {
        echo json_encode(['success' => false, 'message' => 'Prompt not found.']);
        exit();
    }

    // If not already set, update it
    if (empty($prompt['gsc_status'])) {
        $now = date('Y-m-d H:i:s');
        $upd = $pdo->prepare("UPDATE prompts SET gsc_status = ?, gsc_indexed_at = ? WHERE id = ?");
        $upd->execute([$status, $now, $prompt_id]);
        $indexed_at = $now;
    } else {
        $status = $prompt['gsc_status'];
        $indexed_at = $prompt['gsc_indexed_at'];
    }

    $formatted_date = !empty($indexed_at) ? date('M j, Y \a\t g:i A', strtotime($indexed_at)) : date('M j, Y \a\t g:i A');
    $label = ($status === 'already_indexed') ? 'Already Indexed' : 'Requested Indexing';

    echo json_encode([
        'success' => true,
        'prompt_id' => $prompt_id,
        'status' => $status,
        'status_label' => $label,
        'indexed_at_formatted' => $formatted_date
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
