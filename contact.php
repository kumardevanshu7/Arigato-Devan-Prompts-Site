<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="theme-color" content="#2F4156">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us &ndash; Arigato Devan Prompts</title>
    <meta name="description" content="Get in touch with Arigato Devan. We'd love to hear your feedback, suggestions, or answer any questions you have about our AI prompts.">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <?php include_once 'includes/theme_head.php'; ?>
    <link rel="stylesheet" href="css/info-pages.css?v=20260701">
    <?php include_once "gtag.php"; ?>
</head>
<body class="page-store page-info page-contact theme-nogoda">

<?php $nav_active = ''; include 'includes/site_nav.php'; ?>
<div class="nogoda-mesh" aria-hidden="true"></div>

<main class="info-page-main info-page-main--wide">
    <div class="info-page-hero">
        <p class="hero-label">Get in Touch</p>
        <h1>Contact <em>Me</em></h1>
        <p>Questions, collabs, or feedback — drop a message and we'll reply within 24 hours.</p>
    </div>

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
                <a href="about.php">About</a>
                <a href="faq.php">FAQ</a>
                <a href="feedback.php">Feedback</a>
                <a href="gallery.php">Gallery</a>
            </div>
        </div>

        <div class="contact-form-card">
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
</main>

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

<?php include 'footer.php'; ?>

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
