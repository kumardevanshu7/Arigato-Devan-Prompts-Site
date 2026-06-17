<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>About — Arigato Store</title>
  <meta name="description" content="About Arigato Store — premium AI prompts curated by Devan. Learn how to shop and get started."/>
  <link rel="stylesheet" href="css/store.css"/>
  <style>
    /* ===== ABOUT PAGE STYLES ===== */
    .about-page { max-width: 900px; margin: 0 auto; padding: 60px 20px 80px; }

    /* Hero */
    .about-hero {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 48px;
      align-items: center;
      margin-bottom: 80px;
    }

    .about-hero-text {}

    .about-label {
      display: inline-block;
      background: #f8f4ef; color: #8b6914;
      border: 1px solid #e5d5b0; border-radius: 100px;
      font-size: 0.72rem; font-weight: 700;
      letter-spacing: 0.1em; text-transform: uppercase;
      padding: 5px 14px; margin-bottom: 20px;
    }

    .about-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(2rem, 4vw, 2.8rem);
      font-weight: 900; line-height: 1.15;
      color: var(--text-primary); margin-bottom: 16px;
    }

    .about-desc {
      font-size: 0.92rem; color: var(--text-muted);
      line-height: 1.8; margin-bottom: 24px;
    }

    .about-stats {
      display: flex; gap: 28px; flex-wrap: wrap;
    }

    .about-stat-item { text-align: left; }
    .about-stat-num {
      font-family: 'Playfair Display', serif;
      font-size: 2rem; font-weight: 900;
      color: var(--text-primary); line-height: 1;
    }
    .about-stat-label {
      font-size: 0.72rem; color: var(--text-muted);
      text-transform: uppercase; letter-spacing: 0.07em;
      margin-top: 4px;
    }

    /* Creator card */
    .creator-card {
      background: var(--bg-card);
      border: 1.5px solid var(--border);
      border-radius: 20px;
      padding: 32px;
      position: relative;
      overflow: hidden;
    }

    .creator-card::before {
      content: '"';
      position: absolute;
      top: -10px; left: 20px;
      font-family: 'Playfair Display', serif;
      font-size: 8rem; color: var(--border);
      line-height: 1; pointer-events: none;
    }

    .creator-quote {
      font-family: 'Playfair Display', serif;
      font-size: 1.05rem; line-height: 1.7;
      color: var(--text-secondary);
      font-style: italic;
      margin-bottom: 20px;
      position: relative; z-index: 1;
    }

    .creator-name {
      font-size: 0.88rem; font-weight: 700;
      color: var(--text-primary);
    }

    .creator-role {
      font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;
    }

    /* Divider */
    .about-divider {
      display: flex; align-items: center; gap: 16px;
      margin: 60px 0 48px;
    }
    .about-divider-line { flex: 1; height: 1px; background: var(--border); }
    .about-divider-text {
      font-size: 0.72rem; font-weight: 700; letter-spacing: 0.12em;
      text-transform: uppercase; color: var(--text-muted); white-space: nowrap;
    }

    /* How to shop — Steps */
    .shop-guide-header { text-align: center; margin-bottom: 48px; }
    .shop-guide-label {
      display: inline-block;
      background: var(--bg-card); color: var(--text-muted);
      border: 1px solid var(--border); border-radius: 100px;
      font-size: 0.72rem; font-weight: 700;
      letter-spacing: 0.1em; text-transform: uppercase;
      padding: 5px 14px; margin-bottom: 14px;
    }
    .shop-guide-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(1.6rem, 4vw, 2.2rem);
      font-weight: 900; color: var(--text-primary); line-height: 1.2;
    }
    .shop-guide-subtitle {
      font-size: 0.88rem; color: var(--text-muted);
      margin-top: 10px; line-height: 1.6;
    }

    /* Step ladder */
    .steps-ladder {
      display: flex;
      flex-direction: column;
      gap: 0;
      position: relative;
    }

    .step-item {
      display: grid;
      grid-template-columns: 60px 1fr;
      gap: 24px;
      position: relative;
    }

    /* Vertical line connector */
    .step-item:not(:last-child) .step-num-col::after {
      content: '';
      position: absolute;
      left: 30px;
      top: 60px;
      bottom: -20px;
      width: 2px;
      background: linear-gradient(to bottom, var(--border-dark), var(--border));
    }

    .step-num-col {
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding-top: 4px;
    }

    .step-circle {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: var(--text-primary);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Playfair Display', serif;
      font-size: 1.1rem;
      font-weight: 900;
      flex-shrink: 0;
      box-shadow: 0 4px 16px rgba(0,0,0,0.12);
      position: relative;
      z-index: 1;
    }

    .step-circle.accent {
      background: linear-gradient(135deg, #c9a96e, #a07840);
    }

    .step-content {
      background: var(--bg-card);
      border: 1.5px solid var(--border);
      border-radius: 16px;
      padding: 24px;
      margin-bottom: 20px;
      transition: all 0.25s ease;
    }

    .step-content:hover {
      border-color: var(--border-dark);
      box-shadow: 0 8px 24px rgba(0,0,0,0.06);
      transform: translateX(4px);
    }

    .step-tag {
      font-size: 0.68rem; font-weight: 700; letter-spacing: 0.1em;
      text-transform: uppercase; color: var(--text-muted);
      margin-bottom: 6px;
    }

    .step-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.1rem; font-weight: 800;
      color: var(--text-primary); margin-bottom: 8px;
    }

    .step-desc {
      font-size: 0.85rem; color: var(--text-muted);
      line-height: 1.7;
    }

    .step-tip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      margin-top: 10px;
      background: #f8f4ef;
      border-radius: 8px;
      padding: 7px 12px;
      font-size: 0.78rem;
      color: #8b6914;
      font-weight: 500;
    }

    /* CTA */
    .about-cta {
      text-align: center;
      margin-top: 64px;
      padding: 48px 32px;
      background: var(--text-primary);
      border-radius: 24px;
      color: #fff;
    }
    .about-cta-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(1.5rem, 4vw, 2rem);
      font-weight: 900; margin-bottom: 12px;
    }
    .about-cta-text {
      font-size: 0.9rem; opacity: 0.7; line-height: 1.6;
      margin-bottom: 28px; max-width: 420px; margin-left: auto; margin-right: auto;
    }
    .about-cta-btn {
      display: inline-flex; align-items: center; gap: 8px;
      background: #fff; color: var(--text-primary);
      padding: 14px 32px; border-radius: var(--radius-btn);
      font-size: 0.92rem; font-weight: 700; text-decoration: none;
      transition: all 0.2s;
    }
    .about-cta-btn:hover { opacity: 0.9; transform: translateY(-1px); }

    @media (max-width: 680px) {
      .about-hero { grid-template-columns: 1fr; }
      .step-item { grid-template-columns: 48px 1fr; gap: 16px; }
      .step-circle { width: 40px; height: 40px; font-size: 0.9rem; }
    }
  </style>
</head>
<body>
<?php include 'store_nav.php'; ?>

<main class="about-page">

  <!-- ===== HERO SECTION ===== -->
  <div class="about-hero">
    <div class="about-hero-text">
      <span class="about-label">About the Store</span>
      <h1 class="about-title">Premium Prompts,<br><em>Crafted with Intent</em></h1>
      <p class="about-desc">
        Arigato Store is a curated collection of AI prompts designed to produce stunning, 
        consistent, and professional results — every single time you use them.
      </p>
      <div class="about-stats">
        <div class="about-stat-item">
          <div class="about-stat-num">100%</div>
          <div class="about-stat-label">Hand-crafted</div>
        </div>
        <div class="about-stat-item">
          <div class="about-stat-num">∞</div>
          <div class="about-stat-label">Lifetime Use</div>
        </div>
        <div class="about-stat-item">
          <div class="about-stat-num"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></div>
          <div class="about-stat-label">Instant Access</div>
        </div>
      </div>
    </div>

    <div class="creator-card">
      <p class="creator-quote">
        "I got tired of seeing generic, vague prompts that barely worked. 
        So I started building ones that actually perform — tested, refined, 
        and ready to use the moment you buy them."
      </p>
      <div style="display:flex; align-items:center; gap:12px; position:relative; z-index:1;">
        <img src="../aboutmepics/new.webp" alt="Devan" style="width:44px; height:44px; border-radius:50%; object-fit:cover; border:1px solid var(--border);">
        <div>
          <div class="creator-name">Devan</div>
          <div class="creator-role">Founder, Arigato Store</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== DIVIDER ===== -->
  <div class="about-divider">
    <div class="about-divider-line"></div>
    <div class="about-divider-text">How to Shop</div>
    <div class="about-divider-line"></div>
  </div>

  <!-- ===== HOW TO SHOP ===== -->
  <div class="shop-guide-header">
    <span class="shop-guide-label">Step-by-Step</span>
    <h2 class="shop-guide-title">Your First Purchase<br>in Under 2 Minutes</h2>
    <p class="shop-guide-subtitle">From browsing to unlocking your prompt — it's simple, fast, and secure.</p>
  </div>

  <div class="steps-ladder">

    <div class="step-item">
      <div class="step-num-col">
        <div class="step-circle">1</div>
      </div>
      <div class="step-content">
        <div class="step-tag">Discover</div>
        <div class="step-title">Browse the Store</div>
        <p class="step-desc">Head to the Shop page and scroll through our premium AI prompt collection. Each card shows a preview image, category, and the discounted price.</p>
        <span class="step-tip"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2v1"/><path d="M12 7a5 5 0 1 0 5 5c0 2-2 3-2 5H9c0-2-2-3-2-5a5 5 0 0 1 5-5z"/></svg> Use the category filters at the top to quickly find prompts that match your style.</span>
      </div>
    </div>

    <div class="step-item">
      <div class="step-num-col">
        <div class="step-circle">2</div>
      </div>
      <div class="step-content">
        <div class="step-tag">Explore</div>
        <div class="step-title">Open a Prompt Card</div>
        <p class="step-desc">Click any product card to open the full detail page. Here you'll see sample output images, the blurred (locked) prompt text, and a "How to Use" guide.</p>
        <span class="step-tip"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg> Sample images show you exactly what AI outputs to expect before buying.</span>
      </div>
    </div>

    <div class="step-item">
      <div class="step-num-col">
        <div class="step-circle accent">3</div>
      </div>
      <div class="step-content">
        <div class="step-tag">Purchase</div>
        <div class="step-title">Click "Unlock for ₹X"</div>
        <p class="step-desc">Hit the Unlock button. If you're not logged in, a small popup will ask for your email (so we can associate your purchase). You'll then be taken to our secure SuperProfile checkout page.</p>
        <span class="step-tip"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Payment is processed securely by SuperProfile — UPI, cards, and more accepted.</span>
      </div>
    </div>

    <div class="step-item">
      <div class="step-num-col">
        <div class="step-circle accent">4</div>
      </div>
      <div class="step-content">
        <div class="step-tag">Complete Payment</div>
        <div class="step-title">Pay on SuperProfile</div>
        <p class="step-desc">Complete your payment on the SuperProfile checkout. Once done, you'll automatically be redirected back to Arigato Store. SuperProfile also sends a receipt to your email.</p>
        <span class="step-tip"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> Keep the SuperProfile email — it's your backup access if anything goes wrong.</span>
      </div>
    </div>

    <div class="step-item">
      <div class="step-num-col">
        <div class="step-circle" style="background:linear-gradient(135deg,#22c55e,#16a34a);">✓</div>
      </div>
      <div class="step-content">
        <div class="step-tag">Access</div>
        <div class="step-title">Get Your Prompt!</div>
        <p class="step-desc">You're redirected to a secure page showing your full unlocked prompt. Copy it, download the PDF guide (if included), or open the Drive file. The link is single-use for security.</p>
        <span class="step-tip"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg> Logged-in users can always revisit prompts from "My Purchases" in the top-right corner.</span>
      </div>
    </div>

  </div>

  <!-- ===== CTA ===== -->
  <div class="about-cta">
    <h2 class="about-cta-title">Ready to elevate your AI game?</h2>
    <p class="about-cta-text">Browse our growing collection of premium AI prompts — each one tested and refined for maximum results.</p>
    <a href="index.php" class="about-cta-btn">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
      Browse the Store
    </a>
  </div>

</main>

<footer class="store-footer">
  <div class="store-footer-inner">
    <p class="footer-copy">© <?= date('Y') ?> Arigato Store. All rights reserved.</p>
    <div class="footer-links">
      <a href="privacy.php">Privacy Policy</a>
      <a href="terms.php">Terms</a>
      <a href="contact.php">Contact</a>
    </div>
  </div>
</footer>
<script src="js/store.js"></script>
<?php include 'store_firebase_js.php'; ?>
</body>
</html>
