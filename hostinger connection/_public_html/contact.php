<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="theme-color" content="#c084fc">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us &ndash; Arigato Devan Prompts</title>
    <meta name="description" content="Get in touch with Arigato Devan. We'd love to hear your feedback, suggestions, or answer any questions you have about our AI prompts.">
    <link rel="stylesheet" href="style.min.css?v=20260601">
    <style>
        /* ─── Force transparent body so wallpaper shows ─── */
        html, body {
            margin: 0;
            height: 100%;
            background: transparent !important;
        }
        /* ─── Anime Wallpaper Background ─── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            z-index: -2;
            background-image: url('backgroundwally/only-homepage-pic.webp');
            background-size: cover;
            background-position: center top;
            background-repeat: no-repeat;
        }
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            z-index: -1;
            background: rgba(0,0,0,0.52);
            pointer-events: none;
        }
        @media (max-width: 640px) {
            body::before {
                background-image: url('backgroundwally/only-homepage-pic-for-mobile.webp');
                background-position: center center;
            }
        }
        .aurora-bg { display: none !important; }

        /* ─── Contact Page Styles ─── */
        .contact-wrap {
            max-width: 1000px;
            margin: 0 auto;
            padding: 60px 24px 100px;
            position: relative;
            z-index: 1;
            color: #fff;
        }

        .editorial-title {
            font-size: clamp(4rem, 8vw, 6.5rem);
            font-weight: 900;
            letter-spacing: -0.04em;
            line-height: 1;
            margin-bottom: 60px;
            text-align: center;
        }

        .contact-layout {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 40px;
            align-items: start;
        }

        /* Left Info */
        .contact-info-block {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .ci-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .ci-label {
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgba(255, 255, 255, 0.6);
        }

        .ci-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
        }
        a.ci-value:hover { text-decoration: underline; }

        .ci-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.15);
            margin: 10px 0;
            width: 100%;
        }

        .ci-footer-links {
            margin-top: 20px;
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
            font-weight: 700;
        }
        .ci-footer-links a { color: #fff; text-decoration: none; }
        .ci-footer-links a:hover { text-decoration: underline; }

        /* Right Form Card */
        .contact-form-glass {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 40px;
        }

        .cf-group {
            margin-bottom: 24px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .cf-group label {
            font-size: 0.85rem;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.9);
        }

        .cf-group input, .cf-group textarea {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.08);
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            font-family: var(--font-main);
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            outline: none;
            transition: all 0.2s;
            box-sizing: border-box;
        }

        .cf-group input::placeholder, .cf-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .cf-group input:focus, .cf-group textarea:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .cf-group textarea {
            resize: vertical;
            min-height: 140px;
        }

        .cf-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #c084fc, #ec4899);
            border: none;
            border-radius: 12px;
            font-family: var(--font-main);
            font-weight: 900;
            font-size: 1.05rem;
            color: #fff;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(192, 132, 252, 0.4);
            margin-top: 10px;
        }

        .cf-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(192, 132, 252, 0.6);
        }

        @media (max-width: 768px) {
            .contact-layout {
                grid-template-columns: 1fr;
                gap: 50px;
            }
            .editorial-title {
                margin-bottom: 40px;
                text-align: left;
            }
            .contact-form-glass {
                padding: 30px 20px;
            }
        }
        
        /* Modals / Popups (kept from original) */
        #sending-popup { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); z-index: 3000; align-items: center; justify-content: center; }
        .sp-box { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); backdrop-filter: blur(16px); border-radius: 24px; padding: 40px 36px; text-align: center; color: #fff; max-width: 340px; width: 90%; }
        .sp-spinner { width: 56px; height: 56px; border: 5px solid rgba(255,255,255,0.2); border-top-color: #fff; border-radius: 50%; animation: sp-spin .8s linear infinite; margin: 0 auto 20px; }
        @keyframes sp-spin { to { transform: rotate(360deg); } }
        .sp-title { font-size: 1.3rem; font-weight: 900; margin-bottom: 6px; }
        .sp-sub { font-size: .85rem; color: rgba(255,255,255,0.7); font-weight: 600; }

        #success-popup { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); z-index: 3000; align-items: center; justify-content: center; }
        .suc-box { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); backdrop-filter: blur(16px); border-radius: 24px; padding: 40px 36px; text-align: center; color: #fff; max-width: 380px; width: 90%; }
        .suc-icon { width: 72px; height: 72px; background: rgba(34, 197, 94, 0.2); border: 2px solid #22c55e; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #22c55e; margin: 0 auto 20px; }
        .suc-title { font-size: 1.4rem; font-weight: 900; margin-bottom: 8px; }
        .suc-msg { font-size: .88rem; color: rgba(255,255,255,0.8); font-weight: 600; line-height: 1.6; margin-bottom: 24px; }
        .suc-btn { background: #fff; color: #000; border: none; border-radius: 12px; padding: 11px 28px; font-family: var(--font-main); font-weight: 800; font-size: .92rem; cursor: pointer; transition: all .15s; }
        .suc-btn:hover { transform: translateY(-1px); }

        #form-error { display: none; background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; border-radius: 12px; padding: 12px 16px; font-size: .88rem; font-weight: 700; color: #fca5a5; margin-top: 14px; text-align: center; }

    </style>
    <?php include_once "gtag.php"; ?>
</head>
<body>

<header>
    <div class="logo-area" style="cursor:pointer;" onclick="window.location='index.php'">
        <div class="logo-flipper">
            <div class="logo-front"><img src="toplogo/logo01.webp" alt="Logo"></div>
            <div class="logo-back"><img loading="lazy" src="toplogo/logo02.webp" alt=""></div>
        </div>
        <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
    </div>
    <nav class="nav-links">
        <a href="digital_store/index.php" class="shop-nav-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg> SHOP</a>
        <a href="gallery.php">GALLERY</a>
        <a href="about.php">ABOUT</a>
    </nav>
    <div class="header-right">
        <div class="header-divider"></div>
        <?php if (isset($_SESSION["user_id"])): ?>
            <a href="login.php?logout=1" class="logout"><i class="fa-solid fa-right-from-bracket"></i> LOGOUT</a>
        <?php else: ?>
            <a href="login.php" class="comic-btn" style="font-size:.85rem;padding:9px 18px;text-decoration:none;">LOGIN</a>
        <?php endif; ?>
    </div>
</header>

<div class="contact-wrap">
    
    <h1 class="editorial-title">Contact me</h1>

    <div class="contact-layout">

        <!-- Left Info Block -->
        <div class="contact-info-block">
            <div class="ci-item">
                <span class="ci-label">Email (required)</span>
                <a href="mailto:devansh.grow@gmail.com" class="ci-value">devansh.grow@gmail.com</a>
            </div>
            
            <div class="ci-divider"></div>

            <div class="ci-item">
                <span class="ci-label">Instagram</span>
                <a href="https://instagram.com/arigato.devan" target="_blank" rel="noopener" class="ci-value">@arigato.devan</a>
            </div>

            <div class="ci-divider"></div>

            <div class="ci-item">
                <span class="ci-label">Response Time</span>
                <span class="ci-value">Usually within 24 hours</span>
            </div>

            <div class="ci-footer-links">
                <a href="about.php">About Me</a>
                <a href="gallery.php">Gallery</a>
                <a href="privacy.php">Privacy</a>
            </div>
        </div>

        <!-- Right Form Block -->
        <div class="contact-form-glass">
            <form id="contact-form" novalidate>
                <div class="cf-group">
                    <label for="cf-name">Name</label>
                    <input type="text" id="cf-name" name="name" placeholder="Your name" required maxlength="100">
                </div>
                
                <div class="cf-group">
                    <label for="cf-email">Email</label>
                    <input type="email" id="cf-email" name="email" placeholder="Your email" required maxlength="200">
                </div>
                
                <div class="cf-group">
                    <label for="cf-query">Message</label>
                    <textarea id="cf-query" name="query" placeholder="How can we help?" required maxlength="2000"></textarea>
                </div>
                
                <button type="submit" class="cf-submit">Submit Message</button>
                <div id="form-error"></div>
            </form>
        </div>

    </div>
</div>

<!-- Sending Popup -->
<div id="sending-popup">
    <div class="sp-box">
        <div class="sp-spinner"></div>
        <div class="sp-title">Sending...</div>
        <div class="sp-sub">Please wait, your message is being sent.</div>
    </div>
</div>

<!-- Success Popup -->
<div id="success-popup">
    <div class="suc-box">
        <div class="suc-icon"><i class="fa-solid fa-check"></i></div>
        <div class="suc-title">Message Sent!</div>
        <div class="suc-msg">Your message has been sent successfully.<br>We'll get back to you soon.</div>
        <button class="suc-btn" onclick="closeSucessPopup()">Back to Home</button>
    </div>
</div>

<?php include '../footer.php'; ?>

<script>
document.getElementById('contact-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var name  = document.getElementById('cf-name').value.trim();
    var email = document.getElementById('cf-email').value.trim();
    var query = document.getElementById('cf-query').value.trim();
    var errEl = document.getElementById('form-error');
    errEl.style.display = 'none';

    if (!name || !email || !query) {
        errEl.textContent = 'Please fill in all fields.';
        errEl.style.display = 'block'; return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errEl.textContent = 'Please enter a valid email address.';
        errEl.style.display = 'block'; return;
    }

    // Show sending popup
    document.getElementById('sending-popup').style.display = 'flex';

    var fd = new FormData();
    fd.append('name', name);
    fd.append('email', email);
    fd.append('query', query);

    fetch('send_contact.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.getElementById('sending-popup').style.display = 'none';
            if (data.ok) {
                document.getElementById('success-popup').style.display = 'flex';
                document.getElementById('contact-form').reset();
            } else {
                errEl.textContent = data.error || 'Something went wrong. Please try again.';
                errEl.style.display = 'block';
            }
        })
        .catch(function() {
            document.getElementById('sending-popup').style.display = 'none';
            errEl.textContent = 'Network error. Please check your connection and try again.';
            errEl.style.display = 'block';
        });
});

function closeSucessPopup() {
    document.getElementById('success-popup').style.display = 'none';
    window.location.href = 'index.php';
}
</script>
</body>
</html>
