<?php
/**
 * card.php — Universal shareable card link
 * Usage: card.php?id=123
 * Redirects to the correct category page with ?open=123
 * Works for guests (no login required to open the modal)
 */
session_start();
require_once 'db.php';

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header("Location: index.php");
    exit();
}

// Fetch just enough to know the type
$stmt = $pdo->prepare("SELECT id, prompt_type FROM prompts WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) {
    // Prompt doesn't exist — go home
    header("Location: index.php");
    exit();
}

// Map prompt_type → correct category page
$page_map = [
    'secret'           => 'secret_code.php',
    'unreleased'       => 'unreleased.php',
    'insta_viral'      => 'insta_viral.php',
    'already_uploaded' => 'already_uploaded.php',
];

$target = $page_map[$p['prompt_type']] ?? 'gallery.php';

// Redirect to correct page with ?open=ID — JS will auto-open the card
header("Location: {$target}?open={$p['id']}");
exit();
?>
