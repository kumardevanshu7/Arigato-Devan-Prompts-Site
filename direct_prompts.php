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
        WHERE p.prompt_type = 'direct' AND (p.is_trial = 0 OR p.is_trial IS NULL)
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION["user_id"], $_SESSION["user_id"], $_SESSION["user_id"]]);
    $cat_prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $cat_prompts = $pdo
        ->query("SELECT *, 0 as is_unlocked, 0 as is_liked, 0 as is_saved FROM prompts WHERE prompt_type='direct' AND (is_trial = 0 OR is_trial IS NULL) ORDER BY created_at DESC")
        ->fetchAll(PDO::FETCH_ASSOC);
}

$page_title      = 'Direct Prompts — Arigato Devan';
$meta_desc       = 'Direct unlock prompts — tap the heart to reveal instantly.';
$canonical_url   = 'https://arigatodevan.com/direct_prompts.php';
$breadcrumb_name = 'Direct Prompts';
$cat_badge       = 'INSTANT';
$cat_title       = 'Direct';
$cat_title_em    = 'Prompts';
$cat_desc        = 'No codes, no puzzles — just tap the heart the required number of times to unlock each prompt.';
$cat_nav_active  = 'direct';
$cat_empty_icon  = 'fa-hand-pointer';
$cat_empty_title = 'No direct prompts yet';
$cat_empty_text  = 'Direct unlock prompts will appear here soon.';

include 'includes/category_page.php';
