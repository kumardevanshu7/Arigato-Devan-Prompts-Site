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

require_once '../db.php';

if (!$user_email) {
    header('Location: index.php');
    exit;
}

// Fetch purchases
$stmt = $pdo->prepare("
    SELECT p.*, pu.payment_id, pu.purchased_at 
    FROM store_purchases pu
    JOIN store_products p ON pu.product_id = p.id
    WHERE pu.buyer_email = ?
    ORDER BY pu.purchased_at DESC
");
$stmt->execute([$user_email]);
$purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Purchases — Arigato Store</title>
  <link rel="stylesheet" href="css/store.css"/>
  <style>
    .purchases-page { padding: 60px 20px; max-width: 1000px; margin: 0 auto; }
    .purchases-title { font-family: 'Playfair Display', serif; font-size: 2.5rem; margin-bottom: 40px; }
    .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
    .purchase-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; }
    .purchase-card { border: 1px solid var(--border); border-radius: var(--radius-card); background: var(--bg-card); padding: 24px; transition: var(--transition); }
    .purchase-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .purchase-title { font-weight: 600; font-size: 1.15rem; margin-bottom: 10px; color: var(--text-primary); }
    .purchase-meta { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px; }
    .btn-view { display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 12px; background: var(--text-primary); color: #fff; text-decoration: none; border-radius: var(--radius-btn); font-weight: 500; font-size: 0.95rem; }
    .btn-view:hover { opacity: 0.9; }
  </style>
</head>
<body>

<?php include 'store_nav.php'; ?>

<main class="purchases-page">
  <h1 class="purchases-title">My Premium Prompts</h1>

  <?php if (empty($purchases)): ?>
    <div class="empty-state">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:16px;opacity:0.5;">
        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
      <p style="font-size:1.1rem;margin-bottom:20px;">You haven't unlocked any premium prompts yet.</p>
      <a href="index.php" class="btn-view" style="width:auto;padding:12px 32px;">Browse Store</a>
    </div>
  <?php else: ?>
    <div class="purchase-list">
      <?php foreach ($purchases as $p): ?>
        <div class="purchase-card">
          <div class="purchase-title"><?= htmlspecialchars($p['title']) ?></div>
          <div class="purchase-meta">
            Purchased on: <?= date('M j, Y', strtotime($p['purchased_at'])) ?><br>
            Order ID: <?= htmlspecialchars($p['payment_id']) ?>
          </div>
          <a href="product.php?id=<?= $p['id'] ?>" class="btn-view">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            View Prompt
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

</body>
</html>
