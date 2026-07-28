<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
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
        WHERE p.prompt_type = 'solo' AND (p.is_trial = 0 OR p.is_trial IS NULL)
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION["user_id"], $_SESSION["user_id"], $_SESSION["user_id"]]);
    $cat_prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $cat_prompts = $pdo
        ->query("SELECT *, 0 as is_unlocked, 0 as is_liked, 0 as is_saved FROM prompts WHERE prompt_type='solo' AND (is_trial = 0 OR is_trial IS NULL) ORDER BY created_at DESC")
        ->fetchAll(PDO::FETCH_ASSOC);
}

$page_title      = 'Solo Prompts — Arigato Devan';
$meta_desc       = 'Solo AI photo prompts with before and after transformations — unlock and copy for Gemini, ChatGPT and Nano Banana.';
$canonical_url   = 'https://arigatodevan.com/solo_prompts.php';
$breadcrumb_name = 'Solo Prompts';
$cat_badge       = 'SOLO';
$cat_title       = 'Solo';
$cat_title_em    = 'Prompts';
$cat_desc        = 'Single-person AI photo prompts with before and after results. Tap to unlock, copy, and recreate the look.';
$cat_nav_active  = 'solo';
$cat_empty_icon  = 'fa-user';
$cat_empty_title = 'No solo prompts yet';
$cat_empty_text  = 'Solo before-and-after prompts will appear here soon.';

include 'includes/category_page.php';
