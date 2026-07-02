<?php
session_start();
require_once "db.php";

$stmt = $pdo->query("
    SELECT id, title, image_path, unlock_code
    FROM prompts
    WHERE prompt_type = 'secret'
      AND unlock_code IS NOT NULL
      AND TRIM(unlock_code) <> ''
      AND (is_trial = 0 OR is_trial IS NULL)
    ORDER BY created_at DESC, id DESC
");
$code_prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2F4156">
    <title>All Secret Codes &mdash; Arigato Devan</title>
    <meta name="description" content="All secret prompt codes with reel posters. Copy code instantly and unlock prompts.">
    <link rel="canonical" href="https://arigatodevan.com/all_codes.php">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <?php include_once "includes/theme_head.php"; ?>
    <?php include_once "gtag.php"; ?>
    <style>
        body.page-all-codes{background:var(--pal-beige,#F5EFEB);color:var(--pal-navy,#2F4156);overflow-x:hidden}
        .codes-wrap{max-width:1280px;margin:0 auto;padding:32px 20px 72px}
        .codes-hero{text-align:center;margin-bottom:26px}
        .codes-hero h1{margin:0 0 8px;font-family:'Playfair Display',serif;font-size:clamp(1.6rem,4.5vw,2.4rem);font-weight:900;color:var(--pal-navy,#2F4156)}
        .codes-hero p{margin:0;color:var(--pal-teal,#567C8D);font-size:.92rem;font-weight:500}
        .codes-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:16px}
        .code-card{background:#fff;border:1px solid var(--pal-sky,#C8D9E6);border-radius:18px;overflow:hidden;box-shadow:0 8px 24px rgba(47,65,86,.08)}
        .code-card img{width:100%;aspect-ratio:9/16;object-fit:cover;display:block;background:#e8edf2}
        .code-body{padding:12px 12px 14px;display:flex;flex-direction:column;gap:10px}
        .code-title{margin:0;font-size:.82rem;line-height:1.35;font-weight:700;color:var(--pal-navy,#2F4156);min-height:2.2em}
        .code-row{display:flex;align-items:center;gap:8px}
        .code-pill{flex:1;border:1px dashed rgba(47,65,86,.35);border-radius:10px;padding:8px 10px;font-size:.84rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;background:#f9fbfc}
        .copy-code-btn{border:none;border-radius:10px;padding:9px 12px;font-size:.78rem;font-weight:800;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:6px;white-space:nowrap}
        .copy-code-btn{background:var(--nogoda-gradient-h);color:var(--pal-navy,#2F4156)}
        .codes-empty{text-align:center;padding:48px 20px;background:#fff;border-radius:18px;border:1px solid var(--pal-sky,#C8D9E6)}
        @media (max-width: 640px){
            .codes-wrap{padding:20px 12px 52px}
            .codes-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
            .code-body{padding:10px}
            .code-title{font-size:.76rem;min-height:2.1em}
            .code-pill{padding:7px 8px;font-size:.72rem}
            .copy-code-btn{padding:8px 9px;font-size:.7rem}
        }
    </style>
</head>
<body class="page-store theme-nogoda page-all-codes">
<?php $nav_active = "gallery"; include "includes/site_nav.php"; ?>
<div class="nogoda-mesh" aria-hidden="true"></div>
<main class="codes-wrap">
    <section class="codes-hero">
        <p class="hero-label" style="justify-content:center;">Secret Code Hub</p>
        <h1>All Secret Codes</h1>
        <p>Click copy and use the code directly while unlocking prompts.</p>
    </section>
    <?php if (empty($code_prompts)): ?>
        <div class="codes-empty">
            <h2 style="margin:0 0 8px;">No codes available yet</h2>
            <p style="margin:0;color:#567C8D;">New secret codes will appear here soon.</p>
        </div>
    <?php else: ?>
        <section class="codes-grid">
            <?php foreach ($code_prompts as $cp): ?>
                <article class="code-card" id="code-<?= (int)$cp["id"] ?>">
                    <img src="<?= htmlspecialchars($cp["image_path"] ?: "assets/img/placeholder.jpg") ?>" alt="<?= htmlspecialchars($cp["title"] ?: "Reel poster") ?>" loading="lazy">
                    <div class="code-body">
                        <h3 class="code-title"><?= htmlspecialchars($cp["title"] ?: "Secret Prompt") ?></h3>
                        <div class="code-row">
                            <div class="code-pill"><?= htmlspecialchars($cp["unlock_code"]) ?></div>
                            <button type="button" class="copy-code-btn" data-code="<?= htmlspecialchars($cp["unlock_code"]) ?>">
                                <i class="fa-regular fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<script>
document.querySelectorAll(".copy-code-btn").forEach(function(btn){
    btn.addEventListener("click", function(){
        var code = btn.getAttribute("data-code") || "";
        if (!code) return;
        navigator.clipboard.writeText(code).then(function(){
            var old = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied';
            setTimeout(function(){ btn.innerHTML = old; }, 1400);
        }).catch(function(){});
    });
});
</script>
</body>
</html>
