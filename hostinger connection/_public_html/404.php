<?php http_response_code(404); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Nahi Mila | Arigato Devan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&display=swap" rel="stylesheet">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <style>
        :root {
            --bg:      #fdfbf7;
            --ink:     #2d2a35;
            --purple:  #e6d7ff;
            --yellow:  #fff1b8;
            --pink:    #ffd6e7;
            --green:   #a7f3d0;
            --red:     #ff6b6b;
            --shadow:  4px 4px 0 #2d2a35;
            --shadow-h: 6px 6px 0 #2d2a35;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: "Outfit", sans-serif;
            background: var(--bg);
            color: var(--ink);
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* ── Floating Particles ── */
        .particle {
            position: fixed;
            pointer-events: none;
            user-select: none;
            animation: risePart linear infinite;
            opacity: 0;
        }
        @keyframes risePart {
            0%   { transform: translateY(110vh) rotate(0deg) scale(0.6); opacity: 0; }
            8%   { opacity: 0.75; }
            88%  { opacity: 0.75; }
            100% { transform: translateY(-15vh) rotate(540deg) scale(1.1); opacity: 0; }
        }

        /* ── Page Wrapper ── */
        .err-page {
            position: relative;
            z-index: 10;
            text-align: center;
            padding: 40px 24px 60px;
            max-width: 580px;
            width: 100%;
        }

        /* ── Floating Character ── */
        .err-char {
            font-size: 4.5rem;
            display: block;
            line-height: 1;
            margin-bottom: 4px;
            animation: charBob 2.6s ease-in-out infinite;
        }
        @keyframes charBob {
            0%, 100% { transform: translateY(0px) rotate(-6deg); }
            50%       { transform: translateY(-20px) rotate(6deg); }
        }

        /* ── Speech Bubble (arrow pointing DOWN toward character) ── */
        .bubble {
            position: relative;
            display: inline-block;
            background: var(--yellow);
            border: 3px solid var(--ink);
            border-radius: 20px;
            padding: 12px 22px;
            font-weight: 700;
            font-size: .95rem;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        .bubble::after {
            content: "";
            position: absolute;
            bottom: -18px;
            left: 50%;
            transform: translateX(-50%);
            border: 9px solid transparent;
            border-top-color: var(--ink);
        }
        .bubble::before {
            content: "";
            position: absolute;
            bottom: -13px;
            left: 50%;
            transform: translateX(-50%);
            border: 9px solid transparent;
            border-top-color: var(--yellow);
            z-index: 1;
        }

        /* ── Glitch 404 ── */
        .err-num {
            font-size: clamp(5.5rem, 22vw, 10.5rem);
            font-weight: 900;
            letter-spacing: -4px;
            line-height: 1;
            position: relative;
            display: inline-block;
            color: var(--ink);
            margin: 28px 0 16px;
        }
        .err-num::before,
        .err-num::after {
            content: "404";
            position: absolute;
            inset: 0;
            font-size: inherit;
            font-weight: inherit;
            letter-spacing: inherit;
            line-height: inherit;
        }
        .err-num::before {
            color: var(--purple);
            animation: glitchA 3.5s infinite;
            clip-path: polygon(0 0, 100% 0, 100% 42%, 0 42%);
        }
        .err-num::after {
            color: var(--pink);
            animation: glitchB 3.5s infinite;
            clip-path: polygon(0 57%, 100% 57%, 100% 100%, 0 100%);
        }
        @keyframes glitchA {
            0%,78%,100% { transform: translate(0); }
            80%          { transform: translate(-5px,-2px); }
            82%          { transform: translate(5px, 2px); }
            84%          { transform: translate(-3px, 0); }
            86%          { transform: translate(0); }
        }
        @keyframes glitchB {
            0%,78%,100% { transform: translate(0); }
            80%          { transform: translate(5px, 2px); }
            82%          { transform: translate(-5px,-2px); }
            84%          { transform: translate(3px, 0); }
            86%          { transform: translate(0); }
        }

        /* ── Error Badge ── */
        .err-badge {
            display: inline-block;
            background: var(--red);
            color: #fff;
            font-weight: 900;
            font-size: .7rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 5px 14px;
            border: 2px solid var(--ink);
            border-radius: 999px;
            box-shadow: 2px 2px 0 var(--ink);
            margin-bottom: 18px;
        }

        /* ── Title & Desc ── */
        .err-title {
            font-size: clamp(1.5rem, 5vw, 2.1rem);
            font-weight: 900;
            margin-bottom: 10px;
        }
        .err-desc {
            font-size: 1rem;
            font-weight: 600;
            opacity: .72;
            line-height: 1.65;
            margin-bottom: 34px;
        }

        /* ── Buttons ── */
        .err-btns {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .err-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 28px;
            border: 3px solid var(--ink);
            border-radius: 999px;
            font-family: "Outfit", sans-serif;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            color: var(--ink);
            box-shadow: var(--shadow);
            transition: transform .12s ease, box-shadow .12s ease;
            -webkit-tap-highlight-color: transparent;
        }
        .err-btn:hover {
            transform: translateY(-3px) rotate(-1deg);
            box-shadow: var(--shadow-h);
        }
        .err-btn:active {
            transform: translate(2px,2px);
            box-shadow: 1px 1px 0 var(--ink);
        }
        .err-btn-home     { background: var(--purple); }
        .err-btn-surprise { background: var(--yellow); }

        /* ── Decorative comic dots ── */
        .comic-dots {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background-image: radial-gradient(circle, rgba(45,42,53,.06) 1.5px, transparent 1.5px);
            background-size: 28px 28px;
        }

        /* ── Responsive ── */
        @media (max-width: 480px) {
            .err-page { padding: 30px 16px 50px; }
            .err-char  { font-size: 3.5rem; }
            .bubble    { font-size: .85rem; padding: 10px 16px; }
        }
    </style>
</head>
<body>

<div class="comic-dots"></div>

<div class="err-page">

    <!-- Speech bubble ABOVE character -->
    <div class="bubble">Yaar… yeh page toh ghoom gaya! 😅</div>

    <!-- Floating character -->
    <span class="err-char">😵‍💫</span>

    <!-- Glitch 404 -->
    <div class="err-num">404</div>

    <!-- Badge -->
    <div><span class="err-badge"><i class="fa-solid fa-triangle-exclamation"></i> &nbsp;Error 404 — Not Found</span></div>

    <!-- Title -->
    <h1 class="err-title">Oops! Page Nahi Mila!</h1>

    <!-- Desc -->
    <p class="err-desc">
        Lagta hai yeh page apna rasta bhool gaya…<br>
        Koi baat nahi — wapas chalte hain! 🚀
    </p>

    <!-- Buttons -->
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
    var emojis  = ["✨","💫","⭐","🌟","💜","🔥","🎉","🎯","💡","🎲","🌸","🦋"];
    var body    = document.body;
    var COUNT   = 16;

    for (var i = 0; i < COUNT; i++) {
        (function (idx) {
            var el          = document.createElement("span");
            el.className    = "particle";
            el.textContent  = emojis[Math.floor(Math.random() * emojis.length)];
            var dur         = 7 + Math.random() * 9;
            var delay       = -(Math.random() * dur);
            var left        = 2 + Math.random() * 96;
            var size        = 0.9 + Math.random() * 1.1;
            el.style.cssText =
                "left:" + left + "vw;" +
                "font-size:" + size + "rem;" +
                "animation-duration:" + dur + "s;" +
                "animation-delay:" + delay + "s;";
            body.appendChild(el);
        })(i);
    }
})();
</script>

</body>
</html>
