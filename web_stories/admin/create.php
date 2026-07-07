<?php
/**
 * Step 1 of create flow — pick a template, then open the editor.
 */
require_once __DIR__ . '/bootstrap.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../index.php');
    exit();
}

$templates = ws_get_templates();
$categories = [];
foreach ($templates as $key => $tpl) {
    $cat = $tpl['category'] ?? 'General';
    $categories[$cat][$key] = $tpl;
}
$active_cat = $_GET['cat'] ?? array_key_first($categories);
if (!isset($categories[$active_cat])) {
    $active_cat = array_key_first($categories);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create Story — Web Stories</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Oswald:wght@700&family=Playfair+Display:wght@700;900&family=Outfit:wght@800&family=Inter:wght@700&family=Cormorant+Garamond:wght@600&display=swap">
<link rel="stylesheet" href="../assets/css/editor.css?v=8">
</head>
<body class="ws-editor-body">
<header class="ws-ed-top">
    <a href="index.php" class="ws-ed-back"><i class="fa-solid fa-arrow-left"></i> All Stories</a>
    <h1>Create New Story</h1>
    <span></span>
</header>

<main class="ws-create-page">
    <p class="ws-create-lead">Template choose karo → editor khulega → slides edit karo → SEO fill karo → publish.</p>

    <div class="ws-create-layout">
        <aside class="ws-create-cats">
            <h2>Categories</h2>
            <?php foreach (array_keys($categories) as $cat): ?>
            <a href="create.php?cat=<?= urlencode($cat) ?>" class="ws-cat-link <?= $cat === $active_cat ? 'active' : '' ?>">
                <?= htmlspecialchars($cat) ?>
            </a>
            <?php endforeach; ?>
        </aside>

        <section class="ws-create-grid-wrap">
            <h2><?= htmlspecialchars($active_cat) ?> Templates</h2>
            <div class="ws-templates ws-templates-lg">
                <?php foreach ($categories[$active_cat] as $key => $tpl):
                    $slide0 = ws_normalize_slide($tpl['slides'][0] ?? []);
                    $preview = $slide0['bg_color'] ?? '#2F4156';
                    $previewTitle = $slide0['title'] ?? $tpl['label'];
                    $grad = ws_gradient_css((int)$slide0['gradient_opacity'], $slide0['gradient_color']);
                    $fp = ws_font_presets()[$slide0['font_preset'] ?? 'editorial'] ?? [];
                    $fontFamily = $fp['family'] ?? "'Inter', sans-serif";
                ?>
                <a class="ws-tpl-card ws-tpl-card-lg" href="editor.php?new=1&amp;template=<?= urlencode($key) ?>">
                    <div class="ws-tpl-preview" style="background:<?= htmlspecialchars($preview) ?>;font-family:<?= $fontFamily ?>">
                        <?php if ($grad !== 'none'): ?><div class="ws-tpl-grad" style="background:<?= htmlspecialchars($grad) ?>"></div><?php endif; ?>
                        <span class="ws-tpl-preview-text"><?= htmlspecialchars($previewTitle) ?></span>
                    </div>
                    <div class="ws-tpl-meta">
                        <strong><?= htmlspecialchars($tpl['label']) ?></strong>
                        <span><?= count($tpl['slides'] ?? []) ?> slides · <?= htmlspecialchars($fp['label'] ?? 'Custom') ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>
</body>
</html>
