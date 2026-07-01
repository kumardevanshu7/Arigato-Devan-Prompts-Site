<?php
session_start();
require_once "db.php";
if (isset($_SESSION["user_id"]) && empty($_SESSION["onboarding_complete"])) {
    header("Location: onboarding.php");
    exit();
}

if (empty($_SESSION["oauth_state"])) {
    $_SESSION["oauth_state"] = bin2hex(random_bytes(16));
}

if (isset($_SESSION["user_id"])) {
    $stmt = $pdo->prepare("
        SELECT p.*, IF(u.id IS NOT NULL, 1, 0) as is_unlocked,
               IF(l.id IS NOT NULL, 1, 0) as is_liked,
               IF(sv.id IS NOT NULL, 1, 0) as is_saved
        FROM prompts p
        LEFT JOIN unlocked_prompts u ON p.id = u.prompt_id AND u.user_id = ?
        LEFT JOIN likes l ON p.id = l.prompt_id AND l.user_id = ?
        LEFT JOIN saved_prompts sv ON p.id = sv.prompt_id AND sv.user_id = ?
        WHERE p.prompt_type = 'secret' AND (p.is_trial = 0 OR p.is_trial IS NULL)
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION["user_id"], $_SESSION["user_id"], $_SESSION["user_id"]]);
    $cat_prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $cat_prompts = $pdo
        ->query("SELECT *, 0 as is_unlocked, 0 as is_liked, 0 as is_saved FROM prompts WHERE prompt_type='secret' AND (is_trial = 0 OR is_trial IS NULL) ORDER BY created_at DESC")
        ->fetchAll(PDO::FETCH_ASSOC);
}

$page_title      = 'Secret Code Reels — Arigato Devan';
$meta_desc       = 'Watch our Instagram reels, find the secret code, and unlock premium AI couple prompts.';
$canonical_url   = 'https://arigatodevan.com/secret_code.php';
$breadcrumb_name = 'Secret Code Reels';
$cat_badge       = 'SECRET';
$cat_title       = 'Secret Code';
$cat_title_em    = 'Reels';
$cat_desc        = 'Watch the reel, grab the 6-letter code from Instagram, and enter it on each card to unlock the prompt.';
$cat_steps_page  = 'secret_code';
$cat_nav_active  = 'secret_code';
$cat_empty_icon  = 'fa-lock';
$cat_empty_title = 'No secret code reels yet';
$cat_empty_text  = 'New secret code reels will be added here soon.';

include 'includes/category_page.php';
