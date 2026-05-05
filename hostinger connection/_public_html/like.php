<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ¢â‚¬â„¢ Login check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit();
}

// ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ¢â‚¬â„¢ Method check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$user_id = $_SESSION['user_id'];
$prompt_id = isset($_POST['prompt_id']) ? (int)$_POST['prompt_id'] : 0;

if ($prompt_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid prompt']);
    exit();
}

try {
    // ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ‚Â¥ Transaction start (important for consistency)
    $pdo->beginTransaction();

    // Check existing like
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND prompt_id = ?");
    $stmt->execute([$user_id, $prompt_id]);
    $like = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($like) {
        // ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ‚Â¥ UNLIKE
        $pdo->prepare("DELETE FROM likes WHERE id = ?")->execute([$like['id']]);

        $pdo->prepare("
            UPDATE prompts 
            SET likes_count = CASE 
                WHEN likes_count > 0 THEN likes_count - 1 
                ELSE 0 
            END 
            WHERE id = ?
        ")->execute([$prompt_id]);

        $action = 'unliked';

    } else {
        // ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ‚Â¥ LIKE (prevent duplicate)
        $pdo->prepare("
            INSERT IGNORE INTO likes (user_id, prompt_id) 
            VALUES (?, ?)
        ")->execute([$user_id, $prompt_id]);

        $pdo->prepare("
            UPDATE prompts 
            SET likes_count = likes_count + 1 
            WHERE id = ?
        ")->execute([$prompt_id]);

        $action = 'liked';
    }

    // Get updated count
    $stmt = $pdo->prepare("SELECT likes_count FROM prompts WHERE id = ?");
    $stmt->execute([$prompt_id]);
    $likes_count = (int)$stmt->fetchColumn();

    // ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ‚Â¥ Commit
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'action' => $action,
        'likes_count' => $likes_count
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>