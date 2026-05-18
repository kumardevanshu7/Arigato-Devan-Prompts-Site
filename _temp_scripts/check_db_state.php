<?php
require 'db.php';
$cols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo $c['Field'] . "\n";

echo "\n--- Saved prompts table ---\n";
$rows = $pdo->query("SELECT * FROM saved_prompts LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) echo "(empty)\n";
foreach ($rows as $r) print_r($r);

echo "\n--- Users ---\n";
$us = $pdo->query("SELECT id, email FROM users LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
foreach ($us as $u) echo "id=" . $u['id'] . " email=" . $u['email'] . "\n";
?>
