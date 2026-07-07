<?php
require_once __DIR__ . '/bootstrap.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../index.php');
    exit();
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        $tid = (int)($_POST['tid'] ?? 0);
        $val = (int)($_POST['val'] ?? 0) ? 1 : 0;
        if ($val) {
            $st = $pdo->prepare('SELECT * FROM web_stories WHERE id = ?');
            $st->execute([$tid]);
            $story = $st->fetch(PDO::FETCH_ASSOC);
            $pg = $pdo->prepare('SELECT * FROM web_story_pages WHERE story_id = ? ORDER BY sort_order');
            $pg->execute([$tid]);
            $pages = $pg->fetchAll(PDO::FETCH_ASSOC);
            $vErr = $story ? ws_validate_for_publish($story, $pages) : 'Story not found.';
            if ($vErr) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'error' => $vErr]);
                exit;
            }
        }
        $pdo->prepare('UPDATE web_stories SET is_published = ? WHERE id = ?')->execute([$val, $tid]);
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'delete') {
        $del_id = (int)($_POST['del_id'] ?? 0);
        if ($del_id > 0) {
            ws_delete_story_files($pdo, $del_id);
            $pdo->prepare('DELETE FROM web_story_pages WHERE story_id = ?')->execute([$del_id]);
            $pdo->prepare('DELETE FROM web_stories WHERE id = ?')->execute([$del_id]);
            $msg = 'Story deleted.';
        }
    }
}

$stories = $pdo->query('SELECT s.*, (SELECT COUNT(*) FROM web_story_pages p WHERE p.story_id = s.id) AS page_count FROM web_stories s ORDER BY s.updated_at DESC')->fetchAll(PDO::FETCH_ASSOC);
$templates = ws_get_templates();
$csrf = generate_csrf();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Web Stories — Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/editor.css?v=2">
</head>
<body class="ws-editor-body">
<header class="ws-ed-top">
    <a href="../../dashboard.php" class="ws-ed-back"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    <h1><i class="fa-solid fa-bolt"></i> Web Stories</h1>
    <div class="ws-ed-actions">
        <a href="settings.php" class="ws-btn ghost"><i class="fa-solid fa-gear"></i> Settings</a>
        <a href="../index.php" class="ws-btn ghost" target="_blank"><i class="fa-solid fa-eye"></i> Public</a>
        <a href="create.php" class="ws-btn primary"><i class="fa-solid fa-plus"></i> New Story</a>
    </div>
</header>

<main class="ws-list-page">
    <?php if ($msg): ?><div class="ws-flash ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div class="ws-dash-cta">
        <div>
            <strong>Ready for a new story?</strong>
            <p class="ws-hint" style="margin:4px 0 0">Template → Edit slides → SEO → Publish</p>
        </div>
        <a href="create.php" class="ws-btn primary"><i class="fa-solid fa-plus"></i> New Story</a>
    </div>

    <?php if (empty($stories)): ?>
        <p class="ws-hint">No stories yet — start with a template:</p>
        <div class="ws-templates">
            <?php foreach ($templates as $key => $tpl): ?>
            <a class="ws-tpl-card" href="editor.php?new=1&amp;template=<?= urlencode($key) ?>">
                <strong><?= htmlspecialchars($tpl['label']) ?></strong>
                <span><?= htmlspecialchars($tpl['category'] ?? '') ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <table class="ws-table">
            <thead><tr><th>Story</th><th>Slides</th><th>Status</th><th>Live</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($stories as $s): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($s['title']) ?></strong><br>
                    <span class="ws-hint">/web_stories/story.php?slug=<?= htmlspecialchars($s['slug']) ?></span>
                </td>
                <td><?= (int)$s['page_count'] ?></td>
                <td><?= $s['is_published'] ? '<span class="ws-badge pub">Published</span>' : '<span class="ws-badge draft">Draft</span>' ?></td>
                <td><button type="button" class="ws-toggle <?= $s['is_published'] ? 'on' : '' ?>" data-id="<?= (int)$s['id'] ?>"></button></td>
                <td style="white-space:nowrap">
                    <a href="editor.php?edit=<?= (int)$s['id'] ?>" class="ws-btn">Edit</a>
                    <?php if ($s['is_published']): ?>
                    <a href="../story.php?slug=<?= urlencode($s['slug']) ?>" class="ws-btn" target="_blank">View</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
<script>
(function() {
    var csrf = <?= json_encode($csrf) ?>;
    document.querySelectorAll('.ws-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = btn.getAttribute('data-id');
            var next = btn.classList.contains('on') ? 0 : 1;
            var fd = new FormData();
            fd.append('csrf_token', csrf);
            fd.append('action', 'toggle');
            fd.append('tid', id);
            fd.append('val', next);
            fetch('index.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.ok) btn.classList.toggle('on', !!next);
                    else if (d.error) alert(d.error);
                });
        });
    });
})();
</script>
</body>
</html>
