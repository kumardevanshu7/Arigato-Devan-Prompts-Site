<?php if (!empty($_SESSION['user_id'])): ?>
<link rel="stylesheet" href="css/logout-confirm.css?v=20260781">
<?php endif; ?>
<footer class="store-footer">
    <div class="store-footer-inner">
        <div class="footer-copy">&copy; <?= date("Y") ?> <span class="footer-brand">ARIGATO DEVAN</span>. KEEP CREATING.</div>
        <nav class="footer-links" aria-label="Site links">
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
            <a href="faq.php">FAQ</a>
            <a href="feedback.php">Feedback</a>
            <a href="happy_users.php">Happy Users</a>
            <a href="privacy.php">Privacy Policy</a>
            <a href="disclaimer.php">Disclaimer</a>
            <a href="terms.php">Terms of Service</a>
        </nav>
    </div>
</footer>
<?php if (!empty($_SESSION['user_id'])): ?>
    <?php include_once __DIR__ . '/includes/logout_confirm.php'; ?>
    <script src="js/logout-confirm.js?v=20260781" defer></script>
<?php endif; ?>
