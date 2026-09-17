<?php
$qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: /curated_ai_prompts.php' . $qs, true, 301);
exit();
