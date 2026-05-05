<?php
session_start();
require_once 'db.php';
$curPage = basename($_SERVER['PHP_SELF']);
$milestones = [
    ['file'=>'progress01.png','count'=>'693','label'=>'The Beginning','sub'=>'Where it all started','side'=>'left','size'=>'sm'],
    ['file'=>'progress02.png','count'=>'1,000+','label'=>'First 1K!','sub'=>'First major milestone','side'=>'right','size'=>'sm'],
    ['file'=>'progress03.png','count'=>'1,500+','label'=>'Growing Strong','sub'=>'Momentum building','side'=>'left','size'=>'sm'],
    ['file'=>'progress04.png','count'=>'2,000+','label'=>'2K Family','sub'=>'The community grows','side'=>'right','size'=>'md'],
    ['file'=>'progress05.png','count'=>'3,000+','label'=>'3K & Climbing','sub'=>'Growth accelerating','side'=>'left','size'=>'md'],
    ['file'=>'progress06.png','count'=>'4,000+','label'=>'Almost 5K','sub'=>'Something big is coming...','side'=>'right','size'=>'md'],
    ['file'=>'progress07.png','count'=>'1M Views','label'=>'1 Million Views ðŸš€','sub'=>'The viral moment that changed everything','side'=>'center','size'=>'hero'],
    ['file'=>'progress08.png','count'=>'5,000+','label'=>'5K Unlocked','sub'=>'Post-viral surge','side'=>'left','size'=>'md'],
    ['file'=>'progress09.png','count'=>'6,000+','label'=>'6K Strong','sub'=>'Consistent growth','side'=>'right','size'=>'md'],
    ['file'=>'progress10.png','count'=>'7,000+','label'=>'7K Family','sub'=>'Growing every day','side'=>'left','size'=>'md'],
    ['file'=>'progress11.png','count'=>'8,000+','label'=>'8K & Rising','sub'=>'Nearly at the goal','side'=>'right','size'=>'md'],
    ['file'=>'progress12.png','count'=>'9,500+','label'=>'So Close...','sub'=>'The final stretch','side'=>'left','size'=>'lg'],
    ['file'=>'progress13.png','count'=>'10,000+','label'=>'10K Achieved ðŸŽ‰','sub'=>'From 693 to 10K â€” The Journey Complete','side'=>'center','size'=>'finale'],
];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Growth Journey â€” PromptVerse</title>
<meta name="description" content="The story of growing from 693 followers to 10,000+ â€” a visual journey.">
<link rel="stylesheet" href="style.css?v=1777999999">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#faf6f0;font-family:'Outfit',sans-serif;overflow-x:hidden}
.pg-bg{position:fixed;inset:0;background:linear-gradient(135deg,#fdf6ec 0%,#f0e8ff 50%,#fce8f3 100%);z-index:-2}
.pg-bg::after{content:'';position:fixed;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%239C92AC' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");z-index:-1}

/* NAV override for progress page */
.progress-hero{text-align:center;padding:100px 20px 60px;position:relative;z-index:2}
.progress-hero h1{font-size:clamp(2rem,5vw,4rem);font-weight:900;line-height:1.1;margin-bottom:16px;background:linear-gradient(135deg,#2d2a35,#7c3aed,#2d2a35);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.progress-hero p{font-size:1.15rem;color:#666;font-weight:600;max-width:500px;margin:0 auto 24px}
.hero-stat{display:inline-flex;align-items:center;gap:10px;background:#fff;border:2.5px solid #2d2a35;border-radius:40px;padding:12px 28px;font-weight:900;font-size:1.1rem;box-shadow:4px 4px 0 #2d2a35}

/* ROPE TIMELINE */
.timeline-wrap{position:relative;max-width:900px;margin:0 auto;padding:0 20px 120px}
.rope{position:absolute;left:50%;transform:translateX(-50%);top:0;bottom:0;width:6px;background:linear-gradient(to bottom,#c9a87c 0%,#a0784a 30%,#c9a87c 60%,#a0784a 100%);border-radius:3px;box-shadow:2px 0 8px rgba(0,0,0,0.15),-1px 0 0 rgba(255,255,255,0.3);z-index:1}
.rope::before{content:'';position:absolute;top:0;left:50%;transform:translateX(-50%);width:20px;height:20px;background:#a0784a;border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,0.3)}

/* TIMELINE ITEMS */
.tl-item{display:flex;align-items:flex-start;margin-bottom:70px;position:relative;z-index:2}
.tl-item.side-left{flex-direction:row;justify-content:flex-start;padding-right:calc(50% + 40px)}
.tl-item.side-right{flex-direction:row-reverse;justify-content:flex-start;padding-left:calc(50% + 40px)}
.tl-item.side-center{justify-content:center;padding:0}

/* CONNECTOR DOT */
.tl-dot{position:absolute;left:50%;transform:translateX(-50%);width:18px;height:18px;background:#fff;border:3px solid #2d2a35;border-radius:50%;box-shadow:0 2px 6px rgba(0,0,0,0.2);z-index:3;top:30px}
.tl-item.side-center .tl-dot{display:none}

/* POLAROID CARDS */
.polaroid{background:#fff;padding:14px 14px 40px;box-shadow:4px 6px 20px rgba(0,0,0,0.18);border:1px solid #e0d8cc;display:inline-block;cursor:pointer;transition:transform 0.3s ease,box-shadow 0.3s ease}
.polaroid img{width:100%;height:auto;display:block;border:1px solid #f0ece6}
.polaroid .caption{margin-top:10px;text-align:center}
.polaroid .caption .count{font-size:1.1rem;font-weight:900;color:#2d2a35;display:block;line-height:1.2}
.polaroid .caption .label{font-size:0.78rem;color:#888;font-weight:600;display:block;margin-top:3px}

/* PIN */
.pin{position:absolute;top:-16px;left:50%;transform:translateX(-50%);z-index:10}
.pin svg{drop-shadow:0 2px 4px rgba(0,0,0,0.3)}

/* Rope connector line from dot to card */
.tl-connector{position:absolute;top:39px;height:2px;background:repeating-linear-gradient(90deg,#a0784a 0 6px,transparent 6px 12px);z-index:0}
.side-left .tl-connector{right:calc(50% + 9px);width:40px}
.side-right .tl-connector{left:calc(50% + 9px);width:40px}

/* SIZES â€” increased for visual impact */
.size-sm .polaroid{width:260px;transform:rotate(-2deg)}
.size-sm .polaroid:hover{transform:rotate(0deg) scale(1.05)}
.size-md .polaroid{width:300px;transform:rotate(1.5deg)}
.size-md .polaroid:hover{transform:rotate(0deg) scale(1.05)}
.size-lg .polaroid{width:330px;transform:rotate(-1.5deg)}
.size-lg .polaroid:hover{transform:rotate(0deg) scale(1.05)}
.side-right .polaroid{transform:rotate(-2.5deg)}
.side-right .size-md .polaroid{transform:rotate(1.8deg)}
.side-right .size-lg .polaroid{transform:rotate(2deg)}

/* HERO â€” 1M Views */
.size-hero{display:flex;flex-direction:column;align-items:center;width:100%}
.size-hero .polaroid{width:min(360px,90vw);transform:rotate(0deg);border:3px solid #7c3aed;box-shadow:0 0 40px rgba(124,58,237,0.4),0 0 0 6px rgba(124,58,237,0.1),4px 8px 24px rgba(0,0,0,0.2);animation:heroGlow 3s ease-in-out infinite}
.size-hero .polaroid:hover{transform:scale(1.03)}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#7c3aed,#ec4899);color:#fff;padding:8px 22px;border-radius:40px;font-weight:900;font-size:0.9rem;margin-bottom:16px;box-shadow:0 4px 16px rgba(124,58,237,0.4)}
@keyframes heroGlow{0%,100%{box-shadow:0 0 30px rgba(124,58,237,0.3),4px 8px 24px rgba(0,0,0,0.2)}50%{box-shadow:0 0 60px rgba(124,58,237,0.6),0 0 0 8px rgba(124,58,237,0.12),4px 8px 24px rgba(0,0,0,0.2)}}

/* FINALE â€” 10K */
.size-finale{display:flex;flex-direction:column;align-items:center;width:100%}
.size-finale .polaroid{width:min(400px,92vw);transform:rotate(0deg);border:3px solid #f59e0b;box-shadow:0 0 50px rgba(245,158,11,0.5),0 0 0 8px rgba(245,158,11,0.1),4px 10px 30px rgba(0,0,0,0.25);animation:finaleShine 4s ease-in-out infinite}
.size-finale .polaroid:hover{transform:scale(1.02)}
.finale-badge{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#f59e0b,#ef4444);color:#fff;padding:10px 28px;border-radius:40px;font-weight:900;font-size:1rem;margin-bottom:16px;box-shadow:0 4px 20px rgba(245,158,11,0.5)}
@keyframes finaleShine{0%,100%{box-shadow:0 0 40px rgba(245,158,11,0.4),4px 10px 30px rgba(0,0,0,0.2)}50%{box-shadow:0 0 80px rgba(245,158,11,0.7),0 0 0 12px rgba(245,158,11,0.1),4px 10px 30px rgba(0,0,0,0.2)}}

/* SCROLL ANIMATIONS */
.tl-item{opacity:0;transform:translateY(40px)}
.tl-item.side-left{transform:translateX(-50px) translateY(20px)}
.tl-item.side-right{transform:translateX(50px) translateY(20px)}
.tl-item.visible{opacity:1;transform:translate(0,0);transition:opacity 0.6s ease,transform 0.6s cubic-bezier(0.34,1.56,0.64,1)}

/* END MARKER */
.rope-end{text-align:center;padding:40px 20px;position:relative;z-index:2}
.rope-end-badge{display:inline-flex;align-items:center;gap:10px;background:#2d2a35;color:#fff;padding:14px 32px;border-radius:40px;font-weight:900;font-size:1rem;box-shadow:4px 4px 0 #7c3aed}

/* MOBILE */
@media(max-width:640px){
.tl-item.side-left,.tl-item.side-right{flex-direction:column;align-items:center;padding:0;justify-content:center}
.tl-connector{display:none}
.rope{left:20px;transform:none;width:4px}
.tl-dot{left:20px}
.tl-item.side-left,.tl-item.side-right,.tl-item.side-center{padding-left:50px;justify-content:flex-start}
.size-sm .polaroid,.size-md .polaroid,.size-lg .polaroid{width:240px}
}
</style>
</head>
<body>
<div class="pg-bg"></div>

<header>
    <div class="logo-area" onclick="window.location.href='index.php'" style="cursor:pointer">
        <div class="logo-flipper">
            <div class="logo-front"><img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEh9eBlF-H7pQKHB7MV3TrjiL8Fm6HS753UjgtMroNDpSfMt_dmrqGoqAq_Bkhq1iSg1Iuflg_k6GHKXcuNXFEh0EmM0DyKY0XelSyShPXkzDX2u74APxyrIuY62s4bxL2JGRRqUBu9y1C_3SwrvCnqEmkJjJWs2v95MOHRkkLeQ08w2U_xMZvykuxtZeYj-/s1260/DP.png" alt="Logo" id="profile-logo"></div>
            <div class="logo-back"><img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjPksk2I-7a-EOSKAOstvbTPvuQ1DT8-pUI70DyiKNKitbp1lSaZoRRIH1eLK79gIYRUgRa5uW_yqTWkz4vOeq1f3hpdH8kQ6a4DVLDKfy2KYXZB5wjF_nTQjrIvQKW4Db0kAZRepIZ3OYHAAYW-T7oPKjNS09hvHifH54IQJ_ZeZTu06XeCfQIT-nS2fCW/s690/67af64fe-c73c-426c-85db-ca1fccdc2978-modified.png" alt=""></div>
        </div>
        <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
    </div>
    <nav class="nav-links">
        <a href="index.php">HOME</a>
        <a href="gallery.php">GALLERY</a>
        <a href="blogs.php">BLOGS</a>
        <a href="progress.php" title="Growth Journey" style="padding:8px 10px;display:flex;align-items:center;"><i class="fa-solid fa-chart-line nav-progress-icon"></i></a>
        <div class="nav-dropdown">
            <button class="nav-dropdown-btn"><i class="fa-solid fa-film"></i> Reels Type <i class="fa-solid fa-chevron-down dd-arrow"></i></button>
            <div class="nav-dropdown-menu">
                <a href="secret_code.php" <?= $curPage=='secret_code.php'?'style="background:var(--primary-color)"':''?>><i class="fa-solid fa-lock"></i> Secret Code Reels <?= empty($nav_counts['secret_code'])?'<span class="dd-tag soon">SOON</span>':($curPage=='secret_code.php'?'<span class="dd-tag">ACTIVE</span>':'') ?></a>
                <a href="unreleased.php" <?= $curPage=='unreleased.php'?'style="background:var(--primary-color)"':''?>><i class="fa-solid fa-star"></i> Unreleased Reels <?= empty($nav_counts['unreleased'])?'<span class="dd-tag soon">SOON</span>':($curPage=='unreleased.php'?'<span class="dd-tag">ACTIVE</span>':'') ?></a>
                <a href="insta_viral.php" <?= $curPage=='insta_viral.php'?'style="background:var(--primary-color)"':''?>><i class="fa-brands fa-instagram"></i> Insta Viral Reels <?= empty($nav_counts['insta_viral'])?'<span class="dd-tag soon">SOON</span>':($curPage=='insta_viral.php'?'<span class="dd-tag">ACTIVE</span>':'') ?></a>
            </div>
        </div>
        <a href="https://www.instagram.com/arigato.devan/" target="_blank" style="display:flex;align-items:center;gap:8px;text-decoration:none;color:inherit;font-family:var(--font-main);">
            <i class="fa-brands fa-instagram" style="font-size:18px;"></i>
            <span style="font-weight:600;">@arigato.devan</span><span class="pulse-dot"></span><span style="font-weight:800;font-size:1.1rem;">11K+</span>
        </a>
    </nav>
    <div class="header-right">
        <div class="header-divider"></div>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="login.php?logout=1" class="logout"><i class="fa-solid fa-right-from-bracket"></i> LOGOUT</a>
        <?php else: ?>
            <a href="login.php" class="comic-btn" style="font-size:.85rem;padding:9px 18px;text-decoration:none;color:var(--text-color);background:var(--primary-color);">LOGIN</a>
        <?php endif; ?>
    </div>
</header>

<!-- HERO -->
<div class="progress-hero">
    <p style="font-size:0.9rem;font-weight:700;letter-spacing:3px;color:#7c3aed;text-transform:uppercase;margin-bottom:12px;"><i class="fa-solid fa-chart-line"></i> Growth Story</p>
    <h1>From 0 to<br>10,000+</h1>
    <p>A real, raw, emotional journey of building an Instagram community from scratch â€” one prompt at a time.</p>
    <div class="hero-stat"><i class="fa-brands fa-instagram" style="color:#dc2743;"></i> 13 Milestones &nbsp;Â·&nbsp; <i class="fa-solid fa-eye" style="color:#7c3aed;"></i> 1M+ Views &nbsp;Â·&nbsp; <i class="fa-solid fa-users" style="color:#f59e0b;"></i> 10K+ Family</div>
</div>

<!-- TIMELINE -->
<div class="timeline-wrap">
    <div class="rope"></div>

    <!-- ORIGIN MARKER: Started from 0 -->
    <div style="text-align:center;position:relative;z-index:3;margin-bottom:48px;padding-top:24px;">
        <div style="display:inline-flex;flex-direction:column;align-items:center;gap:8px;">
            <div style="width:2px;height:40px;background:linear-gradient(to bottom,transparent,#a0784a);margin:0 auto;"></div>
            <div style="background:#fff;border:2px dashed #a0784a;border-radius:40px;padding:10px 24px;font-size:0.82rem;font-weight:800;letter-spacing:2px;color:#a0784a;text-transform:uppercase;box-shadow:2px 2px 0 rgba(160,120,74,0.2);">
                <i class="fa-solid fa-seedling" style="margin-right:6px;color:#7c3aed;"></i>Started from 0
            </div>
            <div style="width:2px;height:32px;background:linear-gradient(to bottom,#a0784a,transparent);margin:0 auto;"></div>
        </div>
    </div>

    <?php foreach($milestones as $i => $m): ?>
    <div class="tl-item side-<?= $m['side'] ?> size-<?= $m['size'] ?>" data-animate>

        <?php if($m['side'] !== 'center'): ?>
        <div class="tl-dot"></div>
        <div class="tl-connector"></div>
        <?php endif; ?>

        <?php if($m['size'] === 'hero'): ?>
            <div class="size-hero">
                <div class="hero-badge"><i class="fa-solid fa-fire"></i> VIRAL MILESTONE â€” 1M VIEWS</div>
                <div class="polaroid" style="position:relative;">
                    <div class="pin"><svg width="24" height="36" viewBox="0 0 24 36"><circle cx="12" cy="8" r="7" fill="#dc2743" stroke="#fff" stroke-width="2"/><line x1="12" y1="15" x2="12" y2="36" stroke="#888" stroke-width="2"/></svg></div>
                    <img src="progresspics/<?= $m['file'] ?>" alt="<?= htmlspecialchars($m['label']) ?>" loading="lazy">
                    <div class="caption">
                        <span class="count" style="color:#7c3aed;font-size:1.4rem;"><?= $m['count'] ?></span>
                        <span class="label" style="font-size:0.9rem;color:#555;font-weight:700;"><?= $m['label'] ?></span>
                        <span class="label"><?= $m['sub'] ?></span>
                    </div>
                </div>
            </div>

        <?php elseif($m['size'] === 'finale'): ?>
            <div class="size-finale">
                <div class="finale-badge"><i class="fa-solid fa-trophy"></i> FINAL ACHIEVEMENT â€” 10K+ FAMILY</div>
                <div class="polaroid" style="position:relative;">
                    <div class="pin"><svg width="24" height="36" viewBox="0 0 24 36"><circle cx="12" cy="8" r="7" fill="#f59e0b" stroke="#fff" stroke-width="2"/><line x1="12" y1="15" x2="12" y2="36" stroke="#888" stroke-width="2"/></svg></div>
                    <img src="progresspics/<?= $m['file'] ?>" alt="<?= htmlspecialchars($m['label']) ?>" loading="lazy">
                    <div class="caption">
                        <span class="count" style="color:#f59e0b;font-size:1.6rem;"><?= $m['count'] ?></span>
                        <span class="label" style="font-size:0.95rem;color:#555;font-weight:800;"><?= $m['label'] ?></span>
                        <span class="label"><?= $m['sub'] ?></span>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="<?= 'size-'.$m['size'] ?>">
                <div class="polaroid" style="position:relative;">
                    <div class="pin"><svg width="18" height="28" viewBox="0 0 18 28"><circle cx="9" cy="6" r="5" fill="#2d2a35" stroke="#fff" stroke-width="1.5"/><line x1="9" y1="11" x2="9" y2="28" stroke="#888" stroke-width="1.5"/></svg></div>
                    <img src="progresspics/<?= $m['file'] ?>" alt="<?= htmlspecialchars($m['label']) ?>" loading="lazy">
                    <div class="caption">
                        <span class="count"><?= $m['count'] ?></span>
                        <span class="label"><?= $m['label'] ?></span>
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

<footer style="margin-top:60px;">
    <div>&copy; 2026 ARIGATO DEVAN. KEEP CREATING.</div>
    <div class="footer-links"><a href="disclaimer.php">DISCLAIMER</a><a href="terms.php">TERMS OF SERVICE</a></div>
</footer>

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
}, { threshold: 0.1, rootMargin: '0px 0px -60px 0px' });
items.forEach(el => obs.observe(el));

// Mobile tap zoom feedback
document.querySelectorAll('.polaroid').forEach(card => {
    card.addEventListener('touchstart', () => {
        card.style.transform = 'scale(0.97)';
        setTimeout(() => { card.style.transform = ''; }, 200);
    }, { passive: true });
});

// Nav dropdown
const ddBtn = document.querySelector('.nav-dropdown-btn');
const ddMenu = document.querySelector('.nav-dropdown-menu');
if(ddBtn && ddMenu) {
    ddBtn.addEventListener('click', e => { e.stopPropagation(); ddMenu.classList.toggle('open'); });
    document.addEventListener('click', () => ddMenu.classList.remove('open'));
}
</script>
</body>
</html>
