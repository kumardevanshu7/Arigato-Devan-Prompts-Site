<?php
/*
 * store_nav.php — Shared navbar for all digital store pages
 * - Logo from main site
 * - Firebase Google Auth (session-aware)
 * - Admin button shown only for admin UID
 * - "How it Works" removed
 */

$ADMIN_UID = "5RDnMAipOwZTA21JJCnkH2V4E492";
$ADMIN_EMAIL = "devansh.grow@gmail.com";

$is_store_admin = (isset($_SESSION['google_uid']) && $_SESSION['google_uid'] === $ADMIN_UID) 
               || (isset($_SESSION['email']) && strtolower($_SESSION['email']) === $ADMIN_EMAIL);

$is_logged_in   = isset($_SESSION['user_id']);
$user_name      = $_SESSION['username'] ?? '';
$user_avatar    = $_SESSION['profile_image'] ?? '';
// Fix relative path — store is in /digital_store/ subfolder
if ($user_avatar && !str_starts_with($user_avatar, 'http')) {
    $user_avatar = '../' . ltrim($user_avatar, '/');
}
?>
<style>
.shop-glowing-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px !important;
  border-radius: 8px;
  background: rgba(201,169,110,0.1);
  border: 1px solid rgba(201,169,110,0.5);
  color: var(--accent-warm) !important;
  box-shadow: 0 0 10px rgba(201,169,110,0.2), inset 0 0 10px rgba(201,169,110,0.1);
  animation: navGlow 2s infinite alternate;
  font-weight: 600;
}
@keyframes navGlow {
  from { box-shadow: 0 0 5px rgba(201,169,110,0.2), inset 0 0 5px rgba(201,169,110,0.1); }
  to { box-shadow: 0 0 15px rgba(201,169,110,0.5), inset 0 0 15px rgba(201,169,110,0.3); }
}
.back-to-home-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px !important;
  border-radius: 8px;
  border: 1.5px solid var(--border-dark);
  color: var(--text-secondary) !important;
  font-weight: 600;
  transition: all 0.2s;
}
.back-to-home-btn:hover {
  border-color: var(--text-primary);
  color: var(--text-primary) !important;
  transform: translateY(-1px);
}
</style>
<header class="store-header">
  <div class="store-header-inner">

    <!-- LOGO + BRAND TEXT -->
    <a href="index.php" class="store-logo-img" title="Back to Store">
      <img src="../toplogo/logo01.webp" alt="Arigato Devan Prompts Logo" height="36"/>
      <span class="store-logo-text" style="font-family:'Playfair Display',serif;">arigatoPrompt<span class="logo-dot">.</span>store</span>
    </a>

    <!-- NAV LINKS -->
    <nav class="store-nav">
      <a href="index.php" class="shop-glowing-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        Shop
      </a>
      <a href="about.php" class="<?= basename($_SERVER['PHP_SELF']) === 'about.php' ? 'nav-active' : '' ?>">About</a>
      <a href="../index.php" class="back-to-home-btn" title="Back to Main Portfolio">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Back to Home
      </a>
    </nav>

    <!-- RIGHT SIDE BUTTONS -->
    <div class="store-header-right">

      <?php if ($is_store_admin): ?>
        <!-- ADMIN BUTTON — only for your Firebase UID -->
        <a href="admin.php" class="admin-nav-btn" title="Admin Panel">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
          Admin
        </a>
      <?php endif; ?>

      <?php if ($is_logged_in): ?>
        <!-- Logged in: show avatar + My Purchases -->
        <a href="my_purchases.php" class="header-cart-btn">
          <?php if ($user_avatar): ?>
            <img src="<?= htmlspecialchars($user_avatar) ?>" alt="<?= htmlspecialchars($user_name) ?>" style="width:22px;height:22px;border-radius:50%;object-fit:cover;" referrerpolicy="no-referrer">
          <?php else: ?>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <?php endif; ?>
          My Purchases
        </a>
      <?php else: ?>
        <!-- Not logged in: Google Sign-In button (light outline) -->
        <button class="store-signin-btn" id="storeGoogleLoginBtn">
          <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="15" height="15" alt="Google"/>
          Sign in with Google
        </button>
      <?php endif; ?>

    </div>
  </div>
</header>
