<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Terms &amp; Conditions — Arigato Store</title>
  <meta name="description" content="Terms and Conditions for purchasing AI prompts from Arigato Store."/>
  <link rel="stylesheet" href="css/store.css"/>
  <style>
    .policy-page { max-width: 720px; margin: 0 auto; padding: 60px 20px 80px; }
    .policy-label {
      display: inline-block; background: #f8f4ef; color: #8b6914;
      border: 1px solid #e5d5b0; border-radius: 100px;
      font-size: 0.72rem; font-weight: 700; letter-spacing: 0.1em;
      text-transform: uppercase; padding: 5px 14px; margin-bottom: 16px;
    }
    .policy-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(2rem, 5vw, 2.8rem); font-weight: 900;
      color: var(--text-primary); margin-bottom: 8px; line-height: 1.15;
    }
    .policy-updated { font-size: 0.78rem; color: var(--text-muted); margin-bottom: 40px; }
    .policy-section { margin-bottom: 36px; padding-bottom: 36px; border-bottom: 1px solid var(--border); }
    .policy-section:last-child { border-bottom: none; }
    .policy-section h2 {
      font-family: 'Playfair Display', serif; font-size: 1.15rem; font-weight: 800;
      color: var(--text-primary); margin-bottom: 12px;
      display: flex; align-items: center; gap: 10px;
    }
    .policy-section h2 .sec-icon {
      width: 32px; height: 32px; border-radius: 8px; background: #f8f4ef;
      display: inline-flex; align-items: center; justify-content: center;
      font-size: 0.9rem; flex-shrink: 0;
    }
    .policy-section p, .policy-section li {
      font-size: 0.88rem; color: var(--text-secondary);
      line-height: 1.8; margin-bottom: 10px;
    }
    .policy-section ul { padding-left: 20px; }
    .policy-section ul li::marker { color: var(--accent-warm); }
    .highlight-box {
      background: #f8f4ef; border-left: 3px solid #c9a96e;
      border-radius: 8px; padding: 14px 18px; margin: 14px 0;
      font-size: 0.85rem; color: var(--text-secondary); line-height: 1.7;
    }
  </style>
</head>
<body>
<?php include 'store_nav.php'; ?>

<main class="policy-page">
  <span class="policy-label">Legal</span>
  <h1 class="policy-title">Terms &amp; Conditions</h1>
  <p class="policy-updated">Last updated: June 2026</p>

  <div class="policy-section">
    <h2><span class="sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg></span> Agreement</h2>
    <p>By purchasing any product from Arigato Store, you agree to these Terms &amp; Conditions. Please read them carefully before completing your purchase.</p>
  </div>

  <div class="policy-section">
    <h2><span class="sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg></span> Digital Products</h2>
    <ul>
      <li>All products sold are <strong>digital AI prompts</strong> — text-based instructions for use with AI image/text tools.</li>
      <li>Upon successful purchase, you receive a <strong>personal, non-exclusive licence</strong> to use the prompt for personal and commercial projects.</li>
      <li>You may <strong>not</strong> resell, redistribute, or share the prompt text publicly.</li>
      <li>The prompt is delivered instantly via a one-time secure link on our website. SuperProfile also sends a copy to your email.</li>
    </ul>
  </div>

  <div class="policy-section">
    <h2><span class="sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></span> Payments &amp; Pricing</h2>
    <ul>
      <li>All prices are listed in <strong>Indian Rupees (₹ INR)</strong>.</li>
      <li>Payments are processed securely by <strong>SuperProfile</strong>. Arigato Store does not handle or store payment credentials.</li>
      <li>Prices may change at any time. A purchase locks in the price at the time of checkout.</li>
    </ul>
  </div>

  <div class="policy-section">
    <h2><span class="sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg></span> Refund Policy</h2>
    <div class="highlight-box">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:text-bottom;margin-right:4px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Due to the digital nature of our products, <strong>all sales are final</strong>. We do not offer refunds once a prompt has been delivered.
    </div>
    <p>However, if you did not receive your product after a successful payment, please <a href="contact.php" style="color:var(--text-primary);font-weight:600;">raise a support ticket</a> with your payment screenshot. We will resolve it within 48 hours.</p>
  </div>

  <div class="policy-section">
    <h2><span class="sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span> Access &amp; Security</h2>
    <ul>
      <li>Your purchase success link is a <strong>one-time use link</strong> that expires after first view.</li>
      <li>Sharing this link with others is a violation of these Terms and may result in account suspension.</li>
      <li>Logged-in users can always access their prompts from <a href="my_purchases.php" style="color:var(--text-primary);font-weight:600;">My Purchases</a>.</li>
    </ul>
  </div>

  <div class="policy-section">
    <h2><span class="sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span> Limitation of Liability</h2>
    <p>Arigato Store provides prompts "as is". We do not guarantee specific AI-generated results as outputs depend on the AI tool and settings used. Our maximum liability is limited to the amount paid for the specific product in question.</p>
  </div>

  <div class="policy-section">
    <h2><span class="sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span> Contact</h2>
    <p>For any questions about these terms, <a href="contact.php" style="color:var(--text-primary);font-weight:600;">contact us here</a> or email <strong>devansh.grow@gmail.com</strong>.</p>
  </div>
</main>

<footer class="store-footer">
  <div class="store-footer-inner">
    <p class="footer-copy">© <?= date('Y') ?> Arigato Store. All rights reserved.</p>
    <div class="footer-links">
      <a href="privacy.php">Privacy Policy</a>
      <a href="terms.php" class="nav-active">Terms</a>
      <a href="contact.php">Contact</a>
    </div>
  </div>
</footer>
<script src="js/store.js"></script>
<?php include 'store_firebase_js.php'; ?>
</body>
</html>
