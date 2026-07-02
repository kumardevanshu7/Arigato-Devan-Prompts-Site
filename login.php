<?php
session_start();
// Handle logout
if (isset($_GET["logout"])) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        setcookie(session_name(), "", [
            "expires" => time() - 3600,
            "path" => "/",
            "secure" => (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off"),
            "httponly" => true,
            "samesite" => "Lax",
        ]);
    }
    session_destroy();
    header("Location: login.php");
    exit();
}

// Redirect if already logged in
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}
$error = $_SESSION["error_msg"] ?? "";
unset($_SESSION["error_msg"]);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &ndash; Arigato Devan Prompts</title>
    <meta name="description" content="Login to Arigato Devan PromptVerse and unlock premium AI couple prompts.">
    <link rel="stylesheet" href="digital_store/css/store.css">
    <style>
        /* ─── Nogoda Palette Styles ─── */
        :root {
            --nogoda-plum: #6D2D52;
            --nogoda-rose: #F5709D;
            --nogoda-mint: #11FFC9;
            --nogoda-sky: #2FA6C6;
            --nogoda-indigo: #204162;
        }

        html, body {
            background: #F8F6F2 !important;
            background-image: none !important;
            background-color: #F8F6F2 !important;
            font-family: 'Inter', sans-serif !important;
            color: var(--nogoda-indigo) !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: hidden !important;
            -webkit-font-smoothing: antialiased;
        }
        body::before, body::after { display: none !important; }

        .login-root {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            background: #F8F6F2;
        }

        .login-filmstrip, .login-filmstrip-overlay {
            display: none;
        }

        /* ─── Header ─── */
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
            border: 1.5px solid rgba(32, 65, 98, 0.15);
            border-radius: 40px;
            background: var(--bg-card);
            box-shadow: 0 4px 15px rgba(32, 65, 98, 0.04);
        }

        .login-header-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .login-header-logo img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid rgba(32, 65, 98, 0.2);
            object-fit: cover;
        }

        .login-header-logo-text {
            font-family: 'Playfair Display', serif;
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--nogoda-indigo);
            letter-spacing: -0.02em;
        }

        .login-header-nav a {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.82rem;
            color: var(--nogoda-indigo);
            text-decoration: none;
            padding: 8px 18px;
            border: 1.5px solid rgba(32, 65, 98, 0.2);
            border-radius: 20px;
            background: var(--bg-card);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .login-header-nav a:hover {
            border-color: var(--nogoda-sky);
            color: var(--nogoda-plum);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        /* ─── Center Content ─── */
        .login-body {
            position: relative;
            z-index: 5;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        /* ─── Login Card ─── */
        .login-card {
            background: var(--bg-card);
            border: 1.5px solid rgba(32, 65, 98, 0.12);
            border-radius: 28px;
            padding: 48px 40px;
            box-shadow: 0 10px 30px rgba(32, 65, 98, 0.06);
            max-width: 440px;
            width: 100%;
            text-align: center;
            animation: loginCardIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes loginCardIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-logo {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            border: 1.5px solid rgba(32, 65, 98, 0.15);
            object-fit: cover;
            box-shadow: var(--shadow-sm);
            margin: 0 auto 20px;
            display: block;
        }

        .card-stickers {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 22px;
        }

        .card-sticker {
            font-family: 'Inter', sans-serif;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 5px 12px;
            border-radius: 99px;
            border: 1px solid transparent;
        }

        .card-sticker-1 {
            background: rgba(245, 112, 157, 0.15);
            color: var(--nogoda-plum);
            border-color: rgba(245, 112, 157, 0.25);
        }

        .card-sticker-2 {
            background: rgba(47, 166, 198, 0.12);
            color: var(--nogoda-indigo);
            border-color: rgba(47, 166, 198, 0.22);
        }

        .login-card-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.85rem;
            font-weight: 900;
            margin-bottom: 12px;
            line-height: 1.25;
            color: var(--nogoda-indigo);
        }

        .login-card-sub {
            font-family: 'Inter', sans-serif;
            font-size: 0.88rem;
            font-weight: 500;
            color: #5a6e85;
            margin-bottom: 32px;
            line-height: 1.5;
        }

        .login-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            border-radius: 12px;
            padding: 12px 16px;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 0.88rem;
        }

        /* Google Button — Nogoda light gradient, beautiful and colorful */
        .google-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 15px 24px;
            background: linear-gradient(135deg, rgba(245, 112, 157, 0.08) 0%, rgba(47, 166, 198, 0.08) 100%);
            color: var(--nogoda-indigo);
            border: 1.5px solid rgba(47, 166, 198, 0.4);
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(47, 166, 198, 0.06);
            transition: var(--transition);
            cursor: pointer;
            margin-bottom: 16px;
        }

        .google-btn:hover {
            background: linear-gradient(135deg, rgba(245, 112, 157, 0.15) 0%, rgba(47, 166, 198, 0.15) 100%);
            border-color: var(--nogoda-sky);
            transform: translateY(-1.5px);
            box-shadow: 0 6px 15px rgba(47, 166, 198, 0.12);
        }

        .login-explore {
            font-family: 'Inter', sans-serif;
            font-size: 0.82rem;
            font-weight: 600;
            color: #5a6e85;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
            padding: 4px 0;
        }

        .login-explore:hover {
            color: var(--nogoda-rose);
        }

        .card-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
            font-size: 0.75rem;
            font-weight: 600;
            color: #a0aec0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .card-divider::before,
        .card-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(32, 65, 98, 0.1);
        }

        .login-promises {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px dashed rgba(32, 65, 98, 0.12);
        }

        .login-promise-badge {
            font-family: 'Inter', sans-serif;
            font-size: 0.72rem;
            font-weight: 500;
            color: #5a6e85;
            background: rgba(32, 65, 98, 0.03);
            border: 1px solid rgba(32, 65, 98, 0.1);
            border-radius: 20px;
            padding: 4px 12px;
        }

        /* --- Comparison Section --- */
        .login-compare-section {
            position: relative;
            z-index: 5;
            padding: 0 20px 64px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .login-compare-heading {
            font-family: 'Inter', sans-serif;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #a0aec0;
            text-align: center;
            margin-bottom: 4px;
        }

        .login-compare-row {
            display: flex;
            gap: 24px;
            width: 100%;
            max-width: 760px;
            justify-content: center;
        }

        .cmp-card {
            flex: 1;
            max-width: 360px;
            background: var(--bg-card);
            border: 1.5px solid rgba(32, 65, 98, 0.1);
            border-radius: 20px;
            padding: 32px 28px;
            box-shadow: 0 4px 12px rgba(32, 65, 98, 0.02);
            transition: var(--transition);
            text-align: left;
        }

        .cmp-card:hover {
            box-shadow: 0 8px 24px rgba(32, 65, 98, 0.06);
            transform: translateY(-2px);
        }

        .cmp-card-with {
            background: #fffdf9;
            border-color: rgba(245, 112, 157, 0.2);
        }

        .cmp-card-without {
            background: #fafafc;
        }

        .cmp-card-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 99px;
            margin-bottom: 18px;
            white-space: nowrap;
        }

        .cmp-card-with .cmp-card-badge {
            background: rgba(17, 255, 201, 0.15);
            color: #0f766e;
        }

        .cmp-card-without .cmp-card-badge {
            background: rgba(245, 112, 157, 0.12);
            color: #be123c;
        }

        .cmp-card-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 18px;
            color: var(--nogoda-indigo);
        }

        .cmp-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .cmp-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.85rem;
            font-weight: 500;
            line-height: 1.45;
            color: #5a6e85;
        }

        .cmp-icon {
            flex-shrink: 0;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.6rem;
            margin-top: 2px;
        }

        .cmp-card-with .cmp-icon {
            background: var(--nogoda-mint);
            color: var(--nogoda-indigo);
            font-weight: bold;
        }

        .cmp-card-without .cmp-icon {
            background: var(--nogoda-rose);
            color: #fff;
        }

        /* ─── Ticker / Footer ─── */
        .login-ticker {
            position: relative;
            z-index: 10;
            background: var(--nogoda-indigo) !important;
            height: 48px;
            display: flex;
            align-items: center;
            overflow: hidden;
            width: 100%;
            margin-top: auto;
            border-top: 1.5px solid rgba(255, 255, 255, 0.1);
        }

        .ticker-label {
            flex-shrink: 0;
            background: var(--nogoda-rose);
            color: #fff !important;
            font-weight: 800;
            font-size: 0.72rem;
            padding: 0 16px;
            height: 100%;
            display: flex;
            align-items: center;
            letter-spacing: 2px;
            border-right: 1px solid rgba(255, 255, 255, 0.15);
            animation: pulseTicker 1.2s ease-in-out infinite;
        }

        .ticker-track-wrap {
            overflow: hidden;
            flex: 1;
            height: 100%;
            display: flex;
            align-items: center;
        }

        .ticker-track {
            display: flex;
            align-items: center;
            white-space: nowrap;
            width: max-content;
            animation: tickerScroll 45s linear infinite;
            will-change: transform;
        }

        .ticker-item {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.82rem;
            padding: 0 6px;
            color: rgba(255, 255, 255, 0.9) !important;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .ticker-sep {
            margin: 0 16px;
            color: var(--nogoda-mint);
            font-size: 0.7rem;
        }

        @keyframes pulseTicker {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        @keyframes tickerScroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* ─── RESPONSIVE ─── */
        @media (max-width: 700px) {
            .login-compare-row {
                flex-direction: column;
                align-items: center;
            }
            .cmp-card {
                max-width: 100%;
                width: 100%;
            }
        }
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
                font-size: 1.5rem;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,800;0,900;1,600&family=Outfit:wght@400;600;800;900&display=swap" rel="stylesheet">
    <?php include_once "gtag.php"; ?>
</head>

<body>
    <div class="login-root">
        <!-- Aurora Background -->
        <div class="aurora-bg" aria-hidden="true">
            <div class="aurora-blob blob1"></div>
            <div class="aurora-blob blob2"></div>
            <div class="aurora-blob blob3"></div>
            <div class="aurora-blob blob4"></div>
        </div>
        <style>
        .aurora-bg{display:none!important;}
        .login-root>*:not(.aurora-bg){position:relative;z-index:1;}
        </style>


        <!-- Header -->
        <header class="login-header">
            <a href="index.php" class="login-header-logo">
                <img src="toplogo/logo01.webp" alt="Logo">
                <div class="login-header-logo-text">arigatoPrompt</div>
            </a>
            <nav class="login-header-nav">
                <a href="index.php"><i class="fa-solid fa-arrow-left"></i> HOME</a>
            </nav>
        </header>

        <!-- Center Login Card -->
        <div class="login-body">
            <div class="login-card">

                <!-- Avatar -->
                <img src="toplogo/logo01.webp" alt="Arigato Devan" class="card-logo">

                <!-- Sticker tags -->
                <div class="card-stickers">
                    <div class="card-sticker card-sticker-1"><i class="fa-solid fa-wand-magic-sparkles"></i> FREE</div>
                    <div class="card-sticker card-sticker-2"><i class="fa-solid fa-lock"></i> SECURE</div>
                </div>

                <!-- Title -->
                <h1 class="login-card-title">Welcome to<br>arigatoPrompt</h1>
                <p class="login-card-sub">Login to unlock premium AI prompts<br>and save your progress forever.</p>

                <!-- Error -->
                <?php if ($error): ?>
                    <div class="login-error"><i class="fa-solid fa-triangle-exclamation"></i>
                        <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Google Sign-In -->
                <button class="google-btn" id="google-login-btn">
                    <i class="fa-brands fa-google" style="font-size:20px;margin-right:8px;"></i>
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
                    $ticker = [
                        "Couple Prompts are here <i class='fa-solid fa-heart' style='color:#ff3366;'></i>",
                        "Get ready for ultra-realistic AI prompts",
                        "Unlock viral content ideas instantly",
                        "Create stunning couple scenes with AI",
                        "Your next viral reel starts here",
                        "Premium prompts. Real emotions.",
                        "Turn ideas into aesthetic visuals",
                        "AI couple content made easy",
                        "Scroll. Unlock. Create.",
                        "More drops coming every week <i class='fa-solid fa-rocket'></i>",
                    ];
                    foreach (
                        array_merge($ticker, $ticker)
                        as $t
                    ): ?><span class="ticker-item"><?= $t ?><span class="ticker-sep"><i
                                    class="fa-solid fa-star-of-life"></i></span></span><?php endforeach;
                    ?>
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
                        loginBtn.innerHTML = '<i class="fa-brands fa-google" style="font-size:20px;margin-right:8px;"></i> Continue with Google';
                        loginBtn.disabled = false;
                    }
                })
                .catch((error) => {
                    console.error("Firebase Auth Error:", error);
                    loginBtn.innerHTML = '<i class="fa-brands fa-google" style="font-size:20px;margin-right:8px;"></i> Continue with Google';
                    loginBtn.disabled = false;
                    if(error.code !== 'auth/popup-closed-by-user') {
                        alert("Authentication failed. Please try again.");
                    }
                });
        });
    </script>
</body>

</html>
