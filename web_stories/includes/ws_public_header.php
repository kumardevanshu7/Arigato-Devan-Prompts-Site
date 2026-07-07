<?php
/** Public header for Web Stories module (works from /web_stories/ subfolder). */
$ws_nav_active = $ws_nav_active ?? 'stories';
$root = '../';
?>
<header class="ws-topbar">
    <div class="ws-topbar-inner">
        <a href="<?= $root ?>index.php" class="ws-topbar-logo">
            <img src="<?= $root ?>toplogo/logo01.webp" alt="Arigato Devan" height="32" width="32">
            <span>arigato<span class="ws-dot">.</span>stories</span>
        </a>
        <nav class="ws-topbar-nav">
            <a href="<?= $root ?>gallery.php">Gallery</a>
            <a href="<?= $root ?>blogs.php">Blogs</a>
            <a href="index.php" class="<?= $ws_nav_active === 'stories' ? 'active' : '' ?>">Stories</a>
            <?php if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin'): ?>
            <a href="admin/">Editor</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
