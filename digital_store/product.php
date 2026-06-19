<?php 
session_start();


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Product Details — Arigato Store</title>
  <meta name="description" content="View prompt details, sample images, and unlock this premium AI prompt."/>
  <link rel="stylesheet" href="css/store.css"/>
</head>
<body>

<!-- =========== HEADER =========== -->
<?php include 'store_nav.php'; ?>

<?php
require_once '../db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id || !isset($pdo)) {
    header('Location: index.php');
    exit;
}

// Fetch product details
$stmt = $pdo->prepare("SELECT * FROM store_products WHERE id = ? AND active = 1");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) {
    header('Location: index.php');
    exit;
}

// Fetch product images
$img_stmt = $pdo->prepare("SELECT filename FROM store_product_images WHERE product_id = ? ORDER BY sort_order ASC");
$img_stmt->execute([$id]);
$images = $img_stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($images)) {
    $images = ['../assets/placeholder.svg'];
} else {
    foreach ($images as &$img) {
        $img = 'assets/images/' . $img;
    }
}
$p['images'] = $images;

$disc_pct = $p['price'] > 0 ? round((($p['price'] - $p['discount']) / $p['price']) * 100) : 0;

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $host . '/digital_store/success.php?product_id=' . $p['id'];
$return_url = urlencode($base_url);
$buy_url    = $p['super_url'] ? $p['super_url'] : '#';

// Check if user has purchased this product
$is_purchased = false;
if ($user_email) {
    $chk = $pdo->prepare("SELECT id FROM store_purchases WHERE buyer_email = ? AND product_id = ? LIMIT 1");
    $chk->execute([$user_email, $id]);
    if ($chk->fetch()) {
        $is_purchased = true;
    }
}
?>

<!-- =========== PRODUCT PAGE =========== -->
<main class="product-page">

  <!-- Back -->
  <a href="index.php" class="back-btn">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5"/><polyline points="12 19 5 12 12 5"/></svg>
    Back to Store
  </a>

  <div class="product-layout">

    <!-- ===== LEFT: IMAGE SLIDER ===== -->
    <div>
      <div class="slider-wrap" id="mainSlider">
        <div class="slider-track" id="sliderTrack">
          <?php foreach ($p['images'] as $img): ?>
            <img class="slide" src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['title']) ?> preview" loading="lazy"/>
          <?php endforeach; ?>
        </div>

        <!-- Prev / Next -->
        <button class="slider-btn slider-prev" id="sliderPrev" aria-label="Previous">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button class="slider-btn slider-next" id="sliderNext" aria-label="Next">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>

        <!-- Dots -->
        <div class="slider-dots" id="sliderDots">
          <?php for ($i = 0; $i < count($p['images']); $i++): ?>
            <button class="slider-dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>" aria-label="Slide <?= $i+1 ?>"></button>
          <?php endfor; ?>
        </div>
      </div>
    </div>

    <!-- ===== RIGHT: PRODUCT INFO ===== -->
    <div class="product-info">

      <!-- Category -->
      <p class="product-category"><?= htmlspecialchars($p['category']) ?></p>

      <!-- Title -->
      <h1 class="product-title"><?= htmlspecialchars($p['title']) ?></h1>

      <!-- Pricing -->
      <div class="product-pricing-row">
        <span class="product-price-new">₹<?= $p['discount'] ?></span>
        <span class="product-price-original">₹<?= $p['price'] ?></span>
        <span class="product-discount-badge"><?= $disc_pct ?>% off</span>
      </div>

      <!-- Features -->
      <div class="product-features">
        <p class="features-label">What you get</p>
        <?php 
          $features = ['5 stunning sample outputs included','Instant delivery after purchase','Lifetime access, no expiry'];
        ?>
        <?php foreach ($features as $feat): ?>
          <div class="feature-item">
            <span class="feature-icon">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </span>
            <span><?= htmlspecialchars($feat) ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Blurred Prompt Preview -->
      <div class="prompt-preview" style="max-height: <?= $is_purchased ? '400px' : '250px' ?>; overflow-y: <?= $is_purchased ? 'auto' : 'hidden' ?>;">
        <div class="prompt-header" style="position: sticky; top: 0; z-index: 5; background: rgba(255,255,255,0.9); backdrop-filter: blur(4px);">
          <div class="prompt-header-label">
            <div class="prompt-dots">
              <span></span><span></span><span></span>
            </div>
            prompt.txt
          </div>
          <?php if ($is_purchased): ?>
            <div style="display:flex; align-items:center; gap:12px;">
              <span style="font-size:0.72rem;color:var(--success);font-weight:600;">Unlocked</span>
              <button onclick="copyProductPrompt(this)" style="display:flex;align-items:center;gap:4px;font-size:0.7rem;font-weight:600;padding:4px 8px;border-radius:4px;border:1px solid var(--border);background:var(--bg);color:var(--text-primary);cursor:pointer;transition:all 0.2s;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Copy
              </button>
            </div>
          <?php else: ?>
            <span style="font-size:0.72rem;color:var(--text-muted);">Locked</span>
          <?php endif; ?>
        </div>
        <div class="prompt-body">
          <?php if ($is_purchased): ?>
            <p id="theActualPrompt" class="prompt-text" style="filter: blur(0px); user-select: auto; color: var(--text-primary); font-weight:500; font-size:1.05rem; padding-bottom:20px;"><?= htmlspecialchars($p['prompt_text']) ?></p>
          <?php else: ?>
            <p class="prompt-text"><?= htmlspecialchars($p['prompt_text']) ?></p>
            <div class="prompt-lock-overlay" style="background: linear-gradient(to bottom, rgba(253,251,247,0) 0%, rgba(253,251,247,0.9) 60%, var(--bg-card) 100%);">
              <span class="prompt-lock-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:bottom;margin-right:4px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
              <span class="prompt-lock-text">Buy this to unlock the prompt</span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Buy Button or Unlocked Badge -->
      <?php if ($is_purchased): ?>
        <div style="background: #F0FAF4; border: 1.5px solid #BBF7D0; border-radius: 12px; padding: 16px 20px; margin-top: 24px; text-align: center;">
          <div style="color: #166534; font-weight: 700; font-size: 1.05rem; margin-bottom: 8px; display:flex; align-items:center; justify-content:center; gap:6px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            You have successfully purchased this item
          </div>
          <p style="color: #15803D; font-size: 0.85rem; line-height: 1.5; margin-bottom: 0;">The complete prompt is unlocked above.</p>
        </div>
      <?php else: ?>

        <!-- Email popup (for guest users only) -->
        <?php if (!$user_email): ?>
        <div id="emailPopup" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:9999; align-items:center; justify-content:center;">
          <div style="background:var(--bg-card); border:1.5px solid var(--border); border-radius:16px; padding:32px 28px; max-width:380px; width:90%; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.2);">
            <div style="margin-bottom:12px;">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#c9a96e" stroke-width="1.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <h3 style="font-size:1.15rem; font-weight:700; margin-bottom:8px; color:var(--text-primary);">Enter your email</h3>
            <p style="font-size:0.84rem; color:var(--text-muted); margin-bottom:20px; line-height:1.6;">After payment, your prompt will also be sent to this email — so you can always access it later.</p>
            <input type="email" id="guestEmail" placeholder="you@email.com"
              style="width:100%;padding:12px 16px;border:1.5px solid var(--border);border-radius:10px;font-size:0.9rem;background:var(--bg);color:var(--text-primary);outline:none;margin-bottom:16px;box-sizing:border-box;"/>
            <button id="guestProceedBtn" onclick="proceedWithEmail()"
              style="width:100%;padding:13px;background:var(--text-primary);color:#fff;border:none;border-radius:10px;font-size:0.95rem;font-weight:600;cursor:pointer;">
              Continue to Payment →
            </button>
            <button onclick="document.getElementById('emailPopup').style.display='none'"
              style="margin-top:12px;background:none;border:none;color:var(--text-muted);font-size:0.83rem;cursor:pointer;">Cancel</button>
          </div>
        </div>
        <?php endif; ?>

        <button id="unlockBtn" onclick="handleUnlock()"
          class="buy-btn" style="border:none; cursor:pointer; width:100%;">
          Unlock for &#8377;<?= $p['discount'] ?>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </button>

        <script>
          const PRODUCT_ID  = <?= (int)$p['id'] ?>;
          const SUPER_URL   = <?= json_encode($buy_url) ?>;
          const IS_LOGGED_IN = <?= $user_email ? 'true' : 'false' ?>;

          function handleUnlock() {
            if (IS_LOGGED_IN) {
              // Logged-in user: set session token, then redirect
              initAndRedirect('');
            } else {
              // Guest: show email popup
              document.getElementById('emailPopup').style.display = 'flex';
              setTimeout(() => document.getElementById('guestEmail')?.focus(), 100);
            }
          }

          function showPreview(imgSrc) {
            // optional logic here for future features
          }

          function copyProductPrompt(btn) {
            const txt = document.getElementById('theActualPrompt').innerText;
            navigator.clipboard.writeText(txt).then(() => {
              const orig = btn.innerHTML;
              btn.innerHTML = '<span style="color:var(--success)">Copied! ✓</span>';
              setTimeout(() => btn.innerHTML = orig, 2000);
            });
          }

          // Handle guest checkout email
          function proceedWithEmail() {
            const email = document.getElementById('guestEmail')?.value.trim() || '';
            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
              alert('Please enter a valid email address.');
              return;
            }
            document.getElementById('emailPopup').style.display = 'none';
            initAndRedirect(email);
          }

          function initAndRedirect(email) {
            const btn = document.getElementById('unlockBtn');
            if (btn) { btn.disabled = true; btn.style.opacity = '0.7'; }

            const fd = new FormData();
            fd.append('product_id', PRODUCT_ID);
            if (email) fd.append('email', email);

            fetch('init_purchase.php', { method: 'POST', body: fd })
              .then(r => r.json())
              .then(data => {
                if (data.ok) {
                  window.location.href = SUPER_URL;
                } else {
                  alert(data.msg || 'Something went wrong. Please try again.');
                  if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
                }
              })
              .catch(() => {
                // Fallback: redirect anyway (session might not set, but at least they can pay)
                window.location.href = SUPER_URL;
              });
          }

          function openPurchase(pid, secret, btn) {
            const origTxt = btn.innerHTML;
            btn.innerHTML = 'Opening...';
            btn.disabled = true;

            const fd = new FormData();
            fd.append('product_id', pid);

            fetch('init_purchase.php', { method: 'POST', body: fd })
              .then(res => res.json())
              .then(data => {
                if (data.ok) {
                  window.location.href = 'success.php?product_id=' + pid + '&secret=' + secret;
                } else {
                  alert('Error: ' + data.msg);
                  btn.innerHTML = origTxt;
                  btn.disabled = false;
                }
              })
              .catch(err => {
                alert('Network error. Try again.');
                btn.innerHTML = origTxt;
                btn.disabled = false;
              });
          }

          // Allow Enter key in email input
          document.addEventListener('DOMContentLoaded', () => {
            const inp = document.getElementById('guestEmail');
            if (inp) inp.addEventListener('keydown', e => { if (e.key === 'Enter') proceedWithEmail(); });
          });
        </script>

      <?php endif; ?>

      <!-- How to Use -->
      <?php if (!empty($p['how_to_use'])): ?>
      <div class="how-to-use-box" style="margin-top:40px; padding:24px; border:1px solid var(--border-dark); border-radius:12px; background:var(--bg);">
        <h3 style="font-family:'Playfair Display',serif; font-size:1.1rem; margin-bottom:12px; color:var(--text-primary);">How to Use</h3>
        <p style="font-size:0.85rem; color:var(--text-secondary); line-height:1.6; white-space:pre-wrap; font-family:'Inter',sans-serif;"><?= htmlspecialchars($p['how_to_use']) ?></p>
      </div>
      <?php endif; ?>

      <!-- Trust Badges -->
      <div class="trust-badges">
        <span class="trust-item">🔒 Secure Payment</span>
        <span class="trust-item">⚡ Instant Access</span>
        <span class="trust-item">♾️ Lifetime Use</span>
      </div>

    </div>
  </div>

</main>

<!-- =========== FOOTER =========== -->
<?php include '../footer.php'; ?>

<script src="js/store.js"></script>
<?php include 'store_firebase_js.php'; ?>
</body>
</html>
