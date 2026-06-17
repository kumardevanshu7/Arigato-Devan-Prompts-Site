<?php 
session_start();

// SOFT LAUNCH GATE
$allowed_emails = [
    'devansh.grow@gmail.com', 
    'thisisdevanshu7@gmail.com', 
    'kaira.nyxzy@gmail.com'
];
$user_email = isset($_SESSION['email']) ? strtolower($_SESSION['email']) : '';

if (!in_array($user_email, $allowed_emails)) {
    include 'coming_soon.php';
    exit;
}

// Use site's existing DB connection
require_once '../db.php';

$products   = [];
$categories = [];
$total      = 0;

if (isset($pdo)) {
  $stmt = $pdo->query("
    SELECT p.*,
           (SELECT filename FROM store_product_images WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) AS first_image
    FROM store_products p
    WHERE p.active = 1
    ORDER BY p.created_at DESC
  ");
  $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($products as $p) {
    if (!empty($p['category'])) {
      foreach (array_map('trim', explode(',', $p['category'])) as $cat) {
        if ($cat) $categories[$cat] = true;
      }
    }
  }
  if (!empty($categories)) {
    $categories = array_keys($categories);
  }
}
$total = count($products);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Prompt Store — Arigato</title>
  <meta name="description" content="Premium AI prompt packs, curated and handcrafted for stunning results."/>
  <link rel="stylesheet" href="css/store.css"/>
</head>
<body>

<!-- =========== HEADER =========== -->
<?php include 'store_nav.php'; ?>

<!-- =========== HERO =========== -->
<section class="store-hero">
  <p class="hero-label">Curated Prompt Collection</p>
  <h1 class="hero-title">Unlock <em>Premium</em><br>AI Prompts</h1>
  <p class="hero-subtitle">Hand-crafted prompts that produce stunning, consistent results. Buy once, use forever.</p>
</section>

<!-- =========== MARQUEE =========== -->
<div class="marquee-strip">
  <div class="marquee-track">
    <?php
      $items = ['Hot CloseUp Couple Prompt', 'Cinematic Portrait Prompt', 'Aesthetic Outfit Prompt', 'Golden Hour Glow Prompt', 'Dark Moody Boudoir Prompt', 'Viral Reel Thumbnail Prompt', 'Fashion Editorial Prompt', 'Street Style Candid Prompt', 'Soft Glam Studio Prompt', 'Bold Colour Pop Prompt', 'Neon Night Out Prompt'];
      $html = '';
      foreach($items as $item) {
        $html .= '<span class="marquee-item">' . $item . ' <span class="marquee-dot">✦</span></span>';
      }
      // Duplicate for seamless loop
      echo $html . $html;
    ?>
  </div>
</div>

<!-- =========== SECTION HEADER =========== -->
<div class="section-header" style="margin-top:70px;">
  <div>
    <span class="section-number">01/</span>
    <span class="section-title">All Products</span>
  </div>
  <span class="section-count"><?= $total ?> prompt<?= $total !== 1 ? 's' : '' ?> available</span>
</div>

<!-- =========== FILTER PILLS =========== -->
<div class="filter-bar">
  <button class="filter-pill active" data-cat="all">All</button>
  <?php foreach ($categories as $cat): ?>
    <button class="filter-pill" data-cat="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></button>
  <?php endforeach; ?>
</div>

<!-- =========== PRODUCT GRID =========== -->
<?php
  // Render products
?>

<div class="product-grid">
  <?php if (empty($products)): ?>
    <div style="grid-column:1/-1;text-align:center;padding:80px 0;color:var(--text-muted);">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom:16px;opacity:0.4;"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
      <p style="font-size:0.95rem;">No products listed yet.</p>
    </div>
  <?php else: ?>
  <?php foreach ($products as $i => $p):
    $disc_pct  = $p['price'] > 0 ? round((($p['price'] - $p['discount']) / $p['price']) * 100) : 0;
    $img_src   = !empty($p['first_image']) ? 'assets/images/' . htmlspecialchars($p['first_image']) : 'assets/placeholder.svg';
    $cat_slug  = htmlspecialchars($p['category'] ?? '');
  ?>
    <a href="product.php?id=<?= $p['id'] ?>" class="product-card fade-up" style="animation-delay:<?= $i * 0.1 ?>s" data-cat="<?= $cat_slug ?>">
      <div class="card-image-wrap">
        <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($p['title']) ?>" loading="lazy"/>
        <?php if (!empty($p['badge'])): ?>
          <span class="card-badge <?= htmlspecialchars($p['badge_type']) ?>"><?= htmlspecialchars($p['badge']) ?></span>
        <?php endif; ?>
        <div class="card-overlay">
          <span class="quick-view-btn">View Prompt
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 17L17 7M17 7H7M17 7v10"/></svg>
          </span>
        </div>
      </div>
      <div class="card-info">
        <p class="card-title"><?= htmlspecialchars($p['title']) ?></p>
        <div class="card-pricing">
          <span class="price-original">&#8377;<?= $p['price'] ?></span>
          <span class="price-new">&#8377;<?= $p['discount'] ?></span>
          <span class="price-discount-tag"><?= $disc_pct ?>% off</span>
        </div>
      </div>
    </a>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- =========== HOW TO USE =========== -->
<section class="how-section">
  <div class="how-inner">
    <div class="how-header">
      <span class="how-label">Simple Process</span>
      <h2 class="how-title">How It Works</h2>
      <p class="how-subtitle">From discovery to delivery — in four steps</p>
    </div>
    <div class="how-steps">

      <div class="how-step">
        <div class="step-icon-wrap">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        </div>
        <div class="step-num">01</div>
        <h3 class="step-title">Browse</h3>
        <p class="step-desc">Explore our curated collection of premium AI prompts across categories.</p>
      </div>

      <div class="how-connector"></div>

      <div class="how-step">
        <div class="step-icon-wrap">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M3 9h18M9 21V9"/></svg>
        </div>
        <div class="step-num">02</div>
        <h3 class="step-title">Preview</h3>
        <p class="step-desc">Open a prompt card to see 5 sample outputs. Prompt text stays locked until purchase.</p>
      </div>

      <div class="how-connector"></div>

      <div class="how-step">
        <div class="step-icon-wrap">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="step-num">03</div>
        <h3 class="step-title">Purchase</h3>
        <p class="step-desc">Complete secure payment via Super Profile — trusted, instant checkout.</p>
      </div>

      <div class="how-connector"></div>

      <div class="how-step">
        <div class="step-icon-wrap">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="step-num">04</div>
        <h3 class="step-title">Get Prompt</h3>
        <p class="step-desc">Full prompt unlocks instantly. Copy and use it in any AI tool right away.</p>
      </div>

    </div>
  </div>
</section>

<!-- =========== FOOTER =========== -->
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
<script>
  // Filter pills
  document.querySelectorAll('.filter-pill').forEach(pill => {
    pill.addEventListener('click', function() {
      document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
      this.classList.add('active');
    });
  });
</script>
<?php include 'store_firebase_js.php'; ?>

</body>
</html>
