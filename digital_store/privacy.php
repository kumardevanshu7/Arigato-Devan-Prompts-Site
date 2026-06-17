<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Privacy Policy — Arigato Store</title>
  <meta name="description" content="Privacy Policy for Arigato Store — how we collect, use, and protect your data."/>
  <link rel="stylesheet" href="css/store.css"/>
  <style>
    .policy-page { max-width: 720px; margin: 0 auto; padding: 60px 20px 80px; }
    .policy-label {
      display: inline-block;
      background: #f8f4ef; color: #8b6914;
      border: 1px solid #e5d5b0; border-radius: 100px;
      font-size: 0.72rem; font-weight: 700;
      letter-spacing: 0.1em; text-transform: uppercase;
      padding: 5px 14px; margin-bottom: 16px;
    }
    .policy-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(2rem, 5vw, 2.8rem);
      font-weight: 900; color: var(--text-primary);
      margin-bottom: 8px; line-height: 1.15;
    }
    .policy-updated { font-size: 0.78rem; color: var(--text-muted); margin-bottom: 40px; }
    .policy-section { margin-bottom: 36px; padding-bottom: 36px; border-bottom: 1px solid var(--border); }
    .policy-section:last-child { border-bottom: none; }
    .policy-section h2 {
      font-family: 'Playfair Display', serif;
      font-size: 1.15rem; font-weight: 800;
      color: var(--text-primary); margin-bottom: 12px;
      display: flex; align-items: center; gap: 10px;
    }
    .policy-section h2 .sec-icon {
      width: 32px; height: 32px; border-radius: 8px;
      background: #f8f4ef; display: inline-flex;
      align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0;
    }
    .policy-section p, .policy-section li {
      font-size: 0.88rem; color: var(--text-secondary);
      line-height: 1.8; margin-bottom: 10px;
    }
    .policy-section ul { padding-left: 20px; }
    .policy-section ul li::marker { color: var(--accent-warm); }
  </style>
</head>
<body>
<?php include 'store_nav.php'; ?>

<main class="policy-page">
  <span class="policy-label">Legal</span>
  <h1 class="policy-title">Privacy Policy</h1>
  <p class="policy-updated">Last updated: June 2026</p>

  <div class="policy-section">
    <h2><span class="sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span> Who We Are</h2>
    <p>Arigato Store is an AI prompt marketplace operated by Devan (Kumar Devanshu). We sell premium AI prompts and digital content at <strong>arigatodevan.com/digital_store</strong>.</p>
  </div>

  <div class="policy-section">
    <h2><span class="sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg></span> What Data We Collect</h2>
    <ul>
      <li><strong>Email address</strong> — provided during purchase or account login (Google Sign-In)</li>
      <li><strong>Purchase records</strong> — product purchased, date, and payment reference ID</li>
      <li><strong>Support tickets</strong> — name, email, issue description, and optional screenshots you submit</li>
      <li><strong>Session data</strong> — temporary browser session tokens used to secure your purchase flow</li>
    </ul>
  </div>

  <div class="policy-section">
    <h2><span class="sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg></span> How We Use Your Data</h2>
    <ul>
      <li>To deliver your purchased prompts and maintain your "My Purchases" history</li>
      <li>To send purchase confirmation emails and support responses</li>
      <li>To prevent fraudulent access to purchased content</li>
      <li>We do <strong>not</strong> sell or share your data with any third parties for marketing</li>
    </ul>
  </div>

  <div class="policy-section">
    <h2><span class="sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></span> Payments</h2>
    <p>All payments are processed securely through <strong>SuperProfile</strong>. We do not store your card details, UPI IDs, or any payment credentials on our servers. Please refer to SuperProfile's privacy policy for payment data handling.</p>
  </div>

  <div class="policy-section">
    <h2><span class="sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span> Data Security</h2>
    <p>Your purchase data is stored on secure servers. Purchase access links are one-time use and expire immediately after first view. Support screenshots are stored in a private, non-public directory.</p>
  </div>

  <div class="policy-section">
    <h2><span class="sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span> Contact</h2>
    <p>For any privacy-related concerns, please <a href="contact.php" style="color:var(--text-primary);font-weight:600;">contact us here</a> or email directly at <strong>devansh.grow@gmail.com</strong>.</p>
  </div>
</main>

<footer class="store-footer">
  <div class="store-footer-inner">
    <p class="footer-copy">© <?= date('Y') ?> Arigato Store. All rights reserved.</p>
    <div class="footer-links">
      <a href="privacy.php" class="nav-active">Privacy Policy</a>
      <a href="terms.php">Terms</a>
      <a href="contact.php">Contact</a>
    </div>
  </div>
</footer>
<script src="js/store.js"></script>
<?php include 'store_firebase_js.php'; ?>
</body>
</html>
