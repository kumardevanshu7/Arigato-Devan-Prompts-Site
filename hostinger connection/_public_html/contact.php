<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us &ndash; Arigato Devan Prompts</title>
    <meta name="description" content="Get in touch with Arigato Devan — we'd love to hear from you. Send us your queries, feedback, or suggestions.">
    <link rel="canonical" href="https://arigatodevan.com/contact.php">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Arigato Devan Prompts">
    <meta property="og:title" content="Contact Us — Arigato Devan PromptVerse">
    <meta property="og:description" content="Have a question or feedback? Contact Arigato Devan — we reply within 24 hours.">
    <meta property="og:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <meta property="og:url" content="https://arigatodevan.com/contact.php">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Contact Us — Arigato Devan PromptVerse">
    <meta name="twitter:description" content="Have a question or feedback? Contact Arigato Devan — we reply within 24 hours.">
    <meta name="twitter:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <link rel="stylesheet" href="style.min.css?v=20260601">
    <style>
        .contact-wrap { max-width: 860px; margin: 0 auto; padding: 40px 24px 100px; }

        /* Layout */
        .contact-layout { display: grid; grid-template-columns: 1fr 1.4fr; gap: 28px; align-items: start; }

        /* Left info card */
        .contact-info { background: var(--card-bg); border: var(--border-width) solid var(--text-color); border-radius: 24px; padding: 36px 30px; box-shadow: var(--shadow-comic); }
        .contact-info h2 { font-size: 1.8rem; font-weight: 900; margin-bottom: 8px; }
        .contact-info .ci-sub { font-size: .88rem; color: #777; font-weight: 600; margin-bottom: 28px; line-height: 1.6; }
        .ci-item { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 22px; }
        .ci-icon { width: 42px; height: 42px; border-radius: 12px; border: 2.5px solid var(--text-color); display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; box-shadow: 2px 2px 0 var(--text-color); }
        .ci-icon.purple { background: var(--primary-color); }
        .ci-icon.yellow { background: var(--secondary-color); }
        .ci-icon.pink   { background: #fce7f3; }
        .ci-text strong { display: block; font-size: .88rem; font-weight: 900; }
        .ci-text span   { font-size: .82rem; color: #777; font-weight: 600; }

        .contact-social { display: flex; gap: 10px; margin-top: 28px; flex-wrap: wrap; }
        .cs-btn { background: var(--bg-color); border: 2px solid var(--text-color); border-radius: 10px; padding: 8px 16px; font-family: var(--font-main); font-weight: 800; font-size: .78rem; text-decoration: none; color: var(--text-color); box-shadow: 2px 2px 0 var(--text-color); display: inline-flex; align-items: center; gap: 6px; transition: all .15s; }
        .cs-btn:hover { transform: translateY(-1px); box-shadow: 3px 3px 0 var(--text-color); }

        /* Right form card */
        .contact-form-card { background: var(--card-bg); border: var(--border-width) solid var(--text-color); border-radius: 24px; padding: 36px 32px; box-shadow: var(--shadow-comic); }
        .contact-form-card h2 { font-size: 1.5rem; font-weight: 900; margin-bottom: 22px; }

        .cf-group { margin-bottom: 20px; }
        .cf-group label { display: block; font-size: .82rem; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 8px; color: var(--text-color); }
        .cf-group input, .cf-group textarea, .cf-group select {
            width: 100%; padding: 13px 16px; border: 2.5px solid var(--text-color); border-radius: 14px;
            font-family: var(--font-main); font-size: .95rem; font-weight: 600;
            background: var(--bg-color); color: var(--text-color); outline: none;
            box-shadow: 3px 3px 0 var(--text-color); transition: all .2s; box-sizing: border-box;
        }
        .cf-group input:focus, .cf-group textarea:focus, .cf-group select:focus { transform: translateY(-1px); box-shadow: 4px 4px 0 var(--text-color); }
        .cf-group textarea { resize: vertical; min-height: 130px; }

        .cf-submit { width: 100%; padding: 15px; background: var(--primary-color); border: 2.5px solid var(--text-color); border-radius: 14px; font-family: var(--font-main); font-weight: 900; font-size: 1rem; cursor: pointer; box-shadow: 4px 4px 0 var(--text-color); transition: all .15s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .cf-submit:hover { transform: translateY(-2px); box-shadow: 5px 5px 0 var(--text-color); }
        .cf-submit:active { transform: translateY(0); box-shadow: 2px 2px 0 var(--text-color); }

        /* Sending popup */
        #sending-popup { display: none; position: fixed; inset: 0; background: rgba(45,42,53,.6); backdrop-filter: blur(8px); z-index: 3000; align-items: center; justify-content: center; }
        .sp-box { background: var(--card-bg); border: 3px solid var(--text-color); border-radius: 24px; padding: 40px 36px; text-align: center; box-shadow: 8px 8px 0 var(--text-color); max-width: 340px; width: 90%; }
        .sp-spinner { width: 56px; height: 56px; border: 5px solid var(--primary-color); border-top-color: var(--text-color); border-radius: 50%; animation: sp-spin .8s linear infinite; margin: 0 auto 20px; }
        @keyframes sp-spin { to { transform: rotate(360deg); } }
        .sp-title { font-size: 1.3rem; font-weight: 900; margin-bottom: 6px; }
        .sp-sub { font-size: .85rem; color: #888; font-weight: 600; }

        /* Success popup */
        #success-popup { display: none; position: fixed; inset: 0; background: rgba(45,42,53,.6); backdrop-filter: blur(8px); z-index: 3000; align-items: center; justify-content: center; }
        .suc-box { background: var(--card-bg); border: 3px solid var(--text-color); border-radius: 24px; padding: 40px 36px; text-align: center; box-shadow: 8px 8px 0 var(--text-color); max-width: 380px; width: 90%; }
        .suc-icon { width: 72px; height: 72px; background: #d1fae5; border: 3px solid var(--text-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #15803d; margin: 0 auto 20px; box-shadow: 3px 3px 0 var(--text-color); }
        .suc-title { font-size: 1.4rem; font-weight: 900; margin-bottom: 8px; }
        .suc-msg { font-size: .88rem; color: #666; font-weight: 600; line-height: 1.6; margin-bottom: 24px; }
        .suc-btn { background: var(--primary-color); border: 2.5px solid var(--text-color); border-radius: 12px; padding: 11px 28px; font-family: var(--font-main); font-weight: 800; font-size: .92rem; cursor: pointer; box-shadow: 3px 3px 0 var(--text-color); transition: all .15s; }
        .suc-btn:hover { transform: translateY(-1px); box-shadow: 4px 4px 0 var(--text-color); }

        /* Error msg */
        #form-error { display: none; background: #fee2e2; border: 2px solid #ef4444; border-radius: 12px; padding: 12px 16px; font-size: .88rem; font-weight: 700; color: #b91c1c; margin-top: 14px; }

        @media (max-width: 700px) {
            .contact-layout { grid-template-columns: 1fr; }
            .contact-wrap { padding: 20px 14px 80px; }
        }
    </style>
    <?php include_once "gtag.php"; ?>
</head>
<body>
<!-- Aurora -->
<div class="aurora-bg" aria-hidden="true" style="position:fixed;inset:0;z-index:0;overflow:hidden;pointer-events:none;">
    <div style="position:absolute;width:65%;height:65%;background:radial-gradient(circle,#c8b4f8,#e9d8fd);border-radius:50%;filter:blur(90px);opacity:.55;top:-15%;left:-10%;animation:auroraFloat1 12s ease-in-out infinite;"></div>
    <div style="position:absolute;width:55%;height:55%;background:radial-gradient(circle,#ffb3c6,#ffd6e7);border-radius:50%;filter:blur(90px);opacity:.55;bottom:-20%;right:-10%;animation:auroraFloat2 15s ease-in-out infinite;"></div>
    <div style="position:absolute;width:45%;height:45%;background:radial-gradient(circle,#a5f3fc,#e0f2fe);border-radius:50%;filter:blur(90px);opacity:.55;top:30%;right:5%;animation:auroraFloat3 10s ease-in-out infinite;"></div>
</div>
<style>
.aurora-bg~*{position:relative;z-index:1;}
@keyframes auroraFloat1{0%,100%{transform:translate(0,0) scale(1);}33%{transform:translate(6%,8%) scale(1.08);}66%{transform:translate(-4%,5%) scale(0.95);}}
@keyframes auroraFloat2{0%,100%{transform:translate(0,0) scale(1);}33%{transform:translate(-8%,-6%) scale(1.06);}66%{transform:translate(5%,-3%) scale(0.97);}}
@keyframes auroraFloat3{0%,100%{transform:translate(0,0) scale(1);}50%{transform:translate(-10%,8%) scale(1.1);}}
</style>

<header>
    <div class="logo-area" style="cursor:pointer;" onclick="window.location='index.php'">
        <div class="logo-flipper">
            <div class="logo-front"><img src="toplogo/logo01.webp" alt="Logo"></div>
            <div class="logo-back"><img loading="lazy" src="toplogo/logo02.webp" alt=""></div>
        </div>
        <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
    </div>
    <nav class="nav-links">
        <a href="index.php">HOME</a>
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

    <!-- Page title -->
    <div style="text-align:center;margin-bottom:32px;">
        <div style="font-size:2.2rem;font-weight:900;display:inline-flex;align-items:center;gap:12px;">
            <i class="fa-solid fa-envelope" style="color:var(--primary-color);"></i> Contact Us
        </div>
        <div style="font-size:.95rem;color:#888;font-weight:600;margin-top:6px;">We'd love to hear from you — usually reply within 24 hours.</div>
    </div>

    <div class="contact-layout">

        <!-- Left: Info -->
        <div class="contact-info">
            <h2>Get in Touch</h2>
            <p class="ci-sub">Have a question, suggestion, or just want to say hi? Drop us a message and we'll get back to you as soon as possible.</p>

            <div class="ci-item">
                <div class="ci-icon purple"><i class="fa-solid fa-envelope"></i></div>
                <div class="ci-text">
                    <strong>Email</strong>
                    <span>devansh.grow@gmail.com</span>
                </div>
            </div>
            <div class="ci-item">
                <div class="ci-icon yellow"><i class="fa-brands fa-instagram"></i></div>
                <div class="ci-text">
                    <strong>Instagram</strong>
                    <span>@arigato.devan</span>
                </div>
            </div>
            <div class="ci-item">
                <div class="ci-icon pink"><i class="fa-solid fa-clock"></i></div>
                <div class="ci-text">
                    <strong>Response Time</strong>
                    <span>Usually within 24 hours</span>
                </div>
            </div>

            <div class="contact-social">
                <a href="https://instagram.com/arigato.devan" target="_blank" rel="noopener" class="cs-btn">
                    <i class="fa-brands fa-instagram"></i> Instagram
                </a>
                <a href="about.php" class="cs-btn">
                    <i class="fa-solid fa-user"></i> About Us
                </a>
            </div>
        </div>

        <!-- Right: Form -->
        <div class="contact-form-card">
            <h2><i class="fa-solid fa-paper-plane" style="color:var(--primary-color);"></i> Send a Message</h2>
            <form id="contact-form" novalidate>
                <div class="cf-group">
                    <label for="cf-name"><i class="fa-solid fa-user"></i> Your Name</label>
                    <input type="text" id="cf-name" name="name" placeholder="Enter your name" required maxlength="100">
                </div>
                <div class="cf-group">
                    <label for="cf-email"><i class="fa-solid fa-envelope"></i> Your Email</label>
                    <input type="email" id="cf-email" name="email" placeholder="your@email.com" required maxlength="200">
                </div>
                <div class="cf-group">
                    <label for="cf-query"><i class="fa-solid fa-circle-question"></i> What's Your Query?</label>
                    <textarea id="cf-query" name="query" placeholder="Type your message here..." required maxlength="2000"></textarea>
                </div>
                <button type="submit" class="cf-submit">
                    <i class="fa-solid fa-paper-plane"></i> Send Message
                </button>
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
        <div class="suc-msg">Your message has been sent successfully.<br>Admin aapka message review karega aur jaldi reply karega.</div>
        <button class="suc-btn" onclick="closeSucessPopup()"><i class="fa-solid fa-arrow-left"></i> Back to Home</button>
    </div>
</div>

<footer>
    <div>&copy; <?= date("Y") ?> ARIGATO DEVAN. KEEP CREATING.</div>
    <div class="footer-links"><a href="about.php">ABOUT</a><a href="contact.php">CONTACT</a><a href="faq.php">FAQ</a><a href="privacy.php">PRIVACY POLICY</a><a href="disclaimer.php">DISCLAIMER</a><a href="terms.php">TERMS OF SERVICE</a></div>
</footer>

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
