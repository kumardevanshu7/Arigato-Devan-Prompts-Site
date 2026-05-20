<?php
session_start();
require_once "db.php";

// Protect endpoint — must be logged in AND be an admin
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $tag = trim($_POST["tag"] ?? "");
    $prompt_text = trim($_POST["prompt_text"] ?? "");
    $reel_link = trim($_POST["reel_link"] ?? "");
    $prompt_type = trim($_POST["prompt_type"] ?? "secret"); // 'secret', 'unreleased', 'insta_viral'
    $bwi_raw = trim($_POST["best_works_in"] ?? "");
    $best_works_in = in_array($bwi_raw, ["nano_banana", "chatgpt"]) ? $bwi_raw : null;
    $has_assets = isset($_POST["has_assets"]) && $_POST["has_assets"] === "1";
    $asset_title = $has_assets ? trim($_POST["asset_title"] ?? "") : null;
    $asset_images_json = null;

    // Validate prompt_type
    $valid_types = ["secret", "unreleased", "insta_viral", "already_uploaded"];
    if (!in_array($prompt_type, $valid_types)) {
        $prompt_type = "secret";
    }

    // For secret type, unlock code is required
    if ($prompt_type === "secret") {
        $unlock_code = strtoupper(trim($_POST["unlock_code"] ?? ""));
        if (
            empty($title) ||
            empty($tag) ||
            empty($prompt_text) ||
            empty($unlock_code)
        ) {
            $_SESSION["error_msg"] = "All fields are required!";
            header("Location: upload_prompt.php");
            exit();
        }
        if (strlen($unlock_code) !== 6) {
            $_SESSION["error_msg"] =
                "Unlock code must be exactly 6 characters!";
            header("Location: upload_prompt.php");
            exit();
        }
        if (empty($reel_link)) {
            $_SESSION["error_msg"] =
                "Reel Link is required for Secret Code type.";
            header("Location: upload_prompt.php");
            exit();
        }
    } else {
        // No code needed for unreleased / insta_viral
        $unlock_code = "XXXXXX";
        if (empty($title) || empty($tag) || empty($prompt_text)) {
            $_SESSION["error_msg"] = "All fields are required!";
            header("Location: upload_prompt.php");
            exit();
        }
    }

    // Handle Image Upload
    if (
        !isset($_FILES["image"]) ||
        $_FILES["image"]["error"] !== UPLOAD_ERR_OK
    ) {
        $err_code = $_FILES["image"]["error"] ?? "N/A";
        $_SESSION[
            "error_msg"
        ] = "Image upload failed! Error code: $err_code. Make sure file size is under PHP limit.";
        header("Location: upload_prompt.php");
        exit();
    }

    $upload_dir = "uploads/";
    // Create dir if somehow deleted
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_info = pathinfo($_FILES["image"]["name"]);
    $ext = strtolower($file_info["extension"]);

    $allowed_ext = ["jpg", "jpeg", "png", "gif", "webp"];
    if (!in_array($ext, $allowed_ext)) {
        $_SESSION[
            "error_msg"
        ] = "Invalid image format! Use JPG, PNG, GIF, or WebP. (Got: .$ext)";
        header("Location: upload_prompt.php");
        exit();
    }

    // Generate unique filename
    $new_filename = uniqid("img_") . "." . $ext;
    $target_file = $upload_dir . $new_filename;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        // Handle asset images upload (max 2)
        if ($has_assets && isset($_FILES["asset_images"]) && !empty($_FILES["asset_images"]["name"][0])) {
            $asset_dir = "uploads/assets/";
            if (!is_dir($asset_dir)) {
                if (!mkdir($asset_dir, 0755, true)) {
                    $_SESSION["error_msg"] = "Asset folder could not be created. Please create 'uploads/assets/' directory on the server.";
                    header("Location: upload_prompt.php");
                    exit();
                }
            }
            $asset_paths = [];
            $allowed_asset_ext = ["jpg", "jpeg", "png", "gif", "webp"];
            foreach ($_FILES["asset_images"]["tmp_name"] as $i => $tmp) {
                if ($i >= 2) break;
                if ($_FILES["asset_images"]["error"][$i] !== UPLOAD_ERR_OK) continue;
                $aext = strtolower(pathinfo($_FILES["asset_images"]["name"][$i], PATHINFO_EXTENSION));
                if (!in_array($aext, $allowed_asset_ext)) continue;
                $afname = "uploads/assets/" . uniqid("asset_") . "." . $aext;
                if (move_uploaded_file($tmp, $afname)) { $asset_paths[] = $afname; }
            }
            if (!empty($asset_paths)) { $asset_images_json = json_encode($asset_paths); }
        }

        // Insert into DB
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO prompts (title, tag, prompt_text, unlock_code, image_path, reel_link, prompt_type, best_works_in, asset_title, asset_images) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            );
            $stmt->execute([
                $title,
                $tag,
                $prompt_text,
                $unlock_code,
                $target_file,
                $reel_link,
                $prompt_type,
                $best_works_in,
                $asset_title,
                $asset_images_json,
            ]);

            $_SESSION["success_msg"] =
                "Prompt successfully added to the Verse!";

            // Send FCM push notification to all subscribers
            if (file_exists(__DIR__ . '/fcm_notify.php')) {
                require_once __DIR__ . '/fcm_notify.php';
                @sendFCMNotification(
                    '✨ New Prompt: ' . $title,
                    'A new AI couple prompt just dropped! Tap to check it out. 💫',
                    'https://arigatodevan.com'
                );
            }
        } catch (PDOException $e) {
            $_SESSION["error_msg"] = "Database error: " . $e->getMessage();
        }
    } else {
        $_SESSION["error_msg"] =
            "Failed to move uploaded file. Check server write permissions on 'uploads/' folder.";
        header("Location: upload_prompt.php");
        exit();
    }

    header("Location: dashboard.php");
    exit();
} else {
    header("Location: dashboard.php");
    exit();
}
?>
