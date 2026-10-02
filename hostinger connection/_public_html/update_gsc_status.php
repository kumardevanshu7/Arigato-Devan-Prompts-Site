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

$item_type = trim((string)($_POST['item_type'] ?? 'prompt'));
if (!in_array($item_type, ['prompt', 'blog', 'page'], true)) {
    $item_type = 'prompt';
}

$raw_id = trim((string)($_POST['item_id'] ?? $_POST['prompt_id'] ?? ''));
$status = trim((string)($_POST['status'] ?? ''));
$req_attempt = isset($_POST['attempt']) ? (int)$_POST['attempt'] : 0;

if ($raw_id === '' || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID or status.']);
    exit();
}

function nd_gsc_parse_status($status_str) {
    $status_str = trim((string)$status_str);
    if (empty($status_str) || $status_str === 'pending') {
        return ['type' => 'pending', 'attempt' => 1];
    }
    if ($status_str === 'already_indexed_2nd' || $status_str === 'already_indexed_2') {
        return ['type' => 'already_indexed', 'attempt' => 2];
    }
    if (preg_match('/^already_indexed_(\d+)$/', $status_str, $m)) {
        return ['type' => 'already_indexed', 'attempt' => max(1, (int)$m[1])];
    }
    if ($status_str === 'already_indexed') {
        return ['type' => 'already_indexed', 'attempt' => 1];
    }
    if ($status_str === 'indexed_now_2nd' || $status_str === 'indexed_now_2') {
        return ['type' => 'indexed_now', 'attempt' => 2];
    }
    if (preg_match('/^indexed_now_(\d+)$/', $status_str, $m)) {
        return ['type' => 'indexed_now', 'attempt' => max(1, (int)$m[1])];
    }
    if ($status_str === 'indexed_now') {
        return ['type' => 'indexed_now', 'attempt' => 1];
    }
    if ($status_str === 'retry_needed_2' || $status_str === 'retry_needed') {
        return ['type' => 'retry_needed', 'attempt' => 2];
    }
    if (preg_match('/^retry_needed_(\d+)$/', $status_str, $m)) {
        return ['type' => 'retry_needed', 'attempt' => max(2, (int)$m[1])];
    }
    return ['type' => 'pending', 'attempt' => 1];
}

function nd_gsc_ordinal($n) {
    $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
    if ((($n % 100) >= 11) && (($n % 100) <= 13)) return $n . 'th';
    return $n . $ends[$n % 10];
}

try {
    $current_gsc_status = 'pending';
    $current_gsc_indexed_at = null;

    if ($item_type === 'prompt') {
        $prompt_id = (int)$raw_id;
        $stmt = $pdo->prepare("SELECT id, gsc_status, gsc_indexed_at FROM prompts WHERE id = ?");
        $stmt->execute([$prompt_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Prompt not found.']);
            exit();
        }
        $current_gsc_status = $row['gsc_status'] ?? 'pending';
        $current_gsc_indexed_at = $row['gsc_indexed_at'] ?? null;
    } elseif ($item_type === 'blog') {
        $blog_id = (int)$raw_id;
        $stmt = $pdo->prepare("SELECT id, gsc_status, gsc_indexed_at FROM blogs WHERE id = ?");
        $stmt->execute([$blog_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Blog post not found.']);
            exit();
        }
        $current_gsc_status = $row['gsc_status'] ?? 'pending';
        $current_gsc_indexed_at = $row['gsc_indexed_at'] ?? null;
    } elseif ($item_type === 'page') {
        $page_key = preg_replace('/[^a-zA-Z0-9_\-]/', '', $raw_id);
        $setting_key = 'gsc_page_' . $page_key;
        $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$setting_key]);
        $val = $stmt->fetchColumn();
        if ($val) {
            $parsed_setting = json_decode($val, true);
            if (is_array($parsed_setting)) {
                $current_gsc_status = $parsed_setting['gsc_status'] ?? 'pending';
                $current_gsc_indexed_at = $parsed_setting['gsc_indexed_at'] ?? null;
            }
        }
    }

    $curr_parsed = nd_gsc_parse_status($current_gsc_status);
    $curr_attempt = $req_attempt > 0 ? $req_attempt : $curr_parsed['attempt'];

    $now = date('Y-m-d H:i:s');
    $is_verify_mode = false;
    $indexed_at = $now;
    $new_status = $status;
    $target_attempt = $curr_attempt;

    if ($status === 'trigger_verify') {
        $is_verify_mode = true;
        $indexed_at = $current_gsc_indexed_at ?: $now;
        $target_attempt = $curr_attempt;
        $new_status = ($target_attempt > 1) ? "indexed_now_{$target_attempt}" : 'indexed_now';
    } elseif ($status === 'reset_pending') {
        $new_status = 'pending';
        $target_attempt = 1;
        $indexed_at = null;
    } elseif ($status === 'retry_needed') {
        $target_attempt = max(2, $curr_attempt + 1);
        $new_status = "retry_needed_{$target_attempt}";
    } elseif (str_starts_with($status, 'already_indexed')) {
        if (preg_match('/^already_indexed_(\d+)$/', $status, $sm)) {
            $target_attempt = (int)$sm[1];
        }
        $new_status = ($target_attempt > 1) ? "already_indexed_{$target_attempt}" : 'already_indexed';
    } elseif (str_starts_with($status, 'indexed_now')) {
        if (preg_match('/^indexed_now_(\d+)$/', $status, $sm)) {
            $target_attempt = (int)$sm[1];
        }
        $new_status = ($target_attempt > 1) ? "indexed_now_{$target_attempt}" : 'indexed_now';
    }

    // Persist changes based on item type
    if (!$is_verify_mode) {
        if ($item_type === 'prompt') {
            $prompt_id = (int)$raw_id;
            if ($new_status === 'pending') {
                $pdo->prepare("UPDATE prompts SET gsc_status = 'pending', gsc_indexed_at = NULL WHERE id = ?")->execute([$prompt_id]);
            } else {
                $pdo->prepare("UPDATE prompts SET gsc_status = ?, gsc_indexed_at = ? WHERE id = ?")->execute([$new_status, $now, $prompt_id]);
            }
        } elseif ($item_type === 'blog') {
            $blog_id = (int)$raw_id;
            if ($new_status === 'pending') {
                $pdo->prepare("UPDATE blogs SET gsc_status = 'pending', gsc_indexed_at = NULL WHERE id = ?")->execute([$blog_id]);
            } else {
                $pdo->prepare("UPDATE blogs SET gsc_status = ?, gsc_indexed_at = ? WHERE id = ?")->execute([$new_status, $now, $blog_id]);
            }
        } elseif ($item_type === 'page') {
            $page_key = preg_replace('/[^a-zA-Z0-9_\-]/', '', $raw_id);
            $setting_key = 'gsc_page_' . $page_key;
            $save_data = [
                'gsc_status' => $new_status,
                'gsc_indexed_at' => ($new_status === 'pending' ? null : $now)
            ];
            $save_json = json_encode($save_data);
            $pdo->prepare(
                "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            )->execute([$setting_key, $save_json]);
        }
    }

    $formatted_date = !empty($indexed_at) ? date('M j, Y \a\t g:i A', strtotime($indexed_at)) : 'Just now';
    $ordinal = nd_gsc_ordinal($target_attempt);

    echo json_encode([
        'success' => true,
        'item_type' => $item_type,
        'item_id' => $raw_id,
        'prompt_id' => $raw_id,
        'status' => $new_status,
        'attempt' => $target_attempt,
        'ordinal' => $ordinal,
        'is_verify_mode' => $is_verify_mode,
        'indexed_at' => $indexed_at,
        'indexed_at_formatted' => $formatted_date
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
