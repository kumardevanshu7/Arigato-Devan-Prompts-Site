<?php
/**
 * store_footer_links.php — Store-specific info/legal strip.
 * Sits above the shared site footer. The main footer links to the root-level
 * policy pages, so this strip is what makes the store's own About, FAQ,
 * Contact, Disclaimer, Privacy and Terms pages reachable.
 */
$sfl_current = basename($_SERVER['PHP_SELF']);
$sfl_links = [
    'about.php'      => 'About the Store',
    'faq.php'        => 'FAQ',
    'contact.php'    => 'Contact & Support',
    'disclaimer.php' => 'Disclaimer',
    'privacy.php'    => 'Privacy Policy',
    'terms.php'      => 'Terms & Conditions',
];
?>
<style>
.store-legal-strip {
  border-top: 1px solid var(--border);
  background: #faf8f5;
  padding: 26px 20px 28px;
}
.store-legal-inner {
  max-width: 900px;
  margin: 0 auto;
  text-align: center;
}
.store-legal-nav {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 8px 20px;
  margin-bottom: 14px;
}
.store-legal-nav a {
  font-size: 0.79rem;
  font-weight: 600;
  color: var(--text-muted);
  text-decoration: none;
  transition: color 0.2s;
}
.store-legal-nav a:hover { color: var(--text-primary); }
.store-legal-nav a.is-current { color: #8b6914; }
.store-legal-note {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 0.72rem;
  line-height: 1.6;
  color: var(--text-muted);
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 100px;
  padding: 6px 15px;
}
.store-legal-note svg { color: #c9a96e; flex-shrink: 0; }
@media (max-width: 560px) {
  .store-legal-nav { gap: 8px 14px; }
  .store-legal-nav a { font-size: 0.76rem; }
}
</style>
<div class="store-legal-strip">
  <div class="store-legal-inner">
    <nav class="store-legal-nav" aria-label="Store information and policies">
      <?php foreach ($sfl_links as $sfl_href => $sfl_text): ?>
        <a href="<?= $sfl_href ?>"<?= $sfl_current === $sfl_href ? ' class="is-current" aria-current="page"' : '' ?>><?= htmlspecialchars($sfl_text) ?></a>
      <?php endforeach; ?>
    </nav>
    <span class="store-legal-note">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      Payments processed securely by SuperProfile &middot; Digital delivery, instantly
    </span>
  </div>
</div>
