<?php
/**
 * surprise_me.php — Random prompt picker with loading screen.
 * Shows dice animation for 4s, then opens prompt.php directly.
 */
session_start();
require_once "db.php";

$redirect = "gallery.php";

try {
    $stmt = $pdo->query(
        "SELECT id, slug FROM prompts WHERE (is_trial = 0 OR is_trial IS NULL) ORDER BY RAND() LIMIT 1"
    );
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($p) {
        if (!empty($p["slug"])) {
            $redirect = "prompt.php?slug=" . rawurlencode($p["slug"]);
        } else {
            $redirect = "prompt.php?id=" . (int)$p["id"];
        }
    }
} catch (PDOException $e) {
    // fallback stays gallery.php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2F4156">
    <meta name="robots" content="noindex">
    <title>Surprise Me — Arigato Devan</title>
    <?php include_once "includes/theme_head.php"; ?>
    <link rel="stylesheet" href="css/surprise-loading.css?v=20260734">
    <link rel="prefetch" href="<?= htmlspecialchars($redirect) ?>">
    <?php include_once "gtag.php"; ?>
</head>
<body class="page-store theme-nogoda page-surprise-loading">

<div class="surprise-loading" role="status" aria-live="polite" aria-busy="true">
    <span class="surprise-loading-brand">Surprise Me</span>

    <div class="surprise-dice-stage" aria-hidden="true">
        <i class="fa-solid fa-dice-three surprise-die surprise-die--a" id="surprise-die-a"></i>
        <i class="fa-solid fa-dice-five surprise-die surprise-die--b" id="surprise-die-b"></i>
    </div>

    <h1 class="surprise-loading-title">Rolling the dice&hellip;</h1>
    <p class="surprise-loading-msg" id="surprise-msg">Let me find an interesting prompt for you&hellip;</p>

    <div class="surprise-loading-bar" aria-hidden="true">
        <span class="surprise-loading-bar-fill"></span>
    </div>
    <p class="surprise-loading-hint">Hang tight — almost there</p>
</div>

<script>
(function () {
    var REDIRECT = <?= json_encode($redirect, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    var DURATION = 2500;
    var messages = [
        'Let me find an interesting prompt for you\u2026',
        'Almost there \u2014 your next viral moment awaits!'
    ];
    var diceFaces = [
        'fa-dice-one', 'fa-dice-two', 'fa-dice-three',
        'fa-dice-four', 'fa-dice-five', 'fa-dice-six'
    ];

    var msgEl = document.getElementById('surprise-msg');
    var dieA = document.getElementById('surprise-die-a');
    var dieB = document.getElementById('surprise-die-b');
    var msgIdx = 0;

    function randomFace() {
        return diceFaces[Math.floor(Math.random() * diceFaces.length)];
    }

    function rollDice() {
        if (dieA) dieA.className = 'fa-solid ' + randomFace() + ' surprise-die surprise-die--a';
        if (dieB) dieB.className = 'fa-solid ' + randomFace() + ' surprise-die surprise-die--b';
    }

    function cycleMessage() {
        if (!msgEl) return;
        msgIdx = (msgIdx + 1) % messages.length;
        msgEl.classList.add('is-fading');
        setTimeout(function () {
            msgEl.textContent = messages[msgIdx];
            msgEl.classList.remove('is-fading');
        }, 180);
    }

    rollDice();
    var diceTimer = setInterval(rollDice, 140);
    var msgTimer = setInterval(cycleMessage, 1300);

    setTimeout(function () {
        clearInterval(diceTimer);
        clearInterval(msgTimer);
        window.location.replace(REDIRECT);
    }, DURATION);
})();
</script>
</body>
</html>
