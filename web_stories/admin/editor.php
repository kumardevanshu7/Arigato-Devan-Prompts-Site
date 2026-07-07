<?php
require_once __DIR__ . '/bootstrap.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../index.php');
    exit();
}

$err = '';
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$is_new = isset($_GET['new']);
$template_key = trim($_GET['template'] ?? '');

if ($is_new && $template_key === '' && !$edit_id) {
    header('Location: create.php');
    exit;
}

$templates = ws_get_templates();
if ($is_new && $template_key && !isset($templates[$template_key])) {
    header('Location: create.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    verify_csrf();
    $story_id = (int)($_POST['story_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $slug = ws_slugify(trim($_POST['slug'] ?? '') ?: $title);
    $description = trim($_POST['description'] ?? '');
    $keywords = trim($_POST['meta_keywords'] ?? '');
    $publisher = trim($_POST['publisher_name'] ?? '') ?: (ws_setting($pdo, 'publisher_name', 'Arigato Devan') ?: 'Arigato Devan');
    $noindex = !empty($_POST['noindex']) ? 1 : 0;
    $publish_now = !empty($_POST['is_published']);
    $slides = $_POST['slides'] ?? [];

    if ($title === '') {
        $err = 'Title is required.';
    } elseif (count($slides) < 1) {
        $err = 'At least one page is required.';
    } elseif (count($slides) > 30) {
        $err = 'Maximum 30 slides allowed.';
    } else {
        $slugCheck = $pdo->prepare('SELECT id FROM web_stories WHERE slug = ? AND id != ?');
        $slugCheck->execute([$slug, $story_id]);
        if ($slugCheck->fetch()) {
            $slug .= '-' . substr(uniqid(), -4);
        }

        $poster_path = trim($_POST['existing_poster'] ?? '');
        if (!empty($_FILES['poster_image']['tmp_name'])) {
            $up = ws_upload_image($_FILES['poster_image'], 'poster', 720, 1280);
            if ($up) {
                if ($poster_path && is_file(SITE_ROOT . '/' . $poster_path)) {
                    @unlink(SITE_ROOT . '/' . $poster_path);
                }
                $poster_path = $up;
            }
        }

        $storyRow = [
            'title' => $title,
            'description' => $description,
            'poster_image' => $poster_path,
        ];
        $pagesPreview = [];
        foreach ($slides as $i => $slide) {
            $pagesPreview[] = [
                'bg_image' => trim($slide['existing_bg'] ?? ''),
                'bg_color' => $slide['bg_color'] ?? '#2F4156',
                'title' => trim($slide['title'] ?? ''),
                'body_text' => trim($slide['body_text'] ?? ''),
            ];
        }
        if ($publish_now) {
            $err = ws_validate_for_publish($storyRow, $pagesPreview);
        } elseif ($poster_path === '') {
            // draft ok without poster
        }

        if (!$err) {
            if ($story_id > 0) {
                $pdo->prepare('UPDATE web_stories SET title=?, slug=?, description=?, meta_keywords=?, poster_image=?, publisher_name=?, noindex=?, is_published=? WHERE id=?')
                    ->execute([$title, $slug, $description, $keywords, $poster_path, $publisher, $noindex, $publish_now ? 1 : 0, $story_id]);
            } else {
                $pdo->prepare('INSERT INTO web_stories (title, slug, description, meta_keywords, poster_image, publisher_name, noindex, is_published) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$title, $slug, $description, $keywords, $poster_path, $publisher, $noindex, $publish_now ? 1 : 0]);
                $story_id = (int)$pdo->lastInsertId();
            }

            $old_imgs = [];
            $oi = $pdo->prepare('SELECT bg_image FROM web_story_pages WHERE story_id = ?');
            $oi->execute([$story_id]);
            while ($r = $oi->fetch(PDO::FETCH_ASSOC)) {
                $old_imgs[] = $r['bg_image'];
            }
            $pdo->prepare('DELETE FROM web_story_pages WHERE story_id = ?')->execute([$story_id]);

            $used_imgs = [];
            foreach ($slides as $i => $slide) {
                $bg = trim($slide['existing_bg'] ?? '');
                $file_key = 'slide_bg_' . $i;
                if (!empty($_FILES[$file_key]['tmp_name'])) {
                    $up = ws_upload_image($_FILES[$file_key], 'slide', 1080, 1920);
                    if ($up) {
                        if ($bg && is_file(SITE_ROOT . '/' . $bg)) {
                            @unlink(SITE_ROOT . '/' . $bg);
                        }
                        $bg = $up;
                    }
                }
                if ($bg) {
                    $used_imgs[] = $bg;
                }
                $bg_color = preg_match('/^#[0-9A-Fa-f]{3,8}$/', $slide['bg_color'] ?? '') ? $slide['bg_color'] : '#2F4156';
                $align = in_array($slide['text_align'] ?? '', ['left', 'center', 'right'], true) ? $slide['text_align'] : 'left';
                $anim = preg_match('/^[a-z0-9-]+$/', $slide['animate_in'] ?? '') ? $slide['animate_in'] : 'fade-in';
                $adv = max(0, min(30, (int)($slide['auto_advance_sec'] ?? 0)));
                $layout = ws_normalize_slide($slide);
                $pdo->prepare('INSERT INTO web_story_pages (story_id, sort_order, bg_image, bg_color, title, body_text, cta_label, cta_url, text_align, animate_in, auto_advance_sec, text_valign, font_preset, gradient_opacity, gradient_color, show_logo, logo_style, logo_position) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([
                        $story_id, (int)$i, $bg, $bg_color,
                        trim($slide['title'] ?? ''),
                        trim($slide['body_text'] ?? ''),
                        trim($slide['cta_label'] ?? ''),
                        trim($slide['cta_url'] ?? ''),
                        $align, $anim, $adv,
                        $layout['text_valign'],
                        $layout['font_preset'],
                        $layout['gradient_opacity'],
                        $layout['gradient_color'],
                        $layout['show_logo'],
                        $layout['logo_style'],
                        $layout['logo_position'],
                    ]);
            }
            foreach ($old_imgs as $img) {
                if ($img && !in_array($img, $used_imgs, true) && is_file(SITE_ROOT . '/' . $img)) {
                    @unlink(SITE_ROOT . '/' . $img);
                }
            }

            header('Location: editor.php?edit=' . $story_id . '&saved=1');
            exit;
        }
    }
}

$edit_story = null;
$edit_pages = [];
if ($edit_id > 0) {
    $es = $pdo->prepare('SELECT * FROM web_stories WHERE id = ?');
    $es->execute([$edit_id]);
    $edit_story = $es->fetch(PDO::FETCH_ASSOC);
    if ($edit_story) {
        $ep = $pdo->prepare('SELECT * FROM web_story_pages WHERE story_id = ? ORDER BY sort_order ASC, id ASC');
        $ep->execute([$edit_id]);
        $edit_pages = $ep->fetchAll(PDO::FETCH_ASSOC);
    }
}

$templates = $templates ?? ws_get_templates();
if ($is_new && $template_key && isset($templates[$template_key]['slides'])) {
    $edit_pages = $templates[$template_key]['slides'];
}

$default_slides = $edit_pages ?: ($templates['blank']['slides'] ?? array_fill(0, 5, []));
$csrf = generate_csrf();
$saved = !empty($_GET['saved']);
$pub_logo = ws_setting($pdo, 'publisher_logo', '');
$pub_name = ws_setting($pdo, 'publisher_name', 'Arigato Devan') ?: 'Arigato Devan';
$pub_logo_url = $pub_logo ? '../../' . $pub_logo : '../../favicon.ico';
$font_presets_json = json_encode(ws_font_presets(), JSON_UNESCAPED_UNICODE);

function ws_slide_fields(array $slide, int $i): void {
    $defaults = array_merge([
        'bg_color' => '#2F4156', 'title' => '', 'body_text' => '',
        'cta_label' => '', 'cta_url' => '', 'text_align' => 'left',
        'animate_in' => 'fade-in', 'auto_advance_sec' => 0, 'bg_image' => '',
    ], ws_slide_layout_defaults());
    $s = ws_normalize_slide(array_merge($defaults, $slide));
    ?>
    <input type="hidden" name="slides[<?= $i ?>][existing_bg]" value="<?= htmlspecialchars($s['bg_image']) ?>">
    <div class="ws-field">
        <label>Background Image</label>
        <?php
        $bgPreview = $s['bg_image'] ? '../../' . $s['bg_image'] : '';
        ws_render_upload_zone('slide_bg_' . $i, $bgPreview);
        ?>
    </div>
    <div class="ws-row2">
        <div class="ws-field ws-field-color">
            <label>BG Color</label>
            <div class="ws-color-wrap">
                <input type="color" name="slides[<?= $i ?>][bg_color]" value="<?= htmlspecialchars($s['bg_color']) ?>">
                <span class="ws-color-val"><?= htmlspecialchars($s['bg_color']) ?></span>
            </div>
        </div>
        <div class="ws-field">
            <label>Text Align</label>
            <select name="slides[<?= $i ?>][text_align]">
                <option value="left" <?= $s['text_align'] === 'left' ? 'selected' : '' ?>>Left</option>
                <option value="center" <?= $s['text_align'] === 'center' ? 'selected' : '' ?>>Center</option>
                <option value="right" <?= $s['text_align'] === 'right' ? 'selected' : '' ?>>Right</option>
            </select>
        </div>
    </div>
    <?php ws_render_slide_design_fields($s, $i); ?>
    <div class="ws-field">
        <label>Headline</label>
        <input type="text" name="slides[<?= $i ?>][title]" value="<?= htmlspecialchars($s['title']) ?>">
    </div>
    <div class="ws-field">
        <label>Body Text</label>
        <textarea name="slides[<?= $i ?>][body_text]"><?= htmlspecialchars($s['body_text']) ?></textarea>
    </div>
    <div class="ws-row2">
        <div class="ws-field">
            <label>Animation</label>
            <select name="slides[<?= $i ?>][animate_in]">
                <?php foreach (['fade-in','fly-in-bottom','zoom-in','drop'] as $a): ?>
                <option value="<?= $a ?>" <?= $s['animate_in'] === $a ? 'selected' : '' ?>><?= $a ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="ws-field">
            <label>Auto-advance (sec)</label>
            <input type="number" name="slides[<?= $i ?>][auto_advance_sec]" min="0" max="30" value="<?= (int)$s['auto_advance_sec'] ?>" placeholder="0=default">
        </div>
    </div>
    <div class="ws-field">
        <label>CTA Label</label>
        <input type="text" name="slides[<?= $i ?>][cta_label]" value="<?= htmlspecialchars($s['cta_label']) ?>" placeholder="Learn more">
    </div>
    <div class="ws-field">
        <label>CTA URL</label>
        <input type="url" name="slides[<?= $i ?>][cta_url]" value="<?= htmlspecialchars($s['cta_url']) ?>" placeholder="https://arigatodevan.com/...">
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $edit_story ? 'Edit' : 'New' ?> Story — Web Stories</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Playfair+Display:wght@600;700;900&family=Outfit:wght@500;700;800&family=Inter:wght@300;500;700&family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&display=swap">
<link rel="stylesheet" href="../assets/css/editor.css?v=12">
</head>
<body class="ws-editor-body">
<form method="post" enctype="multipart/form-data" id="ws-editor-form">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
<input type="hidden" name="action" value="save">
<input type="hidden" name="story_id" value="<?= (int)($edit_story['id'] ?? 0) ?>">
<input type="hidden" name="existing_poster" value="<?= htmlspecialchars($edit_story['poster_image'] ?? '') ?>">
<div id="ws-editor-data" hidden
     data-pub-logo="<?= htmlspecialchars($pub_logo_url) ?>"
     data-pub-name="<?= htmlspecialchars($pub_name) ?>"
     data-font-presets="<?= htmlspecialchars($font_presets_json, ENT_QUOTES) ?>"></div>

<header class="ws-ed-top">
    <a href="index.php" class="ws-ed-back"><i class="fa-solid fa-arrow-left"></i> All Stories</a>
    <h1><?= $edit_story ? htmlspecialchars($edit_story['title']) : 'New Story' ?></h1>
    <div class="ws-ed-actions">
        <?php if ($edit_story): ?>
        <a href="../story.php?slug=<?= urlencode($edit_story['slug']) ?>&amp;preview=1" class="ws-btn ghost" target="_blank"><i class="fa-solid fa-play"></i> Preview</a>
        <?php endif; ?>
        <button type="submit" class="ws-btn primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
    </div>
</header>

<?php if ($saved): ?><div class="ws-flash ok">Story saved.</div><?php endif; ?>
<?php if ($err): ?><div class="ws-flash err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="ws-editor-layout">
    <aside class="ws-slides-rail" id="ws-slides-wrap">
        <div class="ws-slides-rail-head">
            <h2>Pages</h2>
            <span class="ws-page-count-badge" id="ws-page-count"><?= count($default_slides) ?></span>
        </div>
        <div class="ws-thumbs-list" id="ws-thumbs-list">
        <?php foreach ($default_slides as $i => $slide):
            $color = $slide['bg_color'] ?? '#2F4156';
            $t = $slide['title'] ?? ('Slide ' . ($i + 1));
            $bg = $slide['bg_image'] ?? '';
            $thumbStyle = 'background-color:' . htmlspecialchars($color) . ';';
            if ($bg) {
                $thumbStyle .= 'background-image:url(../../' . htmlspecialchars($bg) . ');background-size:cover;background-position:center;';
            }
        ?>
        <div class="ws-thumb-item" data-idx="<?= $i ?>">
            <button type="button" class="ws-slide-thumb <?= $i === 0 ? 'active' : '' ?>" data-idx="<?= $i ?>">
                <span class="num"><?= $i + 1 ?></span>
                <div class="fill <?= $bg ? 'has-image' : '' ?>" style="<?= $thumbStyle ?>">
                    <span class="fill-label"><?= htmlspecialchars(substr($t, 0, 30)) ?></span>
                </div>
            </button>
            <button type="button" class="ws-thumb-del" title="Delete page" aria-label="Delete page"><i class="fa-solid fa-trash-can"></i></button>
        </div>
        <?php endforeach; ?>
        </div>
        <button type="button" class="ws-add-slide-btn" id="ws-add-slide"><i class="fa-solid fa-plus"></i> Add Page</button>
    </aside>

    <section class="ws-canvas-area">
        <div class="ws-page-bar">
            <span id="ws-page-label">PAGE 1 OF <?= count($default_slides) ?></span>
            <button type="button" class="ws-page-del-btn" id="ws-delete-slide" title="Delete current page"><i class="fa-solid fa-trash-can"></i> Delete page</button>
        </div>
        <div class="ws-phone">
            <div class="ws-phone-screen" id="ws-phone-screen">
                <div class="bg" id="ws-phone-bg"></div>
                <div class="ws-phone-grad" id="ws-phone-grad"></div>
                <div class="ws-phone-layout" id="ws-phone-layout">
                    <div class="ws-phone-logo" id="ws-phone-logo-top" hidden></div>
                    <div class="ws-phone-safe" id="ws-phone-safe">
                        <h3 id="ws-phone-title"></h3>
                        <p id="ws-phone-body"></p>
                        <span class="ws-phone-cta" id="ws-phone-cta"></span>
                    </div>
                    <div class="ws-phone-logo" id="ws-phone-logo-bottom" hidden></div>
                </div>
            </div>
        </div>
    </section>

    <aside class="ws-props">
        <div class="ws-tabs">
            <button type="button" class="ws-tab active" data-tab="slide">Slide</button>
            <button type="button" class="ws-tab" data-tab="seo">SEO</button>
        </div>

        <div id="ws-slide-panels">
            <?php foreach ($default_slides as $i => $slide): ?>
            <div class="ws-panel ws-panel-slide ws-slide-data <?= $i === 0 ? 'active' : '' ?>" data-tab="slide" data-index="<?= $i ?>" data-idx="<?= $i ?>">
                <?php ws_slide_fields($slide, $i); ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="ws-panel" data-tab="seo">
            <div class="ws-field">
                <label>Story Title *</label>
                <input type="text" name="title" id="seo-title" value="<?= htmlspecialchars($edit_story['title'] ?? '') ?>" placeholder="Required — SEO tab">
            </div>
            <div class="ws-field">
                <label>URL Slug</label>
                <input type="text" name="slug" value="<?= htmlspecialchars($edit_story['slug'] ?? '') ?>" placeholder="auto-from-title">
            </div>
            <div class="ws-field">
                <label>SEO Description *</label>
                <textarea name="description" id="seo-desc" placeholder="150-160 chars for Google Discover"><?= htmlspecialchars($edit_story['description'] ?? '') ?></textarea>
            </div>
            <div class="ws-field">
                <label>Keywords (comma separated)</label>
                <input type="text" name="meta_keywords" value="<?= htmlspecialchars($edit_story['meta_keywords'] ?? '') ?>" placeholder="AI prompts, ChatGPT, ...">
            </div>
            <div class="ws-field">
                <label>Poster Image</label>
                <?php
                $posterPreview = !empty($edit_story['poster_image']) ? '../../' . $edit_story['poster_image'] : '';
                ws_render_upload_zone('poster_image', $posterPreview, 'Cover for Google · 9:16');
                ?>
            </div>
            <div class="ws-field">
                <label>Publisher Override</label>
                <input type="text" name="publisher_name" value="<?= htmlspecialchars($edit_story['publisher_name'] ?? ws_setting($pdo, 'publisher_name', 'Arigato Devan')) ?>">
            </div>
            <label class="ws-check">
                <input type="checkbox" name="noindex" value="1" <?= !empty($edit_story['noindex']) ? 'checked' : '' ?>>
                Disable search engine indexing (draft/test)
            </label>
            <label class="ws-check">
                <input type="checkbox" name="is_published" value="1" <?= !empty($edit_story['is_published']) ? 'checked' : '' ?>>
                Publish live (needs 5+ slides, poster, description)
            </label>
            <p class="ws-hint">Google preview:</p>
            <div class="ws-google-preview" id="google-preview">
                <div class="url">arigatodevan.com › web_stories</div>
                <div class="title">Story title</div>
                <div class="desc">Meta description...</div>
            </div>
        </div>
    </aside>
</div>
</form>

<template id="ws-slide-template">
    <input type="hidden" name="slides[__IDX__][existing_bg]" value="">
    <div class="ws-field">
        <label>Background Image</label>
        <div class="ws-upload-zone">
            <input type="file" class="ws-upload-input" name="slide_bg___IDX__" accept="image/jpeg,image/png,image/webp,image/gif">
            <div class="ws-upload-empty">
                <div class="ws-upload-icon" aria-hidden="true"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                <span class="ws-upload-title">Upload image</span>
                <small>JPG, PNG, WebP · 9:16 portrait</small>
            </div>
            <div class="ws-upload-filled" hidden>
                <div class="ws-upload-thumb-wrap">
                    <img class="ws-upload-thumb ws-local-img-preview" alt="">
                </div>
                <div class="ws-upload-bar">
                    <span class="ws-upload-fname"></span>
                    <button type="button" class="ws-upload-btn" data-action="replace">Replace</button>
                </div>
            </div>
        </div>
    </div>
    <div class="ws-row2">
        <div class="ws-field ws-field-color">
            <label>BG Color</label>
            <div class="ws-color-wrap">
                <input type="color" name="slides[__IDX__][bg_color]" value="#2F4156">
                <span class="ws-color-val">#2F4156</span>
            </div>
        </div>
        <div class="ws-field"><label>Text Align</label><select name="slides[__IDX__][text_align]"><option value="left">Left</option><option value="center">Center</option><option value="right">Right</option></select></div>
    </div>
    <!--ws-design-->
    <div class="ws-field"><label>Headline</label><input type="text" name="slides[__IDX__][title]"></div>
    <div class="ws-field"><label>Body Text</label><textarea name="slides[__IDX__][body_text]"></textarea></div>
    <div class="ws-row2">
        <div class="ws-field"><label>Animation</label><select name="slides[__IDX__][animate_in]"><option value="fade-in">fade-in</option><option value="fly-in-bottom">fly-in-bottom</option><option value="zoom-in">zoom-in</option><option value="drop">drop</option></select></div>
        <div class="ws-field"><label>Auto-advance (sec)</label><input type="number" name="slides[__IDX__][auto_advance_sec]" min="0" max="30" value="0"></div>
    </div>
    <div class="ws-field"><label>CTA Label</label><input type="text" name="slides[__IDX__][cta_label]"></div>
    <div class="ws-field"><label>CTA URL</label><input type="url" name="slides[__IDX__][cta_url]"></div>
</template>

<template id="ws-design-template">
<?php ws_render_slide_design_fields(ws_slide_layout_defaults(), '__IDX__'); ?>
</template>

<template id="ws-thumb-template">
    <div class="ws-thumb-item" data-idx="__IDX__">
        <button type="button" class="ws-slide-thumb" data-idx="__IDX__">
            <span class="num">__NUM__</span>
            <div class="fill"><span class="fill-label"></span></div>
        </button>
        <button type="button" class="ws-thumb-del" title="Delete page" aria-label="Delete page"><i class="fa-solid fa-trash-can"></i></button>
    </div>
</template>

<div class="ws-modal-overlay" id="ws-modal" hidden>
    <div class="ws-modal" role="dialog" aria-modal="true" aria-labelledby="ws-modal-title">
        <div class="ws-modal-icon" id="ws-modal-icon" aria-hidden="true"><i class="fa-solid fa-circle-question"></i></div>
        <h3 class="ws-modal-title" id="ws-modal-title">Confirm</h3>
        <p class="ws-modal-msg" id="ws-modal-msg"></p>
        <div class="ws-modal-actions" id="ws-modal-actions"></div>
    </div>
</div>

<script src="../assets/js/editor.js?v=11"></script>
</body>
</html>
