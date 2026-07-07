<?php
$q = $_GET['edit'] ?? '';
if ($q === 'new' || $q === '') {
    $dest = 'web_stories/admin/' . ($q === 'new' ? 'create.php' : '');
} else {
    $dest = 'web_stories/admin/editor.php?edit=' . urlencode($q);
}
header('Location: ' . $dest, true, 302);
exit;
