<?php
// ============================================================
//  admin.php — Digital Store Admin Panel
//  Security: Only accessible to the Firebase Admin UID
//  Session must be active + google_uid must match ADMIN_UID
// ============================================================
session_start();

define('ADMIN_UID', '5RDnMAipOwZTA21JJCnkH2V4E492');
define('ADMIN_EMAIL', 'devansh.grow@gmail.com');

// ---- SECURITY GATE ----
// Must be logged in AND must be the admin UID or Email
$is_admin = false;
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['google_uid']) && $_SESSION['google_uid'] === ADMIN_UID) {
        $is_admin = true;
    }
    if (isset($_SESSION['email']) && strtolower($_SESSION['email']) === ADMIN_EMAIL) {
        $is_admin = true;
    }
}

if (!$is_admin) {
    header('Location: index.php');
    exit;
}

require_once '../db.php';

// ---- HANDLE ACTIONS (Add / Edit / Delete) ----
$action  = $_POST['action'] ?? $_GET['action'] ?? '';
$msg     = '';
$msg_type = 'success';

// ---- CREATE PRODUCTS TABLE IF NOT EXISTS ----
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS store_products (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        title         VARCHAR(255)   NOT NULL,
        category      VARCHAR(100)   DEFAULT '',
        price         INT            NOT NULL DEFAULT 0,
        discount      INT            NOT NULL DEFAULT 0,
        prompt_text   TEXT           NOT NULL,
        how_to_use    TEXT           DEFAULT '',
        badge         VARCHAR(50)    DEFAULT '',
        badge_type    VARCHAR(20)    DEFAULT '',
        super_url     VARCHAR(500)   DEFAULT '',
        active        TINYINT(1)     DEFAULT 1,
        created_at    DATETIME       DEFAULT CURRENT_TIMESTAMP,
        updated_at    DATETIME       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Add how_to_use column if upgrading existing DB
    try {
        $pdo->exec("ALTER TABLE store_products ADD COLUMN how_to_use TEXT DEFAULT '' AFTER prompt_text");
    } catch (PDOException $e) { /* column already exists */ }

    $pdo->exec("CREATE TABLE IF NOT EXISTS store_product_images (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        filename   VARCHAR(255) NOT NULL,
        sort_order TINYINT DEFAULT 0,
        FOREIGN KEY (product_id) REFERENCES store_products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {
    // Table might already exist, continue
}

// ---- UPLOAD HELPER ----
function uploadImages(array $files, int $product_id, PDO $pdo): void {
    $uploadDir = __DIR__ . '/assets/images/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    $order   = 0;
    foreach ($files['tmp_name'] as $i => $tmp) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        if (!in_array($files['type'][$i], $allowed))  continue;
        if ($files['size'][$i] > 5 * 1024 * 1024)    continue; // 5MB max

        $ext      = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
        $filename = 'prod_' . $product_id . '_' . time() . '_' . $order . '.' . $ext;
        move_uploaded_file($tmp, $uploadDir . $filename);

        $pdo->prepare("INSERT INTO store_product_images (product_id, filename, sort_order) VALUES (?,?,?)")
            ->execute([$product_id, $filename, $order]);
        $order++;
    }
}

// ---- ADD PRODUCT ----
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title']    ?? '');
    $category = trim($_POST['category'] ?? '');
    $price    = (int)($_POST['price']   ?? 0);
    $discount = (int)($_POST['discount']?? 0);
    $prompt   = trim($_POST['prompt']   ?? '');
    $badge    = trim($_POST['badge']    ?? '');
    $badge_t  = trim($_POST['badge_type']?? '');
    $url      = trim($_POST['super_url']?? '');

    $how_to_use = trim($_POST['how_to_use'] ?? '');

    if ($title && $price && $prompt) {
        try {
            $pdo->prepare("INSERT INTO store_products (title,category,price,discount,prompt_text,how_to_use,badge,badge_type,super_url) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$title,$category,$price,$discount,$prompt,$how_to_use,$badge,$badge_t,$url]);
            $new_id = $pdo->lastInsertId();

            // Handle image uploads
            if (!empty($_FILES['images']['tmp_name'][0])) {
                uploadImages($_FILES['images'], $new_id, $pdo);
            }

            $msg = "Product \"$title\" added successfully!";
        } catch (PDOException $e) {
            $msg      = "Error: " . $e->getMessage();
            $msg_type = 'error';
        }
    } else {
        $msg      = "Title, Price, and Prompt are required.";
        $msg_type = 'error';
    }
}

// ---- DELETE PRODUCT ----
if ($action === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    try {
        // Delete images from filesystem
        $imgs = $pdo->prepare("SELECT filename FROM store_product_images WHERE product_id = ?");
        $imgs->execute([$del_id]);
        foreach ($imgs->fetchAll(PDO::FETCH_COLUMN) as $f) {
            $fp = __DIR__ . '/assets/images/' . $f;
            if (file_exists($fp)) unlink($fp);
        }
        $pdo->prepare("DELETE FROM store_products WHERE id = ?")->execute([$del_id]);
        $msg = "Product deleted.";
    } catch (PDOException $e) {
        $msg      = "Error: " . $e->getMessage();
        $msg_type = 'error';
    }
}

// ---- TOGGLE ACTIVE ----
if ($action === 'toggle' && isset($_GET['id'])) {
    $tog_id = (int)$_GET['id'];
    $pdo->prepare("UPDATE store_products SET active = 1 - active WHERE id = ?")->execute([$tog_id]);
    $msg = "Product visibility updated.";
}

// ---- EDIT PRODUCT ----
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id  = (int)($_POST['edit_id'] ?? 0);
    $title    = trim($_POST['title']    ?? '');
    $category = trim($_POST['category'] ?? '');
    $price    = (int)($_POST['price']   ?? 0);
    $discount = (int)($_POST['discount']?? 0);
    $prompt   = trim($_POST['prompt']   ?? '');
    $badge    = trim($_POST['badge']    ?? '');
    $badge_t  = trim($_POST['badge_type']?? '');
    $url      = trim($_POST['super_url']?? '');

    $how_to_use = trim($_POST['how_to_use'] ?? '');

    if ($edit_id && $title && $price && $prompt) {
        try {
            $pdo->prepare("UPDATE store_products SET title=?,category=?,price=?,discount=?,prompt_text=?,how_to_use=?,badge=?,badge_type=?,super_url=? WHERE id=?")
                ->execute([$title,$category,$price,$discount,$prompt,$how_to_use,$badge,$badge_t,$url,$edit_id]);

            if (!empty($_FILES['images']['tmp_name'][0])) {
                uploadImages($_FILES['images'], $edit_id, $pdo);
            }

            $msg = "Product updated!";
        } catch (PDOException $e) {
            $msg      = "Error: " . $e->getMessage();
            $msg_type = 'error';
        }
    }
}

// ---- DELETE SINGLE IMAGE ----
if ($action === 'del_img' && isset($_GET['img_id'])) {
    $img_id = (int)$_GET['img_id'];
    $row    = $pdo->prepare("SELECT filename FROM store_product_images WHERE id = ?");
    $row->execute([$img_id]);
    $row = $row->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $fp = __DIR__ . '/assets/images/' . $row['filename'];
        if (file_exists($fp)) unlink($fp);
        $pdo->prepare("DELETE FROM store_product_images WHERE id = ?")->execute([$img_id]);
        $msg = "Image deleted.";
    }
}

// ---- FETCH ALL PRODUCTS ----
try {
    $products = $pdo->query("SELECT p.*, (SELECT COUNT(*) FROM store_product_images WHERE product_id = p.id) AS img_count FROM store_products p ORDER BY p.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
}

// ---- FETCH EDIT PRODUCT ----
$edit_product = null;
$edit_images  = [];
if (isset($_GET['edit'])) {
    $ep = $pdo->prepare("SELECT * FROM store_products WHERE id = ?");
    $ep->execute([(int)$_GET['edit']]);
    $edit_product = $ep->fetch(PDO::FETCH_ASSOC);

    $ei = $pdo->prepare("SELECT * FROM store_product_images WHERE product_id = ? ORDER BY sort_order");
    $ei->execute([(int)$_GET['edit']]);
    $edit_images = $ei->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Panel — Arigato Store</title>
  <meta name="robots" content="noindex, nofollow"/><!-- SEO: Keep admin hidden from search engines -->
  <link rel="stylesheet" href="css/store.css"/>
  <style>
    /* ===== ADMIN-SPECIFIC STYLES ===== */
    .admin-wrap {
      max-width: 1200px;
      margin: 0 auto;
      padding: 40px clamp(16px, 4vw, 60px) 80px;
    }

    /* Page header */
    .admin-page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 36px;
      flex-wrap: wrap;
      gap: 16px;
    }

    .admin-page-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(1.6rem, 4vw, 2.4rem);
      font-weight: 900;
      letter-spacing: -0.03em;
      color: var(--text-primary);
    }

    .admin-page-title span {
      color: var(--accent-warm);
      font-style: italic;
    }

    /* Toast / message */
    .admin-toast {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 20px;
      border-radius: var(--radius-sm);
      font-size: 0.9rem;
      font-weight: 500;
      margin-bottom: 28px;
      animation: fadeUp 0.4s ease forwards;
    }

    .admin-toast.success { background: #F0FAF4; color: #166534; border: 1px solid #BBF7D0; }
    .admin-toast.error   { background: #FFF1F2; color: #9F1239; border: 1px solid #FECDD3; }

    /* Stats row */
    .admin-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 16px;
      margin-bottom: 40px;
    }

    .stat-card {
      background: var(--bg-card);
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 20px 22px;
    }

    .stat-label {
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-bottom: 8px;
    }

    .stat-value {
      font-family: 'Playfair Display', serif;
      font-size: 2rem;
      font-weight: 900;
      color: var(--text-primary);
      letter-spacing: -0.03em;
    }

    /* Products table */
    .products-table-wrap {
      background: var(--bg-card);
      border: 1.5px solid var(--border);
      border-radius: var(--radius-card);
      overflow: hidden;
      margin-bottom: 40px;
    }

    .table-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px 24px;
      border-bottom: 1px solid var(--border);
    }

    .table-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--text-primary);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.875rem;
    }

    th {
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--text-muted);
      padding: 12px 20px;
      text-align: left;
      background: var(--bg);
      border-bottom: 1px solid var(--border);
    }

    td {
      padding: 14px 20px;
      border-bottom: 1px solid var(--border);
      color: var(--text-secondary);
      vertical-align: middle;
    }

    tr:last-child td { border-bottom: none; }
    tr:hover td { background: var(--bg-hover); }

    .td-title {
      font-weight: 600;
      color: var(--text-primary);
      font-family: 'Playfair Display', serif;
      max-width: 180px;
    }

    .td-thumb {
      width: 44px;
      height: 44px;
      object-fit: cover;
      border-radius: 8px;
      border: 1px solid var(--border);
    }

    .td-thumb-placeholder {
      width: 44px;
      height: 44px;
      border-radius: 8px;
      background: var(--border);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
    }

    /* Status badge */
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 0.7rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      padding: 4px 10px;
      border-radius: var(--radius-btn);
    }

    .status-badge.active   { background: #F0FAF4; color: #166534; }
    .status-badge.inactive { background: #F1F5F9; color: #94A3B8; }

    /* Action buttons */
    .action-btns { display: flex; gap: 8px; flex-wrap: wrap; }

    .btn-edit {
      font-size: 0.75rem;
      font-weight: 600;
      padding: 6px 14px;
      border-radius: var(--radius-btn);
      border: 1.5px solid var(--border-dark);
      color: var(--text-secondary);
      background: transparent;
      transition: var(--transition);
      cursor: pointer;
      text-decoration: none;
    }

    .btn-edit:hover {
      background: var(--bg-hover);
      color: var(--text-primary);
      border-color: var(--text-primary);
    }

    .btn-delete {
      font-size: 0.75rem;
      font-weight: 600;
      padding: 6px 14px;
      border-radius: var(--radius-btn);
      border: 1.5px solid #FECDD3;
      color: #9F1239;
      background: transparent;
      transition: var(--transition);
      cursor: pointer;
      text-decoration: none;
    }

    .btn-delete:hover {
      background: #FFF1F2;
      border-color: #9F1239;
    }

    .btn-toggle {
      font-size: 0.75rem;
      font-weight: 600;
      padding: 6px 14px;
      border-radius: var(--radius-btn);
      border: 1.5px solid var(--border);
      color: var(--text-muted);
      background: transparent;
      transition: var(--transition);
      cursor: pointer;
      text-decoration: none;
    }

    .btn-toggle:hover { background: var(--bg-hover); color: var(--text-secondary); }

    /* ADD / EDIT FORM */
    .admin-form-card {
      background: var(--bg-card);
      border: 1.5px solid var(--border);
      border-radius: var(--radius-card);
      overflow: hidden;
    }

    .form-card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px 28px;
      border-bottom: 1px solid var(--border);
      background: var(--bg);
    }

    .form-card-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.15rem;
      font-weight: 700;
      color: var(--text-primary);
    }

    .admin-form {
      padding: 28px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .form-group.full { grid-column: 1 / -1; }

    label {
      font-size: 0.75rem;
      font-weight: 600;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--text-muted);
    }

    input[type="text"],
    input[type="number"],
    input[type="url"],
    select,
    textarea {
      width: 100%;
      padding: 12px 16px;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      background: var(--bg);
      color: var(--text-primary);
      font-family: 'Inter', sans-serif;
      font-size: 0.88rem;
      transition: var(--transition);
      outline: none;
    }

    input:focus, select:focus, textarea:focus {
      border-color: var(--text-primary);
      background: var(--bg-card);
    }

    textarea {
      resize: vertical;
      min-height: 120px;
      font-family: 'DM Mono', monospace;
      font-size: 0.82rem;
      line-height: 1.7;
    }

    /* Image upload zone */
    .upload-zone {
      border: 2px dashed var(--border);
      border-radius: var(--radius-sm);
      padding: 28px;
      text-align: center;
      cursor: pointer;
      transition: var(--transition);
      background: var(--bg);
      position: relative;
    }

    .upload-zone:hover { border-color: var(--border-dark); background: var(--bg-hover); }
    .upload-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .upload-zone-icon { font-size: 1.8rem; margin-bottom: 10px; }
    .upload-zone-text { font-size: 0.84rem; color: var(--text-secondary); }
    .upload-zone-hint { font-size: 0.72rem; color: var(--text-muted); margin-top: 4px; }

    /* Existing images */
    .existing-images {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 14px;
    }

    .existing-img-wrap {
      position: relative;
      width: 72px;
      height: 72px;
      border-radius: 10px;
      overflow: hidden;
      border: 1.5px solid var(--border);
    }

    .existing-img-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .del-img-btn {
      position: absolute;
      top: 3px;
      right: 3px;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background: rgba(159,18,57,0.85);
      color: #fff;
      font-size: 0.65rem;
      display: flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      font-weight: 700;
      transition: var(--transition);
    }

    .del-img-btn:hover { background: #9F1239; }

    /* Form footer */
    .form-footer {
      padding: 20px 28px;
      border-top: 1px solid var(--border);
      display: flex;
      gap: 12px;
      align-items: center;
    }

    .btn-submit {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--btn-bg);
      color: var(--btn-text);
      font-size: 0.9rem;
      font-weight: 600;
      padding: 12px 28px;
      border-radius: var(--radius-btn);
      border: none;
      cursor: pointer;
      transition: var(--transition);
      letter-spacing: 0.03em;
    }

    .btn-submit:hover {
      background: var(--btn-hover);
      transform: translateY(-1px);
      box-shadow: var(--shadow-md);
    }

    .btn-cancel {
      font-size: 0.88rem;
      color: var(--text-muted);
      font-weight: 500;
      text-decoration: none;
      padding: 12px 20px;
      border-radius: var(--radius-btn);
      transition: var(--transition);
    }

    .btn-cancel:hover { color: var(--text-secondary); background: var(--bg-hover); }

    /* Price preview */
    .price-preview {
      font-size: 0.8rem;
      color: var(--text-muted);
      margin-top: 4px;
    }

    .price-preview span { color: var(--accent-warm); font-weight: 600; }

    /* Empty state */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--text-muted);
    }

    .empty-state-icon { font-size: 2.5rem; margin-bottom: 12px; }
    .empty-state p    { font-size: 0.9rem; }

    /* Responsive */
    @media (max-width: 700px) {
      .admin-form { grid-template-columns: 1fr; }
      .form-group.full { grid-column: 1; }
      table { display: block; overflow-x: auto; }
    }
  </style>
</head>
<body>

<?php include 'store_nav.php'; ?>

<main class="admin-wrap">

  <!-- ===== PAGE HEADER ===== -->
  <div class="admin-page-header">
    <h1 class="admin-page-title">
      <span>Admin</span> Panel
    </h1>
    <a href="?add=1" class="buy-btn" style="width:auto;padding:12px 24px;font-size:0.88rem;">
      Add New Product
    </a>
  </div>

  <!-- ===== TOAST MESSAGE ===== -->
  <?php if ($msg): ?>
    <div class="admin-toast <?= $msg_type ?>">
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <!-- ===== STATS ===== -->
  <?php
    $total    = count($products);
    $active   = array_filter($products, fn($p) => $p['active']);
    $inactive = $total - count($active);
  ?>
  <div class="admin-stats">
    <div class="stat-card">
      <p class="stat-label">Total Products</p>
      <p class="stat-value"><?= $total ?></p>
    </div>
    <div class="stat-card">
      <p class="stat-label">Active</p>
      <p class="stat-value" style="color:var(--success-text)"><?= count($active) ?></p>
    </div>
    <div class="stat-card">
      <p class="stat-label">Hidden</p>
      <p class="stat-value" style="color:var(--text-muted)"><?= $inactive ?></p>
    </div>
  </div>

  <!-- ===== PRODUCTS TABLE ===== -->
  <div class="products-table-wrap">
    <div class="table-header">
      <span class="table-title">All Products</span>
      <span style="font-size:0.78rem;color:var(--text-muted)"><?= $total ?> products</span>
    </div>

    <?php if (empty($products)): ?>
      <div class="empty-state">
        <div class="empty-state-icon"></div>
        <p>No products yet. Add your first product above!</p>
      </div>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Image</th>
          <th>Title</th>
          <th>Category</th>
          <th>Price</th>
          <th>Discount</th>
          <th>Imgs</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $prod):
          // Get first image
          $fi = $pdo->prepare("SELECT filename FROM store_product_images WHERE product_id = ? ORDER BY sort_order LIMIT 1");
          $fi->execute([$prod['id']]);
          $thumb = $fi->fetchColumn();
          $disc_pct = $prod['price'] > 0 ? round((($prod['price'] - $prod['discount']) / $prod['price']) * 100) : 0;
        ?>
        <tr>
          <td>
            <?php if ($thumb): ?>
              <img class="td-thumb" src="assets/images/<?= htmlspecialchars($thumb) ?>" alt="Thumb"/>
            <?php else: ?>
              <div class="td-thumb-placeholder">🖼️</div>
            <?php endif; ?>
          </td>
          <td class="td-title"><?= htmlspecialchars($prod['title']) ?></td>
          <td><?= htmlspecialchars($prod['category']) ?></td>
          <td>₹<?= $prod['price'] ?></td>
          <td>
            ₹<?= $prod['discount'] ?>
            <?php if ($disc_pct > 0): ?>
              <span style="font-size:0.7rem;color:var(--accent-warm);font-weight:600;"> (<?= $disc_pct ?>% off)</span>
            <?php endif; ?>
          </td>
          <td><?= $prod['img_count'] ?></td>
          <td>
            <span class="status-badge <?= $prod['active'] ? 'active' : 'inactive' ?>">
              <?= $prod['active'] ? 'Live' : 'Hidden' ?>
            </span>
          </td>
          <td>
            <div class="action-btns">
              <!-- Edit -->
              <a href="?edit=<?= $prod['id'] ?>" class="btn-edit" title="Edit product">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit
              </a>
              <!-- Hide/Show -->
              <a href="?action=toggle&id=<?= $prod['id'] ?>" class="btn-toggle"
                 onclick="return confirm('Toggle visibility?')" title="<?= $prod['active'] ? 'Hide' : 'Show' ?>">
                <?php if ($prod['active']): ?>
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                  Hide
                <?php else: ?>
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  Show
                <?php endif; ?>
              </a>
              <!-- Delete -->
              <a href="?action=delete&id=<?= $prod['id'] ?>" class="btn-delete"
                 onclick="return confirm('Delete \'<?= addslashes($prod['title']) ?>\' permanently?')" title="Delete">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                Delete
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- ===== ADD / EDIT FORM ===== -->
  <?php if (isset($_GET['add']) || $edit_product): ?>
  <div class="admin-form-card" id="productForm">
    <div class="form-card-header">
      <span class="form-card-title">
        <?= $edit_product ? '' . htmlspecialchars($edit_product['title']) : 'Add New Product' ?>
      </span>
      <a href="admin.php" class="btn-cancel">Cancel</a>
    </div>

    <form class="admin-form" method="POST" action="admin.php" enctype="multipart/form-data">
      <input type="hidden" name="action"  value="<?= $edit_product ? 'edit' : 'add' ?>"/>
      <?php if ($edit_product): ?>
        <input type="hidden" name="edit_id" value="<?= $edit_product['id'] ?>"/>
      <?php endif; ?>

      <!-- Title -->
      <div class="form-group">
        <label for="title">Product Title *</label>
        <input type="text" id="title" name="title" required
               value="<?= htmlspecialchars($edit_product['title'] ?? '') ?>"
               placeholder="e.g. Golden Hour Landscape"/>
      </div>

      <!-- Category -->
      <div class="form-group">
        <label for="category">Category</label>
        <input type="text" id="category" name="category"
               value="<?= htmlspecialchars($edit_product['category'] ?? '') ?>"
               placeholder="e.g. Landscape, Portrait, Sci-Fi"/>
      </div>

      <!-- Price -->
      <div class="form-group">
        <label for="price">Original Price (₹) *</label>
        <input type="number" id="price" name="price" min="0" required
               value="<?= $edit_product['price'] ?? '' ?>"
               placeholder="499" oninput="updatePreview()"/>
      </div>

      <!-- Discount Price -->
      <div class="form-group">
        <label for="discount">Discount Price (₹) *</label>
        <input type="number" id="discount" name="discount" min="0" required
               value="<?= $edit_product['discount'] ?? '' ?>"
               placeholder="299" oninput="updatePreview()"/>
        <p class="price-preview" id="pricePreview"></p>
      </div>

      <!-- Badge -->
      <div class="form-group">
        <label for="badge">Badge Text</label>
        <input type="text" id="badge" name="badge"
               value="<?= htmlspecialchars($edit_product['badge'] ?? '') ?>"
               placeholder="e.g. Bestseller, New, Sale (leave empty for none)"/>
      </div>

      <!-- Badge Type -->
      <div class="form-group">
        <label for="badge_type">Badge Style</label>
        <select id="badge_type" name="badge_type">
          <option value=""    <?= ($edit_product['badge_type'] ?? '') === ''     ? 'selected' : '' ?>>Default (Dark)</option>
          <option value="sale"<?= ($edit_product['badge_type'] ?? '') === 'sale' ? 'selected' : '' ?>>Sale (Gold)</option>
        </select>
      </div>

      <!-- Super Profile URL -->
      <div class="form-group full">
        <label for="super_url">Super Profile Payment URL</label>
        <input type="url" id="super_url" name="super_url"
               value="<?= htmlspecialchars($edit_product['super_url'] ?? '') ?>"
               placeholder="https://superprofile.bio/your-product-link"/>
      </div>

      <!-- Prompt Text -->
      <div class="form-group full">
        <label for="prompt">Full Prompt Text * (this will be blurred until purchase)</label>
        <textarea id="prompt" name="prompt" required
                  placeholder="Write your complete AI prompt here..."><?= htmlspecialchars($edit_product['prompt_text'] ?? '') ?></textarea>
      </div>

      <!-- How to Use -->
      <div class="form-group full">
        <label for="how_to_use">How to Use <span style="font-weight:400;color:var(--text-muted);">(shown on product page — step-by-step instructions for the buyer)</span></label>
        <textarea id="how_to_use" name="how_to_use"
                  placeholder="e.g. Step 1: Open ChatGPT or Midjourney&#10;Step 2: Paste the prompt&#10;Step 3: Adjust the settings as needed&#10;Step 4: Generate and enjoy!"
                  style="min-height:130px;"><?= htmlspecialchars($edit_product['how_to_use'] ?? '') ?></textarea>
      </div>

      <!-- Image Upload -->
      <div class="form-group full">
        <label>Product Preview Images (up to 5 images, max 5MB each)</label>

        <?php if ($edit_images): ?>
          <div class="existing-images">
            <?php foreach ($edit_images as $img): ?>
              <div class="existing-img-wrap">
                <img src="assets/images/<?= htmlspecialchars($img['filename']) ?>" alt="Preview"/>
                <a href="?action=del_img&img_id=<?= $img['id'] ?>&edit=<?= $edit_product['id'] ?>"
                   class="del-img-btn"
                   onclick="return confirm('Delete this image?')"></a>
              </div>
            <?php endforeach; ?>
          </div>
          <p style="font-size:0.75rem;color:var(--text-muted);margin-top:8px;">
            Upload more images below (existing ones above will be kept):
          </p>
        <?php endif; ?>

        <div class="upload-zone" id="uploadZone">
          <input type="file" name="images[]" accept="image/*" multiple id="imgInput" onchange="previewFiles(this)"/>
          <div class="upload-zone-icon"></div>
          <p class="upload-zone-text">Click to upload or drag & drop images</p>
          <p class="upload-zone-hint">JPG, PNG, WEBP — up to 5 files, 5MB each</p>
          <div id="imgPreviewRow" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;justify-content:center;"></div>
        </div>
      </div>

    </form>

    <div class="form-footer">
      <button form="productFormEl" class="btn-submit" onclick="document.querySelector('.admin-form').submit()">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        <?= $edit_product ? 'Save Changes' : 'Add Product' ?>
      </button>
      <a href="admin.php" class="btn-cancel">Cancel</a>
    </div>
  </div>
  <?php endif; ?>

</main>

<?php include 'store_firebase_js.php'; ?>

<script>
  // Price discount preview
  function updatePreview() {
    const price    = parseInt(document.getElementById('price').value)    || 0;
    const discount = parseInt(document.getElementById('discount').value) || 0;
    const el       = document.getElementById('pricePreview');
    if (price > 0 && discount > 0 && discount < price) {
      const pct = Math.round(((price - discount) / price) * 100);
      el.innerHTML = 'Customer saves <span>₹' + (price - discount) + ' (' + pct + '% off)</span>';
    } else {
      el.innerHTML = '';
    }
  }
  updatePreview();

  // Image file preview
  function previewFiles(input) {
    const row = document.getElementById('imgPreviewRow');
    row.innerHTML = '';
    Array.from(input.files).slice(0, 5).forEach(file => {
      const reader = new FileReader();
      reader.onload = e => {
        const img = document.createElement('img');
        img.src = e.target.result;
        img.style.cssText = 'width:60px;height:60px;object-fit:cover;border-radius:8px;border:1.5px solid var(--border);';
        row.appendChild(img);
      };
      reader.readAsDataURL(file);
    });
  }

  // Auto-scroll to form if adding/editing
  <?php if (isset($_GET['add']) || $edit_product): ?>
    document.getElementById('productForm')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  <?php endif; ?>

  // Toast auto-hide
  const toast = document.querySelector('.admin-toast');
  if (toast) setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.5s'; }, 4000);
</script>

</body>
</html>
