<?php
/**
 * surprise_me.php — Random Prompt Picker
 * Picks a random prompt from any type and redirects to the
 * correct category page with ?open=ID so the modal auto-opens.
 */
session_start();
require_once "db.php";

try {
    $stmt = $pdo->query("SELECT id, prompt_type FROM prompts WHERE (is_trial = 0 OR is_trial IS NULL) ORDER BY RAND() LIMIT 1");
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header("Location: gallery.php");
    exit();
}

if (!$p) {
    // No prompts yet — fall back to gallery
    header("Location: gallery.php");
    exit();
}

// Map prompt_type → correct category page (same logic as card.php)
$page_map = [
    "secret"           => "secret_code.php",
    "unreleased"       => "unreleased.php",
    "insta_viral"      => "insta_viral.php",
    "already_uploaded" => "already_uploaded.php",
];

$target = $page_map[$p["prompt_type"]] ?? "gallery.php";

header("Location: {$target}?open={$p["id"]}&surprise=1");
exit();
?>
