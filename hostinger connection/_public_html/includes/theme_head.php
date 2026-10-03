<?php
$asset_base = $asset_base ?? '';
$css_dir = dirname(__DIR__) . '/css';
$v_nav    = @filemtime($css_dir . '/site-nav.css') ?: '20261004v1';
$v_prompt = @filemtime($css_dir . '/prompt-pages.css') ?: '20261004v1';
$v_nogoda = @filemtime($css_dir . '/nogoda-theme.css') ?: '20261004v1';
$v_card   = @filemtime($css_dir . '/prompt-card-modern.css') ?: '20261004v1';
?>
<link rel="stylesheet" href="<?= htmlspecialchars($asset_base) ?>css/site-nav.css?v=<?= $v_nav ?>">
<link rel="stylesheet" href="<?= htmlspecialchars($asset_base) ?>css/prompt-pages.css?v=<?= $v_prompt ?>">
<link rel="stylesheet" href="<?= htmlspecialchars($asset_base) ?>css/nogoda-theme.css?v=<?= $v_nogoda ?>">
<link rel="stylesheet" href="<?= htmlspecialchars($asset_base) ?>css/prompt-card-modern.css?v=<?= $v_card ?>">
<link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
