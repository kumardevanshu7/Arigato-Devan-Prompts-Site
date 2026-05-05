<?php
session_start();
require_once 'db.php';

// Protect endpoint
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    exit("Access Denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prompt_id'])) {
    $prompt_id = (int)$_POST['prompt_id'];

    // First fetch the image path so we can delete the file
    $stmt = $pdo->prepare("SELECT image_path FROM prompts WHERE id = ?");
    $stmt->execute([$prompt_id]);
    $prompt = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prompt) {
        $image_path = $prompt['image_path'];
        
        // Try to delete the file
        if (!empty($image_path) && file_exists($image_path)) {
            unlink($image_path);
        }

        // Delete from database
        $delStmt = $pdo->prepare("DELETE FROM prompts WHERE id = ?");
        if ($delStmt->execute([$prompt_id])) {
            // Also delete associated likes
            $pdo->prepare("DELETE FROM likes WHERE prompt_id = ?")->execute([$prompt_id]);
            
            $_SESSION['success_msg'] = "Prompt deleted successfully!";
        } else {
            $_SESSION['error_msg'] = "Database error while deleting the prompt.";
        }
    } else {
        $_SESSION['error_msg'] = "Prompt not found.";
    }
} else {
    $_SESSION['error_msg'] = "Invalid request.";
}

header("Location: dashboard.php");
exit();
?>

