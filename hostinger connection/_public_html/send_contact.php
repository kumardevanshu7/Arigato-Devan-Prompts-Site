<?php
header('Content-Type: application/json');

$name  = trim(strip_tags($_POST['name']  ?? ''));
$email = trim(strip_tags($_POST['email'] ?? ''));
$query = trim(strip_tags($_POST['query'] ?? ''));

if (!$name || !$email || !$query) {
    echo json_encode(['ok' => false, 'error' => 'All fields are required.']); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid email address.']); exit;
}
if (strlen($query) > 2000) {
    echo json_encode(['ok' => false, 'error' => 'Message too long.']); exit;
}

$admin_email = 'devansh.grow@gmail.com';
$from_domain = 'noreply@arigatodevan.com';
$site_name   = 'Arigato Devan PromptVerse';

// ── Email to Admin ──
$admin_subject = "New Contact Message from {$name}";
$admin_body = '<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
  body{font-family:"Outfit",Arial,sans-serif;background:#f8f4ff;margin:0;padding:0;}
  .wrap{max-width:540px;margin:30px auto;background:#fff;border:3px solid #2d2a35;border-radius:20px;overflow:hidden;box-shadow:6px 6px 0 #2d2a35;}
  .hdr{background:#2d2a35;padding:24px 30px;text-align:center;}
  .hdr h1{color:#c084fc;font-size:1.3rem;font-weight:900;margin:0;}
  .hdr p{color:#aaa;font-size:.8rem;margin:4px 0 0;}
  .body{padding:28px 30px;}
  .row{background:#f8f4ff;border:2px solid #2d2a35;border-radius:12px;padding:14px 18px;margin-bottom:14px;}
  .row .lbl{font-size:.72rem;font-weight:900;text-transform:uppercase;letter-spacing:.05em;color:#888;margin-bottom:4px;}
  .row .val{font-size:.95rem;font-weight:700;color:#2d2a35;word-break:break-word;}
  .reply-btn{display:inline-block;background:#c084fc;border:2.5px solid #2d2a35;border-radius:10px;padding:10px 24px;font-weight:900;font-size:.88rem;color:#2d2a35;text-decoration:none;box-shadow:3px 3px 0 #2d2a35;margin-top:6px;}
  .ftr{background:#f0eeff;padding:14px 30px;text-align:center;border-top:2px dashed #c084fc;}
  .ftr p{color:#888;font-size:.75rem;margin:0;}
</style></head><body>
<div class="wrap">
  <div class="hdr"><h1>New Contact Message</h1><p>arigatodevan.com</p></div>
  <div class="body">
    <div class="row"><div class="lbl">Name</div><div class="val">' . htmlspecialchars($name) . '</div></div>
    <div class="row"><div class="lbl">Email</div><div class="val"><a href="mailto:' . htmlspecialchars($email) . '" style="color:#7c3aed;">' . htmlspecialchars($email) . '</a></div></div>
    <div class="row"><div class="lbl">Query</div><div class="val">' . nl2br(htmlspecialchars($query)) . '</div></div>
    <a href="mailto:' . htmlspecialchars($email) . '" class="reply-btn">Reply to User</a>
  </div>
  <div class="ftr"><p>Sent from arigatodevan.com contact form &nbsp;&middot;&nbsp; ' . date('d M Y, h:i A') . ' IST</p></div>
</div>
</body></html>';

$admin_headers  = "MIME-Version: 1.0\r\n";
$admin_headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$admin_headers .= "From: {$site_name} <{$from_domain}>\r\n";
$admin_headers .= "Reply-To: {$name} <{$email}>\r\n";
$admin_headers .= "X-Mailer: PHP/" . phpversion();

$admin_sent = mail($admin_email, $admin_subject, $admin_body, $admin_headers);

// ── Confirmation Email to User ──
$user_subject = "We received your message — {$site_name}";

$user_body_html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
  body{font-family:"Outfit",Arial,sans-serif;background:#f8f4ff;margin:0;padding:0;}
  .wrap{max-width:560px;margin:30px auto;background:#ffffff;border:3px solid #2d2a35;border-radius:20px;overflow:hidden;box-shadow:6px 6px 0 #2d2a35;}
  .header-bar{background:#c084fc;padding:28px 32px;text-align:center;border-bottom:3px solid #2d2a35;}
  .header-bar h1{color:#2d2a35;font-size:1.6rem;font-weight:900;margin:0;}
  .header-bar p{color:#2d2a35;font-size:.88rem;font-weight:600;margin:6px 0 0;}
  .body-sec{padding:32px;}
  .body-sec p{color:#444;font-size:.95rem;line-height:1.75;font-weight:500;margin-bottom:14px;}
  .highlight-box{background:#f8f4ff;border:2px solid #c084fc;border-radius:12px;padding:16px 20px;margin:20px 0;}
  .highlight-box p{margin:0;font-size:.88rem;color:#555;}
  .footer-bar{background:#f0eeff;padding:18px 32px;text-align:center;border-top:2px dashed #c084fc;}
  .footer-bar p{color:#888;font-size:.78rem;font-weight:600;margin:0;}
  .btn{display:inline-block;background:#c084fc;border:2.5px solid #2d2a35;border-radius:10px;padding:12px 28px;font-weight:900;font-size:.9rem;color:#2d2a35;text-decoration:none;box-shadow:3px 3px 0 #2d2a35;margin-top:10px;}
</style></head><body>
<div class="wrap">
  <div class="header-bar">
    <h1>Message Received!</h1>
    <p>Arigato Devan PromptVerse</p>
  </div>
  <div class="body-sec">
    <p>Hi <strong>' . htmlspecialchars($name) . '</strong>,</p>
    <p>Thank you for reaching out to us! Your message has been sent successfully.</p>
    <div class="highlight-box">
      <p><strong>Admin aapka message review karega aur jaldi reply karega.</strong><br>
      We typically respond within <strong>24 hours</strong>.</p>
    </div>
    <p>Your message:</p>
    <div class="highlight-box">
      <p>' . nl2br(htmlspecialchars($query)) . '</p>
    </div>
    <p>In the meantime, feel free to browse our latest prompts or follow us on Instagram for updates.</p>
    <a href="https://arigatodevan.com" class="btn">Visit Site</a>
  </div>
  <div class="footer-bar">
    <p>&copy; ' . date('Y') . ' Arigato Devan PromptVerse &nbsp;&middot;&nbsp; arigatodevan.com</p>
  </div>
</div>
</body></html>';

$user_headers  = "MIME-Version: 1.0\r\n";
$user_headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$user_headers .= "From: {$site_name} <{$from_domain}>\r\n";
$user_headers .= "X-Mailer: PHP/" . phpversion();

$user_sent = mail($email, $user_subject, $user_body_html, $user_headers);

if ($admin_sent) {
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Failed to send message. Please try again or email us directly at devansh.grow@gmail.com']);
}
?>
