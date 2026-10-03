<?php
/**
 * Shared Admin Navigation & Sidebar Component
 * Provides complete, synchronized navigation links and sleek custom scrollbars across all admin panels.
 */
$admin_display_name = $admin_name ?? ($_SESSION["username"] ?? ($_SESSION["user_name"] ?? ($__sn ?? "Admin")));
$admin_avatar = $admin_avatar ?? (!empty($_SESSION['profile_image']) ? htmlspecialchars($_SESSION['profile_image']) : (!empty($__sa) ? htmlspecialchars($__sa) : ''));
$admin_initial = strtoupper(substr($admin_display_name, 0, 1));
$cur_script = basename($_SERVER['PHP_SELF'] ?? '');
$cur_tab = $_GET['tab'] ?? '';

// Helper to determine active state
$is_active = function(string $page, ?string $tab = null) use ($cur_script, $cur_tab): string {
    if ($cur_script !== $page) {
        return '';
    }
    if ($tab !== null && $cur_tab !== $tab) {
        return '';
    }
    return ' active';
};
?>
<style>
/* Sleek unified Admin Sidebar styles & custom scrollbars */
.sidebar {
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  width: 220px;
  background: rgba(7, 6, 15, 0.98);
  border-right: 1px solid rgba(139, 92, 246, 0.18);
  z-index: 200;
  display: flex;
  flex-direction: column;
  box-sizing: border-box;
  overflow: hidden !important;
}
.sb-logo {
  padding: 20px 18px 14px;
  border-bottom: 1px solid rgba(139, 92, 246, 0.08);
  flex-shrink: 0;
}
.sb-brand {
  font-size: .72rem;
  font-weight: 900;
  letter-spacing: .15em;
  text-transform: uppercase;
  background: linear-gradient(135deg, #a78bfa, #f472b6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  display: flex;
  align-items: center;
  gap: 8px;
}
.sb-brand i {
  -webkit-text-fill-color: #a78bfa;
}
.sb-admin {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 18px;
  border-bottom: 1px solid rgba(139, 92, 246, 0.08);
  flex-shrink: 0;
}
.sb-av {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #8b5cf6;
  flex-shrink: 0;
}
.sb-av-ph {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #8b5cf6, #ec4899);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  font-size: .9rem;
  color: #fff;
  flex-shrink: 0;
}
.sb-uname {
  font-size: .78rem;
  font-weight: 800;
  color: #e2e0ff;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.sb-role {
  font-size: .6rem;
  font-weight: 700;
  color: #c084fc;
  text-transform: uppercase;
  letter-spacing: .1em;
}
.sidebar::-webkit-scrollbar {
  display: none !important;
  width: 0 !important;
  height: 0 !important;
}
.sb-nav {
  flex: 1;
  min-height: 0;
  overflow-y: auto !important;
  overflow-x: hidden !important;
  overscroll-behavior: contain !important;
  scroll-behavior: smooth !important;
  padding: 10px 8px;
  scrollbar-width: thin !important;
  scrollbar-color: rgba(139, 92, 246, 0.45) transparent !important;
}
/* Webkit Sleek Scrollbar — removes Windows grey buttons & track */
.sb-nav::-webkit-scrollbar,
.drawer-nav::-webkit-scrollbar {
  width: 5px !important;
}
.sb-nav::-webkit-scrollbar-track,
.drawer-nav::-webkit-scrollbar-track {
  background: transparent !important;
}
.sb-nav::-webkit-scrollbar-thumb,
.drawer-nav::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #8b5cf6, #ec4899) !important;
  border-radius: 999px !important;
}
.sb-nav::-webkit-scrollbar-thumb:hover,
.drawer-nav::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, #a78bfa, #f472b6) !important;
}
.sb-nav::-webkit-scrollbar-button,
.drawer-nav::-webkit-scrollbar-button {
  display: none !important;
  width: 0 !important;
  height: 0 !important;
}
.sb-nav::-webkit-scrollbar-corner,
.drawer-nav::-webkit-scrollbar-corner {
  background: transparent !important;
}
.sb-sec {
  font-size: .58rem;
  font-weight: 900;
  color: #9490bb;
  letter-spacing: .15em;
  text-transform: uppercase;
  padding: 12px 10px 5px;
}
.sb-link {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 8px 10px;
  border-radius: 10px;
  font-size: .78rem;
  font-weight: 600;
  color: #9490bb;
  text-decoration: none;
  transition: all .18s ease;
  border: 1px solid transparent;
  margin-bottom: 2px;
}
.sb-link:hover {
  background: rgba(139, 92, 246, 0.08);
  color: #e2e0ff;
}
.sb-link.active {
  background: rgba(139, 92, 246, 0.16);
  color: #c084fc;
  border-color: rgba(139, 92, 246, 0.25);
  font-weight: 700;
}
.sb-link i {
  width: 16px;
  text-align: center;
  flex-shrink: 0;
}
.nm-dash-brand {
  background: linear-gradient(135deg, #F5709D, #11FFC9);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  font-weight: 900;
}
.sb-bottom {
  padding: 12px 8px;
  border-top: 1px solid rgba(139, 92, 246, 0.08);
  background: rgba(7, 6, 15, 0.98);
  flex-shrink: 0;
}
.sb-logout {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border-radius: 10px;
  font-size: .78rem;
  font-weight: 700;
  color: #f87171;
  text-decoration: none;
  transition: all .2s;
}
.sb-logout:hover {
  background: rgba(248, 113, 113, 0.1);
}

/* Mobile Drawer Elements */
.drawer-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  z-index: 9998;
}
.drawer-overlay.open {
  display: block;
}
.drawer {
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  width: 270px;
  max-width: 85vw;
  background: rgba(7, 6, 15, 0.99);
  border-right: 1px solid rgba(139, 92, 246, 0.25);
  z-index: 9999;
  display: flex;
  flex-direction: column;
  transform: translateX(-100%);
  transition: transform .28s cubic-bezier(0.4, 0, 0.2, 1);
  box-sizing: border-box;
}
.drawer.open {
  transform: translateX(0);
}
.drawer-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border-bottom: 1px solid rgba(139, 92, 246, 0.1);
  flex-shrink: 0;
}
.drawer-brand {
  font-size: .8rem;
  font-weight: 900;
  letter-spacing: .12em;
  text-transform: uppercase;
  background: linear-gradient(135deg, #a78bfa, #f472b6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.drawer-close {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: rgba(248, 113, 113, 0.08);
  border: 1px solid rgba(248, 113, 113, 0.2);
  color: #f87171;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: .85rem;
}
.drawer-user {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  border-bottom: 1px solid rgba(139, 92, 246, 0.1);
  flex-shrink: 0;
}
.d-av {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  border: 2px solid #8b5cf6;
  object-fit: cover;
  flex-shrink: 0;
}
.d-av-ph {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: linear-gradient(135deg, #8b5cf6, #ec4899);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  color: #fff;
  flex-shrink: 0;
}
.d-uname {
  font-size: .82rem;
  font-weight: 800;
  color: #e2e0ff;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.d-role {
  font-size: .62rem;
  color: #c084fc;
  font-weight: 700;
  text-transform: uppercase;
}
.drawer-nav {
  flex: 1;
  min-height: 0;
  overflow-y: auto !important;
  overflow-x: hidden !important;
  padding: 10px 8px;
  scrollbar-width: thin !important;
  scrollbar-color: rgba(139, 92, 246, 0.45) transparent !important;
}
.d-sec {
  font-size: .58rem;
  font-weight: 900;
  color: #9490bb;
  letter-spacing: .15em;
  text-transform: uppercase;
  padding: 12px 10px 5px;
}
.d-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  border-radius: 10px;
  font-size: .82rem;
  font-weight: 600;
  color: #9490bb;
  text-decoration: none;
  transition: all .2s;
  margin-bottom: 2px;
  border: 1px solid transparent;
}
.d-link:hover, .d-link.active {
  background: rgba(139, 92, 246, 0.14);
  color: #c084fc;
  border-color: rgba(139, 92, 246, 0.22);
}
.d-link i {
  width: 18px;
  text-align: center;
  flex-shrink: 0;
}
.drawer-bottom {
  padding: 12px 10px;
  border-top: 1px solid rgba(139, 92, 246, 0.1);
  flex-shrink: 0;
}
.d-logout {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 10px;
  font-size: .82rem;
  font-weight: 700;
  color: #f87171;
  text-decoration: none;
}
.d-logout:hover {
  background: rgba(248, 113, 113, 0.1);
}

@media (max-width: 768px) {
  .sidebar { display: none !important; }
}
</style>

<!-- DESKTOP SIDEBAR -->
<aside class="sidebar">
  <div class="sb-logo">
    <div class="sb-brand"><i class="fa-solid fa-shapes"></i> <span>Arigato Admin</span></div>
  </div>
  <div class="sb-admin">
    <?php if ($admin_avatar): ?>
      <img src="<?= $admin_avatar ?>" class="sb-av" alt="">
    <?php else: ?>
      <div class="sb-av-ph"><?= $admin_initial ?></div>
    <?php endif; ?>
    <div style="min-width:0;">
      <div class="sb-uname"><?= htmlspecialchars($admin_display_name) ?></div>
      <div class="sb-role">Super Admin</div>
    </div>
  </div>

  <nav class="sb-nav">
    <div class="sb-sec">Overview</div>
    <a href="dashboard.php" class="sb-link<?= $is_active('dashboard.php') ?>"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a>
    <a href="analytics.php" class="sb-link<?= $is_active('analytics.php') ?>"><i class="fa-solid fa-chart-line" style="color:#d4f938;"></i> <span style="color:#d4f938; font-weight:700;">Analytics</span></a>

    <div class="sb-sec">Content</div>
    <a href="upload_prompt.php" class="sb-link<?= $is_active('upload_prompt.php') ?>"><i class="fa-solid fa-upload"></i> <span>Upload Prompt</span></a>
    <a href="manage_prompts.php" class="sb-link<?= ($cur_script === 'manage_prompts.php' || $cur_script === 'edit_prompt.php') ? ' active' : '' ?>"><i class="fa-solid fa-list-check"></i> <span>Manage Prompts</span></a>
    <a href="home_card_flow.php" class="sb-link<?= $is_active('home_card_flow.php') ?>"><i class="fa-solid fa-arrows-up-down" style="color:#c084fc;"></i> <span style="color:#c084fc; font-weight:700;">Home Card Flow</span></a>
    <a href="prompt_links.php" class="sb-link<?= $is_active('prompt_links.php') ?>"><i class="fa-solid fa-link"></i> <span>Prompt Links</span></a>
    <a href="potd_manager.php" class="sb-link<?= $is_active('potd_manager.php') ?>"><i class="fa-solid fa-sun"></i> <span>POTD Manager</span></a>
    <a href="trending_settings.php" class="sb-link<?= $is_active('trending_settings.php') ?>"><i class="fa-solid fa-fire-flame-curved"></i> <span>Trending Settings</span></a>
    <a href="gallery_carousel_manager.php" class="sb-link<?= $is_active('gallery_carousel_manager.php') ?>"><i class="fa-solid fa-images"></i> <span>Gallery Carousel</span></a>

    <div class="sb-sec">Blog</div>
    <a href="blog_admin.php" class="sb-link<?= ($cur_script === 'blog_admin.php' || $cur_script === 'blog_edit.php') ? ' active' : '' ?>"><i class="fa-solid fa-pen-nib" style="color:#38bdf8;"></i> <span style="color:#38bdf8; font-weight:700;">Blog Admin</span></a>
    <a href="blog_create.php" class="sb-link<?= $is_active('blog_create.php') ?>"><i class="fa-solid fa-plus" style="color:#38bdf8;"></i> <span style="color:#38bdf8; font-weight:700;">New Post</span></a>

    <div class="sb-sec">Community</div>
    <a href="feedback_admin.php" class="sb-link<?= $is_active('feedback_admin.php') ?>"><i class="fa-solid fa-comments" style="color:#fb923c;"></i> <span style="color:#fb923c; font-weight:700;">Feedback Manager</span></a>

    <div class="sb-sec">Happy Users</div>
    <a href="happy_users_admin.php?tab=upload" class="sb-link<?= $is_active('happy_users_admin.php', 'upload') ?>"><i class="fa-solid fa-cloud-arrow-up"></i> <span>Upload Screenshots</span></a>
    <a href="happy_users_admin.php?tab=manage" class="sb-link<?= ($cur_script === 'happy_users_admin.php' && $cur_tab !== 'upload') ? ' active' : '' ?>"><i class="fa-solid fa-images"></i> <span>Manage Pics</span></a>

    <div class="sb-sec nm-dash-brand">Curated AI Prompts</div>
    <a href="curated_admin.php" class="sb-link nm-dash-brand<?= $is_active('curated_admin.php') ?>"><i class="fa-solid fa-upload" style="color:#e879f9;"></i> <span style="color:#e879f9; font-weight:700;">Curated — Upload</span></a>
    <a href="curated_manage.php" class="sb-link nm-dash-brand<?= $is_active('curated_manage.php') ?>"><i class="fa-solid fa-table-list" style="color:#e879f9;"></i> <span style="color:#e879f9; font-weight:700;">Curated — Manage</span></a>
    <a href="curated_links.php" class="sb-link nm-dash-brand<?= $is_active('curated_links.php') ?>"><i class="fa-solid fa-link" style="color:#e879f9;"></i> <span style="color:#e879f9; font-weight:700;">Curated — Links</span></a>

    <div class="sb-sec">Users</div>
    <a href="user_management.php" class="sb-link<?= $is_active('user_management.php') ?>"><i class="fa-solid fa-users"></i> <span>Users</span></a>

    <div class="sb-sec">Settings</div>
    <a href="site_settings.php" class="sb-link<?= $is_active('site_settings.php') ?>"><i class="fa-solid fa-gear" style="color:#38bdf8;"></i> <span style="color:#38bdf8; font-weight:700;">Site Settings</span></a>
    <a href="social_admin.php" class="sb-link<?= $is_active('social_admin.php') ?>"><i class="fa-solid fa-share-nodes" style="color:#f472b6;"></i> <span style="color:#f472b6; font-weight:700;">Social &amp; Contact</span></a>
    <a href="footer_admin.php" class="sb-link<?= $is_active('footer_admin.php') ?>"><i class="fa-solid fa-table-columns" style="color:#2dd4bf;"></i> <span style="color:#2dd4bf; font-weight:700;">Footer Manager</span></a>

    <div class="sb-sec">Tools</div>
    <a href="index.php" class="sb-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>View Site</span></a>
    <a href="curated_ai_prompts.php" class="sb-link" target="_blank"><i class="fa-solid fa-shapes"></i> <span>View Curated</span></a>
    <a href="contact.php" class="sb-link" target="_blank"><i class="fa-solid fa-envelope"></i> <span>View Contact</span></a>
    <a href="about.php" class="sb-link" target="_blank"><i class="fa-solid fa-user"></i> <span>View About</span></a>
  </nav>

  <div class="sb-bottom">
    <a href="login.php?logout=1" class="sb-logout"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
  </div>
</aside>

<!-- MOBILE DRAWER -->
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="drawer" id="sideDrawer">
  <div class="drawer-head">
    <div class="drawer-brand">Arigato Admin</div>
    <div class="drawer-close" onclick="closeDrawer()"><i class="fa-solid fa-xmark"></i></div>
  </div>
  <div class="drawer-user">
    <?php if ($admin_avatar): ?>
      <img src="<?= $admin_avatar ?>" class="d-av" alt="">
    <?php else: ?>
      <div class="d-av-ph"><?= $admin_initial ?></div>
    <?php endif; ?>
    <div style="min-width:0;">
      <div class="d-uname"><?= htmlspecialchars($admin_display_name) ?></div>
      <div class="d-role">Super Admin</div>
    </div>
  </div>

  <nav class="drawer-nav">
    <div class="d-sec">Overview</div>
    <a href="dashboard.php" class="d-link<?= $is_active('dashboard.php') ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <a href="analytics.php" class="d-link<?= $is_active('analytics.php') ?>"><i class="fa-solid fa-chart-line" style="color:#d4f938;"></i> <span style="color:#d4f938; font-weight:700;">Analytics</span></a>

    <div class="d-sec">Content</div>
    <a href="upload_prompt.php" class="d-link<?= $is_active('upload_prompt.php') ?>"><i class="fa-solid fa-upload"></i> Upload Prompt</a>
    <a href="manage_prompts.php" class="d-link<?= ($cur_script === 'manage_prompts.php' || $cur_script === 'edit_prompt.php') ? ' active' : '' ?>"><i class="fa-solid fa-list-check"></i> Manage Prompts</a>
    <a href="home_card_flow.php" class="d-link<?= $is_active('home_card_flow.php') ?>"><i class="fa-solid fa-arrows-up-down" style="color:#c084fc;"></i> <span style="color:#c084fc; font-weight:700;">Home Card Flow</span></a>
    <a href="prompt_links.php" class="d-link<?= $is_active('prompt_links.php') ?>"><i class="fa-solid fa-link"></i> Prompt Links</a>
    <a href="potd_manager.php" class="d-link<?= $is_active('potd_manager.php') ?>"><i class="fa-solid fa-sun"></i> POTD Manager</a>
    <a href="trending_settings.php" class="d-link<?= $is_active('trending_settings.php') ?>"><i class="fa-solid fa-fire-flame-curved"></i> Trending Settings</a>
    <a href="gallery_carousel_manager.php" class="d-link<?= $is_active('gallery_carousel_manager.php') ?>"><i class="fa-solid fa-images"></i> Gallery Carousel</a>

    <div class="d-sec">Blog</div>
    <a href="blog_admin.php" class="d-link<?= ($cur_script === 'blog_admin.php' || $cur_script === 'blog_edit.php') ? ' active' : '' ?>"><i class="fa-solid fa-pen-nib" style="color:#38bdf8;"></i> <span style="color:#38bdf8; font-weight:700;">Blog Admin</span></a>
    <a href="blog_create.php" class="d-link<?= $is_active('blog_create.php') ?>"><i class="fa-solid fa-plus" style="color:#38bdf8;"></i> <span style="color:#38bdf8; font-weight:700;">New Post</span></a>

    <div class="d-sec">Community</div>
    <a href="feedback_admin.php" class="d-link<?= $is_active('feedback_admin.php') ?>"><i class="fa-solid fa-comments" style="color:#fb923c;"></i> <span style="color:#fb923c; font-weight:700;">Feedback Manager</span></a>

    <div class="d-sec">Happy Users</div>
    <a href="happy_users_admin.php?tab=upload" class="d-link<?= $is_active('happy_users_admin.php', 'upload') ?>"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Screenshots</a>
    <a href="happy_users_admin.php?tab=manage" class="d-link<?= ($cur_script === 'happy_users_admin.php' && $cur_tab !== 'upload') ? ' active' : '' ?>"><i class="fa-solid fa-images"></i> Manage Pics</a>

    <div class="d-sec nm-dash-brand">Curated AI Prompts</div>
    <a href="curated_admin.php" class="d-link nm-dash-brand<?= $is_active('curated_admin.php') ?>"><i class="fa-solid fa-upload" style="color:#e879f9;"></i> <span style="color:#e879f9; font-weight:700;">Curated — Upload</span></a>
    <a href="curated_manage.php" class="d-link nm-dash-brand<?= $is_active('curated_manage.php') ?>"><i class="fa-solid fa-table-list" style="color:#e879f9;"></i> <span style="color:#e879f9; font-weight:700;">Curated — Manage</span></a>
    <a href="curated_links.php" class="d-link nm-dash-brand<?= $is_active('curated_links.php') ?>"><i class="fa-solid fa-link" style="color:#e879f9;"></i> <span style="color:#e879f9; font-weight:700;">Curated — Links</span></a>

    <div class="d-sec">Users</div>
    <a href="user_management.php" class="d-link<?= $is_active('user_management.php') ?>"><i class="fa-solid fa-users"></i> Users</a>

    <div class="d-sec">Settings</div>
    <a href="site_settings.php" class="d-link<?= $is_active('site_settings.php') ?>"><i class="fa-solid fa-gear" style="color:#38bdf8;"></i> <span style="color:#38bdf8; font-weight:700;">Site Settings</span></a>
    <a href="social_admin.php" class="d-link<?= $is_active('social_admin.php') ?>"><i class="fa-solid fa-share-nodes" style="color:#f472b6;"></i> <span style="color:#f472b6; font-weight:700;">Social &amp; Contact</span></a>
    <a href="footer_admin.php" class="d-link<?= $is_active('footer_admin.php') ?>"><i class="fa-solid fa-table-columns" style="color:#2dd4bf;"></i> <span style="color:#2dd4bf; font-weight:700;">Footer Manager</span></a>

    <div class="d-sec">Tools</div>
    <a href="index.php" class="d-link" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Site</a>
    <a href="curated_ai_prompts.php" class="d-link" target="_blank"><i class="fa-solid fa-shapes"></i> View Curated</a>
    <a href="contact.php" class="d-link" target="_blank"><i class="fa-solid fa-envelope"></i> View Contact</a>
    <a href="about.php" class="d-link" target="_blank"><i class="fa-solid fa-user"></i> View About</a>
  </nav>

  <div class="drawer-bottom">
    <a href="login.php?logout=1" class="d-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </div>
</div>

<script>
if (typeof window.openDrawer !== 'function') {
  window.openDrawer = function() {
    var d = document.getElementById('sideDrawer');
    var o = document.getElementById('drawerOverlay');
    if (d) d.classList.add('open');
    if (o) o.classList.add('open');
  };
}
if (typeof window.closeDrawer !== 'function') {
  window.closeDrawer = function() {
    var d = document.getElementById('sideDrawer');
    var o = document.getElementById('drawerOverlay');
    if (d) d.classList.remove('open');
    if (o) o.classList.remove('open');
  };
}
// Auto-scroll active link into view in sidebar & drawer
document.addEventListener('DOMContentLoaded', function() {
  try {
    var act1 = document.querySelector('.sb-nav .sb-link.active');
    if (act1) { act1.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); }
    var act2 = document.querySelector('.drawer-nav .d-link.active');
    if (act2) { act2.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); }
  } catch(e) {}
});
</script>
