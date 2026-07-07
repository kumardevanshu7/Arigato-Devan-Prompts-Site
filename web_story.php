<?php
$q = $_SERVER['QUERY_STRING'] ?? '';
header('Location: web_stories/story.php' . ($q ? '?' . $q : ''), true, 301);
exit;
