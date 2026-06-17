<?php
/**
 * view_prompt.php — One-Time Prompt Viewer
 * 
 * - Verifies the one-time token from DB
 * - Marks token as "used" immediately on load
 * - Shows full prompt + PDF download + Drive link
 * - If token already used or invalid → shows expired page
 */
session_start();
require_once '../db.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$error = '';
$p     = null;
$tok   = null;

if (!$token || !isset($pdo)) {
    $error = 'invalid';
}

if (!$error) {
    // Fetch token from DB
    $stmt = $pdo->prepare("SELECT * FROM store_view_tokens WHERE token = ? LIMIT 1");
    $stmt->execute([$token]);
    $tok = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tok) {
        $error = 'not_found';
    } elseif ($tok['used']) {
        $error = 'already_used';
    }
}

if (!$error) {
    // Fetch product
    $ps = $pdo->prepare("SELECT * FROM store_products WHERE id = ? AND active = 1");
    $ps->execute([$tok['product_id']]);
    $p = $ps->fetch(PDO::FETCH_ASSOC);

    if (!$p) {
        $error = 'product_gone';
    }
}

if (!$error) {
    // ---- CONSUME TOKEN IMMEDIATELY ----
    $pdo->prepare("UPDATE store_view_tokens SET used = 1 WHERE token = ?")
        ->execute([$token]);
}

$buyer_email = $tok['buyer_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $error ? 'Link Expired — Arigato Store' : 'Your Prompt — Arigato Store' ?></title>
  <meta name="robots" content="noindex, nofollow"/>
  <link rel="stylesheet" href="css/store.css"/>
  <style>
    /* ===== VIEW PROMPT EXTRA STYLES ===== */
    .vp-wrap {
      max-width: 720px;
      margin: 0 auto;
      padding: 60px 20px 80px;
    }

    /* Success header */
    .vp-header {
      text-align: center;
      margin-bottom: 40px;
    }

    .vp-check {
      width: 64px;
      height: 64px;
      background: linear-gradient(135deg, #d4edda, #a8d5b5);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      margin: 0 auto 20px;
      box-shadow: 0 8px 24px rgba(22,101,52,0.15);
    }

    .vp-label {
      display: inline-block;
      background: #F0FAF4;
      color: #166534;
      border: 1px solid #BBF7D0;
      border-radius: 100px;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      padding: 5px 14px;
      margin-bottom: 16px;
    }

    .vp-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(1.8rem, 5vw, 2.6rem);
      font-weight: 900;
      color: var(--text-primary);
      margin-bottom: 12px;
      line-height: 1.2;
    }

    .vp-subtitle {
      font-size: 0.95rem;
      color: var(--text-muted);
      line-height: 1.7;
      max-width: 480px;
      margin: 0 auto;
    }

    /* Prompt box */
    .vp-prompt-box {
      background: var(--bg-card);
      border: 1.5px solid var(--border);
      border-radius: 16px;
      overflow: hidden;
      margin-bottom: 20px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    }

    .vp-prompt-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 20px;
      border-bottom: 1px solid var(--border);
      background: var(--bg);
    }

    .vp-prompt-dots {
      display: flex;
      gap: 5px;
    }

    .vp-prompt-dots span {
      width: 10px;
      height: 10px;
      border-radius: 50%;
    }

    .vp-prompt-dots span:nth-child(1) { background: #FF5F57; }
    .vp-prompt-dots span:nth-child(2) { background: #FFBD2E; }
    .vp-prompt-dots span:nth-child(3) { background: #28CA41; }

    .vp-unlocked-badge {
      font-size: 0.72rem;
      color: #166534;
      font-weight: 700;
      letter-spacing: 0.05em;
    }

    .vp-prompt-text {
      padding: 24px;
      font-family: 'DM Mono', monospace;
      font-size: 0.88rem;
      line-height: 1.8;
      color: var(--text-primary);
      white-space: pre-wrap;
      word-break: break-word;
    }

    /* Copy button */
    .vp-copy-btn {
      display: flex;
      align-items: center;
      gap: 8px;
      background: var(--text-primary);
      color: #fff;
      border: none;
      padding: 13px 24px;
      border-radius: var(--radius-btn);
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      width: 100%;
      justify-content: center;
      margin-bottom: 12px;
    }

    .vp-copy-btn:hover { opacity: 0.85; transform: translateY(-1px); }

    /* Download / Drive buttons */
    .vp-downloads {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-bottom: 32px;
    }

    .vp-dl-btn {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 13px 20px;
      border-radius: var(--radius-btn);
      font-size: 0.88rem;
      font-weight: 600;
      text-decoration: none;
      transition: var(--transition);
      border: 1.5px solid var(--border-dark);
      color: var(--text-secondary);
      background: var(--bg-card);
    }

    .vp-dl-btn:hover {
      background: var(--bg-hover);
      color: var(--text-primary);
      transform: translateY(-1px);
    }

    .vp-dl-btn .dl-icon {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .dl-pdf  { background: #FFF1F2; }
    .dl-drive { background: #EFF6FF; }

    /* Meta info */
    .vp-meta {
      text-align: center;
      font-size: 0.78rem;
      color: var(--text-muted);
      margin-bottom: 32px;
      padding: 14px;
      background: var(--bg-card);
      border-radius: 10px;
      border: 1px solid var(--border);
    }

    /* Actions row */
    .vp-actions {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .vp-actions a {
      flex: 1;
      min-width: 140px;
      text-align: center;
      padding: 12px 20px;
      border-radius: var(--radius-btn);
      font-size: 0.88rem;
      font-weight: 600;
      text-decoration: none;
      transition: var(--transition);
    }

    .vp-actions .btn-primary {
      background: var(--text-primary);
      color: #fff;
    }

    .vp-actions .btn-primary:hover { opacity: 0.85; }

    .vp-actions .btn-secondary {
      background: transparent;
      color: var(--text-secondary);
      border: 1.5px solid var(--border-dark);
    }

    .vp-actions .btn-secondary:hover { background: var(--bg-hover); }

    /* ===== EXPIRED STATE ===== */
    .expired-page {
      text-align: center;
      padding: 80px 20px;
      max-width: 520px;
      margin: 0 auto;
    }

    .expired-icon {
      font-size: 3.5rem;
      margin-bottom: 20px;
    }

    .expired-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.9rem;
      font-weight: 900;
      margin-bottom: 12px;
    }

    .expired-text {
      font-size: 0.9rem;
      color: var(--text-muted);
      line-height: 1.7;
      margin-bottom: 30px;
    }
  </style>
</head>
<body>
<?php include 'store_nav.php'; ?>

<?php if ($error): ?>
<!-- =========== EXPIRED STATE =========== -->
<main>
  <div class="expired-page">
    <div class="expired-icon"><svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 22h14"/><path d="M5 2h14"/><path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22"/><path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"/></svg></div>
    <span class="vp-label" style="background:#FFF1F2;color:#9F1239;border-color:#FECDD3;margin-bottom:20px;display:inline-block;">Link Expired</span>
    <h1 class="expired-title">This link has expired</h1>
    <p class="expired-text">
      <?php if ($error === 'already_used'): ?>
        This prompt view link has already been used. For security, each link works only once.<br><br>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:text-bottom;margin-right:4px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> <strong>SuperProfile has emailed you your receipt</strong> with the prompt attached.<br><br>
        If you have an account, visit <strong>My Purchases</strong> below to view all your prompts anytime.
      <?php else: ?>
        This link is invalid or has expired. Please check your SuperProfile email for your purchase receipt.
      <?php endif; ?>
    </p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="index.php" class="btn-primary" style="padding:12px 28px;border-radius:var(--radius-btn);background:var(--text-primary);color:#fff;text-decoration:none;font-weight:600;font-size:0.9rem;">Browse Store</a>
      <a href="my_purchases.php" class="btn-secondary" style="padding:12px 28px;border-radius:var(--radius-btn);border:1.5px solid var(--border-dark);color:var(--text-secondary);text-decoration:none;font-weight:600;font-size:0.9rem;">My Purchases</a>
    </div>
  </div>
</main>

<?php else: ?>
<!-- =========== SUCCESS STATE =========== -->
<main>
  <div class="vp-wrap">

    <!-- Header -->
    <div class="vp-header">
      <div class="vp-check"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#166534" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>
      <span class="vp-label">Payment Confirmed</span>
      <h1 class="vp-title">Your prompt is ready!</h1>
      <p class="vp-subtitle">
        <strong><?= htmlspecialchars($p['title']) ?></strong> is now unlocked.
        Copy the prompt below and use it in any AI tool instantly.
      </p>
    </div>

    <!-- Prompt Box -->
    <div class="vp-prompt-box">
      <div class="vp-prompt-header">
        <div class="vp-prompt-dots">
          <span></span><span></span><span></span>
        </div>
        <span style="font-size:0.72rem;color:var(--text-muted);">prompt.txt</span>
        <span class="vp-unlocked-badge"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:text-bottom;margin-right:2px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg> Unlocked</span>
      </div>
      <p class="vp-prompt-text" id="vp_promptText"><?= htmlspecialchars($p['prompt_text']) ?></p>
    </div>

    <!-- Copy Button -->
    <button class="vp-copy-btn" onclick="vpCopy()">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
      <span id="vp_copyTxt">Copy Prompt</span>
    </button>

    <!-- Downloads -->
    <?php $hasPdf = !empty($p['pdf_file']); $hasDrive = !empty($p['drive_url']); ?>
    <?php if ($hasPdf || $hasDrive): ?>
    <div class="vp-downloads">
      <?php if ($hasPdf): ?>
      <a href="assets/pdfs/<?= htmlspecialchars($p['pdf_file']) ?>" download class="vp-dl-btn">
        <div class="dl-icon dl-pdf">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9F1239" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
        </div>
        <div>
          <div style="font-size:0.9rem;color:var(--text-primary);">Download PDF Guide</div>
          <div style="font-size:0.75rem;color:var(--text-muted);font-weight:400;">Detailed step-by-step instructions</div>
        </div>
        <svg style="margin-left:auto;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
      </a>
      <?php endif; ?>

      <?php if ($hasDrive): ?>
      <a href="<?= htmlspecialchars($p['drive_url']) ?>" target="_blank" rel="noopener noreferrer" class="vp-dl-btn">
        <div class="dl-icon dl-drive">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1D4ED8" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
        </div>
        <div>
          <div style="font-size:0.9rem;color:var(--text-primary);">View on Google Drive</div>
          <div style="font-size:0.75rem;color:var(--text-muted);font-weight:400;">Open full guide in Drive</div>
        </div>
        <svg style="margin-left:auto;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Meta -->
    <div class="vp-meta">
      Purchased by <strong><?= htmlspecialchars($buyer_email) ?></strong> &nbsp;·&nbsp;
      <a href="my_purchases.php" style="color:var(--text-primary);font-weight:600;">View all purchases →</a>
    </div>

    <!-- Actions -->
    <div class="vp-actions">
      <a href="index.php" class="btn-primary">Browse More Prompts</a>
      <a href="my_purchases.php" class="btn-secondary">My Purchases</a>
    </div>

  </div>
</main>
<?php endif; ?>

<!-- Footer -->
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

<script>
  function vpCopy() {
    const text = document.getElementById('vp_promptText')?.innerText;
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
      const btn = document.getElementById('vp_copyTxt');
      btn.textContent = 'Copied! ✓';
      setTimeout(() => { btn.textContent = 'Copy Prompt'; }, 2500);
    });
  }
</script>
<script src="js/store.js"></script>
<?php include 'store_firebase_js.php'; ?>
</body>
</html>
