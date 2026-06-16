<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Purchase Confirmed — Arigato Store</title>
  <meta name="description" content="Your prompt has been unlocked! Thank you for your purchase."/>
  <link rel="stylesheet" href="css/store.css"/>
</head>
<body>

<!-- =========== HEADER =========== -->
<?php include 'store_nav.php'; ?>

<?php
require_once '../db.php';

// Log the incoming SuperProfile redirect parameters for debugging
file_put_contents('redirect_log.txt', date('Y-m-d H:i:s') . " - " . print_r($_GET, true) . "\n", FILE_APPEND);

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if (!$product_id || !isset($pdo)) {
    header('Location: index.php');
    exit;
}

// Fetch product details
$stmt = $pdo->prepare("SELECT * FROM store_products WHERE id = ? AND active = 1");
$stmt->execute([$product_id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) {
    header('Location: index.php');
    exit;
}

/*
  NOTE FOR BACKEND PHASE:
  -------------------------
  When Super Profile redirects back here, they may send:
  - $_GET['transaction_id'] or $_GET['order_id']
  - You can use this to verify the payment with Super Profile's API
  - Then store the purchase in your database
  - For now, we show a static success UI
*/
$transaction_id = isset($_GET['transaction_id']) ? htmlspecialchars($_GET['transaction_id']) : 'SP-' . strtoupper(substr(md5(time()), 0, 8));
?>

<!-- =========== SUCCESS PAGE =========== -->
<main class="success-page">

  <!-- Icon -->
  <div class="success-icon-wrap">✅</div>

  <!-- Label -->
  <span class="success-label">Payment Confirmed</span>

  <!-- Title -->
  <h1 class="success-title">You're all set!</h1>

  <!-- Subtitle -->
  <p class="success-subtitle">
    Your purchase of <strong>"<?= htmlspecialchars($p['title']) ?>"</strong> was successful.
    Your prompt is now unlocked below. Copy and use it anywhere!
  </p>

  <!-- Unlocked Prompt -->
  <div class="unlocked-prompt">
    <p class="unlocked-prompt-label">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#166534" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
      Your Unlocked Prompt
    </p>
    <p class="unlocked-prompt-text" id="promptText"><?= htmlspecialchars($p['prompt']) ?></p>
    <button class="copy-btn" onclick="copyPrompt()">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
      <span id="copyText">Copy Prompt</span>
    </button>
  </div>

  <!-- Transaction Info -->
  <p style="font-size:0.78rem;color:var(--text-muted);margin-bottom:32px;">
    Transaction ID: <code style="font-family:'DM Mono',monospace;color:var(--text-secondary);"><?= $transaction_id ?></code>
  </p>

  <!-- Action Buttons -->
  <div class="success-actions">
    <a href="index.php" class="btn-primary">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Browse More Prompts
    </a>
    <a href="product.php?id=<?= $product_id ?>" class="btn-secondary">
      View Product Again
    </a>
  </div>

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
    const text = document.getElementById('promptText').innerText;
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
