<?php
session_start();
require_once "db.php";
if (isset($_SESSION["user_id"]) && empty($_SESSION["onboarding_complete"])) {
    header("Location: onboarding.php");
    exit();
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
        WHERE p.prompt_type = 'insta_viral' AND (p.is_trial = 0 OR p.is_trial IS NULL)
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION["user_id"], $_SESSION["user_id"], $_SESSION["user_id"]]);
    $cat_prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $cat_prompts = $pdo
        ->query("SELECT *, 0 as is_unlocked, 0 as is_liked, 0 as is_saved FROM prompts WHERE prompt_type='insta_viral' AND (is_trial = 0 OR is_trial IS NULL) ORDER BY created_at DESC")
        ->fetchAll(PDO::FETCH_ASSOC);
}

$page_title      = 'Insta Viral Reels — Arigato Devan';
$meta_desc       = 'Viral Instagram reel prompts — solve a quick math challenge to unlock.';
$canonical_url   = 'https://arigatodevan.com/insta_viral.php';
$breadcrumb_name = 'Insta Viral Reels';
$cat_badge       = 'VIRAL';
$cat_title       = 'Insta';
$cat_title_em    = 'Viral';
$cat_desc        = 'Our most viral reel prompts. Solve a quick math puzzle on each card to reveal the full prompt.';
$cat_steps_page  = 'insta_viral';
$cat_nav_active  = 'insta_viral';
$cat_empty_icon  = 'fa-fire';
$cat_empty_title = 'No viral prompts yet';
$cat_empty_text  = 'Viral reel prompts will show up here when added.';

include 'includes/category_page.php';
