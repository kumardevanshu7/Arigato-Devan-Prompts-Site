<?php
require 'db.php';
$user_id = 1;

echo "=== Testing index.php query ===\n";
try {
    $stmt = $pdo->prepare("
        SELECT p.*, IF(u.id IS NOT NULL, 1, 0) as is_unlocked,
               IF(l.id IS NOT NULL, 1, 0) as is_liked,
               IF(sv.id IS NOT NULL, 1, 0) as is_saved
        FROM prompts p
        LEFT JOIN unlocked_prompts u ON p.id = u.prompt_id AND u.user_id = ?
        LEFT JOIN likes l ON p.id = l.prompt_id AND l.user_id = ?
        LEFT JOIN saved_prompts sv ON p.id = sv.prompt_id AND sv.user_id = ?
        WHERE p.prompt_type = 'secret'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    $prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "OK - " . count($prompts) . " secret prompts found\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Testing saved_prompts.php query ===\n";
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.image_path, p.prompt_type, p.likes_count,
               p.tag, p.prompt_text,
               IF(l.id IS NOT NULL, 1, 0) as is_liked
        FROM saved_prompts sp
        JOIN prompts p ON p.id = sp.prompt_id
        LEFT JOIN likes l ON l.prompt_id = p.id AND l.user_id = :uid
        WHERE sp.user_id = :uid2
        ORDER BY sp.created_at DESC
    ");
    $stmt->execute([":uid" => $user_id, ":uid2" => $user_id]);
    $saved = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "OK - " . count($saved) . " saved prompts found\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Testing save_prompt.php (INSERT) ===\n";
try {
    $stmt = $pdo->query("SELECT id FROM prompts LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $ins = $pdo->prepare("INSERT IGNORE INTO saved_prompts (user_id, prompt_id) VALUES (?, ?)");
        $ins->execute([$user_id, $row['id']]);
        echo "OK - Inserted/already exists for prompt_id=" . $row['id'] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Table structure ===\n";
$cols = $pdo->query("DESCRIBE saved_prompts")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  " . $c['Field'] . " (" . $c['Type'] . ")\n";
?>
