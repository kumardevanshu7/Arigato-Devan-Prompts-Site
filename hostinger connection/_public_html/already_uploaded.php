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
        WHERE p.prompt_type = 'already_uploaded' AND (p.is_trial = 0 OR p.is_trial IS NULL)
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION["user_id"], $_SESSION["user_id"], $_SESSION["user_id"]]);
    $cat_prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $cat_prompts = $pdo
        ->query("SELECT *, 0 as is_unlocked, 0 as is_liked, 0 as is_saved FROM prompts WHERE prompt_type='already_uploaded' AND (is_trial = 0 OR is_trial IS NULL) ORDER BY created_at DESC")
        ->fetchAll(PDO::FETCH_ASSOC);
}

$page_title      = 'Already Uploaded Prompts — Arigato Devan';
$meta_desc       = 'Prompts already shared on Instagram — unlock with just 9 heart taps.';
$canonical_url   = 'https://arigatodevan.com/already_uploaded.php';
$breadcrumb_name = 'Already Uploaded Prompts';
$cat_badge       = 'INSTAGRAM';
$cat_title       = 'Already';
$cat_title_em    = 'Uploaded';
$cat_desc        = 'These prompts are already live on our Instagram. Tap the heart 9 times on each card to unlock the full prompt.';
$cat_steps_page  = 'already_uploaded';
$cat_nav_active  = 'already_uploaded';
$cat_empty_icon  = 'fa-brands fa-instagram';
$cat_empty_title = 'No uploaded prompts yet';
$cat_empty_text  = 'When we share prompts on Instagram, they will appear here.';

$cat_instruction = [
    'icon'  => 'fa-heart',
    'title' => 'Har card pe 9 baar heart tap karo — tabhi prompt unlock hoga',
];
$cat_hide_hero = true;

include 'includes/category_page.php';
