<?php
/**
 * Logout confirmation modal — included when user is logged in.
 */
$logout_pic = 'logout-pic/logout-cute-pic-arigato-prompt-devan.webp';
?>
<div class="logout-confirm-overlay" id="logoutConfirmOverlay" aria-hidden="true">
    <div class="logout-confirm-card" role="dialog" aria-modal="true" aria-labelledby="logoutConfirmTitle">
        <button type="button" class="logout-confirm-close" id="logoutConfirmClose" aria-label="Close">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="logout-confirm-pic-wrap">
            <img src="<?= htmlspecialchars($logout_pic) ?>"
                 alt=""
                 class="logout-confirm-pic"
                 width="220"
                 height="220"
                 loading="lazy">
        </div>
        <h2 class="logout-confirm-title" id="logoutConfirmTitle">Mat jao, mujhe chhor ke?</h2>
        <p class="logout-confirm-sub">Tumhare bina prompts akeli reh jayengi...</p>
        <div class="logout-confirm-actions">
            <button type="button" class="logout-confirm-stay" id="logoutConfirmStay">
                <i class="fa-solid fa-heart"></i> Stay Logged In
            </button>
            <a href="login.php?logout=1" class="logout-confirm-go" id="logoutConfirmGo">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>
</div>
