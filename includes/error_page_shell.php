<?php
/**
 * Shared error page shell — set before include:
 * $err_code, $err_title, $err_badge, $err_heading, $err_desc, $err_bubble
 */
$err_code    = $err_code ?? '404';
$err_title   = $err_title ?? 'Page Not Found';
$err_badge   = $err_badge ?? 'Error 404 — Not Found';
$err_heading = $err_heading ?? 'Oops! Page Nahi Mila!';
$err_desc    = $err_desc ?? 'Lagta hai yeh page apna rasta bhool gaya...<br>Koi baat nahi — wapas chalte hain!';
$err_bubble  = $err_bubble ?? 'Yaar... yeh page toh ghoom gaya!';
$err_badge_class = ($err_code === '500') ? ' err-badge--500' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#F5EFEB">
    <title><?= (int) $err_code ?> — <?= htmlspecialchars($err_title) ?> | Arigato Devan</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <?php include_once __DIR__ . '/theme_head.php'; ?>
    <link rel="stylesheet" href="css/error-pages.css?v=20260760">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="page-store theme-nogoda page-error">

<div class="err-aurora" aria-hidden="true"></div>

<div class="err-page">
    <div class="err-bubble">
        <?= htmlspecialchars($err_bubble) ?>
        <i class="fa-solid fa-face-dizzy" aria-hidden="true"></i>
    </div>

    <span class="err-char" aria-hidden="true"><i class="fa-solid fa-robot"></i></span>

    <div class="err-num" data-code="<?= htmlspecialchars($err_code) ?>"><?= htmlspecialchars($err_code) ?></div>

    <div><span class="err-badge<?= $err_badge_class ?>"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($err_badge) ?></span></div>

    <h1 class="err-title"><?= htmlspecialchars($err_heading) ?></h1>

    <p class="err-desc"><?= $err_desc ?></p>

    <div class="err-btns">
        <a href="index.php" class="err-btn err-btn-home">
            <i class="fa-solid fa-house"></i> Go Home
        </a>
        <a href="surprise_me.php" class="err-btn err-btn-surprise">
            <i class="fa-solid fa-dice"></i> Surprise Me
        </a>
    </div>
</div>

<script>
(function () {
    var icons = ['fa-star', 'fa-heart', 'fa-fire', 'fa-bolt', 'fa-rocket', 'fa-dice', 'fa-wand-magic-sparkles', 'fa-sparkles'];
    var colors = ['#F5709D', '#11FFC9', '#2FA6C6', '#6D2D52', '#567C8D', '#C8D9E6'];
    for (var i = 0; i < 14; i++) {
        var el = document.createElement('span');
        el.className = 'err-particle';
        el.innerHTML = '<i class="fa-solid ' + icons[Math.floor(Math.random() * icons.length)] + '"></i>';
        var dur = 8 + Math.random() * 8;
        el.style.cssText =
            'left:' + (4 + Math.random() * 92) + 'vw;' +
            'font-size:' + (0.75 + Math.random() * 0.9) + 'rem;' +
            'color:' + colors[Math.floor(Math.random() * colors.length)] + ';' +
            'animation-duration:' + dur + 's;' +
            'animation-delay:' + (-(Math.random() * dur)) + 's;';
        document.body.appendChild(el);
    }
})();
</script>
</body>
</html>
