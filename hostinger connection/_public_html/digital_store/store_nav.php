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
<header class="store-header">
  <div class="store-header-inner">

    <!-- LOGO + BRAND TEXT -->
    <a href="../index.php" class="store-logo-img" title="Back to Arigato">
      <img src="../toplogo/logo01.webp" alt="Arigato Devan Prompts Logo" height="36"/>
      <span class="store-logo-text">Arigato<span class="logo-dot">.</span>Store</span>
    </a>

    <!-- NAV LINKS -->
    <nav class="store-nav">
      <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'nav-active' : '' ?>">Shop</a>
      <a href="about.php" class="<?= basename($_SERVER['PHP_SELF']) === 'about.php' ? 'nav-active' : '' ?>">About</a>
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
