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
    <link rel="stylesheet" href="css/info-pages.css?v=20260703">
    <?php include_once "gtag.php"; ?>
</head>
<body class="page-store page-info page-contact theme-nogoda">

<?php $nav_active = ''; include 'includes/site_nav.php'; ?>
<div class="nogoda-mesh" aria-hidden="true"></div>

<main class="info-page-main info-page-main--wide">
    <div class="info-page-hero contact-hero">
        <p class="hero-label">Get in Touch</p>
        <h1>Contact <em>Me</em></h1>
        <p>Questions, collabs, or feedback — drop a message and we'll reply within 24 hours.</p>
    </div>

    <div class="contact-chips">
        <a href="about.php">About</a>
        <a href="faq.php">FAQ</a>
        <a href="feedback.php">Feedback</a>
        <a href="gallery.php">Gallery</a>
    </div>

    <div class="contact-layout">
        <div class="contact-info-grid">
            <a href="mailto:devansh.grow@gmail.com" class="contact-info-card">
                <span class="ci-card-icon ci-card-icon--email"><i class="fa-solid fa-envelope"></i></span>
                <span class="ci-card-label">Email</span>
                <span class="ci-card-value">devansh.grow@gmail.com</span>
            </a>
            <a href="https://instagram.com/arigato.devan" target="_blank" rel="noopener" class="contact-info-card">
                <span class="ci-card-icon ci-card-icon--insta"><i class="fa-brands fa-instagram"></i></span>
                <span class="ci-card-label">Instagram</span>
                <span class="ci-card-value">@arigato.devan</span>
            </a>
            <div class="contact-info-card contact-info-card--static">
                <span class="ci-card-icon ci-card-icon--time"><i class="fa-solid fa-clock"></i></span>
                <span class="ci-card-label">Response Time</span>
                <span class="ci-card-value">Usually within 24 hours</span>
            </div>
        </div>

        <div class="contact-form-card">
            <h2 class="contact-form-title">Send a message</h2>
            <form id="contact-form" novalidate>
                <div class="cf-group">
                    <label for="cf-name">Name</label>
                    <input type="text" id="cf-name" name="name" placeholder="Your name" required maxlength="100">
                </div>
                <div class="cf-group">
                    <label for="cf-email">Email</label>
                    <input type="email" id="cf-email" name="email" placeholder="your@email.com" required maxlength="200">
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

<div id="sending-popup">
    <div class="sp-box">
        <div class="sp-spinner"></div>
        <div class="sp-title">Sending...</div>
        <div class="sp-sub">Please wait, your message is being sent.</div>
    </div>
</div>

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
