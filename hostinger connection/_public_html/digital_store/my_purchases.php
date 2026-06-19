<?php 
session_start();



require_once '../db.php';

if (!$user_email) {
    header('Location: index.php');
    exit;
}

// Fetch purchases
$stmt = $pdo->prepare("
    SELECT p.*, pu.payment_id, pu.purchased_at,
           (SELECT filename FROM store_product_images WHERE product_id = p.id ORDER BY sort_order LIMIT 1) as thumb
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
    .purchases-page { padding: 60px 20px 80px; max-width: 1000px; margin: 0 auto; min-height: 70vh; }
    
    .purchases-header { text-align: center; margin-bottom: 48px; }
    .purchases-label {
      display: inline-block;
      background: #f8f4ef; color: #8b6914;
      border: 1px solid #e5d5b0; border-radius: 100px;
      font-size: 0.72rem; font-weight: 700;
      letter-spacing: 0.1em; text-transform: uppercase;
      padding: 5px 14px; margin-bottom: 16px;
    }
    .purchases-title { font-family: 'Playfair Display', serif; font-size: clamp(2rem, 5vw, 2.8rem); font-weight: 900; color: var(--text-primary); margin-bottom: 10px; }
    .purchases-subtitle { font-size: 0.95rem; color: var(--text-muted); }

    .empty-state { text-align: center; padding: 60px 20px; background: var(--bg-card); border: 1.5px dashed var(--border); border-radius: 20px; margin-top: 20px; }
    
    .purchase-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 24px; }
    
    .purchase-card {
      border: 1.5px solid var(--border);
      border-radius: 16px;
      background: var(--bg-card);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: var(--transition);
      box-shadow: 0 4px 16px rgba(0,0,0,0.02);
    }
    .purchase-card:hover { transform: translateY(-4px); border-color: var(--border-dark); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
    
    .purchase-thumb-wrap { width: 100%; aspect-ratio: 9/16; position: relative; background: var(--bg); border-bottom: 1px solid var(--border); }
    .purchase-thumb { width: 100%; height: 100%; object-fit: cover; }
    
    .purchase-content { padding: 16px; display: flex; flex-direction: column; flex: 1; }
    
    .purchase-cat { font-size: 0.65rem; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #c9a96e; margin-bottom: 4px; }
    .purchase-title { font-family: 'Playfair Display', serif; font-weight: 800; font-size: 1.25rem; line-height: 1.2; margin-bottom: 12px; color: var(--text-primary); }
    
    .purchase-meta { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 20px; flex: 1; line-height: 1.5; }
    .purchase-meta strong { color: var(--text-primary); font-weight: 600; }
    
    .btn-view {
      display: flex; align-items: center; justify-content: center; gap: 6px;
      width: 100%; padding: 12px 10px;
      background: var(--text-primary); color: #fff;
      border: none; border-radius: 12px;
      font-weight: 600; font-size: 0.85rem; cursor: pointer;
      transition: var(--transition);
      white-space: nowrap;
    }
    .btn-view:hover { background: #000; transform: translateY(-1px); }
  </style>
</head>
<body>

<?php include 'store_nav.php'; ?>

<main class="purchases-page">
  <div class="purchases-header">
    <span class="purchases-label">Library</span>
    <h1 class="purchases-title">My Premium Prompts</h1>
    <p class="purchases-subtitle">Access all your unlocked prompts and guides anytime.</p>
  </div>

  <?php if (empty($purchases)): ?>
    <div class="empty-state">
      <div style="margin-bottom:16px;opacity:0.6;color:var(--text-primary);"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg></div>
      <p style="font-size:1.05rem;font-weight:600;color:var(--text-primary);margin-bottom:8px;">Your library is empty</p>
      <p style="font-size:0.9rem;color:var(--text-muted);margin-bottom:24px;">You haven't unlocked any premium prompts yet.</p>
      <a href="index.php" class="btn-view" style="width:auto;padding:12px 32px;display:inline-flex;text-decoration:none;">Browse Store</a>
    </div>
  <?php else: ?>
    <div class="purchase-list">
      <?php foreach ($purchases as $p): ?>
        <div class="purchase-card">
          <div class="purchase-thumb-wrap">
            <?php if ($p['thumb']): ?>
              <img src="assets/images/<?= htmlspecialchars($p['thumb']) ?>" alt="Thumbnail" class="purchase-thumb" loading="lazy"/>
            <?php else: ?>
              <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--border);"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>
            <?php endif; ?>
          </div>
          <div class="purchase-content">
            <div class="purchase-cat"><?= htmlspecialchars($p['category']) ?></div>
            <div class="purchase-title"><?= htmlspecialchars($p['title']) ?></div>
            <div class="purchase-meta">
              Purchased: <strong><?= date('M j, Y', strtotime($p['purchased_at'])) ?></strong><br>
              Order ID: <span><?= htmlspecialchars($p['payment_id']) ?></span>
            </div>
            <!-- Triggers session token generation -> then redirects to success URL -->
            <button class="btn-view" onclick="openPurchase(<?= $p['id'] ?>, '<?= htmlspecialchars($p['secret_key'] ?? '') ?>', this)">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              View Prompt
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<?php include '../footer.php'; ?>

<script>
  function openPurchase(pid, secret, btn) {
    const origTxt = btn.innerHTML;
    btn.innerHTML = 'Opening...';
    btn.disabled = true;

    // We must hit init_purchase.php first to set $_SESSION['pending_pid']
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
</script>
<script src="js/store.js"></script>
<?php include 'store_firebase_js.php'; ?>
</body>
</html>
