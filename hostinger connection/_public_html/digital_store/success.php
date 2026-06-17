<?php
/**
 * success.php — Purchase Success Page
 * 
 * Security Flow:
 * 1. Verifies `secret` param matches DB secret_key for this product
 * 2. Verifies one-time session token (`pending_pid`) exists and matches
 * 3. Consumes (deletes) the session token immediately — link becomes useless after first load
 * 4. Records purchase in DB if not already recorded
 * 5. Shows full prompt to user
 */
session_start();
require_once '../db.php';

// ---- Read & validate URL params ----
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$secret     = isset($_GET['secret'])     ? trim($_GET['secret'])     : '';

$error = '';
$p     = null;

if (!$product_id || !$secret || !isset($pdo)) {
    $error = 'Invalid link.';
}

if (!$error) {
    // ---- Check 1: Secret key matches DB ----
    $stmt = $pdo->prepare("SELECT * FROM store_products WHERE id = ? AND active = 1");
    $stmt->execute([$product_id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$p || empty($p['secret_key'])) {
        $error = 'Product not found.';
    } elseif ($p['secret_key'] !== $secret) {
        $error = 'Invalid or expired link.';
    }
}

if (!$error) {
    // ---- Check 2: Session token must exist and match ----
    $session_pid = isset($_SESSION['pending_pid']) ? (int)$_SESSION['pending_pid'] : 0;
    if ($session_pid !== $product_id) {
        $error = 'This link has already been used or has expired. Check your SuperProfile email for access.';
    }
}

// ---- Determine buyer email ----
$buyer_email = '';
if (!$error) {
    if (isset($_SESSION['email']) && !empty($_SESSION['email'])) {
        $buyer_email = strtolower(trim($_SESSION['email']));
    } elseif (isset($_SESSION['pending_email']) && !empty($_SESSION['pending_email'])) {
        $buyer_email = strtolower(trim($_SESSION['pending_email']));
    } else {
        $error = 'Could not identify buyer. Check your SuperProfile email for access.';
    }
}

// ---- Check 3: Record purchase (avoid duplicates) ----
$already_purchased = false;
if (!$error) {
    $chk = $pdo->prepare("SELECT id FROM store_purchases WHERE buyer_email = ? AND product_id = ? LIMIT 1");
    $chk->execute([$buyer_email, $product_id]);
    if ($chk->fetch()) {
        $already_purchased = true; // Already in DB — still show, just don't re-insert
    }

    if (!$already_purchased) {
        // Get transaction ID from SuperProfile if they pass it
        $transaction_id = isset($_GET['transaction_id']) ? trim($_GET['transaction_id']) : ('SP-' . strtoupper(substr(md5(uniqid()), 0, 8)));
        $pdo->prepare("INSERT INTO store_purchases (product_id, buyer_email, payment_id) VALUES (?, ?, ?)")
            ->execute([$product_id, $buyer_email, $transaction_id]);
    }

    // ---- Consume (destroy) the session token immediately ----
    unset($_SESSION['pending_pid']);
    unset($_SESSION['pending_email']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $error ? 'Link Expired — Arigato Store' : 'Purchase Confirmed — Arigato Store' ?></title>
  <meta name="description" content="Your prompt has been unlocked! Thank you for your purchase."/>
  <meta name="robots" content="noindex, nofollow"/>
  <link rel="stylesheet" href="css/store.css"/>
</head>
<body>

<!-- =========== HEADER =========== -->
<?php include 'store_nav.php'; ?>

<main class="success-page">

<?php if ($error): ?>
  <!-- =========== ERROR STATE =========== -->
  <div class="success-icon-wrap">⏳</div>
  <span class="success-label" style="background:#FFF1F2; color:#9F1239; border-color:#FECDD3;">Link Expired</span>
  <h1 class="success-title" style="font-size:1.8rem;">This link has expired</h1>
  <p class="success-subtitle"><?= htmlspecialchars($error) ?></p>
  <p class="success-subtitle" style="margin-top:-16px;">
    ✉️ <strong>Check your email</strong> — SuperProfile has sent you a receipt with access to your purchase.<br>
    If you are logged in, you can also visit <a href="my_purchases.php" style="color:var(--text-primary); font-weight:600;">My Purchases</a>.
  </p>
  <div class="success-actions">
    <a href="index.php" class="btn-primary">Browse Store</a>
    <a href="my_purchases.php" class="btn-secondary">My Purchases</a>
  </div>

<?php else: ?>
  <!-- =========== SUCCESS STATE =========== -->

  <!-- Icon -->
  <div class="success-icon-wrap">✅</div>

  <!-- Label -->
  <span class="success-label">Payment Confirmed</span>

  <!-- Title -->
  <h1 class="success-title">You're all set!</h1>

  <!-- Subtitle -->
  <p class="success-subtitle">
    Your purchase of <strong>"<?= htmlspecialchars($p['title']) ?>"</strong> was successful.
    Your prompt is unlocked below — copy and use it anywhere!
  </p>

  <?php if ($already_purchased): ?>
    <p style="font-size:0.8rem; color:var(--accent-warm); margin-bottom:8px;">✓ This purchase was already in your account.</p>
  <?php endif; ?>

  <!-- Unlocked Prompt -->
  <div class="unlocked-prompt">
    <p class="unlocked-prompt-label">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#166534" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
      Your Unlocked Prompt
    </p>
    <p class="unlocked-prompt-text" id="promptText"><?= htmlspecialchars($p['prompt_text']) ?></p>
    <button class="copy-btn" onclick="copyPrompt()">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
      <span id="copyText">Copy Prompt</span>
    </button>
  </div>

  <!-- Buyer Info -->
  <p style="font-size:0.78rem; color:var(--text-muted); margin-bottom:32px;">
    Purchased by: <code style="font-family:'DM Mono',monospace; color:var(--text-secondary);"><?= htmlspecialchars($buyer_email) ?></code>
    <?php if (!empty($transaction_id)): ?>
      &nbsp;·&nbsp; Order: <code style="font-family:'DM Mono',monospace; color:var(--text-secondary);"><?= htmlspecialchars($transaction_id) ?></code>
    <?php endif; ?>
  </p>

  <!-- Action Buttons -->
  <div class="success-actions">
    <a href="index.php" class="btn-primary">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Browse More Prompts
    </a>
    <a href="my_purchases.php" class="btn-secondary">My Purchases</a>
  </div>

<?php endif; ?>

</main>

<!-- =========== FOOTER =========== -->
<footer class="store-footer">
  <div class="store-footer-inner">
    <p class="footer-copy">© <?= date('Y') ?> Arigato Store. All rights reserved.</p>
    <div class="footer-links">
      <a href="#">Privacy Policy</a>
      <a href="#">Terms</a>
      <a href="#">Contact</a>
    </div>
  </div>
</footer>

<script>
  function copyPrompt() {
    const text = document.getElementById('promptText')?.innerText;
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
      const btn = document.getElementById('copyText');
      btn.textContent = 'Copied! ✓';
      setTimeout(() => { btn.textContent = 'Copy Prompt'; }, 2500);
    });
  }
</script>
<script src="js/store.js"></script>
<?php include 'store_firebase_js.php'; ?>
</body>
</html>
