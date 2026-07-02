<?php
session_start();
require_once "db.php";

$id   = (int)($_GET['id'] ?? 0);
$slug = trim($_GET['slug'] ?? '');
if ($id <= 0 && empty($slug)) { header("Location: gallery.php"); exit(); }

$where        = $slug ? "p.slug = ?"  : "p.id = ?";
$where_plain  = $slug ? "slug = ?"   : "id = ?";
$where_val    = $slug ? $slug         : $id;

if (isset($_SESSION["user_id"])) {
    $stmt = $pdo->prepare("
        SELECT p.*,
               IF(u.id IS NOT NULL, 1, 0) as is_unlocked,
               IF(l.id IS NOT NULL, 1, 0) as is_liked,
               IF(sv.id IS NOT NULL, 1, 0) as is_saved
        FROM prompts p
        LEFT JOIN unlocked_prompts u ON p.id = u.prompt_id AND u.user_id = ?
        LEFT JOIN likes l ON p.id = l.prompt_id AND l.user_id = ?
        LEFT JOIN saved_prompts sv ON p.id = sv.prompt_id AND sv.user_id = ?
        WHERE {$where}
    ");
    $stmt->execute([$_SESSION["user_id"], $_SESSION["user_id"], $_SESSION["user_id"], $where_val]);
} else {
    $stmt = $pdo->prepare("SELECT *, 0 as is_unlocked, 0 as is_liked, 0 as is_saved FROM prompts WHERE {$where_plain}");
    $stmt->execute([$where_val]);
}

$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) { header("Location: gallery.php"); exit(); }
$id = (int)$p['id'];

$db_type  = $p["prompt_type"] ?? "secret";
$ptype    = match($db_type) {
    "insta_viral"     => "insta_viral",
    "unreleased"      => "unreleased",
    "already_uploaded"=> "already_uploaded",
    "direct"          => "direct",
    default           => "secret_code"
};
$tinfo = [
    "secret_code"      => ["label" => "SECRET CODE",       "bg" => "#e6d7ff", "color" => "#4a00b0"],
    "unreleased"       => ["label" => "UNRELEASED",         "bg" => "#fff1b8", "color" => "#7a5c00"],
    "insta_viral"      => ["label" => "INSTA VIRAL",        "bg" => "#c8f5d4", "color" => "#1a5c30"],
    "already_uploaded" => ["label" => "ALREADY UPLOADED",   "bg" => "#e6f2ff", "color" => "#00509e"],
    "direct"           => ["label" => "DIRECT PROMPT",      "bg" => "#ffe4e6", "color" => "#be123c"],
][$ptype];

$rel_stmt = $pdo->prepare("SELECT id, slug, title, image_path FROM prompts WHERE prompt_type = ? AND id != ? AND is_trial = 0 ORDER BY RAND() LIMIT 4");
$rel_stmt->execute([$db_type, $id]);
$related = $rel_stmt->fetchAll(PDO::FETCH_ASSOC);

$is_unlocked  = (bool)$p["is_unlocked"];
// Track view
$pdo->prepare("UPDATE prompts SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);
$asset_images = json_decode($p['asset_images'] ?? '[]', true) ?: [];
$tags_arr          = array_filter(array_map('trim', explode(',', $p['tag'] ?? '')));
$extra_prompts_arr = json_decode($p['extra_prompts'] ?? '[]', true) ?: [];
$total_prompts     = 1 + count($extra_prompts_arr);
$og_img       = "https://arigatodevan.com/" . ltrim($p["image_path"] ?? "landingpics/lan9.webp", "/");
$page_title   = htmlspecialchars($p["title"]) . " ï¿½ AI Prompt | Arigato Devan";
$canonical    = !empty($p['slug']) ? "https://arigatodevan.com/prompts/" . $p['slug'] : "https://arigatodevan.com/prompt.php?id={$id}";
$tags_str     = !empty($tags_arr) ? implode(', ', array_slice($tags_arr, 0, 3)) : '';
$meta_desc    = !empty($p['description'])
              ? htmlspecialchars($p['description'])
              : htmlspecialchars($p['title']) . ' is a ' . $tinfo['label'] . ' AI couple prompt on Arigato Devan.'
                . (!empty($tags_str) ? ' Perfect for ' . $tags_str . '.' : '')
                . ' Copy and use instantly on ChatGPT or any AI tool.';
$type_page    = match($ptype) {
    'insta_viral'      => 'insta_viral.php',
    'unreleased'       => 'unreleased.php',
    'already_uploaded' => 'already_uploaded.php',
    'direct'           => 'gallery.php', // Assuming there's no direct.php list page yet
    default            => 'gallery.php',
};
$is_local = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="theme-color" content="#2F4156">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= ($_SERVER['HTTP_HOST'] === 'localhost') ? '/Arigato%20Development%20Site/' : '/' ?>">
    <title><?= $page_title ?></title>
    <meta name="description" content="<?= $meta_desc ?>">
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?= htmlspecialchars($p['title']) ?> ï¿½ Arigato Devan">
    <meta property="og:description" content="<?= $meta_desc ?>">
    <meta property="og:image" content="<?= $og_img ?>">
    <link rel="canonical" href="<?= $canonical ?>">
    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <meta property="og:url" content="<?= $canonical ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="<?= $og_img ?>">
    <script type="application/ld+json">
    <?= json_encode([
        '@context'  => 'https://schema.org',
        '@type'     => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',              'item' => 'https://arigatodevan.com'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $tinfo['label'],     'item' => 'https://arigatodevan.com/' . $type_page],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $p['title'],         'item' => $canonical],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <script type="application/ld+json">
    <?= json_encode([
        '@context'        => 'https://schema.org',
        '@type'           => 'CreativeWork',
        'name'            => $p['title'],
        'description'     => $meta_desc,
        'url'             => $canonical,
        'image'           => $og_img,
        'author'          => ['@type' => 'Organization', 'name' => 'Arigato Devan'],
        'publisher'       => ['@type' => 'Organization', 'name' => 'Arigato Devan', 'url' => 'https://arigatodevan.com'],
        'keywords'        => implode(', ', $tags_arr),
        'genre'           => $tinfo['label'],
        'datePublished'   => isset($p['created_at']) ? date('c', strtotime($p['created_at'])) : null,
        'inLanguage'      => 'en',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <?php include_once 'includes/theme_head.php'; ?>
    <?php include_once "gtag.php"; ?>
</head>
<body class="page-store theme-nogoda page-prompt">

<?php $nav_active = 'gallery'; include 'includes/site_nav.php'; ?>
<div class="nogoda-mesh" aria-hidden="true"></div>
    <div class="pp-wrap">
  
        <div class="pp-layout">
            <!-- Image Column -->
            <div class="pp-img-col <?= ($ptype === 'unreleased' && !$is_unlocked) ? 'blurred' : '' ?>" id="pp-img-col">
                <div class="pp-img-frame">
                    <img loading="lazy" src="<?= htmlspecialchars($p['image_path']) ?>" class="pp-prompt-img" id="pp-main-img" alt="<?= htmlspecialchars($p['title']) ?>">
                    <span class="pp-badge"><?= $tinfo['label'] ?></span>
                </div>
                <div class="pp-img-meta">
                    <div class="pp-like-mini">
                        <i class="fa-solid fa-heart"></i>
                        <span id="pp-like-count-mini"><?= (int)$p['likes_count'] ?></span> likes
                    </div>
                    <?php if (!empty($tags_arr)): ?>
                    <div class="pp-tags">
                        <?php foreach ($tags_arr as $t): ?>
                            <span class="pp-tag"><?= htmlspecialchars(ucfirst($t)) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info Column -->
            <div class="pp-info-col">
                <?php if ($total_prompts > 1): ?>
                <div class="pp-multi-badge"><i class="fa-solid fa-layer-group"></i> <?= $total_prompts ?> Prompts Inside!</div>
                <?php endif; ?>
                <h1 class="pp-title"><?= htmlspecialchars($p['title']) ?></h1>

                <!-- -- TASK SECTION (shown when locked) -- -->
                <?php if (!$is_unlocked): ?>
                <div id="pp-task" class="pp-task-card">
                    <?php if ($ptype === 'secret_code'): ?>
                        <div class="pp-task-icon"><i class="fa-solid fa-lock"></i></div>
                        <h3>Enter Secret Code</h3>
                        <p>All secret codes are listed in one place. Open the code hub and copy your 6-letter code.</p>
                        <a href="all_codes.php#code-<?= (int)$p['id'] ?>" class="pp-reel-btn">
                            <i class="fa-solid fa-code"></i> All Codes Here - Click to Know
                        </a>
                        <div class="pp-input-group">
                            <input type="text" id="pp-code-input" placeholder="6-LETTER CODE" maxlength="6" autocomplete="off" style="letter-spacing:.2em;">
                            <button id="pp-submit-code" class="pp-unlock-btn"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate Prompt</button>
                        </div>

                    <?php elseif ($ptype === 'unreleased'): ?>
                        <div class="pp-task-icon"><i class="fa-solid fa-heart"></i></div>
                        <h3>Show Some Love!</h3>
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <p>Tap the heart <strong>20 times</strong> to unlock this prompt.</p>
                        <?php else: ?>
                        <p>Tap the heart <strong>90 times</strong> to unlock ï¿½ or <a href="login.php" style="font-weight:900;color:var(--primary-dark);">login</a> for just 20 taps!</p>
                        <?php endif; ?>
                        <div class="pp-love-area">
                            <button id="pp-love-btn" class="pp-love-btn"><i class="fa-solid fa-heart"></i></button>
                            <div class="pp-progress-bar"><div class="pp-progress-fill" id="pp-progress-fill" style="width:0%"></div></div>
                            <div class="pp-love-progress"><span id="pp-tap-count">0</span> / <span id="pp-tap-total"><?= isset($_SESSION['user_id']) ? 20 : 90 ?></span></div>
                        </div>

                    <?php elseif ($ptype === 'insta_viral'): ?>
                        <div class="pp-task-icon"><i class="fa-solid fa-calculator"></i></div>
                        <h3>Quick Math Challenge</h3>
                        <p>Solve this to prove you're human and unlock the prompt!</p>
                        <div class="pp-math-q" id="pp-math-q">Loading...</div>
                        <div class="pp-input-group">
                            <input type="number" id="pp-math-input" placeholder="Your Answer" style="font-size:1.2rem;">
                            <button id="pp-submit-math" class="pp-unlock-btn"><i class="fa-solid fa-check"></i> Unlock Prompt</button>
                        </div>

                    <?php elseif ($ptype === 'already_uploaded'): ?>
                        <div class="pp-task-icon"><i class="fa-brands fa-instagram" style="background:linear-gradient(135deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"></i></div>
                        <h3>Already on Instagram!</h3>
                        <p>This prompt has been shared on our Instagram. Tap the heart <strong>9 times</strong> to unlock it!</p>
                        <div class="pp-love-area">
                            <button id="pp-love-btn-au" class="pp-love-btn"><i class="fa-solid fa-heart"></i></button>
                            <div class="pp-progress-bar"><div class="pp-progress-fill" id="pp-progress-fill-au" style="width:0%"></div></div>
                            <div class="pp-love-progress"><span id="pp-tap-count-au">0</span> / 9</div>
                        </div>

                    <?php elseif ($ptype === 'direct'): ?>
                        <?php $req_taps = (int)($p['unlock_code'] ?: 9); ?>
                        <div class="pp-task-icon"><i class="fa-solid fa-hand-pointer" style="color:#f43f5e"></i></div>
                        <h3>Direct Unlock!</h3>
                        <p>Tap the heart <strong><?= $req_taps ?> times</strong> to unlock this prompt!</p>
                        <div class="pp-love-area">
                            <button id="pp-love-btn-dir" class="pp-love-btn" style="color:#f43f5e;border-color:#f43f5e"><i class="fa-solid fa-heart"></i></button>
                            <div class="pp-progress-bar"><div class="pp-progress-fill" id="pp-progress-fill-dir" style="width:0%;background:#f43f5e"></div></div>
                            <div class="pp-love-progress"><span id="pp-tap-count-dir">0</span> / <?= $req_taps ?></div>
                            <script>const DIR_REQ_TAPS = <?= $req_taps ?>;</script>
                        </div>
                    <?php endif; ?>

                    <div id="pp-task-error" class="pp-error" style="display:none;"></div>
                </div>
                <?php endif; ?>

                <!-- -- CONTENT SECTION (shown when unlocked) -- -->
                <div id="pp-content" class="pp-content-section" <?= !$is_unlocked ? 'style="display:none;"' : '' ?>>
                    <div class="pp-prompt-head">
                        <span class="pp-prompt-label"><i class="fa-solid fa-scroll"></i> THE PROMPT:</span>
                        <?php if (!empty($p['best_works_in'])): ?>
                        <span class="pp-bwi-badge <?= $p['best_works_in'] === 'nano_banana' ? 'pp-bwi-nano' : 'pp-bwi-chatgpt' ?>">
                            <?php if ($p['best_works_in'] === 'nano_banana'): ?>
                                <i class="fa-solid fa-banana"></i> Best in Nano Banana
                            <?php else: ?>
                                <i class="fa-solid fa-robot"></i> Best in ChatGPT
                            <?php endif; ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="pp-code-block">
                        <div class="pp-code-header">
                            <div class="pp-code-header-dots"><span style="background:#ff5f57"></span><span style="background:#febc2e"></span><span style="background:#28c840"></span></div>
                            <span>PROMPT.txt</span>
                            <span style="opacity:.6;font-size:.7rem;" id="pp-word-count"><?= $is_unlocked ? str_word_count($p['prompt_text']) : 0 ?> words</span>
                        </div>
                        <div class="pp-prompt-text" id="pp-prompt-text"><?= $is_unlocked ? htmlspecialchars($p['prompt_text']) : '' ?></div>
                    </div>

                    <div class="pp-actions">
                        <button type="button" class="pp-btn pp-copy-btn" id="pp-copy-btn"><i class="fa-solid fa-copy"></i> COPY</button>
                        <button type="button" class="pp-btn pp-save-btn" id="pp-save-btn" data-prompt-id="<?= $id ?>" data-saved="<?= $p['is_saved'] ? 'true' : 'false' ?>">
                            <i class="fa-solid fa-bookmark"></i> <span id="pp-save-label"><?= $p['is_saved'] ? 'SAVED' : 'SAVE' ?></span>
                        </button>
                        <button class="pp-like-btn <?= $p['is_liked'] ? 'is-liked' : '' ?>" id="pp-like-btn" data-prompt-id="<?= $id ?>">
                            <i class="fa-solid fa-heart <?= $p['is_liked'] ? 'liked-heart' : '' ?>" id="pp-like-icon"></i>
                            <span id="pp-like-count"><?= (int)$p['likes_count'] ?></span>
                        </button>
                        <button type="button" class="pp-btn pp-share-btn" id="pp-share-btn"><i class="fa-solid fa-share-nodes"></i> SHARE</button>
                    </div>

                    <?php if (!empty($asset_images) || !empty($p['asset_title'])): ?>
                    <div class="pp-assets">
                        <div class="pp-assets-title"><i class="fa-solid fa-paperclip"></i> <?= htmlspecialchars($p['asset_title'] ?? 'Assets') ?></div>
                        <div class="pp-assets-grid">
                            <?php foreach ($asset_images as $i => $ai): ?>
                            <div style="position:relative;display:inline-flex;flex-direction:column;gap:6px;">
                                <img loading="lazy" src="<?= htmlspecialchars($ai) ?>" alt="Asset <?= $i+1 ?>">
                                <a href="<?= htmlspecialchars($ai) ?>" download target="_blank" style="display:flex;align-items:center;justify-content:center;gap:5px;background:var(--secondary-color);border:2px solid var(--text-color);border-radius:8px;padding:5px 8px;font-size:0.72rem;font-weight:800;font-family:var(--font-main);text-decoration:none;color:var(--text-color);box-shadow:2px 2px 0 var(--text-color);transition:all .15s;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform=''"><i class="fa-solid fa-download"></i> Download</a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php foreach ($extra_prompts_arr as $ep_i => $ep): ?>
                    <div class="pp-extra-section" id="pp-extra-<?= $ep_i ?>">
                        <div class="pp-extra-num">? Prompt <?= $ep_i + 2 ?></div>
                        <div class="pp-extra-layout">
                            <?php if (!empty($ep['image_path'])): ?>
                            <div class="pp-extra-img-col">
                                <img loading="lazy" src="<?= htmlspecialchars($ep['image_path']) ?>" class="pp-extra-img" alt="Prompt <?= $ep_i + 2 ?>">
                            </div>
                            <?php endif; ?>
                            <div class="pp-extra-info">
                                <?php if (!empty($ep['title'])): ?>
                                <h2 class="pp-extra-title"><?= htmlspecialchars($ep['title']) ?></h2>
                                <?php endif; ?>
                                <div class="pp-code-block">
                                    <div class="pp-code-header">
                                        <div class="pp-code-header-dots"><span style="background:#ff5f57"></span><span style="background:#febc2e"></span><span style="background:#28c840"></span></div>
                                        <span>PROMPT <?= $ep_i + 2 ?>.txt</span>
                                    </div>
                                    <div class="pp-prompt-text" id="pp-extra-text-<?= $ep_i ?>"><?= $is_unlocked ? htmlspecialchars($ep['prompt_text']) : '' ?></div>
                                </div>
                                <div style="margin-top:12px;">
                                    <button class="pp-btn pp-copy-btn" onclick="copyExtra(<?= $ep_i ?>, this)"><i class="fa-solid fa-copy"></i> COPY</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Related Prompts -->
        <?php if (!empty($related)): ?>
        <div class="pp-related">
            <h2>More <?= htmlspecialchars($tinfo['label']) ?> Prompts</h2>
            <div class="pp-rel-grid">
                <?php foreach ($related as $r): ?>
                <a href="<?= (!$is_local && !empty($r['slug'])) ? '/prompts/' . htmlspecialchars($r['slug']) : 'prompt.php?id=' . $r['id'] ?>" class="pp-rel-card">
                    <img loading="lazy" src="<?= htmlspecialchars($r['image_path']) ?>" alt="<?= htmlspecialchars($r['title']) ?>" loading="lazy">
                    <div class="pp-rel-card-title"><?= htmlspecialchars($r['title']) ?></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <script>
    const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
    const promptId = <?= $id ?>;
    const ptype    = '<?= $ptype ?>';

    // -- Error helper --
    function showError(msg) {
        const el = document.getElementById('pp-task-error');
        if (!el) return;
        el.textContent = msg;
        el.style.display = 'block';
        setTimeout(() => el.style.display = 'none', 4000);
    }

    // -- Reveal content after unlock --
    function revealPrompt(text, extraPrompts) {
        const task = document.getElementById('pp-task');
        const content = document.getElementById('pp-content');
        const imgCol = document.getElementById('pp-img-col');
        if (task) task.style.display = 'none';
        if (content) { 
            content.style.display = 'flex'; 
            document.getElementById('pp-prompt-text').textContent = text; 
            const wcEl = document.getElementById('pp-word-count');
            if (wcEl) wcEl.textContent = text.trim().split(/\s+/).filter(w => w.length > 0).length + ' words';
        }
        if (imgCol) imgCol.classList.remove('blurred');
        const mainImg = document.getElementById('pp-main-img');
        if (mainImg) mainImg.style.filter = '';
        if (extraPrompts && Array.isArray(extraPrompts)) {
            extraPrompts.forEach(function(ep, i) {
                const el = document.getElementById('pp-extra-text-' + i);
                if (el) el.textContent = ep.prompt_text || '';
            });
        }
        content.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function copyToClipboard(text) {
        if (!text || !text.trim()) return Promise.reject(new Error('empty'));
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text).catch(function() {
                return copyToClipboardFallback(text);
            });
        }
        return copyToClipboardFallback(text);
    }
    function copyToClipboardFallback(text) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(ta);
        return Promise.resolve();
    }

    function copyExtra(idx, btn) {
        const el = document.getElementById('pp-extra-text-' + idx);
        if (!el || !el.textContent.trim()) return;
        copyToClipboard(el.textContent.trim()).then(function() {
            btn.innerHTML = '<i class="fa-solid fa-check"></i> COPIED!';
            setTimeout(() => btn.innerHTML = '<i class="fa-solid fa-copy"></i> COPY', 2000);
        });
    }

    // -- SECRET CODE --
    const submitCode = document.getElementById('pp-submit-code');
    if (submitCode) {
        submitCode.addEventListener('click', async function() {
            const code = document.getElementById('pp-code-input').value.trim();
            if (code.length < 4) { showError('Please enter the code!'); return; }
            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking...';
            const fd = new FormData();
            fd.append('action', 'verify'); fd.append('prompt_id', promptId); fd.append('code', code);
            const res = await fetch('unlock.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) { revealPrompt(res.prompt_text, res.extra_prompts); }
            else { showError(res.message || 'Wrong code! Watch the reel to get it.'); this.disabled = false; this.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Generate Prompt'; }
        });
    }

    // -- UNRELEASED (20 taps logged in / 90 taps guest) --
    const loveBtn = document.getElementById('pp-love-btn');
    if (loveBtn) {
        let tapCount = 0;
        const TAPS = (typeof isLoggedIn !== 'undefined' && isLoggedIn) ? 20 : 90;
        document.getElementById('pp-tap-total').textContent = TAPS;
        loveBtn.addEventListener('click', async function() {
            if (tapCount === 0) {
                const fd = new FormData(); fd.append('action', 'init_love'); fd.append('prompt_id', promptId);
                await fetch('unlock.php', { method: 'POST', body: fd });
            }
            tapCount++;
            document.getElementById('pp-tap-count').textContent = tapCount;
            document.getElementById('pp-progress-fill').style.width = (tapCount / TAPS * 100) + '%';
            this.style.transform = 'scale(1.35)';
            setTimeout(() => this.style.transform = '', 120);
            if (tapCount >= TAPS) {
                this.disabled = true;
                const fd = new FormData(); fd.append('action', 'unreleased'); fd.append('prompt_id', promptId);
                const res = await fetch('unlock.php', { method: 'POST', body: fd }).then(r => r.json());
                if (res.success) { revealPrompt(res.prompt_text, res.extra_prompts); }
                else { tapCount = 0; document.getElementById('pp-tap-count').textContent = '0'; document.getElementById('pp-progress-fill').style.width = '0%'; this.disabled = false; showError(res.message); }
            }
        });
    }

    // -- ALREADY UPLOADED (9 heart taps) --
    const loveBtnAu = document.getElementById('pp-love-btn-au');
    if (loveBtnAu) {
        let tapCountAu = 0;
        const TAPS_AU = 9;
        loveBtnAu.addEventListener('click', async function() {
            tapCountAu++;
            document.getElementById('pp-tap-count-au').textContent = tapCountAu;
            document.getElementById('pp-progress-fill-au').style.width = (tapCountAu / TAPS_AU * 100) + '%';
            this.style.transform = 'scale(1.35)';
            setTimeout(() => this.style.transform = '', 120);
            if (tapCountAu >= TAPS_AU) {
                this.disabled = true;
                const fd = new FormData(); fd.append('action', 'already_uploaded'); fd.append('prompt_id', promptId);
                const res = await fetch('unlock.php', { method: 'POST', body: fd }).then(r => r.json());
                if (res.success) { revealPrompt(res.prompt_text, res.extra_prompts); }
                else { tapCountAu = 0; document.getElementById('pp-tap-count-au').textContent = '0'; document.getElementById('pp-progress-fill-au').style.width = '0%'; this.disabled = false; showError(res.message); }
            }
        });
    }

    // -- DIRECT PROMPT (configurable heart taps) --
    const loveBtnDir = document.getElementById('pp-love-btn-dir');
    if (loveBtnDir) {
        let tapCountDir = 0;
        const TAPS_DIR = typeof DIR_REQ_TAPS !== 'undefined' ? DIR_REQ_TAPS : 9;
        loveBtnDir.addEventListener('click', async function() {
            tapCountDir++;
            document.getElementById('pp-tap-count-dir').textContent = tapCountDir;
            document.getElementById('pp-progress-fill-dir').style.width = (tapCountDir / TAPS_DIR * 100) + '%';
            this.style.transform = 'scale(1.35)';
            setTimeout(() => this.style.transform = '', 120);
            if (tapCountDir >= TAPS_DIR) {
                this.disabled = true;
                const fd = new FormData(); fd.append('action', 'direct'); fd.append('prompt_id', promptId);
                const res = await fetch('unlock.php', { method: 'POST', body: fd }).then(r => r.json());
                if (res.success) { revealPrompt(res.prompt_text, res.extra_prompts); }
                else { tapCountDir = 0; document.getElementById('pp-tap-count-dir').textContent = '0'; document.getElementById('pp-progress-fill-dir').style.width = '0%'; this.disabled = false; showError(res.message); }
            }
        });
    }

    // -- INSTA VIRAL (math) --
    const mathQ = document.getElementById('pp-math-q');
    if (mathQ) {
        (async function initMath() {
            const fd = new FormData(); fd.append('action', 'get_challenge'); fd.append('prompt_id', promptId);
            const d = await fetch('unlock.php', { method: 'POST', body: fd }).then(r => r.json());
            mathQ.textContent = d.n1 + ' + ' + d.n2 + ' + ' + d.n3 + ' + ' + d.n4 + ' = ?';
        })();
        document.getElementById('pp-submit-math').addEventListener('click', async function() {
            const ans = document.getElementById('pp-math-input').value;
            if (!ans) { showError('Enter your answer!'); return; }
            this.disabled = true;
            const fd = new FormData(); fd.append('action', 'insta_viral'); fd.append('prompt_id', promptId); fd.append('user_answer', ans);
            const res = await fetch('unlock.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) { revealPrompt(res.prompt_text, res.extra_prompts); }
            else { showError(res.message || 'Wrong answer! Try again.'); this.disabled = false; mathQ.textContent = 'Loading...'; initMath(); }
        });
    }


    // -- COPY (with fallback for non-HTTPS / arigato.local) --
    const copyBtn = document.getElementById('pp-copy-btn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const text = document.getElementById('pp-prompt-text').textContent;
            copyToClipboard(text).then(() => {
                this.innerHTML = '<i class="fa-solid fa-check"></i> COPIED!';
                setTimeout(() => this.innerHTML = '<i class="fa-solid fa-copy"></i> COPY', 2000);
                const fd = new FormData(); fd.append('action','copy'); fd.append('prompt_id', promptId);
                fetch('track.php', {method:'POST', body:fd});
            }).catch(function() { showError('Could not copy — try selecting the text manually.'); });
        });
    }

    // -- SAVE --
    const saveBtn = document.getElementById('pp-save-btn');
    if (saveBtn) {
        saveBtn.addEventListener('click', async function() {
            <?php if (!isset($_SESSION['user_id'])): ?>
            window.location.href = 'login.php';
            return;
            <?php endif; ?>
            const isSaved = this.dataset.saved === 'true';
            const action  = isSaved ? 'unsave' : 'save';
            const fd = new FormData(); fd.append('prompt_id', promptId); fd.append('action', action);
            const res = await fetch('save_prompt.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                this.dataset.saved = res.saved ? 'true' : 'false';
                document.getElementById('pp-save-label').textContent = res.saved ? 'SAVED' : 'SAVE';
                this.style.background = res.saved ? 'var(--primary-color)' : 'var(--secondary-color)';
            }
        });
    }

    // -- SHARE --
    const shareBtn = document.getElementById('pp-share-btn');
    if (shareBtn) {
        shareBtn.addEventListener('click', async function() {
            const url = window.location.href;
            const title = <?= json_encode($p['title']) ?>;
            if (navigator.share) {
                try { await navigator.share({ title: title + ' ï¿½ Arigato Devan', url: url }); return; } catch(e) {}
            }
            navigator.clipboard.writeText(url).then(() => {
                this.innerHTML = '<i class="fa-solid fa-check"></i> COPIED!';
                setTimeout(() => this.innerHTML = '<i class="fa-solid fa-share-nodes"></i> SHARE', 2000);
            });
        });
    }

    // -- LIKE --
    const likeBtn = document.getElementById('pp-like-btn');
    if (likeBtn) {
        likeBtn.addEventListener('click', async function() {
            <?php if (!isset($_SESSION['user_id'])): ?>
            window.location.href = 'login.php';
            return;
            <?php endif; ?>
            const fd = new FormData(); fd.append('prompt_id', promptId);
            const res = await fetch('like.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                const isLiked = res.action === 'liked';
                this.classList.toggle('is-liked', isLiked);
                document.getElementById('pp-like-icon').classList.toggle('liked-heart', isLiked);
                document.getElementById('pp-like-count').textContent = res.likes_count;
                document.getElementById('pp-like-count-mini').textContent = res.likes_count;
            }
        });
    }
    </script>
</body>
</html>

