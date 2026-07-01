<?php
session_start();
require_once "db.php";
$curPage = basename($_SERVER["PHP_SELF"]);
$milestones = [
    [
        "file" => "progress01.jpg",
        "count" => "693",
        "label" => "The Beginning",
        "sub" => "Where it all started",
        "side" => "left",
        "size" => "sm",
    ],
    [
        "file" => "progress02.jpg",
        "count" => "1,000+",
        "label" => "First 1K!",
        "sub" => "First major milestone",
        "side" => "right",
        "size" => "sm",
    ],
    [
        "file" => "progress03.jpg",
        "count" => "1,500+",
        "label" => "Growing Strong",
        "sub" => "Momentum building",
        "side" => "left",
        "size" => "sm",
    ],
    [
        "file" => "progress04.jpg",
        "count" => "2,000+",
        "label" => "2K Family",
        "sub" => "The community grows",
        "side" => "right",
        "size" => "md",
    ],
    [
        "file" => "progress05.jpg",
        "count" => "3,000+",
        "label" => "3K & Climbing",
        "sub" => "Growth accelerating",
        "side" => "left",
        "size" => "md",
    ],
    [
        "file" => "progress06.jpg",
        "count" => "4,000+",
        "label" => "Almost 5K",
        "sub" => "Something big is coming...",
        "side" => "right",
        "size" => "md",
    ],
    [
        "file" => "progress07.jpg",
        "count" => "1M Views",
        "label" => "1 Million Views",
        "sub" => "The viral moment that changed everything",
        "side" => "center",
        "size" => "hero",
    ],
    [
        "file" => "progress08.jpg",
        "count" => "5,000+",
        "label" => "5K Unlocked",
        "sub" => "Post-viral surge",
        "side" => "left",
        "size" => "md",
    ],
    [
        "file" => "progress09.jpg",
        "count" => "6,000+",
        "label" => "6K Strong",
        "sub" => "Consistent growth",
        "side" => "right",
        "size" => "md",
    ],
    [
        "file" => "progress10.jpg",
        "count" => "7,000+",
        "label" => "7K Family",
        "sub" => "Growing every day",
        "side" => "left",
        "size" => "md",
    ],
    [
        "file" => "progress11.jpg",
        "count" => "8,000+",
        "label" => "8K & Rising",
        "sub" => "Nearly at the goal",
        "side" => "right",
        "size" => "md",
    ],
    [
        "file" => "progress12.jpg",
        "count" => "9,500+",
        "label" => "So Close...",
        "sub" => "The final stretch",
        "side" => "left",
        "size" => "lg",
    ],
    [
        "file" => "progress13.jpg",
        "count" => "10,000+",
        "label" => "10K Achieved",
        "sub" => "From 693 to 10K &mdash; The Journey Complete",
        "side" => "center",
        "size" => "finale",
    ],
];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#2F4156">
<title>Growth Journey — Arigato Devan Prompts</title>
<meta name="description" content="The story of growing from 693 followers to 10,000+ — a visual journey.">
<link rel="canonical" href="https://arigatodevan.com/progress.php">
<?php include_once 'includes/theme_head.php'; ?>
<link rel="stylesheet" href="css/progress-page.css">
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"name":"Home","item":"https://arigatodevan.com"},{"@type":"ListItem","position":2,"name":"Our Journey","item":"https://arigatodevan.com/progress.php"}]}
</script>
<?php include_once "gtag.php"; ?>
</head>
<body class="page-store theme-nogoda page-progress">

<?php $nav_active = 'progress'; include 'includes/site_nav.php'; ?>

<!-- HERO -->
<div class="progress-hero">
    <p class="hero-eyebrow"><i class="fa-solid fa-chart-line"></i> Growth Story</p>
    <h1>From 0 to<br><em>10,000+</em></h1>
    <p>A real, raw, emotional journey of building an Instagram community from scratch — one prompt at a time.</p>
    <div class="hero-stat">
        <span><i class="fa-brands fa-instagram" style="color:#dc2743;"></i> 13 Milestones</span>
        <span style="opacity:0.4;">·</span>
        <span><i class="fa-solid fa-eye" style="color:#7c3aed;"></i> 1M+ Views</span>
        <span style="opacity:0.4;">·</span>
        <span><i class="fa-solid fa-users" style="color:#f59e0b;"></i> 10K+ Family</span>
    </div>
</div>

<!-- TIMELINE -->
<div class="timeline-wrap">
    <div class="rope"></div>

    <!-- ORIGIN MARKER: Started from 0 -->
    <div class="origin-marker">
        <div style="display:inline-flex;flex-direction:column;align-items:center;gap:8px;">
            <div style="width:2px;height:32px;background:linear-gradient(to bottom,transparent,#a0784a);"></div>
            <div class="origin-badge">
                <i class="fa-solid fa-seedling" style="color:#7c3aed;"></i> Started from 0
            </div>
            <div style="width:2px;height:24px;background:linear-gradient(to bottom,#a0784a,transparent);"></div>
        </div>
    </div>

    <?php foreach ($milestones as $i => $m): ?>
    <div class="tl-item side-<?= $m["side"] ?> size-<?= $m[
     "size"
 ] ?>" data-animate>

        <?php if ($m["side"] !== "center"): ?>
        <div class="tl-dot"></div>
        <div class="tl-connector"></div>
        <?php endif; ?>

        <?php if ($m["size"] === "hero"): ?>
            <div class="size-hero">
                <div class="hero-badge"><i class="fa-solid fa-fire"></i> VIRAL MILESTONE &mdash; 1M VIEWS</div>
                <div class="polaroid" style="position:relative;">
                    <div class="pin"><svg width="24" height="36" viewBox="0 0 24 36"><circle cx="12" cy="8" r="7" fill="#dc2743" stroke="#fff" stroke-width="2"/><line x1="12" y1="15" x2="12" y2="36" stroke="#888" stroke-width="2"/></svg></div>
                    <img loading="lazy" src="progresspics/<?= $m[
                        "file"
                    ] ?>" alt="<?= htmlspecialchars(
    $m["label"],
) ?>" loading="lazy">
                    <div class="caption">
                        <span class="count" style="color:#7c3aed;font-size:1.4rem;"><?= $m[
                            "count"
                        ] ?></span>
                        <span class="label" style="font-size:0.9rem;color:#555;font-weight:700;"><?= $m[
                            "label"
                        ] ?></span>
                        <span class="label"><?= $m["sub"] ?></span>
                    </div>
                </div>
            </div>

        <?php elseif ($m["size"] === "finale"): ?>
            <div class="size-finale">
                <div class="finale-badge"><i class="fa-solid fa-trophy"></i> FINAL ACHIEVEMENT &mdash; 10K+ FAMILY</div>
                <div class="polaroid" style="position:relative;">
                    <div class="pin"><svg width="24" height="36" viewBox="0 0 24 36"><circle cx="12" cy="8" r="7" fill="#f59e0b" stroke="#fff" stroke-width="2"/><line x1="12" y1="15" x2="12" y2="36" stroke="#888" stroke-width="2"/></svg></div>
                    <img loading="lazy" src="progresspics/<?= $m[
                        "file"
                    ] ?>" alt="<?= htmlspecialchars(
    $m["label"],
) ?>" loading="lazy">
                    <div class="caption">
                        <span class="count" style="color:#f59e0b;font-size:1.6rem;"><?= $m[
                            "count"
                        ] ?></span>
                        <span class="label" style="font-size:0.95rem;color:#555;font-weight:800;"><?= $m[
                            "label"
                        ] ?></span>
                        <span class="label"><?= $m["sub"] ?></span>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="<?= "size-" . $m["size"] ?>">
                <div class="polaroid" style="position:relative;">
                    <div class="pin"><svg width="18" height="28" viewBox="0 0 18 28"><circle cx="9" cy="6" r="5" fill="#2d2a35" stroke="#fff" stroke-width="1.5"/><line x1="9" y1="11" x2="9" y2="28" stroke="#888" stroke-width="1.5"/></svg></div>
                    <img loading="lazy" src="progresspics/<?= $m[
                        "file"
                    ] ?>" alt="<?= htmlspecialchars(
    $m["label"],
) ?>" loading="lazy">
                    <div class="caption">
                        <span class="count"><?= $m["count"] ?></span>
                        <span class="label"><?= $m["label"] ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<div class="rope-end">
    <div class="rope-end-badge"><i class="fa-solid fa-flag-checkered"></i> Journey Continues... Stay Tuned</div>
</div>

<?php include_once 'footer.php'; ?>

<script>
// Intersection Observer for scroll animations
const items = document.querySelectorAll('[data-animate]');
const obs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if(entry.isIntersecting) {
            entry.target.classList.add('visible');
            obs.unobserve(entry.target);
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
items.forEach(el => obs.observe(el));
</script>
</body>
</html>
