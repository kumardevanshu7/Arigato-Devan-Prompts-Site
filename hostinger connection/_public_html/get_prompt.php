<?php
/**
 * get_prompt.php — AJAX endpoint for fetching prompt text
 * Called by insta_viral.php after the math challenge is solved.
 * Returns JSON: { prompt_text: "..." } or { error: "..." }
 */
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['error' => 'Invalid ID']);
    exit();
}

$stmt = $pdo->prepare("SELECT prompt_text, tag FROM prompts WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['error' => 'Prompt not found']);
    exit();
}

// Only serve viral-tagged prompts via this endpoint
$tags = strtolower($row['tag']);
if (strpos($tags, 'viral') === false) {
    echo json_encode(['error' => 'Not a viral prompt']);
    exit();
}

echo json_encode(['prompt_text' => $row['prompt_text']]);
