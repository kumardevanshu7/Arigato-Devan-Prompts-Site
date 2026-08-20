<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
$target = '/curated_ai_prompts.php' . ($query !== '' ? '?' . $query : '');
header('Location: ' . $target, true, 301);
exit;
