<?php
session_start();
require_once "db.php";

// Must be logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

// If already onboarded, send home
if (!empty($_SESSION["onboarding_complete"])) {
    header("Location: index.php");
    exit();
}

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

$errors = [];

// --- Handle POST submission ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $avatar = trim($_POST["avatar"] ?? "");
    $gender = trim($_POST["gender"] ?? "");

    // Validate username
    if (strlen($username) < 3 || strlen($username) > 15) {
        $errors[] = "Username must be 3-15 characters.";
    }
    if (!preg_match('/^[a-zA-Z0-9_. ]+$/', $username)) {
        $errors[] =
            "Username can only contain letters, numbers, spaces, underscores, and dots.";
    }

    // Validate avatar
    $all_avatars = array_merge($male_avatars, $female_avatars);
    if (empty($avatar) || !in_array($avatar, $all_avatars)) {
        $errors[] = "Please select an avatar.";
    }

    // Validate gender
    if (!in_array($gender, ["male", "female", "nonbinary"])) {
        $errors[] = "Please select a gender option.";
    }

    // Check username uniqueness (only if no format errors so far)
    if (empty($errors)) {
        $existingUser = $pdo->prepare(
            "SELECT id FROM users WHERE username = ? AND id != ?",
        );
        $existingUser->execute([$username, $_SESSION["user_id"]]);
        if ($existingUser->fetch()) {
            $errors[] =
                "This username is already taken. Please choose another.";
        }
    }

    if (empty($errors)) {
        // Save to DB
        $stmt = $pdo->prepare(
            "UPDATE users SET username = ?, avatar = ?, gender = ?, onboarding_complete = 1 WHERE id = ?",
        );
        $stmt->execute([$username, $avatar, $gender, $_SESSION["user_id"]]);

        // Update session
        $_SESSION["username"] = $username;
        $_SESSION["profile_image"] = $avatar;
        $_SESSION["onboarding_complete"] = 1;

        // Fire GA4 event then redirect
        ?><!DOCTYPE html><html><head><?php include_once "gtag.php"; ?>    <style>
        html, body { background: transparent !important; height: 100%; margin: 0; }
        body::before { content: ''; position: fixed; inset: 0; z-index: -2; background-image: url('backgroundwally/only-homepage-pic.webp'); background-size: cover; background-position: center top; background-repeat: no-repeat; }
        body::after { content: ''; position: fixed; inset: 0; z-index: -1; background: rgba(0,0,0,0.52); pointer-events: none; }
        @media (max-width: 640px) { body::before { background-image: url('backgroundwally/only-homepage-pic-for-mobile.webp'); background-position: center center; } }
        .aurora-bg { display: none !important; }
    </style>
</head><body>
        <script>
        if(typeof gtag!=='undefined') gtag('event','onboarding_complete',{user_id:<?= (int)$_SESSION["user_id"] ?>});
        setTimeout(function(){ window.location.replace('index.php'); }, 300);
        </script></body></html><?php
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome &ndash; Set Up Your Profile | Arigato Devan Prompts</title>
    <meta name="description" content="Set up your PromptVerse profile before exploring exclusive AI prompts.">
    <?php include_once 'includes/theme_head.php'; ?>
    <?php include_once "gtag.php"; ?>
    <style>
        body.page-onboarding {
            min-height: 100vh;
            background: var(--pal-beige, #F5EFEB);
            color: var(--pal-navy, #2F4156);
        }
        .ob-wrap {
            width: 100%;
            max-width: 920px;
            margin: 0 auto;
            padding: 36px 16px 72px;
            box-sizing: border-box;
            position: relative;
            z-index: 2;
        }
        .ob-head {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 26px;
            text-align: center;
        }
        .ob-head img {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 2px solid var(--pal-sky, #C8D9E6);
            object-fit: cover;
        }
        .ob-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.32rem;
            font-weight: 900;
            line-height: 1.05;
        }
        .ob-card {
            background: #fff;
            border: 1px solid var(--pal-sky, #C8D9E6);
            border-radius: 24px;
            box-shadow: 0 12px 38px rgba(47,65,86,.08);
            padding: clamp(22px, 3.5vw, 36px);
        }
        .ob-step-label {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: .74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--pal-teal, #567C8D);
            margin-bottom: 10px;
        }
        .ob-title {
            margin: 0 0 8px;
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.6rem, 4.8vw, 2.2rem);
            font-weight: 900;
            color: var(--pal-navy, #2F4156);
            line-height: 1.15;
        }
        .ob-title em {
            font-style: italic;
            background: var(--nogoda-gradient-h);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .ob-sub {
            margin: 0 0 24px;
            color: var(--pal-teal, #567C8D);
            font-weight: 500;
            font-size: .92rem;
        }
        .ob-errors {
            background: #fff1f1;
            border: 1px solid #f1b4b4;
            color: #b42318;
            border-radius: 14px;
            padding: 12px 14px;
            margin-bottom: 20px;
            font-weight: 700;
            font-size: .86rem;
        }
        .ob-errors ul { margin: 8px 0 0 18px; padding: 0; }
        .ob-section { margin-bottom: 24px; }
        .ob-section-title {
            font-size: .82rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--pal-teal, #567C8D);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }
        .avatar-divider {
            margin: 12px 0 8px;
            font-size: .72rem;
            font-weight: 700;
            color: #8b9aaa;
            text-transform: uppercase;
            letter-spacing: .07em;
        }
        .avatar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 10px;
        }
        .avatar-option input[type="radio"],
        .gender-option input[type="radio"] { display: none; }
        .avatar-img-wrap {
            width: 100%;
            aspect-ratio: 1 / 1;
            border-radius: 50%;
            border: 2px solid var(--pal-sky, #C8D9E6);
            overflow: hidden;
            background: #fff;
            transition: transform .16s, border-color .16s, box-shadow .16s;
            box-shadow: 0 4px 12px rgba(47,65,86,.06);
        }
        .avatar-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .avatar-option { cursor: pointer; display: block; }
        .avatar-option:hover .avatar-img-wrap {
            transform: translateY(-2px);
            border-color: var(--pal-teal, #567C8D);
        }
        .avatar-option input[type="radio"]:checked + .avatar-img-wrap {
            border-color: var(--nogoda-pink, #F5709D);
            box-shadow: 0 0 0 3px rgba(245,112,157,.2), 0 8px 18px rgba(47,65,86,.12);
            transform: translateY(-2px) scale(1.03);
        }
        .ob-input {
            width: 100%;
            padding: 13px 14px;
            border: 1.5px solid var(--pal-sky, #C8D9E6);
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: .95rem;
            font-weight: 600;
            color: var(--pal-navy, #2F4156);
            box-sizing: border-box;
            background: #fff;
            outline: none;
            transition: border-color .16s, box-shadow .16s;
        }
        .ob-input:focus {
            border-color: var(--pal-teal, #567C8D);
            box-shadow: 0 0 0 3px rgba(86,124,141,.15);
        }
        .ob-input-hint {
            margin-top: 8px;
            font-size: .76rem;
            font-weight: 600;
            color: #7d8da0;
        }
        .gender-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }
        .gender-box {
            border: 1.5px solid var(--pal-sky, #C8D9E6);
            border-radius: 14px;
            background: #fff;
            padding: 14px 8px;
            text-align: center;
            font-weight: 700;
            color: var(--pal-navy, #2F4156);
            transition: transform .16s, border-color .16s, box-shadow .16s;
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: center;
        }
        .gender-emoji { font-size: 1.2rem; }
        .gender-option { cursor: pointer; }
        .gender-option:hover .gender-box {
            transform: translateY(-2px);
            border-color: var(--pal-teal, #567C8D);
        }
        .gender-option input[type="radio"]:checked + .gender-box {
            border-color: var(--nogoda-pink, #F5709D);
            background: rgba(245,112,157,.08);
            box-shadow: 0 0 0 3px rgba(245,112,157,.12);
        }
        .ob-submit {
            width: 100%;
            margin-top: 6px;
            border: none;
            border-radius: 999px;
            padding: 13px 18px;
            font-family: 'Inter', sans-serif;
            font-size: .92rem;
            font-weight: 800;
            background: var(--nogoda-gradient-h);
            color: var(--pal-navy, #2F4156);
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(47,65,86,.16);
            transition: transform .16s, box-shadow .16s;
        }
        .ob-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 28px rgba(47,65,86,.2);
        }
        @media (max-width: 760px) {
            .ob-wrap { padding-top: 24px; }
            .avatar-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 8px; }
            .gender-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="page-store theme-nogoda page-onboarding">
    <div class="nogoda-mesh" aria-hidden="true"></div>
    <div class="ob-wrap">
        <div class="ob-head">
            <img src="toplogo/logo01.webp" alt="Arigato Devan">
            <div class="ob-brand">arigato.<span style="color:#F5709D;">prompt</span></div>
        </div>
        <div class="ob-card">
            <div class="ob-step-label"><i class="fa-solid fa-sparkles"></i> Profile Setup</div>
            <h1 class="ob-title">Welcome to <em>PromptVerse</em></h1>
            <p class="ob-sub">Quick setup before you unlock prompts — takes less than 20 seconds.</p>

            <!-- Errors -->
            <?php if (!empty($errors)): ?>
                <div class="ob-errors">
                    <i class="fa-solid fa-triangle-exclamation"></i> Please fix the following:
                    <ul>
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" id="onboarding-form">

                <!-- Avatar Selection -->
                <div class="ob-section">
                    <div class="ob-section-title"><i class="fa-solid fa-user"></i> Choose Your Avatar</div>

                    <div class="avatar-divider">Male Avatars</div>
                    <div class="avatar-grid">
                        <?php foreach ($male_avatars as $i => $av): ?>
                        <label class="avatar-option">
                            <input type="radio" name="avatar" value="<?= htmlspecialchars(
                                $av,
                            ) ?>"
                                <?= ($_POST["avatar"] ?? "") === $av
                                    ? "checked"
                                    : "" ?>>
                            <div class="avatar-img-wrap">
                                <picture>
                                    <source srcset="<?= htmlspecialchars(
                                        $av,
                                    ) ?>" type="image/webp">
                                    <img loading="lazy" src="<?= htmlspecialchars(
                                        str_replace(".webp", ".png", $av),
                                    ) ?>" alt="Male Avatar <?= $i +
    1 ?>" loading="lazy">
                                </picture>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="avatar-divider">Female Avatars</div>
                    <div class="avatar-grid">
                        <?php foreach ($female_avatars as $i => $av): ?>
                        <label class="avatar-option">
                            <input type="radio" name="avatar" value="<?= htmlspecialchars(
                                $av,
                            ) ?>"
                                <?= ($_POST["avatar"] ?? "") === $av
                                    ? "checked"
                                    : "" ?>>
                            <div class="avatar-img-wrap">
                                <picture>
                                    <source srcset="<?= htmlspecialchars(
                                        $av,
                                    ) ?>" type="image/webp">
                                    <img loading="lazy" src="<?= htmlspecialchars(
                                        str_replace(".webp", ".png", $av),
                                    ) ?>" alt="Female Avatar <?= $i +
    1 ?>" loading="lazy">
                                </picture>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Username -->
                <div class="ob-section">
                    <div class="ob-section-title"><i class="fa-solid fa-pen"></i> Choose a Username</div>
                    <input
                        type="text"
                        name="username"
                        class="ob-input"
                        id="username-input"
                        placeholder="e.g. devan_, art_lover, kai03"
                        minlength="3"
                        maxlength="15"
                        value="<?= htmlspecialchars(
                            $_POST["username"] ?? "",
                        ) ?>"
                        autocomplete="off"
                        required>
                    <div class="ob-input-hint" id="char-counter">3-15 characters. Letters, numbers, spaces, _ or . only.</div>
                </div>

                <!-- Gender -->
                <div class="ob-section">
                    <div class="ob-section-title"><i class="fa-solid fa-venus-mars"></i> Gender</div>
                    <div class="gender-grid">
                        <label class="gender-option">
                            <input type="radio" name="gender" value="male"
                                <?= ($_POST["gender"] ?? "") === "male"
                                    ? "checked"
                                    : "" ?>>
                            <div class="gender-box">
                                <span class="gender-emoji"><i class="fa-solid fa-mars"></i></span>
                                Male
                            </div>
                        </label>
                        <label class="gender-option">
                            <input type="radio" name="gender" value="female"
                                <?= ($_POST["gender"] ?? "") === "female"
                                    ? "checked"
                                    : "" ?>>
                            <div class="gender-box">
                                <span class="gender-emoji"><i class="fa-solid fa-venus"></i></span>
                                Female
                            </div>
                        </label>
                        <label class="gender-option">
                            <input type="radio" name="gender" value="nonbinary"
                                <?= ($_POST["gender"] ?? "") === "nonbinary"
                                    ? "checked"
                                    : "" ?>>
                            <div class="gender-box">
                                <span class="gender-emoji"><i class="fa-solid fa-transgender"></i></span>
                                Non-binary
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="ob-submit" id="ob-submit-btn">
                    Continue <i class="fa-solid fa-arrow-right"></i>
                </button>

            </form>
        </div>
    </div>

    <script>
        // Live character counter for username
        const input = document.getElementById('username-input');
        const hint  = document.getElementById('char-counter');
        input.addEventListener('input', () => {
            const len = input.value.length;
            if (len < 3) {
                hint.textContent = `${len}/15 &mdash; Need at least 3 characters`;
                hint.style.color = '#FF6B6B';
            } else if (len > 15) {
                hint.textContent = `${len}/15 &mdash; Too long!`;
                hint.style.color = '#FF6B6B';
            } else {
                hint.innerHTML = `${len}/15 <i class="fa-solid fa-check"></i> Looks good!`;
                hint.style.color = '#2ecc71';
            }
        });

        // Submit button pulse on validate
        document.getElementById('onboarding-form').addEventListener('submit', (e) => {
            const btn = document.getElementById('ob-submit-btn');
            btn.innerHTML = 'Saving... <i class="fa-solid fa-spinner fa-spin"></i>';
            btn.style.opacity = '0.8';
        });
    </script>
</body>
</html>
