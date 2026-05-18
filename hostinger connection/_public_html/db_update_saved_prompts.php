<?php
require 'db.php';

echo "<h2>Hostinger DB Updater for Saved Prompts</h2>";

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS `saved_prompts` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `prompt_id` int(11) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `user_prompt_unique` (`user_id`,`prompt_id`),
      KEY `user_id` (`user_id`),
      KEY `prompt_id` (`prompt_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);
    echo "<p style='color:green;'>✅ Successfully created saved_prompts table!</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Error creating table: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<br><a href='index.php'>Go to Homepage</a>";
?>
