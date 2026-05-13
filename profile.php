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

// --- Avatar Pool ---
$male_avatars = [
    "profiledp/b1.webp",
    "profiledp/b2.webp",
    "profiledp/b3.webp",
    "profiledp/b4.webp",
    "profiledp/b5.webp",
    "profiledp/b6.webp",
    "profiledp/b7.webp",
];
$female_avatars = [
    "profiledp/g1.webp",
    "profiledp/g2.webp",
    "profiledp/g3.webp",
    "profiledp/g4.webp",
    "profiledp/g5.webp",
    "profiledp/g6.webp",
    "profiledp/g7.webp",
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
            "UPDATE users SET username = ?, avatar = ?, gender = ?, onboarding_complete = 1 WHERE id = ?",
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
    <title>Edit Profile &ndash; Arigato Devan Prompts</title>
    <link rel="stylesheet" href="style.css?v=1778100000">
    <style>
        body { min-height: 100vh; padding: 40px 16px 80px; background: var(--bg-color); }

        .prof-wrap { max-width: 660px; margin: 0 auto; }

        .prof-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 800;
            font-size: 0.9rem;
            color: var(--text-color);
            text-decoration: none;
            margin-bottom: 24px;
            padding: 8px 16px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            transition: all 0.2s;
        }
        .prof-back:hover { border-color: var(--text-color); transform: translateX(-3px); }

        .prof-card {
            background: var(--card-bg);
            border: var(--border-width) solid var(--text-color);
            border-radius: 28px;
            padding: 44px;
            box-shadow: var(--shadow-comic);
        }

        .prof-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 2px dashed var(--border-color);
        }

        .prof-current-avatar {
            width: 80px; height: 80px;
            border-radius: 50%;
            border: var(--border-width) solid var(--text-color);
            object-fit: cover;
            box-shadow: var(--shadow-comic);
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .prof-header-info h2 {
            font-size: 1.6rem;
            font-weight: 900;
            margin-bottom: 4px;
        }

        .prof-header-info p {
            font-size: 0.9rem;
            color: #888;
            font-weight: 600;
        }

        .ob-section { margin-bottom: 32px; }
        .ob-section-title {
            font-size: 0.95rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Avatar Grid */
        .avatar-divider {
            display: flex; align-items: center; gap: 12px;
            margin: 14px 0 10px;
            font-size: 0.75rem; font-weight: 700; color: #bbb;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .avatar-divider::before, .avatar-divider::after {
            content: ''; flex: 1; height: 1px; background: var(--border-color);
        }

        .avatar-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 14px; }

        .avatar-option { display: flex; flex-direction: column; align-items: center; cursor: pointer; }
        .avatar-option input[type="radio"] { display: none; }

        .avatar-img-wrap {
            width: 66px; height: 66px;
            border-radius: 50%;
            border: 3px solid var(--border-color);
            overflow: hidden;
            transition: all 0.2s ease-out;
            background: var(--bg-color);
        }
        .avatar-img-wrap img { width: 100%; height: 100%; object-fit: cover; }

        .avatar-option input[type="radio"]:checked + .avatar-img-wrap {
            border-color: var(--text-color);
            box-shadow: 3px 3px 0px var(--text-color);
            transform: scale(1.1);
        }
        .avatar-option:hover .avatar-img-wrap { border-color: var(--primary-dark); transform: scale(1.05); }

        /* Username */
        .ob-input {
            width: 100%;
            padding: 13px 18px;
            border: var(--border-width) solid var(--text-color);
            border-radius: 14px;
            font-family: var(--font-main);
            font-size: 1rem;
            font-weight: 700;
            background: var(--bg-color);
            color: var(--text-color);
            box-shadow: var(--shadow-comic);
            outline: none;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        .ob-input:focus { border-color: var(--primary-dark); box-shadow: var(--shadow-comic-hover); transform: translateY(-1px); }
        .ob-input-hint { font-size: 0.8rem; color: #999; font-weight: 600; margin-top: 7px; }

        /* Gender */
        .gender-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .gender-option { cursor: pointer; display: block; }
        .gender-option input[type="radio"] { display: none; }
        .gender-box {
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; gap: 8px; padding: 16px 10px;
            border: var(--border-width) solid var(--border-color);
            border-radius: 16px; font-weight: 800; font-size: 0.9rem;
            transition: all 0.2s; background: var(--bg-color);
            box-shadow: 2px 2px 0px transparent;
        }
        .gender-emoji { font-size: 1.6rem; line-height: 1; }
        .gender-option:hover .gender-box { border-color: var(--text-color); transform: translateY(-2px); }
        .gender-option input[type="radio"]:checked + .gender-box {
            border-color: var(--text-color);
            background: var(--primary-color);
            box-shadow: 4px 4px 0px var(--text-color);
            transform: translateY(-2px);
        }

        /* Success / Error */
        .flash-success {
            background: #d9f5e5; color: #1e5c36;
            border: var(--border-width) solid var(--text-color);
            border-radius: 14px; padding: 14px 18px;
            font-weight: 700; margin-bottom: 24px;
            box-shadow: 3px 3px 0px var(--text-color);
            animation: popIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .flash-error {
            background: #ffe6e6; color: #a70000;
            border: var(--border-width) solid var(--text-color);
            border-radius: 14px; padding: 14px 18px;
            font-weight: 700; margin-bottom: 24px;
            box-shadow: 3px 3px 0px var(--text-color);
        }
        .flash-error ul { margin: 6px 0 0 18px; padding: 0; }
        .flash-error li { margin-bottom: 4px; font-size: 0.95rem; }

        /* Submit */
        .prof-submit {
            width: 100%; padding: 15px;
            font-size: 1rem; font-weight: 900;
            font-family: var(--font-main);
            text-transform: uppercase; letter-spacing: 1px;
            background: var(--secondary-color); color: var(--text-color);
            border: var(--border-width) solid var(--text-color);
            border-radius: 16px; cursor: pointer;
            box-shadow: var(--shadow-comic);
            transition: all 0.2s ease-out; margin-top: 6px;
        }
        .prof-submit:hover { transform: translateY(-3px); box-shadow: var(--shadow-comic-hover); background: var(--primary-color); }
        .prof-submit:active { transform: translate(4px, 4px); box-shadow: 0px 0px 0px var(--text-color); }

        @media (max-width: 500px) {
            .prof-card { padding: 28px 18px 36px; }
            .avatar-img-wrap { width: 52px; height: 52px; }
        }
    </style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <?php include_once "gtag.php"; ?>
</head>
<body>
    <div class="prof-wrap">

        <a href="index.php" class="prof-back"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>

        <div class="prof-card">

            <!-- Current Avatar Preview Header -->
            <div class="prof-header">
                <img src="<?= htmlspecialchars(
                    $user["avatar"] ?:
                    "https://api.dicebear.com/7.x/avataaars/svg?seed=default",
                ) ?>"
                     alt="Your Avatar" class="prof-current-avatar" id="live-avatar-preview" referrerpolicy="no-referrer">
                <div class="prof-header-info">
                    <h2><?= htmlspecialchars(
                        $user["username"] ?? "Your Profile",
                    ) ?></h2>
                    <p>Update your profile anytime &mdash; no restrictions!</p>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="flash-success"><i class="fa-solid fa-check"></i> Profile updated successfully!</div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="flash-error">
                    <i class="fa-solid fa-triangle-exclamation"></i> Fix the following:
                    <ul><?php foreach (
                        $errors
                        as $e
                    ): ?><li><?= htmlspecialchars(
    $e,
) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="POST">

                <!-- Avatar Selection -->
                <div class="ob-section">
                    <div class="ob-section-title"><i class="fa-solid fa-user"></i> Avatar</div>

                    <div class="avatar-divider">Male</div>
                    <div class="avatar-grid">
                        <?php foreach ($male_avatars as $av): ?>
                        <label class="avatar-option">
                            <input type="radio" name="avatar" value="<?= htmlspecialchars(
                                $av,
                            ) ?>"
                                <?= $cur_avatar === $av ? "checked" : "" ?>>
                            <div class="avatar-img-wrap">
                                <img src="<?= htmlspecialchars(
                                    $av,
                                ) ?>" alt="Avatar" loading="lazy">
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="avatar-divider">Female</div>
                    <div class="avatar-grid">
                        <?php foreach ($female_avatars as $av): ?>
                        <label class="avatar-option">
                            <input type="radio" name="avatar" value="<?= htmlspecialchars(
                                $av,
                            ) ?>"
                                <?= $cur_avatar === $av ? "checked" : "" ?>>
                            <div class="avatar-img-wrap">
                                <img src="<?= htmlspecialchars(
                                    $av,
                                ) ?>" alt="Avatar" loading="lazy">
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Username -->
                <div class="ob-section">
                    <div class="ob-section-title"><i class="fa-solid fa-pen-nib"></i> Username</div>
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
                    <div class="ob-section-title"><i class="fa-solid fa-venus-mars"></i> Gender</div>
                    <div class="gender-grid">
                        <label class="gender-option">
                            <input type="radio" name="gender" value="male" <?= $cur_gender ===
                            "male"
                                ? "checked"
                                : "" ?>>
                            <div class="gender-box"><span class="gender-emoji"><i class="fa-solid fa-mars"></i></span> Male</div>
                        </label>
                        <label class="gender-option">
                            <input type="radio" name="gender" value="female" <?= $cur_gender ===
                            "female"
                                ? "checked"
                                : "" ?>>
                            <div class="gender-box"><span class="gender-emoji"><i class="fa-solid fa-venus"></i></span> Female</div>
                        </label>
                        <label class="gender-option">
                            <input type="radio" name="gender" value="nonbinary" <?= $cur_gender ===
                            "nonbinary"
                                ? "checked"
                                : "" ?>>
                            <div class="gender-box"><span class="gender-emoji"><i class="fa-solid fa-genderless"></i></span> Non-binary</div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="prof-submit">Save Changes <i class="fa-solid fa-wand-magic-sparkles"></i></button>
            </form>
        </div>
    </div>

    <script>
        // Live char counter
        const input   = document.getElementById('username-input');
        const hint    = document.getElementById('char-counter');
        input.addEventListener('input', () => {
            const len = input.value.length;
            if (len < 3)       { hint.textContent = `${len}/15 "Ã¢â‚¬Â Need at least 3`; hint.style.color = '#FF6B6B'; }
            else if (len > 15) { hint.textContent = `${len}/15 "Ã¢â‚¬Â Too long!`;        hint.style.color = '#FF6B6B'; }
            else               { hint.textContent = `${len}/15 ✓`;                  hint.style.color = '#2ecc71'; }
        });

        // Live avatar preview in header
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
