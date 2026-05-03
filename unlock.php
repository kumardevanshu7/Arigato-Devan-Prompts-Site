<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify') {
    $prompt_id = $_POST['prompt_id'] ?? '';
    $code = $_POST['code'] ?? '';

    if(empty($prompt_id) || empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Missing data']);
        exit;
    }

    // Verify code in DB (Case-insensitive)
    $stmt = $pdo->prepare("SELECT prompt_text FROM prompts WHERE id = ? AND LOWER(unlock_code) = LOWER(?)");
    $stmt->execute([$prompt_id, $code]);
    $prompt = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prompt) {
        // If logged in, save unlock state permanently
        if (isset($_SESSION['user_id'])) {
            try {
                $insertStmt = $pdo->prepare("INSERT IGNORE INTO unlocked_prompts (user_id, prompt_id) VALUES (?, ?)");
                $insertStmt->execute([$_SESSION['user_id'], $prompt_id]);
            } catch (PDOException $e) {
                // Ignore duplicate entry errors
            }
        }

        echo json_encode([
            'success' => true,
            'prompt_text' => $prompt['prompt_text']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid code'
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'insta_viral') {
    $prompt_id = $_POST['prompt_id'] ?? '';
    
    if(empty($prompt_id)) {
        echo json_encode(['success' => false, 'message' => 'Missing data']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT prompt_text FROM prompts WHERE id = ?");
    $stmt->execute([$prompt_id]);
    $prompt = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prompt) {
        if (isset($_SESSION['user_id'])) {
            try {
                $insertStmt = $pdo->prepare("INSERT IGNORE INTO unlocked_prompts (user_id, prompt_id) VALUES (?, ?)");
                $insertStmt->execute([$_SESSION['user_id'], $prompt_id]);
            } catch (PDOException $e) {}
        }

        echo json_encode([
            'success' => true,
            'prompt_text' => $prompt['prompt_text']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Prompt not found']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'unreleased') {
    $prompt_id = $_POST['prompt_id'] ?? '';
    
    if(empty($prompt_id)) {
        echo json_encode(['success' => false, 'message' => 'Missing data']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT prompt_text FROM prompts WHERE id = ?");
    $stmt->execute([$prompt_id]);
    $prompt = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prompt) {
        if (isset($_SESSION['user_id'])) {
            try {
                $insertStmt = $pdo->prepare("INSERT IGNORE INTO unlocked_prompts (user_id, prompt_id) VALUES (?, ?)");
                $insertStmt->execute([$_SESSION['user_id'], $prompt_id]);
            } catch (PDOException $e) {}
        }
        echo json_encode(['success' => true, 'prompt_text' => $prompt['prompt_text']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Prompt not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>

