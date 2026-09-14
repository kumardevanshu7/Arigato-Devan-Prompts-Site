<?php
header('Content-Type: application/json');

require_once __DIR__ . '/includes/session_bootstrap.php';
require_once __DIR__ . '/db.php';

$name  = trim(strip_tags($_POST['name']  ?? ''));
$email = trim(strip_tags($_POST['email'] ?? ''));
$query = trim(strip_tags($_POST['query'] ?? ''));

if (!$name || !$email || !$query) {
    echo json_encode(['ok' => false, 'error' => 'Please fill in all required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'Please provide a valid email address.']);
    exit;
}

if (strlen($query) > 2000) {
    echo json_encode(['ok' => false, 'error' => 'Message length must not exceed 2,000 characters.']);
    exit;
}

$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

// 1. Save message securely into database
$db_saved = false;
try {
    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, message, ip_address, user_agent, status) VALUES (?, ?, ?, ?, ?, 'unread')");
    $db_saved = $stmt->execute([$name, $email, $query, $ip_address, $user_agent]);
} catch (Exception $e) {
    error_log("Contact Form Database Error: " . $e->getMessage());
}

$admin_email = 'devansh.grow@gmail.com';
$from_domain = 'noreply@arigatodevan.com';
$site_name   = 'Arigato Devan';
$formatted_date = date('d M Y, h:i A') . ' IST';

// -------------------------------------------------------------
// 2. Ultra-Clean Professional Admin Notification Email (Google / Workspace Style)
// -------------------------------------------------------------
$admin_subject = "New Contact Inquiry from {$name} - {$site_name}";
$safe_name  = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safe_email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$safe_query = nl2br(htmlspecialchars($query, ENT_QUOTES, 'UTF-8'));
$mailto_reply = "mailto:" . rawurlencode($email) . "?subject=" . rawurlencode("Re: Your message to Arigato Devan");

$admin_body = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New Contact Inquiry</title>
</head>
<body style="margin:0;padding:0;background-color:#F8F9FA;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,\'Helvetica Neue\',Arial,sans-serif;color:#1F2937;">
  <div style="width:100%;background-color:#F8F9FA;padding:36px 0;">
    <div style="max-width:580px;margin:0 auto;background:#FFFFFF;border-radius:14px;border:1px solid #E5E7EB;overflow:hidden;box-shadow:0 4px 18px rgba(0,0,0,0.03);">
      
      <!-- Header -->
      <div style="background-color:#204162;padding:26px 32px;text-align:left;">
        <div style="display:inline-block;background:rgba(255,255,255,0.15);color:#FFFFFF;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;padding:4px 10px;border-radius:6px;margin-bottom:8px;">New Contact Inquiry</div>
        <h1 style="font-size:20px;font-weight:800;color:#FFFFFF;margin:0;letter-spacing:-0.01em;">Message from ' . $safe_name . '</h1>
        <p style="font-size:13px;color:rgba(255,255,255,0.8);margin:5px 0 0;">Received on ' . $formatted_date . '</p>
      </div>

      <!-- Content -->
      <div style="padding:28px 32px;">
        <!-- Sender Meta Table -->
        <table style="width:100%;border-collapse:separate;border-spacing:0;border:1px solid #E5E7EB;border-radius:10px;overflow:hidden;margin-bottom:22px;">
          <tr>
            <td style="width:100px;padding:12px 16px;font-size:13px;font-weight:600;color:#6B7280;background-color:#F9FAFB;border-bottom:1px solid #F3F4F6;">Name</td>
            <td style="padding:12px 16px;font-size:14px;font-weight:600;color:#111827;border-bottom:1px solid #F3F4F6;">' . $safe_name . '</td>
          </tr>
          <tr>
            <td style="padding:12px 16px;font-size:13px;font-weight:600;color:#6B7280;background-color:#F9FAFB;border-bottom:1px solid #F3F4F6;">Email</td>
            <td style="padding:12px 16px;font-size:14px;font-weight:600;color:#204162;border-bottom:1px solid #F3F4F6;">
              <a href="mailto:' . $safe_email . '" style="color:#204162;text-decoration:none;">' . $safe_email . '</a>
            </td>
          </tr>
          <tr>
            <td style="padding:12px 16px;font-size:13px;font-weight:600;color:#6B7280;background-color:#F9FAFB;">Received</td>
            <td style="padding:12px 16px;font-size:13px;color:#4B5563;">' . $formatted_date . '</td>
          </tr>
        </table>

        <!-- Message Body -->
        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#6B7280;margin:0 0 8px;">Message / Query</div>
        <div style="background-color:#F9FAFB;border-left:4px solid #204162;border-radius:0 10px 10px 0;padding:16px 20px;font-size:14px;line-height:1.65;color:#1F2937;margin-bottom:26px;">' . $safe_query . '</div>

        <!-- Reply Button -->
        <div style="text-align:center;margin:10px 0 16px;">
          <a href="' . $mailto_reply . '" style="display:inline-block;background-color:#204162;color:#FFFFFF;font-size:14px;font-weight:600;text-decoration:none;padding:12px 28px;border-radius:8px;box-shadow:0 2px 6px rgba(32,65,98,0.2);">Reply to ' . $safe_name . '</a>
        </div>
      </div>

      <!-- Footer -->
      <div style="background-color:#F9FAFB;padding:18px 32px;text-align:center;border-top:1px solid #F3F4F6;">
        <p style="font-size:12px;color:#9CA3AF;margin:0;line-height:1.5;">Sent securely via Arigato Devan contact form &nbsp;&middot;&nbsp; <a href="https://arigatodevan.com" style="color:#6B7280;text-decoration:none;">arigatodevan.com</a></p>
      </div>

    </div>
  </div>
</body>
</html>';

$admin_headers  = "MIME-Version: 1.0\r\n";
$admin_headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$admin_headers .= "From: {$site_name} <{$from_domain}>\r\n";
$admin_headers .= "Reply-To: {$name} <{$email}>\r\n";
$admin_headers .= "X-Mailer: PHP/" . phpversion();

$admin_sent = @mail($admin_email, $admin_subject, $admin_body, $admin_headers);

// -------------------------------------------------------------
// 3. Ultra-Clean Professional User Confirmation Email (Apple / Google Style)
// -------------------------------------------------------------
$user_subject = "We received your message - {$site_name}";

$user_body_html = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Message Received - Arigato Devan</title>
</head>
<body style="margin:0;padding:0;background-color:#F8F9FA;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,\'Helvetica Neue\',Arial,sans-serif;color:#1F2937;">
  <div style="width:100%;background-color:#F8F9FA;padding:36px 0;">
    <div style="max-width:580px;margin:0 auto;background:#FFFFFF;border-radius:14px;border:1px solid #E5E7EB;overflow:hidden;box-shadow:0 4px 18px rgba(0,0,0,0.03);">

      <!-- Clean Header with Logo -->
      <div style="padding:28px 32px 20px;text-align:center;border-bottom:1px solid #F3F4F6;">
        <img src="https://arigatodevan.com/toplogo/logo01.webp" alt="Arigato Devan" style="height:48px;width:auto;max-height:48px;border:0;outline:none;display:inline-block;vertical-align:middle;" />
        <div style="font-size:18px;font-weight:800;color:#204162;margin-top:10px;letter-spacing:-0.02em;">Arigato Devan</div>
      </div>

      <!-- Main Body -->
      <div style="padding:30px 32px;">
        <div style="display:inline-block;background-color:#ECFDF5;color:#065F46;border:1px solid #A7F3D0;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:5px 12px;border-radius:999px;margin-bottom:16px;">&#10003; &nbsp;Message Received</div>

        <h1 style="font-size:21px;font-weight:800;color:#111827;margin:0 0 10px;line-height:1.3;">Hi ' . $safe_name . ',</h1>
        <p style="font-size:14px;line-height:1.65;color:#4B5563;margin:0 0 20px;">Thank you for reaching out to us! We have received your inquiry and our team is currently reviewing it. We typically reply directly to your email within <strong>24 hours</strong>.</p>

        <!-- Message Summary Card -->
        <div style="background-color:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;padding:18px 20px;margin-bottom:24px;">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#6B7280;margin:0 0 8px;">Your Message Summary</div>
          <div style="font-size:13px;line-height:1.6;color:#1F2937;font-style:italic;">&#8220;' . $safe_query . '&#8221;</div>
        </div>

        <p style="font-size:13px;line-height:1.6;color:#6B7280;margin:0 0 20px;">In the meantime, feel free to explore our latest trending AI couple prompts or follow our journey.</p>

        <!-- Action Button -->
        <div style="text-align:center;margin:16px 0;">
          <a href="https://arigatodevan.com/gallery.php" style="display:inline-block;background-color:#204162;color:#FFFFFF;font-size:13px;font-weight:600;text-decoration:none;padding:11px 26px;border-radius:8px;box-shadow:0 2px 6px rgba(32,65,98,0.2);">Explore Prompts</a>
        </div>

        <!-- Social Note -->
        <div style="margin-top:24px;padding-top:16px;border-top:1px dashed #E5E7EB;text-align:center;">
          <p style="font-size:12px;color:#6B7280;margin:0;">Need urgent assistance? Reach out on Instagram: <a href="https://instagram.com/arigato.devan" style="color:#2FA6C6;text-decoration:none;font-weight:600;">@arigato.devan</a></p>
        </div>
      </div>

      <!-- Footer -->
      <div style="background-color:#F9FAFB;padding:20px 32px;text-align:center;border-top:1px solid #F3F4F6;">
        <p style="font-size:12px;color:#9CA3AF;margin:0 0 4px;line-height:1.5;">&copy; ' . date('Y') . ' Arigato Devan Prompts &nbsp;&middot;&nbsp; All rights reserved.</p>
        <p style="font-size:11px;color:#9CA3AF;margin:0;"><a href="https://arigatodevan.com/privacy.php" style="color:#6B7280;text-decoration:none;">Privacy Policy</a> &nbsp;&middot;&nbsp; <a href="https://arigatodevan.com/contact.php" style="color:#6B7280;text-decoration:none;">Contact</a></p>
      </div>

    </div>
  </div>
</body>
</html>';

$user_headers  = "MIME-Version: 1.0\r\n";
$user_headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$user_headers .= "From: {$site_name} <{$from_domain}>\r\n";
$user_headers .= "X-Mailer: PHP/" . phpversion();

$user_sent = @mail($email, $user_subject, $user_body_html, $user_headers);

// If either email was sent OR database recorded the message, it's a success!
if ($admin_sent || $db_saved) {
    echo json_encode(['ok' => true]);
} else {
    echo json_encode([
        'ok' => false,
        'error' => 'Failed to send message. Please email us directly at devansh.grow@gmail.com'
    ]);
}
?>