<?php
require 'db.php';
$stmt = $pdo->query("DESCRIBE saved_prompts");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
