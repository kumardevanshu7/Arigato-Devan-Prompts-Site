<?php
require 'db.php';
$user_id = 1;
$prompt_id = 1; // Assuming prompt_id 1 exists. We can query one.
$stmt = $pdo->query("SELECT id FROM prompts LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) die("No prompts");
$prompt_id = $row['id'];

try {
    $ins = $pdo->prepare("INSERT IGNORE INTO saved_prompts (user_id, prompt_id) VALUES (?, ?)");
    $ins->execute([$user_id, $prompt_id]);
    echo "Saved prompt_id $prompt_id for user 1\n";
} catch (Exception $e) {
    echo "Error saving: " . $e->getMessage() . "\n";
}

try {
    $del = $pdo->prepare("DELETE FROM saved_prompts WHERE user_id = ? AND prompt_id = ?");
    $del->execute([$user_id, $prompt_id]);
    echo "Unsaved prompt_id $prompt_id for user 1\n";
} catch (Exception $e) {
    echo "Error unsaving: " . $e->getMessage() . "\n";
}
?>
