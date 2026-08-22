<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once "db.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? '') !== "admin") {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$action = trim($_POST['action'] ?? '');

if ($action === 'rename_tag') {
    $old_tag = trim($_POST['old_tag'] ?? '');
    $new_tag = trim($_POST['new_tag'] ?? '');
    $new_tag = trim(str_replace(',', ' ', $new_tag));

    if ($old_tag === '' || $new_tag === '') {
        echo json_encode(['success' => false, 'message' => 'Both old tag and new tag names are required.']);
        exit();
    }

    if (strcasecmp($old_tag, $new_tag) === 0 && $old_tag === $new_tag) {
        echo json_encode(['success' => true, 'message' => 'No change detected.', 'affected_prompts' => 0]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT id, tag FROM prompts WHERE tag IS NOT NULL AND tag != ''");
        $stmt->execute();
        $prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $affected = 0;
        $update_stmt = $pdo->prepare("UPDATE prompts SET tag = ? WHERE id = ?");

        foreach ($prompts as $p) {
            $raw_tags = array_map('trim', explode(',', $p['tag'] ?? ''));
            $modified = false;
            $updated_tags = [];

            foreach ($raw_tags as $t) {
                if ($t === '') continue;
                if (strcasecmp($t, $old_tag) === 0) {
                    $updated_tags[] = $new_tag;
                    $modified = true;
                } else {
                    $updated_tags[] = $t;
                }
            }

            if ($modified) {
                $unique_tags = array_values(array_unique($updated_tags));
                $new_tag_str = implode(',', $unique_tags);
                $update_stmt->execute([$new_tag_str, $p['id']]);
                $affected++;
            }
        }

        echo json_encode([
            'success' => true,
            'message' => "Tag '{$old_tag}' successfully renamed to '{$new_tag}' across {$affected} prompt(s).",
            'affected_prompts' => $affected,
            'old_tag' => $old_tag,
            'new_tag' => $new_tag
        ]);
        exit();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}

if ($action === 'delete_tag') {
    $tag_name = trim($_POST['tag_name'] ?? '');

    if ($tag_name === '') {
        echo json_encode(['success' => false, 'message' => 'Tag name is required.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT id, tag FROM prompts WHERE tag IS NOT NULL AND tag != ''");
        $stmt->execute();
        $prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $affected = 0;
        $update_stmt = $pdo->prepare("UPDATE prompts SET tag = ? WHERE id = ?");

        foreach ($prompts as $p) {
            $raw_tags = array_map('trim', explode(',', $p['tag'] ?? ''));
            $modified = false;
            $updated_tags = [];

            foreach ($raw_tags as $t) {
                if ($t === '') continue;
                if (strcasecmp($t, $tag_name) === 0) {
                    $modified = true;
                } else {
                    $updated_tags[] = $t;
                }
            }

            if ($modified) {
                $new_tag_str = implode(',', array_values(array_unique($updated_tags)));
                $update_stmt->execute([$new_tag_str, $p['id']]);
                $affected++;
            }
        }

        echo json_encode([
            'success' => true,
            'message' => "Tag '{$tag_name}' successfully removed from {$affected} prompt(s).",
            'affected_prompts' => $affected,
            'deleted_tag' => $tag_name
        ]);
        exit();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
exit();
