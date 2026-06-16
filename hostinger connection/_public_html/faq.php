<?php
session_start();

$curPage = 'faq.php';
if (isset($_SESSION['user_id'])) {
    require_once "db.php";
    try {
        $stmt = $pdo->prepare("SELECT
            (SELECT COUNT(*) FROM prompts WHERE prompt_type='secret') as secret_code,
            (SELECT COUNT(*) FROM prompts WHERE prompt_type='unreleased') as unreleased,
            (SELECT COUNT(*) FROM prompts WHERE prompt_type='insta_viral') as insta_viral,
            (SELECT COUNT(*) FROM prompts WHERE prompt_type='already_uploaded') as already_uploaded
        ");
        $stmt->execute();
        $nav_counts = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $nav_counts = []; }
} else { $nav_counts = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Arigato Devan Prompts</title>
    <link rel="stylesheet" href="style.min.css?v=20260601">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* === Vintar Theme Clean Override === */
        html, body {
            background: #f9fafb !important; /* light grey/white background */
            color: #111827 !important;
            margin: 0; padding: 0;
            font-family: var(--font-main, 'Outfit', sans-serif);
        }
        body::before { display: none !important; }
        body::after { display: none !important; }
        .aurora-bg, .scroll-bg-container { display: none !important; }

        /* Remove header overrides to keep the site's global pill-shaped comic header */

        .faq-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 24px;
        }

        .faq-hero {
            text-align: center;
            margin-bottom: 64px;
        }
        .faq-hero h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            margin-bottom: 16px;
            letter-spacing: -0.02em;
            color: #111827;
        }
        .faq-hero h1 span { color: #007bff; }
        .faq-hero p {
            font-size: 1.15rem;
            color: #4b5563;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .faq-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: start;
        }
        @media (max-width: 800px) {
            .faq-grid { grid-template-columns: 1fr; gap: 60px; }
        }

        .faq-column h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 24px;
            color: #111827;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 12px;
        }

        .faq-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 16px;
            overflow: hidden;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .faq-card:hover {
            border-color: #d1d5db;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .faq-question {
            width: 100%;
            background: none;
            border: none;
            text-align: left;
            padding: 20px 24px;
            font-size: 1.1rem;
            font-weight: 600;
            color: #111827;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: inherit;
        }
        .faq-icon {
            color: #007bff;
            font-size: 1.2rem;
            transition: transform 0.3s ease;
            flex-shrink: 0;
            margin-left: 16px;
        }
        .faq-card.open .faq-icon {
            transform: rotate(45deg);
            color: #ef4444; /* changes to red 'x' visually, or keep blue */
        }
        
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        .faq-answer-inner {
            padding: 0 24px 24px;
            color: #4b5563;
            line-height: 1.6;
            font-size: 1rem;
        }
        .faq-answer-inner a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }
        .faq-answer-inner a:hover { text-decoration: underline; }

        /* Vintar style Footer */
        .vintar-footer {
            background: #fff;
            border-top: 1px solid #e5e7eb;
            padding: 80px 24px 40px;
            margin-top: 60px;
        }
        .vintar-footer-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
        }
        .footer-col h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 20px;
        }
        .footer-col a {
            display: block;
            color: #4b5563;
            text-decoration: none;
            margin-bottom: 12px;
            font-size: 0.95rem;
            transition: color 0.2s;
        }
        .footer-col a:hover { color: #007bff; }
        .footer-col.newsletter p {
            color: #4b5563;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 20px;
        }
        .insta-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #111827;
            color: #fff !important;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
        }
        .insta-btn:hover { background: #1f2937; }
        .footer-bottom {
            max-width: 1200px;
            margin: 40px auto 0;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #9ca3af;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <header>
        <div class="logo-area" id="logo-container" style="cursor:pointer;" onclick="window.location='index.php'">
            <div class="logo-flipper">
                <div class="logo-front"><img src="toplogo/logo01.webp" alt="Arigato Devan Logo" id="profile-logo"></div>
                <div class="logo-back"><img src="toplogo/logo02.webp" alt="Logo Alt" loading="lazy"></div>
            </div>
            <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
        </div>
        <nav class="nav-links">
            <a href="digital_store/index.php">SHOP</a>
            <a href="gallery.php">GALLERY</a>
            <a href="blogs.php">BLOGS</a>
            <a href="faq.php" title="FAQ" class="active" style="padding:8px 10px;display:flex;align-items:center;"><i class="fa-solid fa-circle-question" style="font-size:1.2rem;"></i></a>
        </nav>
        <div class="header-right">
            <div class="header-divider"></div>
            <?php if (isset($_SESSION["user_id"])): ?>
                <a href="profile.php" title="Profile" style="display:flex;align-items:center;">
                    <?php if (!empty($_SESSION["profile_image"])): ?>
                        <img loading="lazy" src="<?= htmlspecialchars($_SESSION["profile_image"]) ?>" referrerpolicy="no-referrer" style="width:36px;height:36px;border-radius:50%;border:2px solid var(--primary-color);">
                    <?php else: ?>
                        <div style="width:36px;height:36px;border-radius:50%;background:var(--primary-color);border:2px solid var(--text-color);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.9rem;color:#fff;"><?= strtoupper(substr($_SESSION["username"] ?? "U", 0, 1)) ?></div>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <a href="login.php" class="btn-primary" style="font-size:.82rem;padding:8px 16px;">Sign In</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="faq-wrapper">
        <div class="faq-hero">
            <h1>Frequently Asked <span>Question</span></h1>
            <p>We provide a secure, powerful platform to get the absolute best AI prompts directly from viral Instagram reels.</p>
        </div>

        <div class="faq-grid">
            <!-- Left Column -->
            <div class="faq-column">
                <h2>Get to Know Arigato Devan Better</h2>

                <div class="faq-card">
                    <button class="faq-question">
                        Is this site completely free?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner"><strong>Yes, 100% free for now!</strong> No subscriptions, no hidden charges. Enjoy unlimited access.</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        How often are new prompts added?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">New prompts are added <strong>every 2–3 days</strong>. Follow <a href='https://www.instagram.com/arigato.devan/' target='_blank'>@arigato.devan</a> on Instagram to get notified first!</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        Is my data safe?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">100% safe. Only your name and email are collected — nothing else. Everything is secured through Google's own services.</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        What is a streak and how do I increase it?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">Your <strong>streak increases by 1 every day you log in</strong>. Miss a single day and it resets to zero. Stay consistent!</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        Is Google login required?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">Not at all! You can browse and unlock prompts <strong>without logging in</strong>. But login unlocks extra benefits: save &amp; like prompts, and unlock Unreleased prompts with just <strong>20 taps</strong> instead of 90.</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        What is the Already Uploaded section?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">Before this site existed, prompts were shared via <strong>Notion and Instagram Reels</strong>. This section is the complete archive — every old prompt, all in one place. New prompts won't come here; they go straight to the main sections.</div></div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="faq-column">
                <h2>Questions About Our Prompts</h2>

                <div class="faq-card">
                    <button class="faq-question">
                        What is a Secret Code prompt?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">A Secret Code is a <strong>6-letter code</strong> hidden inside an Instagram Reel. Drop a comment on the reel &rarr; the code arrives in your DMs automatically via Auto-DM &rarr; enter it on the site &rarr; exclusive prompt unlocked!</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        How do I unlock a Secret Code prompt?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner"><strong>Step 1:</strong> Go to the Instagram Reel &nbsp;&rarr;&nbsp; <strong>Step 2:</strong> Drop any comment &nbsp;&rarr;&nbsp; <strong>Step 3:</strong> Code arrives in DMs via Auto-DM &nbsp;&rarr;&nbsp; <strong>Step 4:</strong> Copy it &nbsp;&rarr;&nbsp; <strong>Step 5:</strong> Paste in the Secret Code box on this site &nbsp;&rarr;&nbsp; Prompt unlocked!</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        What are Unreleased prompts?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">These are prompts that were <strong>created but never posted</strong> on Instagram — too experimental, too niche, or just didn't feel right for the feed. Instead of deleting them, they're shared here exclusively.</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        How do I unlock Unreleased prompts?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">No code needed here! <br><strong>Without login:</strong> 90 heart taps to unlock. <br><strong>With Google login:</strong> Just 20 taps. Simple! <a href='login.php'>Login here &rarr;</a></div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        What are Insta Viral prompts?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">These are prompts <strong>spotted going viral on Instagram</strong> — trending reels that everyone is using right now. Collected, curated, and brought here for you!</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        Are these actually viral on Instagram?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">Absolutely! These are <strong>actual prompts from viral reels</strong> — real trending content. If you've seen a reel blowing up with AI couple content, the prompt behind it is probably right here.</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        Which AI tool should I use?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner"><strong>Gemini (Google)</strong> is the best — 90% of prompts are optimized for Gemini. It generates stunning, hot romantic visuals with far fewer restrictions. ChatGPT tends to block or water down couple content.</div></div>
                </div>

                <div class="faq-card">
                    <button class="faq-question">
                        Why does the same prompt give different results?
                        <i class="fa-solid fa-plus faq-icon"></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">AI tools generate images using <strong>probability and creativity</strong> — even the same prompt produces a unique output every single time. This is a <strong>feature, not a bug!</strong> Try 2–3 times and pick your favourite result.</div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vintar Footer -->
    <div class="vintar-footer">
        <div class="vintar-footer-inner">
            <div class="footer-col">
                <h3>Company</h3>
                <a href="about.php">About</a>
                <a href="contact.php">Contact</a>
                <a href="blogs.php">Blogs</a>
                <a href="digital_store/index.php">SHOP</a>
            </div>
            <div class="footer-col">
                <h3>Legal</h3>
                <a href="privacy.php">Privacy Policy</a>
                <a href="terms.php">Terms of Service</a>
                <a href="disclaimer.php">Disclaimer</a>
            </div>
            <div class="footer-col newsletter">
                <h3>Subscribe to our Updates</h3>
                <p>Follow us on Instagram to get notified whenever new prompts drop, exclusive behind-the-scenes, and much more.</p>
                <a href="https://www.instagram.com/arigato.devan/" target="_blank" class="insta-btn"><i class="fa-brands fa-instagram"></i> Follow @arigato.devan</a>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?= date("Y") ?> ARIGATO DEVAN. ALL RIGHTS RESERVED.
        </div>
    </div>

    <script>
    document.querySelectorAll('.faq-question').forEach(button => {
        button.addEventListener('click', () => {
            const card = button.closest('.faq-card');
            const answer = card.querySelector('.faq-answer');
            const isOpen = card.classList.contains('open');

            // Close all other open cards
            document.querySelectorAll('.faq-card.open').forEach(openCard => {
                openCard.classList.remove('open');
                openCard.querySelector('.faq-answer').style.maxHeight = null;
            });

            if (!isOpen) {
                card.classList.add('open');
                answer.style.maxHeight = answer.scrollHeight + 'px';
            }
        });
    });
    </script>
</body>
</html>
