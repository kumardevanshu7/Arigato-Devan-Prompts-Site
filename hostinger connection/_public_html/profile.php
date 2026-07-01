<?php
session_start();
require_once "db.php";

// Must be logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION["user_id"]]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user statistics
$stmt = $pdo->prepare("SELECT COUNT(*) FROM unlocked_prompts WHERE user_id = ?");
$stmt->execute([$_SESSION["user_id"]]);
$unlocked_count = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_prompts WHERE user_id = ?");
$stmt->execute([$_SESSION["user_id"]]);
$saved_count = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ?");
$stmt->execute([$_SESSION["user_id"]]);
$likes_count = (int)$stmt->fetchColumn();

// Fetch nav counts for header
$nav_counts = [];
try {
    $stmt = $pdo->prepare("SELECT
        SUM(CASE WHEN prompt_type = 'secret' THEN 1 ELSE 0 END) as secret_code,
        SUM(CASE WHEN prompt_type = 'unreleased' THEN 1 ELSE 0 END) as unreleased,
        SUM(CASE WHEN prompt_type = 'insta_viral' THEN 1 ELSE 0 END) as insta_viral,
        SUM(CASE WHEN prompt_type = 'already_uploaded' THEN 1 ELSE 0 END) as already_uploaded,
        SUM(CASE WHEN prompt_type = 'direct' THEN 1 ELSE 0 END) as direct
    FROM prompts");
    $stmt->execute();
    $nav_counts = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $nav_counts = []; }

// --- Avatar Pool ---
$male_avatars = [
    "profiledp/b1.webp",
    "profiledp/b2.webp",
    "profiledp/b3.webp",
    "profiledp/b4.webp",
    "profiledp/b5.webp",
    "profiledp/b6.webp",
    "profiledp/b7.webp",
    "profiledp/b8.webp",
    "profiledp/b9.webp",
    "profiledp/b10.webp",
    "profiledp/b11.webp",
    "profiledp/b12.webp",
    "profiledp/b13.webp",
    "profiledp/b14.webp",
];
$female_avatars = [
    "profiledp/g1.webp",
    "profiledp/g2.webp",
    "profiledp/g3.webp",
    "profiledp/g4.webp",
    "profiledp/g5.webp",
    "profiledp/g6.webp",
    "profiledp/g7.webp",
    "profiledp/g8.webp",
    "profiledp/g9.webp",
    "profiledp/g10.webp",
    "profiledp/g11.webp",
    "profiledp/g12.webp",
    "profiledp/g13.webp",
    "profiledp/g14.webp",
];
$all_avatars = array_merge($male_avatars, $female_avatars);

$errors = [];
$success = false;

// Defaults: current values (or POST values if re-showing form after error)
$cur_username = $_POST["username"] ?? ($user["username"] ?? "");
$cur_avatar = $_POST["avatar"] ?? ($user["avatar"] ?? "");
$cur_gender = $_POST["gender"] ?? ($user["gender"] ?? "");

// --- Handle POST ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $avatar = trim($_POST["avatar"] ?? "");
    $gender = trim($_POST["gender"] ?? "");

    if (strlen($username) < 3 || strlen($username) > 15) {
        $errors[] = "Username must be 3-15 characters.";
    }
    if (!preg_match('/^[a-zA-Z0-9_. ]+$/', $username)) {
        $errors[] = "Username: letters, numbers, spaces, _ or . only.";
    }
    if (!in_array($avatar, $all_avatars)) {
        $errors[] = "Please select an avatar.";
    }
    if (!in_array($gender, ["male", "female", "nonbinary"])) {
        $errors[] = "Please select a gender.";
    }

    if (empty($errors)) {
        $pdo->prepare(
            "UPDATE users SET username = ?, avatar = ?, gender = ?, onboarding_complete = 1 WHERE id = ?"
        )->execute([$username, $avatar, $gender, $_SESSION["user_id"]]);

        $_SESSION["username"] = $username;
        $_SESSION["profile_image"] = $avatar;
        $_SESSION["onboarding_complete"] = 1;

        $success = true;
        // Re-fetch for display
        $user["username"] = $username;
        $user["avatar"] = $avatar;
        $user["gender"] = $gender;
        $cur_username = $username;
        $cur_avatar = $avatar;
        $cur_gender = $gender;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2F4156">
    <title>Edit Profile — Arigato Devan Prompts</title>
    <?php include_once 'includes/theme_head.php'; ?>
    <link rel="stylesheet" href="css/profile-pages.css">
    <?php include_once "gtag.php"; ?>
</head>
<body class="page-store theme-nogoda page-profile">

<?php $nav_active = 'profile'; include 'includes/site_nav.php'; ?>

<main class="prof-main">
    <div class="profile-container-grid">
        
        <!-- Left: Form -->
        <div class="prof-card">
            <div class="prof-header">
                <img loading="lazy" src="<?= htmlspecialchars(sessionAvatar()) ?>"
                     alt="Your Avatar" class="prof-current-avatar" id="live-avatar-preview" referrerpolicy="no-referrer">
                <div class="prof-header-info">
                    <h2>Edit Profile</h2>
                    <p>Customize your profile & representation on Arigato Devan!</p>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="flash-success"><i class="fa-solid fa-circle-check"></i> Profile updated successfully!</div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="flash-error">
                    <i class="fa-solid fa-triangle-exclamation"></i> Fix the following:
                    <ul>
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST">
                <!-- Avatar Selection -->
                <div class="ob-section">
                    <div class="prof-section-title"><i class="fa-solid fa-user-tag"></i> Choose Avatar</div>
                    
                    <div class="avatar-section">
                        <div class="avatar-divider">Male Avatars</div>
                        <div class="avatar-grid">
                            <?php foreach ($male_avatars as $av): ?>
                            <label class="avatar-option">
                                <input type="radio" name="avatar" value="<?= htmlspecialchars($av) ?>" <?= $cur_avatar === $av ? "checked" : "" ?>>
                                <div class="avatar-img-wrap">
                                    <img loading="lazy" src="<?= htmlspecialchars($av) ?>" alt="Avatar">
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="avatar-divider" style="margin-top:20px;">Female Avatars</div>
                        <div class="avatar-grid">
                            <?php foreach ($female_avatars as $av): ?>
                            <label class="avatar-option">
                                <input type="radio" name="avatar" value="<?= htmlspecialchars($av) ?>" <?= $cur_avatar === $av ? "checked" : "" ?>>
                                <div class="avatar-img-wrap">
                                    <img loading="lazy" src="<?= htmlspecialchars($av) ?>" alt="Avatar">
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Username -->
                <div class="ob-section">
                    <div class="prof-section-title"><i class="fa-solid fa-pen-nib"></i> Username</div>
                    <input type="text" name="username" class="ob-input"
                           id="username-input"
                           placeholder="3-15 characters"
                           minlength="3" maxlength="15"
                           value="<?= htmlspecialchars($cur_username) ?>"
                           autocomplete="off" required>
                    <div class="ob-input-hint" id="char-counter">
                        <?= strlen($cur_username) ?>/15 characters
                    </div>
                </div>

                <!-- Gender -->
                <div class="ob-section">
                    <div class="prof-section-title"><i class="fa-solid fa-venus-mars"></i> Gender / Identity</div>
                    <div class="gender-grid">
                        <label class="gender-option">
                            <input type="radio" name="gender" value="male" <?= $cur_gender === "male" ? "checked" : "" ?>>
                            <div class="gender-box"><span class="gender-emoji"><i class="fa-solid fa-mars" style="color:#3b82f6;"></i></span> Male</div>
                        </label>
                        <label class="gender-option">
                            <input type="radio" name="gender" value="female" <?= $cur_gender === "female" ? "checked" : "" ?>>
                            <div class="gender-box"><span class="gender-emoji"><i class="fa-solid fa-venus" style="color:#ec4899;"></i></span> Female</div>
                        </label>
                        <label class="gender-option">
                            <input type="radio" name="gender" value="nonbinary" <?= $cur_gender === "nonbinary" ? "checked" : "" ?>>
                            <div class="gender-box"><span class="gender-emoji"><i class="fa-solid fa-genderless" style="color:#8b5cf6;"></i></span> Other</div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="prof-submit">Save Changes <i class="fa-solid fa-wand-magic-sparkles"></i></button>
            </form>
        </div>
        
        <!-- Right: Stats & Info -->
        <div class="prof-stats-card">
            <h3>Your Statistics</h3>
            <p class="prof-stats-sub">Track your interaction metrics and account details.</p>
            
            <div class="stats-grid">
                <div class="stat-pill">
                    <span class="stat-val"><?= $unlocked_count ?></span>
                    <span class="stat-lbl">Unlocked</span>
                </div>
                <div class="stat-pill">
                    <span class="stat-val"><?= $saved_count ?></span>
                    <span class="stat-lbl">Saved</span>
                </div>
                <div class="stat-pill">
                    <span class="stat-val"><?= $likes_count ?></span>
                    <span class="stat-lbl">Liked</span>
                </div>
            </div>
            
            <hr class="prof-divider">
            
            <h3>Account Details</h3>
            
            <div class="info-list">
                <div class="info-item">
                    <div class="info-icon"><i class="fa-solid fa-envelope" style="color:#f59e0b;"></i></div>
                    <div class="info-content">
                        <div class="info-lbl">Email Address</div>
                        <div class="info-val" title="<?= htmlspecialchars($user["email"] ?? "") ?>"><?= htmlspecialchars($user["email"] ?? "Linked Account") ?></div>
                    </div>
                    <div style="font-size:0.8rem; color:#10b981;" title="Verified Google Account"><i class="fa-solid fa-circle-check"></i></div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon"><i class="fa-solid fa-calendar-days" style="color:#ec4899;"></i></div>
                    <div class="info-content">
                        <div class="info-lbl">Member Since</div>
                        <div class="info-val"><?= date("d M Y", strtotime($user["created_at"] ?? "now")) ?></div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon"><i class="fa-solid fa-shield-halved" style="color:#6366f1;"></i></div>
                    <div class="info-content">
                        <div class="info-lbl">Account Status</div>
                        <div class="info-val" style="text-transform: capitalize; color:<?= ($user["role"] ?? "member") === "admin" ? "#8b5cf6" : "inherit" ?>;"><?= htmlspecialchars($user["role"] ?? "Member") ?></div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($user["role"]) && $user["role"] === "admin"): ?>
                <a href="dashboard.php" class="prof-admin-btn">
                    <i class="fa-solid fa-toolbox"></i> Admin Control Panel
                </a>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php include_once "footer.php"; ?>

<script>
    // Live char counter
    const input = document.getElementById('username-input');
    const hint = document.getElementById('char-counter');
    input.addEventListener('input', () => {
        const len = input.value.length;
        if (len < 3) {
            hint.textContent = `${len}/15 - Need at least 3`;
            hint.style.color = '#FF6B6B';
        } else if (len > 15) {
            hint.textContent = `${len}/15 - Too long!`;
            hint.style.color = '#FF6B6B';
        } else {
            hint.textContent = `${len}/15 - Perfect!`;
            hint.style.color = '#2ecc71';
        }
    });

    // Live avatar preview
    const preview = document.getElementById('live-avatar-preview');
    document.querySelectorAll('input[name="avatar"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (preview) {
                preview.style.transform = 'scale(0.85)';
                setTimeout(() => {
                    preview.src = radio.value;
                    preview.style.transform = 'scale(1)';
                }, 150);
            }
        });
    });
</script>
</body>
</html>
