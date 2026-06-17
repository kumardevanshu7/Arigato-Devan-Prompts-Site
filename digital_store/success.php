<?php
/**
 * success.php — Entry Door (SuperProfile Redirect Target)
 * 
 * 1. Verifies static secret key
 * 2. Verifies one-time session token
 * 3. Records purchase in DB
 * 4. Generates a one-time view token
 * 5. Redirects to view_prompt.php?token=XYZ
 */
session_start();
require_once '../db.php';

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$secret     = isset($_GET['secret'])     ? trim($_GET['secret'])     : '';

// ---- Helper: generate random token ----
function generateToken(int $length = 32): string {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $token = '';
    for ($i = 0; $i < $length; $i++) {
        $token .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $token;
}

// ---- Ensure view tokens table exists ----
if (isset($pdo)) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS store_view_tokens (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            token       VARCHAR(32) NOT NULL UNIQUE,
            product_id  INT NOT NULL,
            buyer_email VARCHAR(255) NOT NULL,
            used        TINYINT(1) DEFAULT 0,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    } catch (PDOException $e) { /* already exists */ }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS store_support_tickets (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            name         VARCHAR(255) NOT NULL,
            email        VARCHAR(255) NOT NULL,
            order_id     VARCHAR(100) DEFAULT '',
            issue_type   VARCHAR(50) DEFAULT '',
            sub_type     VARCHAR(100) DEFAULT '',
            description  TEXT NOT NULL,
            screenshot   VARCHAR(255) DEFAULT '',
            status       VARCHAR(20) DEFAULT 'open',
            created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    } catch (PDOException $e) { /* already exists */ }
}

$error = '';
$p = null;

if (!$product_id || !$secret || !isset($pdo)) {
    $error = 'invalid_link';
}

if (!$error) {
    // ---- Check 1: Secret key matches DB ----
    $stmt = $pdo->prepare("SELECT * FROM store_products WHERE id = ? AND active = 1");
    $stmt->execute([$product_id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$p || empty($p['secret_key'])) {
        $error = 'not_found';
    } elseif ($p['secret_key'] !== $secret) {
        $error = 'invalid_secret';
    }
}

if (!$error) {
    // ---- Check 2: Session token must exist and match ----
    $session_pid = isset($_SESSION['pending_pid']) ? (int)$_SESSION['pending_pid'] : 0;
    if ($session_pid !== $product_id) {
        $error = 'expired';
    }
}

// ---- Determine buyer email ----
$buyer_email = '';
if (!$error) {
    if (!empty($_SESSION['email'])) {
        $buyer_email = strtolower(trim($_SESSION['email']));
    } elseif (!empty($_SESSION['pending_email'])) {
        $buyer_email = strtolower(trim($_SESSION['pending_email']));
    } else {
        $error = 'no_email';
    }
}

if (!$error) {
    // ---- Consume session token immediately ----
    unset($_SESSION['pending_pid'], $_SESSION['pending_email']);

    // ---- Check/Record purchase (avoid duplicates) ----
    $chk = $pdo->prepare("SELECT id FROM store_purchases WHERE buyer_email = ? AND product_id = ? LIMIT 1");
    $chk->execute([$buyer_email, $product_id]);
    if (!$chk->fetch()) {
        $txn_id = isset($_GET['transaction_id']) ? trim($_GET['transaction_id']) : ('SP-' . strtoupper(substr(md5(uniqid()), 0, 8)));
        $pdo->prepare("INSERT INTO store_purchases (product_id, buyer_email, payment_id) VALUES (?, ?, ?)")
            ->execute([$product_id, $buyer_email, $txn_id]);
    }

    // ---- Generate one-time view token ----
    $view_token = generateToken(32);
    $pdo->prepare("INSERT INTO store_view_tokens (token, product_id, buyer_email) VALUES (?, ?, ?)")
        ->execute([$view_token, $product_id, $buyer_email]);

    // ---- Redirect to view_prompt.php ----
    header("Location: view_prompt.php?token=" . urlencode($view_token));
    exit;
}

// ---- If we get here, something failed — show error page ----
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Link Expired — Arigato Store</title>
  <meta name="robots" content="noindex, nofollow"/>
  <link rel="stylesheet" href="css/store.css"/>
</head>
<body>
<?php include 'store_nav.php'; ?>
<main class="success-page">
  <div class="success-icon-wrap"><svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 22h14"/><path d="M5 2h14"/><path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22"/><path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"/></svg></div>
  <span class="success-label" style="background:#FFF1F2;color:#9F1239;border-color:#FECDD3;">Link Expired</span>
  <h1 class="success-title" style="font-size:1.8rem;">This link has expired</h1>
  <p class="success-subtitle">
    This success link has already been used or is no longer valid.<br>
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:text-bottom;margin-right:4px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> <strong>Check your email</strong> — SuperProfile has sent you a receipt with your purchase.
  </p>
  <p class="success-subtitle" style="margin-top:-12px;">
    Already have an account? Visit <a href="my_purchases.php" style="color:var(--text-primary);font-weight:600;">My Purchases</a> to access your prompts.
  </p>
  <div class="success-actions">
    <a href="index.php" class="btn-primary">Browse Store</a>
    <a href="my_purchases.php" class="btn-secondary">My Purchases</a>
  </div>
</main>
<?php include '../footer.php'; ?>
<script src="js/store.js"></script>
<?php include 'store_firebase_js.php'; ?>
</body>
</html>
