<?php
require_once 'db.php';

try {
    $pdo->exec("ALTER TABLE saved_prompts CHANGE saved_at created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "Column renamed successfully.";
} catch (PDOException $e) {
    echo "Error renaming column: " . $e->getMessage();
}
?>
