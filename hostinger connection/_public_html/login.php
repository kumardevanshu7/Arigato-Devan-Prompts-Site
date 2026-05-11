<?php
session_start();
// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$error = $_SESSION['error_msg'] ?? '';
unset($_SESSION['error_msg']);

$strip_imgs = [
    'landingpics/lan1.webp',
    'landingpics/lan2.webp',
    'landingpics/lan3.webp',
    'landingpics/lan4.webp',
    'landingpics/lan5.webp',
    'landingpics/lan6.webp',
    'landingpics/lan7.webp',
    'landingpics/lan8.webp',
    'landingpics/lan9.webp',
    'landingpics/lan10.webp',
    'landingpics/lan11.webp',
    'landingpics/lan12.webp',
    'landingpics/lan13.webp',
    'landingpics/lan14.webp',
    'landingpics/lan15.webp',
    'landingpics/lan16.webp',
    'landingpics/lan17.webp',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login "&ndash; Arigato Devan PromptVerse</title>
    <meta name="description" content="Login to Arigato Devan PromptVerse and unlock premium AI couple prompts.">
    <link rel="stylesheet" href="style.css?v=1777999999">
    <style>
        /* &mdash;&ndash;&mdash;&ndash;&mdash;&ndash;&mdash; Login Page Root &mdash;&ndash;&mdash;&ndash;&mdash;&ndash;&mdash; */
        html,
        body {
            height: 100%;
            margin: 0;
        }

        .login-root {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            background: var(--bg-color);
        }

        /* Filmstrip bg (lighter version) */
        .login-filmstrip {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 0;
        }

        .login-filmstrip .filmstrip-row {
            height: 50%;
        }

        .login-filmstrip-overlay {
            position: fixed;
            inset: 0;
            z-index: 1;
            background: radial-gradient(ellipse 80% 80% at 50% 50%,
                    rgba(253, 251, 247, 0.97) 0%,
                    rgba(253, 251, 247, 0.93) 45%,
                    rgba(253, 251, 247, 0.7) 70%,
                    rgba(253, 251, 247, 0.25) 100%);
        }

        /* &mdash; Minimal Login Header &mdash; */
        .login-header {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 28px;
            margin: 24px auto;
            max-width: 800px;
            width: 90%;
            border: var(--border-width) solid var(--border-color);
            border-radius: 40px;
            background: rgba(253, 251, 247, 0.9);
            backdrop-filter: blur(12px);
            box-shadow: 6px 6px 0px var(--text-color);
        }

        .login-header-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-color);
        }

        .login-header-logo img {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            border: var(--border-width) solid var(--text-color);
            object-fit: cover;
        }

        .login-header-logo-text {
            font-size: 1.3rem;
            font-weight: 900;
            line-height: 1.1;
        }

        .login-header-nav a {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--text-color);
            text-decoration: none;
            padding: 8px 16px;
            border: 2px solid transparent;
            border-radius: 20px;
            transition: all 0.2s;
        }

        .login-header-nav a:hover {
            border-color: var(--border-color);
            background: var(--primary-color);
        }

        /* &mdash;&ndash;&mdash;&ndash;&mdash;&ndash;&mdash; Center Content &mdash;&ndash;&mdash;&ndash;&mdash;&ndash;&mdash; */
        .login-body {
            position: relative;
            z-index: 5;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        /* &mdash;&ndash;&mdash;&ndash;&mdash;&ndash;&mdash; Login Card &mdash;&ndash;&mdash;&ndash;&mdash;&ndash;&mdash; */
        .login-card {
            background: var(--card-bg);
            border: var(--border-width) solid var(--text-color);
            border-radius: 28px;
            padding: 52px 48px 48px;
            box-shadow: 8px 8px 0px var(--text-color);
            max-width: 440px;
            width: 100%;
            text-align: center;
            animation: loginCardIn 0.65s cubic-bezier(0.34, 1.56, 0.64, 1) both;
            transform-origin: center bottom;
        }

        @keyframes loginCardIn {
            from {
                opacity: 0;
                transform: scale(0.82) translateY(30px) rotate(-2deg);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0) rotate(0deg);
            }
        }

        /* Logo inside card */
        .card-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: var(--border-width) solid var(--text-color);
            object-fit: cover;
            box-shadow: var(--shadow-comic);
            margin: 0 auto 20px;
            display: block;
            animation: loginCardIn 0.65s 0.08s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }

        /* Sticker pair */
        .card-stickers {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 22px;
        }

        .card-sticker {
            font-size: 0.72rem;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 5px 14px;
            border: 2px solid var(--text-color);
            border-radius: 30px;
            box-shadow: 2px 2px 0px var(--text-color);
        }

        .card-sticker-1 {
            background: #FFF1B8;
            transform: rotate(-3deg);
        }

        .card-sticker-2 {
            background: #D6EDFF;
            transform: rotate(2deg);
        }

        /* Title */
        .login-card-title {
            font-size: 1.75rem;
            font-weight: 900;
            margin-bottom: 8px;
            line-height: 1.2;
            letter-spacing: -0.5px;
        }

        .login-card-sub {
            font-size: 0.95rem;
            font-weight: 600;
            color: #777;
            margin-bottom: 32px;
            line-height: 1.5;
        }

        /* Error */
        .login-error {
            background: #ffe6e6;
            color: #a70000;
            border: var(--border-width) solid var(--text-color);
            border-radius: 12px;
            padding: 12px 16px;
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 0.9rem;
            box-shadow: 3px 3px 0px var(--text-color);
        }

        /* Google Button */
        .google-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 16px 24px;
            background: #fff;
            color: var(--text-color);
            border: 3px solid var(--text-color);
            border-radius: 16px;
            font-family: var(--font-main);
            font-weight: 900;
            font-size: 1.05rem;
            text-decoration: none;
            box-shadow: 5px 5px 0px var(--text-color);
            transition: all 0.18s cubic-bezier(0.34, 1.56, 0.64, 1);
            letter-spacing: 0.3px;
            cursor: pointer;
            margin-bottom: 16px;
        }

        .google-btn img {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }

        .google-btn:hover {
            transform: translateY(-4px) rotate(-1deg);
            box-shadow: 7px 7px 0px var(--text-color);
            background: #f8f8ff;
        }

        .google-btn:active {
            transform: translate(4px, 4px);
            box-shadow: 1px 1px 0px var(--text-color);
        }

        /* Explore link */
        .login-explore {
            font-size: 0.85rem;
            font-weight: 700;
            color: #888;
            text-decoration: none;
            display: block;
            transition: color 0.2s;
            padding: 4px 0;
        }

        .login-explore:hover {
            color: var(--primary-dark);
        }

        /* Divider */
        .card-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
            font-size: 0.8rem;
            font-weight: 700;
            color: #ccc;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .card-divider::before,
        .card-divider::after {
            content: '';
            flex: 1;
            height: 1.5px;
            background: var(--border-color);
            border-radius: 2px;
        }

        /* Promise badges row */
        .login-promises {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 2px dashed var(--border-color);
        }

        .login-promise-badge {
            font-size: 0.75rem;
            font-weight: 700;
            color: #666;
            background: var(--bg-color);
            border: 1.5px solid var(--border-color);
            border-radius: 20px;
            padding: 4px 12px;
        }

        /* ─── Comparison Cards ─── */
        .login-compare-section {
            position: relative;
            z-index: 5;
            padding: 0 20px 48px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }

        .login-compare-heading {
            font-size: 0.8rem;
            font-weight: 900;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #aaa;
            text-align: center;
            margin-bottom: 4px;
        }

        .login-compare-row {
            display: flex;
            gap: 20px;
            width: 100%;
            max-width: 760px;
            justify-content: center;
        }

        .cmp-card {
            flex: 1;
            max-width: 360px;
            background: var(--card-bg);
            border: 3px solid var(--text-color);
            border-radius: 20px;
            padding: 28px 24px 24px;
            box-shadow: 6px 6px 0px var(--text-color);
            transition: transform 0.18s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.18s ease;
            cursor: default;
        }

        .cmp-card:hover {
            transform: translateY(-5px) rotate(-0.5deg);
            box-shadow: 8px 10px 0px var(--text-color);
        }

        .cmp-card:active {
            transform: translate(3px, 3px);
            box-shadow: 3px 3px 0px var(--text-color);
        }

        .cmp-card-with {
            background: #fffbe8;
            border-color: var(--text-color);
        }

        .cmp-card-without {
            background: #fff0f0;
            border-color: var(--text-color);
        }

        .cmp-card-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 0.72rem;
            font-weight: 900;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 5px 14px;
            border: 2.5px solid var(--text-color);
            border-radius: 30px;
            margin-bottom: 16px;
        }

        .cmp-card-with .cmp-card-badge {
            background: #ffe066;
            color: var(--text-color);
        }

        .cmp-card-without .cmp-card-badge {
            background: #ffb3b3;
            color: var(--text-color);
        }

        .cmp-card-title {
            font-size: 1.15rem;
            font-weight: 900;
            margin-bottom: 16px;
            letter-spacing: -0.3px;
        }

        .cmp-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .cmp-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.88rem;
            font-weight: 600;
            line-height: 1.4;
            color: #333;
        }

        .cmp-icon {
            flex-shrink: 0;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 2px solid var(--text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            margin-top: 1px;
        }

        .cmp-card-with .cmp-icon {
            background: #2ecc71;
            color: #fff;
        }

        .cmp-card-without .cmp-icon {
            background: #e74c3c;
            color: #fff;
        }

        /* &mdash;&ndash; &mdash;&ndash; &mdash;&ndash; &mdash; Ticker at bottom &mdash;&ndash; &mdash;&ndash; &mdash;&ndash; &mdash; */
        .login-ticker {
            position: relative;
            z-index: 10;
            background: var(--text-color);
            height: 42px;
            display: flex;
            align-items: center;
            overflow: hidden;
            border-top: 3px solid var(--primary-dark);
        }

        /* &mdash; RESPONSIVE &mdash; */
        @media (max-width: 600px) {
            .login-header {
                padding: 12px 20px;
                width: 95%;
                margin: 16px auto;
                border-radius: 30px;
            }

            .login-card {
                padding: 36px 24px 32px;
                border-radius: 20px;
            }

            .login-card-title {
                font-size: 1.4rem;
            }

            .google-btn {
                font-size: 0.95rem;
                padding: 14px 20px;
            }

            .card-logo {
                width: 68px;
                height: 68px;
            }

            .login-compare-row {
                flex-direction: column;
                align-items: center;
            }

            .cmp-card {
                max-width: 100%;
                width: 100%;
            }
        }

        @media (max-width: 380px) {
            .login-header-logo-text {
                font-size: 1.1rem;
            }

            .login-card-title {
                font-size: 1.25rem;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap"
        rel="stylesheet">
</head>

<body>
    <div class="login-root">

        <!-- Film Strip Background (lighter) -->
        <div class="login-filmstrip" aria-hidden="true">
            <div class="filmstrip-row row-1">
                <div class="filmstrip-track">
                    <?php foreach (array_merge($strip_imgs, $strip_imgs) as $img): ?>
                        <div class="filmstrip-frame">
                            <picture>
                                <source srcset="<?= $img ?>" type="image/webp">
                                <img src="<?= str_replace('.webp', '.png', $img) ?>" alt="" loading="lazy">
                            </picture>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="filmstrip-row row-2">
                <div class="filmstrip-track track-reverse">
                    <?php foreach (array_merge(array_reverse($strip_imgs), array_reverse($strip_imgs)) as $img): ?>
                        <div class="filmstrip-frame">
                            <picture>
                                <source srcset="<?= $img ?>" type="image/webp">
                                <img src="<?= str_replace('.webp', '.png', $img) ?>" alt="" loading="lazy">
                            </picture>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="login-filmstrip-overlay"></div>

        <!-- Header -->
        <header class="login-header">
            <a href="index.php" class="login-header-logo">
                <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEh9eBlF-H7pQKHB7MV3TrjiL8Fm6HS753UjgtMroNDpSfMt_dmrqGoqAq_Bkhq1iSg1Iuflg_k6GHKXcuNXFEh0EmM0DyKY0XelSyShPXkzDX2u74APxyrIuY62s4bxL2JGRRqUBu9y1C_3SwrvCnqEmkJjJWs2v95MOHRkkLeQ08w2U_xMZvykuxtZeYj-/s1260/DP.png"
                    alt="Logo">
                <div class="login-header-logo-text">ARIGATO<br>DEVAN PROMPTS</div>
            </a>
            <nav class="login-header-nav">
                <a href="index.php"><i class="fa-solid fa-arrow-left"></i> HOME</a>
            </nav>
        </header>

        <!-- Center Login Card -->
        <div class="login-body">
            <div class="login-card">

                <!-- Avatar -->
                <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEh9eBlF-H7pQKHB7MV3TrjiL8Fm6HS753UjgtMroNDpSfMt_dmrqGoqAq_Bkhq1iSg1Iuflg_k6GHKXcuNXFEh0EmM0DyKY0XelSyShPXkzDX2u74APxyrIuY62s4bxL2JGRRqUBu9y1C_3SwrvCnqEmkJjJWs2v95MOHRkkLeQ08w2U_xMZvykuxtZeYj-/s1260/DP.png"
                    alt="Arigato Devan" class="card-logo">

                <!-- Sticker tags -->
                <div class="card-stickers">
                    <div class="card-sticker card-sticker-1"><i class="fa-solid fa-wand-magic-sparkles"></i> FREE</div>
                    <div class="card-sticker card-sticker-2"><i class="fa-solid fa-lock"></i> SECURE</div>
                </div>

                <!-- Title -->
                <h1 class="login-card-title">Welcome to<br>Arigato Devan Prompts</h1>
                <p class="login-card-sub">Login to unlock premium AI prompts<br>and save your progress forever.</p>

                <!-- Error -->
                <?php if ($error): ?>
                    <div class="login-error"><i class="fa-solid fa-triangle-exclamation"></i>
                        <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Google Sign-In -->
                <button class="google-btn" id="google-login-btn">
                    <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google">
                    Continue with Google
                </button>

                <!-- Or divider -->
                <div class="card-divider">or</div>

                <!-- Explore without login -->
                <a href="gallery.php" class="login-explore">
                    Continue to explore prompts <i class="fa-solid fa-arrow-right"></i>
                </a>

                <!-- Trust badges -->
                <div class="login-promises">
                    <div class="login-promise-badge"><i class="fa-solid fa-key"></i> No password</div>
                    <div class="login-promise-badge"><i class="fa-solid fa-bolt"></i> Instant access</div>
                    <div class="login-promise-badge"><i class="fa-solid fa-ban"></i> No spam</div>
                </div>
            </div>
        </div>

        <!-- Comparison Cards -->
        <section class="login-compare-section" aria-label="Login vs Guest comparison">
            <p class="login-compare-heading"><i class="fa-solid fa-scale-balanced"></i>&nbsp; What you get</p>
            <div class="login-compare-row">

                <!-- WITH LOGIN -->
                <div class="cmp-card cmp-card-with">
                    <div class="cmp-card-badge">
                        <i class="fa-solid fa-circle-check"></i> WITH LOGIN
                    </div>
                    <div class="cmp-card-title">Logged-in Benefits</div>
                    <ul class="cmp-list">
                        <li>
                            <span class="cmp-icon"><i class="fa-solid fa-check"></i></span>
                            <span>Save your prompts permanently</span>
                        </li>
                        <li>
                            <span class="cmp-icon"><i class="fa-solid fa-check"></i></span>
                            <span>No need to unlock again after refresh</span>
                        </li>
                        <li>
                            <span class="cmp-icon"><i class="fa-solid fa-check"></i></span>
                            <span>Only <strong>20 taps</strong> required to unlock prompts</span>
                        </li>
                        <li>
                            <span class="cmp-icon"><i class="fa-solid fa-check"></i></span>
                            <span>Access &amp; purchase premium couple prompts</span>
                        </li>
                        <li>
                            <span class="cmp-icon"><i class="fa-solid fa-check"></i></span>
                            <span>Can comment on blog posts</span>
                        </li>
                    </ul>
                </div>

                <!-- WITHOUT LOGIN -->
                <div class="cmp-card cmp-card-without">
                    <div class="cmp-card-badge">
                        <i class="fa-solid fa-circle-xmark"></i> WITHOUT LOGIN
                    </div>
                    <div class="cmp-card-title">Guest Limitations</div>
                    <ul class="cmp-list">
                        <li>
                            <span class="cmp-icon"><i class="fa-solid fa-xmark"></i></span>
                            <span>Cannot save prompts permanently</span>
                        </li>
                        <li>
                            <span class="cmp-icon"><i class="fa-solid fa-xmark"></i></span>
                            <span>Need to unlock again after refresh</span>
                        </li>
                        <li>
                            <span class="cmp-icon"><i class="fa-solid fa-xmark"></i></span>
                            <span><strong>90 taps</strong> required to unlock prompts</span>
                        </li>
                        <li>
                            <span class="cmp-icon"><i class="fa-solid fa-xmark"></i></span>
                            <span>Cannot access or purchase premium couple prompts</span>
                        </li>
                        <li>
                            <span class="cmp-icon"><i class="fa-solid fa-xmark"></i></span>
                            <span>Cannot comment on blog posts</span>
                        </li>
                    </ul>
                </div>

            </div>
        </section>

        <!-- Ticker -->
        <div class="login-ticker">
            <div class="ticker-label">LIVE</div>
            <div class="ticker-track-wrap">
                <div class="ticker-track">
                    <?php
                    $ticker = ["Couple Prompts are here <i class='fa-solid fa-heart' style='color:#ff3366;'></i>", "Get ready for ultra-realistic AI prompts", "Unlock viral content ideas instantly", "Create stunning couple scenes with AI", "Your next viral reel starts here", "Premium prompts. Real emotions.", "Turn ideas into aesthetic visuals", "AI couple content made easy", "Scroll. Unlock. Create.", "More drops coming every week <i class='fa-solid fa-rocket'></i>"];
                    foreach (array_merge($ticker, $ticker) as $t):
                        ?><span class="ticker-item"><?= $t ?><span class="ticker-sep"><i
                                    class="fa-solid fa-star-of-life"></i></span></span><?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>

    <script>
        // Press effect on Google btn
        const btn = document.getElementById('google-login-btn');
        if (btn) {
            btn.addEventListener('mousedown', () => {
                btn.style.transform = 'translate(4px,4px)';
                btn.style.boxShadow = '1px 1px 0px var(--text-color)';
            });
            btn.addEventListener('mouseup', () => {
                btn.style.transform = '';
                btn.style.boxShadow = '';
            });
        }
    </script>
    
    <!-- Firebase Authentication SDK -->
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js";
        import { getAuth, signInWithPopup, GoogleAuthProvider } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js";

        const firebaseConfig = {
            apiKey: "AIzaSyBAzDxElpLX--lJ8xnvCrQBP-zYFMW_QLQ",
            authDomain: "arigato-devan-prompts.firebaseapp.com",
            projectId: "arigato-devan-prompts",
            storageBucket: "arigato-devan-prompts.firebasestorage.app",
            messagingSenderId: "770814780270",
            appId: "1:770814780270:web:03e1cd5de780452217d77f"
        };

        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);
        const provider = new GoogleAuthProvider();
        provider.setCustomParameters({ prompt: 'select_account' });

        const loginBtn = document.getElementById('google-login-btn');
        
        loginBtn.addEventListener('click', () => {
            loginBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Authenticating...';
            loginBtn.disabled = true;
            
            signInWithPopup(auth, provider)
                .then(async (result) => {
                    // Get the secure ID token
                    const idToken = await result.user.getIdToken();
                    
                    // Send to our PHP backend to start the session
                    const response = await fetch('firebase_auth.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ idToken: idToken })
                    });
                    
                    const data = await response.json();
                    if(data.success) {
                        window.location.href = 'index.php'; // PHP session created, go to home
                    } else {
                        alert("Login error: " + data.error);
                        loginBtn.innerHTML = '<img src="https://developers.google.com/identity/images/g-logo.png" alt="Google"> Continue with Google';
                        loginBtn.disabled = false;
                    }
                })
                .catch((error) => {
                    console.error("Firebase Auth Error:", error);
                    loginBtn.innerHTML = '<img src="https://developers.google.com/identity/images/g-logo.png" alt="Google"> Continue with Google';
                    loginBtn.disabled = false;
                    if(error.code !== 'auth/popup-closed-by-user') {
                        alert("Authentication failed. Please try again.");
                    }
                });
        });
    </script>
</body>

</html>