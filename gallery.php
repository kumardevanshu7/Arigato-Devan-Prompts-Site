<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once 'db.php';
require_once __DIR__ . '/includes/prompt_cards.php';
require_once 'includes/gallery_helpers.php';

if (isset($_SESSION["user_id"]) && empty($_SESSION["onboarding_complete"])) {
    header("Location: onboarding.php");
    exit();
}

$is_ajax_cards = isset($_GET['ajax']) && $_GET['ajax'] === 'cards';
$tag_filter    = trim(strtolower($_GET['tag'] ?? ''));
$per_page      = 20;
if ($is_ajax_cards) {
    $per_page = ($tag_filter && $tag_filter !== 'all') ? 120 : 20;
}
$page          = max(1, (int) ($_GET['page'] ?? 1));
$_page_canonical = 'https://arigatodevan.com/gallery.php' . (($tag_filter && $tag_filter !== 'all') ? '?tag=' . urlencode($tag_filter) : '');

$gal_data      = gallery_fetch_prompts($pdo, $_SESSION['user_id'] ?? null, [
    'page'     => $page,
    'per_page' => $per_page,
    'tag'      => $tag_filter,
]);
$prompts       = $gal_data['prompts'];
$total         = $gal_data['total'];
$total_pages   = $gal_data['total_pages'];
$tag_filter    = $gal_data['tag_filter'];

if ($is_ajax_cards) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok'    => true,
        'html'  => render_gallery_prompt_cards($prompts),
        'total' => $total,
        'tag'   => $tag_filter ?: 'all',
        'empty' => count($prompts) === 0,
    ]);
    exit;
}
// All unique tags for filter buttons (separate query, not paginated)
$all_tags_raw = $pdo->query("SELECT tag FROM prompts WHERE tag IS NOT NULL AND tag != '' AND (is_trial = 0 OR is_trial IS NULL)")->fetchAll(PDO::FETCH_COLUMN);
$all_tags = [];
foreach ($all_tags_raw as $ts) { foreach (explode(',', strtolower($ts)) as $t) { $t = trim($t); if ($t) $all_tags[] = $t; } }
$tag_counts = [];
foreach ($all_tags_raw as $ts) { foreach (explode(',', strtolower($ts)) as $t) { $t = trim($t); if ($t) $tag_counts[$t] = ($tag_counts[$t] ?? 0) + 1; } }
$all_tags = array_unique($all_tags); sort($all_tags);

$trending_prompts = fetch_trending_prompts($pdo, $_SESSION['user_id'] ?? null);
$gal_banner_slides = gallery_banner_slides();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="theme-color" content="#2F4156">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery &mdash; Arigato Devan Prompts</title>
    <meta name="description" content="Browse all AI couple prompts in one place. Save, unlock &amp; share your favourites &mdash; only on Arigato Devan!">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Gallery &mdash; All AI Couple Prompts | Arigato Devan">
    <meta property="og:description" content="Browse all AI couple prompts in one place. Save, unlock &amp; share your favourites &mdash; only on Arigato Devan!">
    <meta property="og:image" content="https://arigatodevan.com/landingpics/lan9.webp">
    <meta property="og:url" content="https://arigatodevan.com/gallery.php">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">

    <?php include_once 'includes/theme_head.php'; ?>
    <link rel="stylesheet" href="css/gallery-extras.css?v=20260794">
    <?php include_once 'includes/card_skeleton_assets.php'; ?>

    <!-- Breadcrumb Schema -->
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"name":"Home","item":"https://arigatodevan.com"},{"@type":"ListItem","position":2,"name":"Gallery","item":"https://arigatodevan.com/gallery.php"}]}
    </script>
    <?php include_once "gtag.php"; ?>

</head>
<body class="page-store page-gallery theme-nogoda">

<?php $nav_active = 'gallery'; include 'includes/site_nav.php'; ?>
<div class="nogoda-mesh" aria-hidden="true"></div>

<?php include 'includes/gallery_banner.php'; ?>
<?php render_trending_row($trending_prompts); ?>

<main class="page-main">

    <?php if (count($prompts) === 0): ?>
        <div class="gal-browse-panel">
            <div class="gal-search-wrap">
                <i class="fa-solid fa-magnifying-glass gal-search-icon"></i>
                <input type="text" id="gallery-search" placeholder="Search prompts by name or tag..." autocomplete="off">
                <button type="button" class="gal-search-clear" id="search-clear-btn" aria-label="Clear search" onclick="clearGallerySearch()"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                <div class="gal-autocomplete" id="search-autocomplete"></div>
            </div>
        </div>
        <p style="text-align:center;font-weight:500;font-size:1.1rem;margin-top:40px;color:var(--text-muted);">No prompts yet. Check back soon!</p>
    <?php else: ?>

    <div class="gal-browse-panel">
        <!-- Search Bar -->
        <div class="gal-search-wrap">
            <i class="fa-solid fa-magnifying-glass gal-search-icon"></i>
            <input type="text" id="gallery-search" placeholder="Search prompts by name or tag..." autocomplete="off">
            <button type="button" class="gal-search-clear" id="search-clear-btn" aria-label="Clear search" onclick="clearGallerySearch()"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
            <div class="gal-autocomplete" id="search-autocomplete"></div>
        </div>

        <!-- Tag Controls Row -->
        <div class="gal-tag-bar">
            <span class="gal-tag-label"><i class="fa-solid fa-tag" style="margin-right:4px;font-size:0.7rem;"></i> Filter by Tag</span>
            <div class="gal-tag-ctrl">
                <button class="gal-sort-btn active" data-sort="az">A &rarr; Z</button>
                <button class="gal-sort-btn" data-sort="za">Z &rarr; A</button>
                <button class="gal-sort-btn" data-sort="pop">Popular</button>
                <button class="gal-toggle-btn" id="tag-toggle-btn">
                    <i class="fa-solid fa-chevron-up" id="tag-toggle-icon"></i>
                    <span id="tag-toggle-label">Hide</span>
                </button>
            </div>
        </div>

        <!-- Tag Pills -->
        <div class="gal-tag-wrap" id="tag-filter-container">
            <div class="gal-tag-inner" id="tag-scroll-inner">
                <button type="button"
                   class="gallery-tag-btn tag-filter-btn <?= !$tag_filter || $tag_filter === 'all' ? 'active' : '' ?>"
                   data-tag="all"
                   data-label="All" data-count="9999">All</button>
                <?php
                $badge_colors = ['#c084fc','#f43f5e','#fb923c','#22c55e','#0ea5e9','#f59e0b','#8b5cf6','#ec4899','#14b8a6','#ef4444'];
                $ci = 0;
                foreach ($all_tags as $t):
                    $bc = $badge_colors[$ci % count($badge_colors)]; $ci++;
                ?>
                    <button type="button"
                       class="gallery-tag-btn tag-filter-btn <?= $tag_filter === $t ? 'active' : '' ?>"
                       data-tag="<?= htmlspecialchars($t) ?>"
                       data-label="<?= htmlspecialchars(ucfirst($t)) ?>"
                       data-count="<?= $tag_counts[$t] ?? 0 ?>">
                        <?= htmlspecialchars(ucfirst($t)) ?>
                        <span class="gal-tag-count" style="background:<?= $bc ?>"><?= $tag_counts[$t] ?? 0 ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

        <div class="gal-all-prompts-section">
        <div class="gal-all-prompts-head">
            <div class="gal-all-prompts-titles">
                <p class="hero-label">Curated Prompt Collection</p>
                <h2>All Prompts <em>Gallery</em></h2>
            </div>
            <div class="gal-all-prompts-stat">
                <span class="page-hero-num" id="gallery-count-badge"><?= $total ?></span>
                <span class="page-hero-label">prompts</span>
            </div>
        </div>
        </div>

        <!-- Card Grid -->
        <div class="prompt-grid" id="card-stack">
        <?= render_gallery_prompt_cards($prompts) ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="gal-pagination">
            <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="gal-page-btn">&larr; Prev</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="gal-page-btn <?= $i === $page ? 'cur' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="gal-page-btn">Next &rarr;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="gal-no-results" id="gallery-no-results">
            <div class="emoji">&#128269;</div>
            <h3>No prompts found</h3>
            <p>Try a different keyword or clear the search</p>
        </div>

    <?php endif; ?>

</main>

<?php include 'footer.php'; ?>

<!-- ============================================================
     UNLOCK MODAL
     ============================================================ -->
<div id="unlock-modal" class="modal-overlay" hidden>
    <div class="modal-content split-view">
        <button type="button" class="close-modal" aria-label="Close dialog">&times;</button>
        <div class="modal-left">
            <img loading="lazy" src="" id="modal-image" alt="Prompt Preview">
        </div>
        <div class="modal-right">
            <h2 id="modal-title">Prompt Locked</h2>
            <div class="want-code-section" id="modal-want-code" style="display:none;">
                <p class="want-code-text">Need Secret Code?</p>
                <a href="all_codes.php" id="modal-reel-link" class="comic-btn-small">
                    <i class="fa-solid fa-code"></i> All Codes Here - Click to Know
                </a>
            </div>
            <div class="modal-unlock-area" id="modal-unlock-area">
                <p>Enter the secret code to reveal this prompt.</p>
                <input type="text" id="unlock-code-input" placeholder="6-LETTER CODE" maxlength="6">
                <button id="submit-code"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate Prompt</button>
            </div>
            <div class="modal-unlocked-area" id="modal-unlocked-area" style="display:none;flex-direction:column;text-align:left;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;flex-wrap:wrap;">
                    <h3 style="color:var(--text-primary);font-size:0.9rem;margin:0;font-family:'Playfair Display',serif;font-weight:700;">
                        <i class="fa-solid fa-scroll"></i> The Prompt:
                    </h3>
                    <div id="modal-bwi-badge"></div>
                </div>
                <div class="unlocked-text" id="modal-unlocked-text"></div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button class="copy-btn" id="modal-copy-btn" style="flex:1;min-width:120px;"><i class="fa-solid fa-copy"></i> Copy</button>
                    <button class="save-prompt-btn" id="modal-save-btn" data-prompt-id="" style="flex:1;min-width:120px;"><i class="fa-solid fa-bookmark"></i> Save</button>
                </div>
                <?php if (isset($_SESSION["user_id"])): ?>
                <button class="modal-like-btn" id="modal-like-btn" data-prompt-id="">
                    <i class="fa-solid fa-heart"></i> <span id="modal-like-count">0</span>
                </button>
                <?php else: ?>
                <button class="modal-like-btn" id="modal-like-btn" data-prompt-id="" data-guest="true">
                    <i class="fa-solid fa-heart"></i> <span id="modal-like-count">0</span>
                </button>
                <?php endif; ?>
            </div>
            <div id="modal-assets-area" style="display:none;margin-top:16px;border-top:1px solid var(--border);padding-top:14px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;font-weight:700;font-size:.88rem;color:var(--text-primary);font-family:'Inter',sans-serif;">
                    <i class="fa-solid fa-paperclip"></i> <span id="modal-asset-title">Assets</span>
                </div>
                <div id="modal-asset-images" style="display:flex;gap:10px;flex-wrap:wrap;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Login-to-Save Popup -->
<div id="login-save-popup" style="display:none;position:fixed;inset:0;background:rgba(17,17,17,.55);backdrop-filter:blur(12px);z-index:3000;align-items:center;justify-content:center;">
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:24px;padding:36px 32px;max-width:380px;width:90%;box-shadow:0 40px 100px rgba(0,0,0,0.18);text-align:center;">
        <div style="font-size:2.2rem;margin-bottom:12px;color:var(--text-primary);"><i class="fa-solid fa-lock"></i></div>
        <h3 style="font-family:'Playfair Display',serif;font-size:1.3rem;font-weight:700;margin-bottom:10px;letter-spacing:-0.02em;">Login Required</h3>
        <p style="font-weight:400;color:var(--text-secondary);margin-bottom:24px;font-size:0.9rem;">Login is required to save your prompt.</p>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <button onclick="document.getElementById('login-save-popup').style.display='none'" style="flex:1;padding:12px;background:var(--bg-hover);border:1.5px solid var(--border);border-radius:12px;font-family:'Inter',sans-serif;font-weight:600;font-size:0.9rem;cursor:pointer;color:var(--text-primary);">Cancel</button>
            <a id="login-save-url" href="login.php" style="flex:1;padding:12px;background:var(--btn-bg);border:1.5px solid var(--btn-bg);border-radius:12px;font-weight:600;font-size:0.9rem;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;color:#fff;font-family:'Inter',sans-serif;gap:8px;">
                <i class="fa-brands fa-google"></i> Sign in
            </a>
        </div>
    </div>
</div>

<!-- Wrong Code Popup -->
<div id="wrong-code-popup">
    <div class="wrong-code-card">
        <span class="wrong-code-emoji"><i class="fa-solid fa-xmark"></i></span>
        <div class="wrong-code-title">No No Bacha...</div>
        <div class="wrong-code-msg">It's the wrong code <i class="fa-solid fa-face-sad-cry"></i><br>Watch the reel to get the correct one!</div>
        <button class="wrong-code-close" onclick="document.getElementById('wrong-code-popup').classList.remove('show')">Try Again <i class="fa-solid fa-rotate"></i></button>
    </div>
</div>

<script>const isLoggedIn = <?= isset($_SESSION["user_id"]) ? "true" : "false" ?>;</script>

<!-- Tag Sort + Toggle JS -->
<script>
(function(){
  var container  = document.getElementById('tag-filter-container');
  var inner      = document.getElementById('tag-scroll-inner');
  var toggleBtn  = document.getElementById('tag-toggle-btn');
  var toggleIcon = document.getElementById('tag-toggle-icon');
  var toggleLabel= document.getElementById('tag-toggle-label');
  var sortBtns   = document.querySelectorAll('.gal-sort-btn');
  if (!container || !toggleBtn || !inner) return;

  function getTags() {
    return Array.from(inner.querySelectorAll('.tag-filter-btn:not([data-label="All"])'));
  }
  function sortTags(type) {
    var tags = getTags();
    tags.sort(function(a, b) {
      if (type === 'az') return a.dataset.label.localeCompare(b.dataset.label);
      if (type === 'za') return b.dataset.label.localeCompare(a.dataset.label);
      if (type === 'pop') return (parseInt(b.dataset.count)||0) - (parseInt(a.dataset.count)||0);
      return 0;
    });
    tags.forEach(function(t){ inner.appendChild(t); });
    sortBtns.forEach(function(b){ b.classList.toggle('active', b.dataset.sort === type); });
    try { localStorage.setItem('tagbar_sort', type); } catch(e){}
  }
  function applyHide(hidden) {
    container.classList.toggle('tags-hidden', hidden);
    toggleIcon.className = hidden ? 'fa-solid fa-chevron-down' : 'fa-solid fa-chevron-up';
    toggleLabel.textContent = hidden ? 'Show' : 'Hide';
    try { localStorage.setItem('tagbar_hidden', hidden ? '1' : '0'); } catch(e){}
  }
  var savedSort = 'az';
  try { savedSort = localStorage.getItem('tagbar_sort') || 'az'; } catch(e){}
  sortTags(savedSort);
  var wasHidden = false;
  try { wasHidden = localStorage.getItem('tagbar_hidden') === '1'; } catch(e){}
  if (wasHidden) applyHide(true);
  sortBtns.forEach(function(btn){
    btn.addEventListener('click', function(){ sortTags(btn.dataset.sort); });
  });
  toggleBtn.addEventListener('click', function(){
    applyHide(!container.classList.contains('tags-hidden'));
  });
})();
</script>

<script defer src="script.js?v=20260702"></script>
<script src="js/gallery-tag-filter.js?v=20260793" defer></script>

<script>
function promptPageUrl(card) {
    if (card.dataset.slug) {
        return 'prompt.php?slug=' + encodeURIComponent(card.dataset.slug);
    }
    return 'prompt.php?id=' + card.dataset.id;
}

(function() {
    var inp       = document.getElementById('gallery-search');
    var clearBtn  = document.getElementById('search-clear-btn');
    var countBadge= document.getElementById('gallery-count-badge');
    var noResults = document.getElementById('gallery-no-results');
    var ac        = document.getElementById('search-autocomplete');
    if (!inp) return;

    var cards = Array.from(document.querySelectorAll('.prompt-grid .prompt-card'));
    var activeIdx = -1, acMatches = [];

    window.gallerySearchRefresh = function () {
        cards = window.galleryGetCards ? window.galleryGetCards() : cards;
        filterGallery(inp.value.trim().toLowerCase());
    };

    function escHTML(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    var typeLabels = {secret_code:'SECRET',unreleased:'UNRELEASED',insta_viral:'VIRAL',already_uploaded:'UPLOADED',direct:'DIRECT'};
    var typeColors = {secret_code:'#111111',unreleased:'#f97316',insta_viral:'#e11d48',already_uploaded:'#16a34a',direct:'#567C8D'};

    function filterGallery(q) {
        var visible = 0;
        cards.forEach(function(card) {
            var match = !q || (card.dataset.title||'').toLowerCase().includes(q) || (card.dataset.tags||'').toLowerCase().includes(q);
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        if (countBadge) countBadge.textContent = visible;
        if (noResults)  noResults.classList.toggle('show', visible === 0 && q.length > 0);
        if (clearBtn) clearBtn.classList.toggle('visible', q.length > 0);
    }
    function buildAC(q) {
        if (!q) { hideAC(); return; }
        acMatches = cards.filter(function(c){ return (c.dataset.title||'').toLowerCase().includes(q)||(c.dataset.tags||'').toLowerCase().includes(q); }).slice(0,7);
        if (!acMatches.length) { hideAC(); return; }
        ac.innerHTML = '<div class="ac-header"><i class="fa-solid fa-magnifying-glass" style="margin-right:5px;"></i>Suggestions</div>';
        acMatches.forEach(function(card, i) {
            var type=card.dataset.promptType||'secret_code';
            var tagList=(card.dataset.tags||'').split(',').slice(0,3).map(function(t){return t.trim();}).filter(Boolean).join(' · ');
            var img=card.dataset.image||'';
            var thumb=img?'<div class="ac-thumb"><img src="'+escHTML(img)+'" alt="" loading="lazy" decoding="async"></div>':'';
            var item=document.createElement('div'); item.className='ac-item';
            item.innerHTML=thumb+
                '<div class="ac-badge" style="background:'+typeColors[type]+'">'+(typeLabels[type]||'PROMPT')+'</div>'+
                '<div class="ac-info"><div class="ac-title">'+escHTML(card.dataset.title||'')+'</div>'+(tagList?'<div class="ac-tags">'+escHTML(tagList)+'</div>':'')+
                '</div><i class="fa-solid fa-arrow-right ac-arrow"></i>';
            item.addEventListener('mouseover',function(){setActive(i);});
            item.addEventListener('mousedown',function(e){e.preventDefault();pickSuggestion(acMatches[i]);});
            ac.appendChild(item);
        });
        activeIdx=-1; ac.style.display='block';
    }
    function setActive(i){ activeIdx=i; ac.querySelectorAll('.ac-item').forEach(function(el,idx){el.classList.toggle('active',idx===i);}); }
    function pickSuggestion(card){
        inp.value=card.dataset.title||''; hideAC(); filterGallery(inp.value.trim().toLowerCase());
        card.style.display='';
        setTimeout(function(){
            card.scrollIntoView({behavior:'smooth',block:'center'});
            card.style.outline='2px solid var(--accent-warm)'; card.style.outlineOffset='3px';
            setTimeout(function(){card.style.outline='';card.style.outlineOffset='';},1800);
        },80);
    }
    function hideAC(){ ac.style.display='none'; ac.innerHTML=''; activeIdx=-1; acMatches=[]; }
    inp.addEventListener('input',function(){ var q=inp.value.trim().toLowerCase(); filterGallery(q); buildAC(q); });
    inp.addEventListener('keydown',function(e){
        var items=ac.querySelectorAll('.ac-item');
        if(ac.style.display!=='block'||!items.length)return;
        if(e.key==='ArrowDown'){e.preventDefault();setActive(Math.min(activeIdx+1,items.length-1));}
        else if(e.key==='ArrowUp'){e.preventDefault();setActive(Math.max(activeIdx-1,-1));}
        else if(e.key==='Enter'&&activeIdx>=0){e.preventDefault();if(acMatches[activeIdx])pickSuggestion(acMatches[activeIdx]);}
        else if(e.key==='Escape'){hideAC();}
    });
    inp.addEventListener('focus',function(){ if(inp.value.trim().length>0)buildAC(inp.value.trim().toLowerCase()); });
    document.addEventListener('click',function(e){ if(!e.target.closest('.gal-search-wrap'))hideAC(); });
    window.clearGallerySearch=function(){ inp.value=''; filterGallery(''); hideAC(); inp.focus(); };
})();
</script>

<script>
(function() {
    var track = document.getElementById('gal-banner-track');
    if (!track) return;
    var slides = track.children.length;
    if (slides <= 1) return;
    var idx = 0, timer;
    function goTo(i) {
        idx = (i + slides) % slides;
        track.style.transform = 'translateX(-' + (idx * 100) + '%)';
        document.querySelectorAll('.gal-banner-dot').forEach(function(d, j) {
            d.classList.toggle('active', j === idx);
        });
    }
    function next() { goTo(idx + 1); }
    function prev() { goTo(idx - 1); }
    function startAuto() { timer = setInterval(next, 5500); }
    function stopAuto() { clearInterval(timer); }
    var prevBtn = document.getElementById('gal-banner-prev');
    var nextBtn = document.getElementById('gal-banner-next');
    var wrap = document.getElementById('gal-banner-wrap');
    if (prevBtn) prevBtn.addEventListener('click', function() { stopAuto(); prev(); startAuto(); });
    if (nextBtn) nextBtn.addEventListener('click', function() { stopAuto(); next(); startAuto(); });
    document.querySelectorAll('.gal-banner-dot').forEach(function(dot) {
        dot.addEventListener('click', function() {
            stopAuto();
            goTo(parseInt(dot.dataset.index, 10));
            startAuto();
        });
    });
    if (wrap) {
        wrap.addEventListener('mouseenter', stopAuto);
        wrap.addEventListener('mouseleave', startAuto);
    }
    startAuto();
})();

(function() {
    var row = document.getElementById('gal-trending-scroll');
    if (!row) return;
    var step = Math.min(320, row.clientWidth * 0.75);
    var prev = document.querySelector('.gal-trend-prev');
    var next = document.querySelector('.gal-trend-next');
    if (prev) prev.addEventListener('click', function() { row.scrollBy({ left: -step, behavior: 'smooth' }); });
    if (next) next.addEventListener('click', function() { row.scrollBy({ left: step, behavior: 'smooth' }); });
    document.querySelectorAll('.trending-card').forEach(function(card) {
        card.addEventListener('click', function() {
            var url = promptPageUrl(card);
            document.body.style.transition = 'opacity 0.15s ease';
            document.body.style.opacity = '0';
            setTimeout(function() { window.location.href = url; }, 150);
        });
    });
})();
</script>

</body>
</html>
