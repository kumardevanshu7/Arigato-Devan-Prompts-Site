<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
$target = '/curated_manage.php' . ($query !== '' ? '?' . $query : '');
header('Location: ' . $target, true, 302);
exit;
