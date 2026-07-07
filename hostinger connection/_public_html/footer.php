<?php $site_base = $site_base ?? ''; ?>
<?php if (!empty($_SESSION['user_id'])): ?>
<link rel="stylesheet" href="<?= htmlspecialchars($site_base) ?>css/logout-confirm.css?v=20260781">
<?php endif; ?>
<footer class="store-footer">
    <div class="store-footer-inner">
        <div class="footer-copy">&copy; <?= date("Y") ?> <span class="footer-brand">ARIGATO DEVAN</span>. KEEP CREATING.</div>
        <nav class="footer-links" aria-label="Site links">
            <a href="<?= $site_base ?>about.php">About</a>
            <a href="<?= $site_base ?>contact.php">Contact</a>
            <a href="<?= $site_base ?>faq.php">FAQ</a>
            <a href="<?= $site_base ?>web_stories/">Stories</a>
            <a href="<?= $site_base ?>feedback.php">Feedback</a>
            <a href="<?= $site_base ?>happy_users.php">Happy Users</a>
            <a href="<?= $site_base ?>privacy.php">Privacy Policy</a>
            <a href="<?= $site_base ?>disclaimer.php">Disclaimer</a>
            <a href="<?= $site_base ?>terms.php">Terms of Service</a>
        </nav>
    </div>
</footer>
<?php if (!empty($_SESSION['user_id'])): ?>
    <?php include_once __DIR__ . '/includes/logout_confirm.php'; ?>
    <script src="<?= htmlspecialchars($site_base) ?>js/logout-confirm.js?v=20260781" defer></script>
<?php endif; ?>
