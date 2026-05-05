<?php
session_start();
require_once 'db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// If already onboarded, send home
if (!empty($_SESSION['onboarding_complete'])) {
    header("Location: index.php");
    exit();
}

// --- Avatar Pool ---
$male_avatars = [
    'profiledp/b1.webp',
    'profiledp/b2.webp',
    'profiledp/b3.webp',
    'profiledp/b4.webp',
    'profiledp/b5.webp',
    'profiledp/b6.webp',
    'profiledp/b7.webp',
];

$female_avatars = [
    'profiledp/g1.webp',
    'profiledp/g2.webp',
    'profiledp/g3.webp',
    'profiledp/g4.webp',
    'profiledp/g5.webp',
    'profiledp/g6.webp',
    'profiledp/g7.webp',
];

$errors = [];

// --- Handle POST submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $avatar   = trim($_POST['avatar'] ?? '');
    $gender   = trim($_POST['gender'] ?? '');

    // Validate username
    if (strlen($username) < 3 || strlen($username) > 15) {
        $errors[] = "Username must be 3-15 characters.";
    }
    if (!preg_match('/^[a-zA-Z0-9_. ]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, spaces, underscores, and dots.";
    }

    // Validate avatar
    $all_avatars = array_merge($male_avatars, $female_avatars);
    if (empty($avatar) || !in_array($avatar, $all_avatars)) {
        $errors[] = "Please select an avatar.";
    }

    // Validate gender
    if (!in_array($gender, ['male', 'female', 'nonbinary'])) {
        $errors[] = "Please select a gender option.";
    }

    if (empty($errors)) {
        // Save to DB
        $stmt = $pdo->prepare("UPDATE users SET username = ?, avatar = ?, gender = ?, onboarding_complete = 1 WHERE id = ?");
        $stmt->execute([$username, $avatar, $gender, $_SESSION['user_id']]);

        // Update session
        $_SESSION['username'] = $username;
        $_SESSION['profile_image'] = $avatar;
        $_SESSION['onboarding_complete'] = 1;

        header("Location: index.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome "ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â Set Up Your Profile | PromptVerse</title>
    <meta name="description" content="Set up your PromptVerse profile before exploring exclusive AI prompts.">
    <link rel="stylesheet" href="style.css?v=1777999999">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 40px 16px 80px;
            background: var(--bg-color);
        }

        .ob-wrap {
            width: 100%;
            max-width: 680px;
        }

        .ob-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: center;
            margin-bottom: 36px;
        }

        .ob-logo img {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            border: var(--border-width) solid var(--text-color);
            object-fit: cover;
        }

        .ob-logo-text {
            font-size: 1.4rem;
            font-weight: 900;
            line-height: 1.1;
        }

        .ob-card {
            background: var(--card-bg);
            border: var(--border-width) solid var(--text-color);
            border-radius: 28px;
            padding: 44px 44px 50px;
            box-shadow: var(--shadow-comic);
        }

        .ob-step-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary-color);
            border: 2px solid var(--text-color);
            border-radius: 40px;
            padding: 6px 18px;
            font-weight: 800;
            font-size: 0.8rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            box-shadow: 2px 2px 0px var(--text-color);
            margin-bottom: 16px;
        }

        .ob-title {
            font-size: 2rem;
            font-weight: 900;
            margin-bottom: 6px;
            line-height: 1.2;
        }

        .ob-sub {
            font-size: 1rem;
            color: #777;
            font-weight: 600;
            margin-bottom: 36px;
        }

        .ob-section {
            margin-bottom: 36px;
        }

        .ob-section-title {
            font-size: 1rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Avatar Grid */
        .avatar-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
        }

        .avatar-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        .avatar-option input[type="radio"] {
            display: none;
        }

        .avatar-img-wrap {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 3px solid var(--border-color);
            overflow: hidden;
            transition: all 0.2s ease-out;
            box-shadow: 2px 2px 0px transparent;
            background: var(--bg-color);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-gender-tag {
            font-size: 0.65rem;
            font-weight: 800;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .avatar-option input[type="radio"]:checked + .avatar-img-wrap {
            border-color: var(--text-color);
            box-shadow: 3px 3px 0px var(--text-color);
            transform: scale(1.08);
        }

        .avatar-option:hover .avatar-img-wrap {
            border-color: var(--primary-dark);
            transform: scale(1.05);
        }

        /* Avatar separator */
        .avatar-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 16px 0 12px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #bbb;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .avatar-divider::before,
        .avatar-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }

        /* Username Input */
        .ob-input {
            width: 100%;
            padding: 14px 18px;
            border: var(--border-width) solid var(--text-color);
            border-radius: 14px;
            font-family: var(--font-main);
            font-size: 1rem;
            font-weight: 700;
            background: var(--bg-color);
            color: var(--text-color);
            box-shadow: var(--shadow-comic);
            outline: none;
            transition: all 0.2s ease-out;
            box-sizing: border-box;
        }

        .ob-input:focus {
            border-color: var(--primary-dark);
            box-shadow: var(--shadow-comic-hover);
            transform: translateY(-1px);
        }

        .ob-input-hint {
            font-size: 0.8rem;
            color: #999;
            font-weight: 600;
            margin-top: 8px;
        }

        /* Gender Selector */
        .gender-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .gender-option {
            cursor: pointer;
            display: block;
        }

        .gender-option input[type="radio"] {
            display: none;
        }

        .gender-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 18px 10px;
            border: var(--border-width) solid var(--border-color);
            border-radius: 16px;
            font-weight: 800;
            font-size: 0.9rem;
            transition: all 0.2s ease-out;
            background: var(--bg-color);
            box-shadow: 2px 2px 0px transparent;
        }

        .gender-emoji {
            font-size: 1.8rem;
            line-height: 1;
        }

        .gender-option:hover .gender-box {
            border-color: var(--text-color);
            transform: translateY(-2px);
            box-shadow: 3px 3px 0px var(--border-color);
        }

        .gender-option input[type="radio"]:checked + .gender-box {
            border-color: var(--text-color);
            background: var(--primary-color);
            box-shadow: 4px 4px 0px var(--text-color);
            transform: translateY(-2px);
        }

        /* Errors */
        .ob-errors {
            background: #ffe6e6;
            color: #a70000;
            border: var(--border-width) solid var(--text-color);
            border-radius: 14px;
            padding: 14px 18px;
            font-weight: 700;
            margin-bottom: 24px;
            box-shadow: 3px 3px 0px var(--text-color);
        }

        .ob-errors ul {
            margin: 6px 0 0 18px;
            padding: 0;
        }

        .ob-errors li {
            margin-bottom: 4px;
            font-size: 0.95rem;
        }

        /* Submit Button */
        .ob-submit {
            width: 100%;
            padding: 16px;
            font-size: 1.1rem;
            font-weight: 900;
            font-family: var(--font-main);
            text-transform: uppercase;
            letter-spacing: 1px;
            background: var(--secondary-color);
            color: var(--text-color);
            border: var(--border-width) solid var(--text-color);
            border-radius: 16px;
            cursor: pointer;
            box-shadow: var(--shadow-comic);
            transition: all 0.2s ease-out;
            margin-top: 8px;
        }

        .ob-submit:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-comic-hover);
            background: var(--primary-color);
        }

        .ob-submit:active {
            transform: translate(4px, 4px);
            box-shadow: 0px 0px 0px var(--text-color);
        }

        @media (max-width: 500px) {
            .ob-card { padding: 28px 20px 36px; }
            .ob-title { font-size: 1.6rem; }
            .avatar-grid { grid-template-columns: repeat(5, 1fr); gap: 8px; }
            .avatar-img-wrap { width: 52px; height: 52px; }
            .gender-grid { grid-template-columns: repeat(3, 1fr); }
        }
    </style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
</head>
<body>
    <div class="ob-wrap">

        <!-- Logo -->
        <div class="ob-logo">
            <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEh9eBlF-H7pQKHB7MV3TrjiL8Fm6HS753UjgtMroNDpSfMt_dmrqGoqAq_Bkhq1iSg1Iuflg_k6GHKXcuNXFEh0EmM0DyKY0XelSyShPXkzDX2u74APxyrIuY62s4bxL2JGRRqUBu9y1C_3SwrvCnqEmkJjJWs2v95MOHRkkLeQ08w2U_xMZvykuxtZeYj-/s1260/DP.png" alt="Logo">
            <div class="ob-logo-text">ARIGATO<br>DEVAN</div>
        </div>

        <!-- Card -->
        <div class="ob-card">
            <div class="ob-step-label"><i class="fa-solid fa-sparkles"></i> Profile Setup</div>
            <h1 class="ob-title">Welcome to <span class="highlight">PromptVerse!</span></h1>
            <p class="ob-sub">Quick setup before you unlock the magic ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â takes 10 seconds!</p>

            <!-- Errors -->
            <?php if (!empty($errors)): ?>
                <div class="ob-errors">
                    <i class="fa-solid fa-triangle-exclamation"></i> Please fix the following:
                    <ul>
                        <?php foreach($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" id="onboarding-form">

                <!-- Avatar Selection -->
                <div class="ob-section">
                    <div class="ob-section-title"><i class="fa-solid fa-user"></i> Choose Your Avatar</div>

                    <div class="avatar-divider">Male</div>
                    <div class="avatar-grid">
                        <?php foreach($male_avatars as $i => $av): ?>
                        <label class="avatar-option">
                            <input type="radio" name="avatar" value="<?= htmlspecialchars($av) ?>"
                                <?= (($_POST['avatar'] ?? '') === $av) ? 'checked' : '' ?>>
                            <div class="avatar-img-wrap">
                                <picture>
                                    <source srcset="<?= htmlspecialchars($av) ?>" type="image/webp">
                                    <img src="<?= htmlspecialchars(str_replace('.webp', '.png', $av)) ?>" alt="Male Avatar <?= $i+1 ?>" loading="lazy">
                                </picture>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="avatar-divider">Female</div>
                    <div class="avatar-grid">
                        <?php foreach($female_avatars as $i => $av): ?>
                        <label class="avatar-option">
                            <input type="radio" name="avatar" value="<?= htmlspecialchars($av) ?>"
                                <?= (($_POST['avatar'] ?? '') === $av) ? 'checked' : '' ?>>
                            <div class="avatar-img-wrap">
                                <picture>
                                    <source srcset="<?= htmlspecialchars($av) ?>" type="image/webp">
                                    <img src="<?= htmlspecialchars(str_replace('.webp', '.png', $av)) ?>" alt="Female Avatar <?= $i+1 ?>" loading="lazy">
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
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
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
                                <?= (($_POST['gender'] ?? '') === 'male') ? 'checked' : '' ?>>
                            <div class="gender-box">
                                <span class="gender-emoji"><i class="fa-solid fa-mars"></i></span>
                                Male
                            </div>
                        </label>
                        <label class="gender-option">
                            <input type="radio" name="gender" value="female"
                                <?= (($_POST['gender'] ?? '') === 'female') ? 'checked' : '' ?>>
                            <div class="gender-box">
                                <span class="gender-emoji"><i class="fa-solid fa-venus"></i></span>
                                Female
                            </div>
                        </label>
                        <label class="gender-option">
                            <input type="radio" name="gender" value="nonbinary"
                                <?= (($_POST['gender'] ?? '') === 'nonbinary') ? 'checked' : '' ?>>
                            <div class="gender-box">
                                <span class="gender-emoji"><i class="fa-solid fa-transgender"></i></span>
                                Non-binary
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="ob-submit" id="ob-submit-btn">
                    Enter PromptVerse <i class="fa-solid fa-rocket"></i>
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
                hint.textContent = `${len}/15 ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â Need at least 3 characters`;
                hint.style.color = '#FF6B6B';
            } else if (len > 15) {
                hint.textContent = `${len}/15 ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â Too long!`;
                hint.style.color = '#FF6B6B';
            } else {
                hint.innerHTML = `${len}/15 <i class="fa-solid fa-check"></i> Looks good!`;
                hint.style.color = '#2ecc71';
            }
        });

        // Avatar selection visual feedback
        document.querySelectorAll('input[name="avatar"]').forEach(radio => {
            radio.addEventListener('change', () => {
                document.querySelectorAll('.avatar-img-wrap').forEach(w => w.classList.remove('selected'));
                radio.nextElementSibling.classList.add('selected');
            });
        });

        // Submit button pulse on validate
        document.getElementById('onboarding-form').addEventListener('submit', (e) => {
            const btn = document.getElementById('ob-submit-btn');
            btn.innerHTML = 'Setting up... <i class="fa-solid fa-spinner fa-spin"></i>';
            btn.style.opacity = '0.8';
        });
    </script>
</body>
</html>





