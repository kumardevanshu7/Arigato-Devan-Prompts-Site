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
        WHERE p.prompt_type = 'unreleased' AND (p.is_trial = 0 OR p.is_trial IS NULL)
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION["user_id"], $_SESSION["user_id"], $_SESSION["user_id"]]);
    $cat_prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $cat_prompts = $pdo
        ->query("SELECT *, 0 as is_unlocked, 0 as is_liked, 0 as is_saved FROM prompts WHERE prompt_type='unreleased' AND (is_trial = 0 OR is_trial IS NULL) ORDER BY created_at DESC")
        ->fetchAll(PDO::FETCH_ASSOC);
}

$page_title      = 'Unreleased Reels — Arigato Devan';
$meta_desc       = 'Exclusive unreleased AI couple prompts — show love with heart taps to unlock.';
$canonical_url   = 'https://arigatodevan.com/unreleased.php';
$breadcrumb_name = 'Unreleased Reels';
$cat_badge       = 'EXCLUSIVE';
$cat_title       = 'Unreleased';
$cat_title_em    = 'Reels';
$cat_desc        = 'Never-before-seen prompts. Tap the heart 20 times (logged in) or 90 times (guest) to unlock each one.';
$cat_steps_page  = 'unreleased';
$cat_nav_active  = 'unreleased';
$cat_empty_icon  = 'fa-star';
$cat_empty_title = 'Nothing unreleased yet';
$cat_empty_text  = 'Fresh exclusive prompts will land here soon.';

include 'includes/category_page.php';
