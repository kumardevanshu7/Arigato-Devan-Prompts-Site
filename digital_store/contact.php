<?php
/**
 * contact.php — Support / Contact Page
 * Handles ticket submission with dynamic form + email notifications
 */
session_start();
require_once '../db.php';

$success_msg = '';
$error_msg   = '';

// ---- HANDLE FORM SUBMISSION ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $name       = trim($_POST['name']       ?? '');
    $email      = trim($_POST['email']      ?? '');
    $order_id   = trim($_POST['order_id']   ?? '');
    $issue_type = trim($_POST['issue_type'] ?? '');
    $sub_type   = trim($_POST['sub_type']   ?? '');
    $description = trim($_POST['description'] ?? '');
    $screenshot_filename = '';

    // Basic validation
    if (!$name || !$email || !$issue_type || !$description) {
        $error_msg = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Please enter a valid email address.';
    } elseif (strlen($description) > 600) {
        $error_msg = 'Description is too long (max ~50 words / 600 characters).';
    } else {
        // Handle screenshot upload (only for payment issues)
        if ($issue_type === 'Payment Issue' && !empty($_FILES['screenshot']['tmp_name'])) {
            $sc = $_FILES['screenshot'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (in_array($sc['type'], $allowed_types) && $sc['size'] <= 5 * 1024 * 1024) {
                $sc_dir = __DIR__ . '/assets/tickets/';
                if (!is_dir($sc_dir)) mkdir($sc_dir, 0755, true);
                $ext = pathinfo($sc['name'], PATHINFO_EXTENSION);
                $screenshot_filename = 'ticket_' . time() . '_' . random_int(100, 999) . '.' . $ext;
                move_uploaded_file($sc['tmp_name'], $sc_dir . $screenshot_filename);
            }
        }

        // Save to DB
        if (isset($pdo)) {
            try {
                $pdo->prepare("INSERT INTO store_support_tickets (name, email, order_id, issue_type, sub_type, description, screenshot) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$name, $email, $order_id, $issue_type, $sub_type, $description, $screenshot_filename]);
            } catch (PDOException $e) { /* log if needed */ }
        }

        // ---- Email to Admin ----
        $admin_email = 'devansh.grow@gmail.com';
        $admin_subject = "🎫 New Support Ticket — Arigato Store [{$issue_type}]";
        $admin_body = "
        <html><body style='font-family:Inter,sans-serif;background:#f5f5f0;padding:0;margin:0;'>
        <div style='max-width:580px;margin:32px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);'>
          <div style='background:#1a1a1a;padding:28px 32px;'>
            <div style='color:#fff;font-size:1.1rem;font-weight:700;letter-spacing:-0.02em;'>Arigato<span style='color:#c9a96e;'>.</span>Store</div>
            <div style='color:#aaa;font-size:0.8rem;margin-top:4px;'>Support Ticket Received</div>
          </div>
          <div style='padding:32px;'>
            <div style='background:#f8f4ef;border-left:4px solid #c9a96e;padding:16px 20px;border-radius:8px;margin-bottom:24px;'>
              <div style='font-size:0.75rem;color:#888;text-transform:uppercase;letter-spacing:0.08em;'>Issue Type</div>
              <div style='font-size:1.05rem;font-weight:700;color:#1a1a1a;margin-top:4px;'>{$issue_type}" . ($sub_type ? " → {$sub_type}" : "") . "</div>
            </div>
            <table style='width:100%;border-collapse:collapse;margin-bottom:20px;'>
              <tr><td style='padding:8px 0;color:#888;font-size:0.82rem;width:110px;'>Name</td><td style='padding:8px 0;font-size:0.9rem;font-weight:600;color:#1a1a1a;'>" . htmlspecialchars($name) . "</td></tr>
              <tr><td style='padding:8px 0;color:#888;font-size:0.82rem;'>Email</td><td style='padding:8px 0;font-size:0.9rem;font-weight:600;color:#1a1a1a;'>" . htmlspecialchars($email) . "</td></tr>
              " . ($order_id ? "<tr><td style='padding:8px 0;color:#888;font-size:0.82rem;'>Order ID</td><td style='padding:8px 0;font-size:0.9rem;font-weight:600;color:#1a1a1a;'>" . htmlspecialchars($order_id) . "</td></tr>" : "") . "
            </table>
            <div style='margin-bottom:20px;'>
              <div style='font-size:0.75rem;color:#888;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;'>Description</div>
              <div style='background:#f5f5f0;padding:16px;border-radius:8px;font-size:0.88rem;color:#333;line-height:1.7;'>" . nl2br(htmlspecialchars($description)) . "</div>
            </div>
            " . ($screenshot_filename ? "<div style='margin-bottom:20px;'><div style='font-size:0.75rem;color:#888;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;'>Screenshot Uploaded</div><div style='font-size:0.85rem;color:#1a1a1a;'>✅ Screenshot attached — check: <code>digital_store/assets/tickets/{$screenshot_filename}</code></div></div>" : "") . "
            <div style='font-size:0.78rem;color:#aaa;border-top:1px solid #eee;padding-top:16px;margin-top:8px;'>Received: " . date('D, d M Y — H:i') . " IST</div>
          </div>
        </div>
        </body></html>";

        $admin_headers  = "MIME-Version: 1.0\r\n";
        $admin_headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $admin_headers .= "From: Arigato Store <noreply@arigatodevan.com>\r\n";
        @mail($admin_email, $admin_subject, $admin_body, $admin_headers);

        // ---- Confirmation Email to User ----
        $user_subject = "We received your query — Arigato Store";
        $user_body = "
        <html><body style='font-family:Inter,sans-serif;background:#f5f5f0;padding:0;margin:0;'>
        <div style='max-width:540px;margin:32px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);'>
          <div style='background:#1a1a1a;padding:28px 32px;'>
            <div style='color:#fff;font-size:1.1rem;font-weight:700;letter-spacing:-0.02em;'>Arigato<span style='color:#c9a96e;'>.</span>Store</div>
          </div>
          <div style='padding:32px;'>
            <div style='font-size:2rem;margin-bottom:16px;'>👋</div>
            <h2 style='font-size:1.3rem;font-weight:800;color:#1a1a1a;margin:0 0 12px;'>Hi " . htmlspecialchars($name) . ", we got your message!</h2>
            <p style='font-size:0.9rem;color:#555;line-height:1.7;margin-bottom:20px;'>
              Thank you for reaching out to Arigato Store. Your support ticket has been received and our team will look into it shortly.
            </p>
            <div style='background:#f8f4ef;border-radius:10px;padding:16px 20px;margin-bottom:24px;'>
              <div style='font-size:0.78rem;color:#888;margin-bottom:4px;text-transform:uppercase;letter-spacing:0.06em;'>Your Issue</div>
              <div style='font-size:0.9rem;font-weight:700;color:#1a1a1a;'>{$issue_type}" . ($sub_type ? " → {$sub_type}" : "") . "</div>
            </div>
            <div style='background:#1a1a1a;border-radius:10px;padding:16px 20px;margin-bottom:28px;text-align:center;'>
              <div style='color:#c9a96e;font-size:1.4rem;font-weight:900;'>⏱ 48 Hours</div>
              <div style='color:#aaa;font-size:0.82rem;margin-top:4px;'>We'll resolve your issue within 48 hours</div>
            </div>
            <p style='font-size:0.82rem;color:#888;line-height:1.6;'>
              If you have any additional information to share, simply reply to this email.
            </p>
          </div>
          <div style='background:#f5f5f0;padding:20px 32px;text-align:center;'>
            <p style='font-size:0.75rem;color:#aaa;margin:0;'>© " . date('Y') . " Arigato Store. All rights reserved.</p>
          </div>
        </div>
        </body></html>";

        $user_headers  = "MIME-Version: 1.0\r\n";
        $user_headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $user_headers .= "From: Arigato Store Support <noreply@arigatodevan.com>\r\n";
        @mail($email, $user_subject, $user_body, $user_headers);

        $success_msg = "Your ticket has been submitted! Check your email — we'll get back to you within 48 hours. 🎉";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Contact &amp; Support — Arigato Store</title>
  <meta name="description" content="Raise a support ticket for payment issues or any queries. Arigato Store support."/>
  <link rel="stylesheet" href="css/store.css"/>
  <style>
    .contact-page { max-width: 680px; margin: 0 auto; padding: 60px 20px 80px; }

    .contact-header { text-align: center; margin-bottom: 48px; }
    .contact-label {
      display: inline-block;
      background: #f8f4ef;
      color: #8b6914;
      border: 1px solid #e5d5b0;
      border-radius: 100px;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      padding: 5px 14px;
      margin-bottom: 16px;
    }
    .contact-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(2rem, 5vw, 3rem);
      font-weight: 900;
      color: var(--text-primary);
      margin-bottom: 12px;
      line-height: 1.15;
    }
    .contact-subtitle { font-size: 0.95rem; color: var(--text-muted); line-height: 1.7; }

    /* Form card */
    .ticket-card {
      background: var(--bg-card);
      border: 1.5px solid var(--border);
      border-radius: 20px;
      padding: 36px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.04);
    }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .form-row.full-row { grid-template-columns: 1fr; }

    .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
    .form-group label {
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--text-muted);
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
      padding: 12px 16px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      background: var(--bg);
      color: var(--text-primary);
      font-family: 'Inter', sans-serif;
      font-size: 0.88rem;
      outline: none;
      transition: border-color 0.2s;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus { border-color: var(--text-primary); }
    .form-group textarea { resize: vertical; min-height: 100px; line-height: 1.6; }

    /* Sub-options (dynamic) */
    .sub-options {
      display: none;
      background: var(--bg);
      border: 1.5px solid var(--border);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 16px;
    }
    .sub-options.visible { display: block; }
    .sub-option-label { font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.07em; }

    .radio-group { display: flex; flex-direction: column; gap: 10px; }
    .radio-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 16px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.2s;
      background: var(--bg-card);
    }
    .radio-item:hover { border-color: var(--border-dark); background: var(--bg-hover); }
    .radio-item input[type="radio"] { accent-color: var(--text-primary); }
    .radio-item-text { font-size: 0.88rem; color: var(--text-secondary); font-weight: 500; }

    /* Screenshot upload */
    .upload-area {
      display: none;
      border: 2px dashed var(--border);
      border-radius: 12px;
      padding: 24px;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s;
      background: var(--bg);
      position: relative;
      margin-top: 12px;
    }
    .upload-area.visible { display: block; }
    .upload-area:hover { border-color: var(--border-dark); background: var(--bg-hover); }
    .upload-area input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .upload-icon { font-size: 1.5rem; margin-bottom: 8px; }
    .upload-text { font-size: 0.84rem; color: var(--text-secondary); }
    .upload-hint { font-size: 0.72rem; color: var(--text-muted); margin-top: 4px; }

    /* Word counter */
    .word-counter { font-size: 0.72rem; color: var(--text-muted); text-align: right; margin-top: 4px; }

    /* Submit button */
    .submit-btn {
      width: 100%;
      padding: 15px;
      background: var(--text-primary);
      color: #fff;
      border: none;
      border-radius: 12px;
      font-size: 0.95rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
      margin-top: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    .submit-btn:hover { opacity: 0.85; transform: translateY(-1px); }

    /* Alert messages */
    .alert {
      padding: 16px 20px;
      border-radius: 12px;
      font-size: 0.88rem;
      font-weight: 500;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .alert-success { background: #F0FAF4; color: #166534; border: 1px solid #BBF7D0; }
    .alert-error   { background: #FFF1F2; color: #9F1239; border: 1px solid #FECDD3; }

    /* Info cards at top */
    .info-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 36px; }
    .info-card {
      background: var(--bg-card);
      border: 1.5px solid var(--border);
      border-radius: 14px;
      padding: 20px;
      text-align: center;
    }
    .info-card-icon { font-size: 1.4rem; margin-bottom: 8px; }
    .info-card-title { font-size: 0.9rem; font-weight: 700; color: var(--text-primary); margin-bottom: 4px; }
    .info-card-text { font-size: 0.78rem; color: var(--text-muted); line-height: 1.5; }

    @media (max-width: 520px) {
      .form-row { grid-template-columns: 1fr; }
      .info-cards { grid-template-columns: 1fr; }
      .ticket-card { padding: 24px 20px; }
    }
  </style>
</head>
<body>
<?php include 'store_nav.php'; ?>

<main class="contact-page">

  <!-- Header -->
  <div class="contact-header">
    <span class="contact-label">Support</span>
    <h1 class="contact-title">How can we<br><em>help you?</em></h1>
    <p class="contact-subtitle">Raise a ticket below — we'll get back to you within 48 hours.</p>
  </div>

  <!-- Info Cards -->
  <div class="info-cards">
    <div class="info-card">
      <div class="info-card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></div>
      <div class="info-card-title">Fast Response</div>
      <div class="info-card-text">All tickets resolved within 48 hours, usually much faster.</div>
    </div>
    <div class="info-card">
      <div class="info-card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
      <div class="info-card-title">Secure Uploads</div>
      <div class="info-card-text">Your payment screenshots are stored securely and only seen by admin.</div>
    </div>
  </div>

  <!-- Alerts -->
  <?php if ($success_msg): ?>
    <div class="alert alert-success"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> <?= htmlspecialchars($success_msg) ?></div>
  <?php endif; ?>
  <?php if ($error_msg): ?>
    <div class="alert alert-error"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> <?= htmlspecialchars($error_msg) ?></div>
  <?php endif; ?>

  <!-- Ticket Form -->
  <?php if (!$success_msg): ?>
  <div class="ticket-card">
    <form method="POST" enctype="multipart/form-data" id="ticketForm">

      <!-- Name + Email -->
      <div class="form-row">
        <div class="form-group">
          <label>Your Name *</label>
          <input type="text" name="name" placeholder="Rahul Sharma" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"/>
        </div>
        <div class="form-group">
          <label>Your Email *</label>
          <input type="email" name="email" placeholder="you@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
        </div>
      </div>

      <!-- Issue Type -->
      <div class="form-group">
        <label>Issue Type *</label>
        <select name="issue_type" id="issueType" required onchange="handleIssueType(this.value)">
          <option value="" disabled selected>Select your issue...</option>
          <option value="Payment Issue" <?= ($_POST['issue_type'] ?? '') === 'Payment Issue' ? 'selected' : '' ?>>Payment Issue</option>
          <option value="Others" <?= ($_POST['issue_type'] ?? '') === 'Others' ? 'selected' : '' ?>>Others</option>
        </select>
      </div>

      <!-- Payment Sub-Options (shown only for Payment Issue) -->
      <div class="sub-options" id="paymentSubOptions">
        <div class="sub-option-label">What's the payment issue?</div>
        <div class="radio-group">
          <label class="radio-item" id="radioPaymentReceived">
            <input type="radio" name="sub_type" value="Payment done but product not received" id="r1" onchange="handleSubType('payment_not_received')"/>
            <span class="radio-item-text"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:text-bottom;margin-right:6px;"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg> Payment done but product not received</span>
          </label>
          <label class="radio-item" id="radioOtherPayment">
            <input type="radio" name="sub_type" value="Other payment query" id="r2" onchange="handleSubType('other_payment')"/>
            <span class="radio-item-text"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:text-bottom;margin-right:6px;"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Other payment query</span>
          </label>
        </div>
      </div>

      <!-- Order ID (shown for payment issues) -->
      <div class="form-group" id="orderIdGroup" style="display:none;">
        <label>Order ID / Transaction ID</label>
        <input type="text" name="order_id" id="orderId" placeholder="SP-XXXXXXXX or transaction reference" value="<?= htmlspecialchars($_POST['order_id'] ?? '') ?>"/>
      </div>

      <!-- Screenshot Upload (shown only for "payment not received") -->
      <div class="upload-area" id="screenshotArea">
        <input type="file" name="screenshot" id="screenshotInput" accept="image/*" onchange="showFileName(this)"/>
        <div class="upload-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></div>
        <div class="upload-text" id="uploadLabel">Upload Payment Screenshot</div>
        <div class="upload-hint">JPEG, PNG or WebP — max 5MB</div>
      </div>

      <!-- Description -->
      <div class="form-group" style="margin-top:16px;">
        <label id="descLabel">Describe your issue * <span style="font-weight:400;text-transform:none;letter-spacing:0;">(max 50 words)</span></label>
        <textarea name="description" id="descField" placeholder="Briefly describe your issue..." required oninput="countWords(this)"></textarea>
        <div class="word-counter" id="wordCount">0 / 50 words</div>
      </div>

      <input type="hidden" name="submit_ticket" value="1"/>
      <button type="submit" class="submit-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        Submit Ticket
      </button>
    </form>
  </div>
  <?php endif; ?>

</main>

<!-- Footer -->
<?php include '../footer.php'; ?>

<script>
  function handleIssueType(val) {
    const payOpts = document.getElementById('paymentSubOptions');
    const orderGrp = document.getElementById('orderIdGroup');
    const scArea   = document.getElementById('screenshotArea');
    const descLabel = document.getElementById('descLabel');

    if (val === 'Payment Issue') {
      payOpts.classList.add('visible');
      orderGrp.style.display = 'flex';
    } else {
      payOpts.classList.remove('visible');
      orderGrp.style.display = 'none';
      scArea.classList.remove('visible');
      // Reset radios
      document.querySelectorAll('input[name="sub_type"]').forEach(r => r.checked = false);
      document.getElementById('descLabel').innerHTML = 'Describe your issue * <span style="font-weight:400;text-transform:none;letter-spacing:0;">(max 50 words)</span>';
    }
  }

  function handleSubType(type) {
    const scArea = document.getElementById('screenshotArea');
    if (type === 'payment_not_received') {
      scArea.classList.add('visible');
      document.getElementById('descLabel').innerHTML = 'Describe your issue * <span style="font-weight:400;text-transform:none;letter-spacing:0;">(max 50 words — explain what happened)</span>';
    } else {
      scArea.classList.remove('visible');
    }
  }

  function showFileName(input) {
    if (input.files && input.files[0]) {
      document.getElementById('uploadLabel').textContent = '✓ ' + input.files[0].name;
    }
  }

  function countWords(textarea) {
    const words = textarea.value.trim().split(/\s+/).filter(w => w.length > 0);
    const count = words.length;
    const el = document.getElementById('wordCount');
    el.textContent = count + ' / 50 words';
    el.style.color = count > 50 ? '#9F1239' : 'var(--text-muted)';
  }

  // Restore state on page load if POST error
  <?php if (!empty($_POST['issue_type'])): ?>
  window.addEventListener('DOMContentLoaded', () => {
    handleIssueType(<?= json_encode($_POST['issue_type']) ?>);
    <?php if (!empty($_POST['sub_type'])): ?>
    const r = document.querySelector(`input[value=<?= json_encode($_POST['sub_type']) ?>]`);
    if (r) { r.checked = true; handleSubType(r.value.includes('not received') ? 'payment_not_received' : 'other_payment'); }
    <?php endif; ?>
    const txt = document.getElementById('descField');
    txt.value = <?= json_encode($_POST['description'] ?? '') ?>;
    countWords(txt);
  });
  <?php endif; ?>
</script>
<script src="js/store.js"></script>
<?php include 'store_firebase_js.php'; ?>
</body>
</html>
