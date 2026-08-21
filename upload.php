<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once "db.php";

// â”€â”€ Auto resize + convert to WebP on upload â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function resizeToWebP(string $src, int $maxW = 800, int $maxH = 800, int $quality = 82): string {
    if (!file_exists($src)) return $src;
    $info = @getimagesize($src);
    if (!$info) return $src;
    [$origW, $origH, $type] = [$info[0], $info[1], $info[2]];

    // Check actual GD WebP compile-time support (not just function_exists)
    $gdInfo      = function_exists('gd_info') ? gd_info() : [];
    $webpSupport = !empty($gdInfo['WebP Support']);

    // Load source image
    if ($type === IMAGETYPE_JPEG) {
        $img = @imagecreatefromjpeg($src);
    } elseif ($type === IMAGETYPE_PNG) {
        $img = @imagecreatefrompng($src);
    } elseif ($type === IMAGETYPE_GIF) {
        $img = @imagecreatefromgif($src);
    } elseif ($type === IMAGETYPE_WEBP && $webpSupport) {
        $img = @imagecreatefromwebp($src);
    } else {
        return $src; // unsupported type or WebP without GD support
    }
    if (!$img) return $src;

    // Calculate new dimensions (maintain aspect ratio, never upscale)
    $ratio = min($maxW / $origW, $maxH / $origH, 1.0);
    $newW = (int)round($origW * $ratio);
    $newH = (int)round($origH * $ratio);
    $resized = imagecreatetruecolor($newW, $newH);
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
    imagedestroy($img);

    // Save as WebP if supported, else JPEG fallback
    if ($webpSupport) {
        $dest = preg_replace('/\.[^.]+$/', '.webp', $src);
        imagewebp($resized, $dest, $quality);
    } else {
        $dest = preg_replace('/\.[^.]+$/', '.jpg', $src);
        imagejpeg($resized, $dest, $quality);
    }
    imagedestroy($resized);
    if ($dest !== $src && file_exists($src)) @unlink($src);
    return $dest;
}

/**
 * Validate, store and re-encode one uploaded image.
 *
 * @throws RuntimeException when the upload is invalid.
 */
function storePromptImage(array $file, string $directory, string $prefix): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed. Please choose the image again.');
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Image too large. Maximum allowed size is 5MB per image.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
    if ($finfo) finfo_close($finfo);
    $mime_to_ext = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    if (!$mime || !isset($mime_to_ext[$mime])) {
        throw new RuntimeException('Invalid image format. Use JPG, PNG, GIF or WebP.');
    }

    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Image folder could not be created.');
    }

    $path = rtrim($directory, '/\\') . '/' . uniqid($prefix, true) . '.' . $mime_to_ext[$mime];
    if (!move_uploaded_file($file['tmp_name'], $path)) {
        throw new RuntimeException('Failed to save uploaded image.');
    }

    return resizeToWebP($path);
}


// Protect endpoint â€” must be logged in AND be an admin
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $title = trim($_POST["title"] ?? "");
    $tag = trim($_POST["tag"] ?? "");
    $prompt_text = trim($_POST["prompt_text"] ?? "");
    $reel_link = trim($_POST["reel_link"] ?? "");
    $prompt_type = trim($_POST["prompt_type"] ?? "secret"); // secret, unreleased, already_uploaded, direct, solo
    $bwi_raw = trim($_POST["best_works_in"] ?? "");
    $best_works_in = in_array($bwi_raw, ["nano_banana", "chatgpt"]) ? $bwi_raw : null;
    $has_assets = isset($_POST["has_assets"]) && $_POST["has_assets"] === "1";
    $asset_title = $has_assets ? trim($_POST["asset_title"] ?? "") : null;
    $asset_images_json = null;
    $description = trim($_POST["description"] ?? "");
    if (mb_strlen($description) > 160) {
        $description = mb_substr($description, 0, 160);
    }
    $about_prompt = trim($_POST["about_prompt"] ?? "");
    if ($about_prompt !== "") {
        $about_words = preg_split('/\s+/u', $about_prompt, -1, PREG_SPLIT_NO_EMPTY);
        if (count($about_words) > 200) {
            $about_prompt = implode(' ', array_slice($about_words, 0, 200));
        }
    }
    $meta_keywords = trim($_POST["meta_keywords"] ?? "");
    if ($meta_keywords !== "") {
        $kw_parts = array_values(array_filter(array_map("trim", explode(",", $meta_keywords)), static function ($k) {
            return $k !== "";
        }));
        if (count($kw_parts) > 10) {
            $kw_parts = array_slice($kw_parts, 0, 10);
        }
        $meta_keywords = implode(", ", $kw_parts);
    }
    $solo_before_image = null;
    $solo_examples_json = null;

    // Validate prompt_type
    $valid_types = ["secret", "unreleased", "already_uploaded", "direct", "solo", "premium"];
    if (!in_array($prompt_type, $valid_types)) {
        $prompt_type = "secret";
    }
    if ($prompt_type === "solo") {
        $tag_list = array_filter(array_map("trim", explode(",", $tag)));
        $has_solo_tag = false;
        foreach ($tag_list as $existing_tag) {
            if (strtolower($existing_tag) === "solo") {
                $has_solo_tag = true;
                break;
            }
        }
        if (!$has_solo_tag) {
            array_unshift($tag_list, "Solo");
            $tag = implode(",", $tag_list);
        }
    }
    $is_trial = isset($_POST['is_trial']) ? 1 : 0;

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
    } else if ($prompt_type === "direct" || $prompt_type === "solo" || $prompt_type === "premium") {
        $allowed_taps = ["09", "11", "19", "21", "37", "77"];
        $requested_taps = str_pad((string)(int)($_POST["direct_taps"] ?? "09"), 2, "0", STR_PAD_LEFT);
        $unlock_code = in_array($requested_taps, $allowed_taps, true) ? $requested_taps : "09";
        if (empty($title) || empty($tag) || empty($prompt_text)) {
            $_SESSION["error_msg"] = "All fields are required!";
            header("Location: upload_prompt.php");
            exit();
        }
    } else {
        // No code needed for unreleased / already_uploaded
        $unlock_code = "XXXXXX";
        if (empty($title) || empty($tag) || empty($prompt_text)) {
            $_SESSION["error_msg"] = "All fields are required!";
            header("Location: upload_prompt.php");
            exit();
        }
    }

    // Handle cover image upload. SOLO uses its result/after image as the cover.
    $cover_field = $prompt_type === "solo" ? "solo_after_image" : "image";
    if (
        !isset($_FILES[$cover_field]) ||
        $_FILES[$cover_field]["error"] !== UPLOAD_ERR_OK
    ) {
        $err_code = $_FILES[$cover_field]["error"] ?? "N/A";
        $_SESSION[
            "error_msg"
        ] = ($prompt_type === "solo" ? "SOLO result image" : "Cover image") . " upload failed! Error code: $err_code. Make sure file size is under PHP limit.";
        header("Location: upload_prompt.php");
        exit();
    }

    $cover_file = $_FILES[$cover_field];
    $upload_dir = $prompt_type === "solo" ? "uploads/solo/after/" : "uploads/";
    // Create dir if somehow deleted
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_info = pathinfo($cover_file["name"]);
    $ext = strtolower($file_info["extension"]);

    // Security Checks: File Size and MIME type
    if ($cover_file["size"] > 5 * 1024 * 1024) {
        $_SESSION["error_msg"] = "Image too large! Maximum allowed size is 5MB.";
        header("Location: upload_prompt.php");
        exit();
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $cover_file["tmp_name"]);
    if (!str_starts_with($mime, 'image/')) {
        $_SESSION["error_msg"] = "Invalid file type. Only actual images are allowed.";
        finfo_close($finfo);
        header("Location: upload_prompt.php");
        exit();
    }
    finfo_close($finfo);

    $allowed_ext = ["jpg", "jpeg", "png", "gif", "webp"];
    if (!in_array($ext, $allowed_ext)) {
        $_SESSION[
            "error_msg"
        ] = "Invalid image format! Use JPG, PNG, GIF, or WebP. (Got: .$ext)";
        header("Location: upload_prompt.php");
        exit();
    }

    // Generate unique filename
    $new_filename = uniqid($prompt_type === "solo" ? "solo_after_" : "img_") . "." . $ext;
    $target_file = $upload_dir . $new_filename;

    if (move_uploaded_file($cover_file["tmp_name"], $target_file)) {
        $target_file = resizeToWebP($target_file);
        $new_filename = basename($target_file);
        if ($prompt_type === "solo") {
            $solo_created_paths = [];
            try {
                if (!isset($_FILES["solo_before_image"])) {
                    throw new RuntimeException("SOLO before image is required.");
                }
                $solo_before_image = storePromptImage(
                    $_FILES["solo_before_image"],
                    "uploads/solo/before/",
                    "solo_before_"
                );
                $solo_created_paths[] = $solo_before_image;

                $example_pairs = [];
                $before_files = $_FILES["solo_example_before"] ?? null;
                $after_files  = $_FILES["solo_example_after"] ?? null;
                $example_count = max(
                    is_array($before_files["name"] ?? null) ? count($before_files["name"]) : 0,
                    is_array($after_files["name"] ?? null) ? count($after_files["name"]) : 0
                );
                if ($example_count > 5) {
                    throw new RuntimeException("A maximum of 5 SOLO examples is allowed.");
                }

                for ($i = 0; $i < $example_count; $i++) {
                    $before_error = $before_files["error"][$i] ?? UPLOAD_ERR_NO_FILE;
                    $after_error  = $after_files["error"][$i] ?? UPLOAD_ERR_NO_FILE;
                    if ($before_error === UPLOAD_ERR_NO_FILE && $after_error === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    if ($before_error !== UPLOAD_ERR_OK || $after_error !== UPLOAD_ERR_OK) {
                        throw new RuntimeException("Every SOLO example needs both a before and an after image.");
                    }

                    $before_file = [
                        "name"     => $before_files["name"][$i],
                        "type"     => $before_files["type"][$i],
                        "tmp_name" => $before_files["tmp_name"][$i],
                        "error"    => $before_error,
                        "size"     => $before_files["size"][$i],
                    ];
                    $after_file = [
                        "name"     => $after_files["name"][$i],
                        "type"     => $after_files["type"][$i],
                        "tmp_name" => $after_files["tmp_name"][$i],
                        "error"    => $after_error,
                        "size"     => $after_files["size"][$i],
                    ];
                    $example_before_path = storePromptImage($before_file, "uploads/solo/examples/before/", "solo_ex_before_");
                    $solo_created_paths[] = $example_before_path;
                    $example_after_path = storePromptImage($after_file, "uploads/solo/examples/after/", "solo_ex_after_");
                    $solo_created_paths[] = $example_after_path;
                    $example_pairs[] = [
                        "before" => $example_before_path,
                        "after"  => $example_after_path,
                    ];
                }
                $solo_examples_json = $example_pairs ? json_encode($example_pairs) : null;
            } catch (RuntimeException $e) {
                @unlink($target_file);
                foreach ($solo_created_paths as $created_path) {
                    @unlink($created_path);
                }
                $_SESSION["error_msg"] = $e->getMessage();
                header("Location: upload_prompt.php");
                exit();
            }
        }
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
                if (move_uploaded_file($tmp, $afname)) { $afname = resizeToWebP($afname); $asset_paths[] = $afname; }
            }
            if (!empty($asset_paths)) { $asset_images_json = json_encode($asset_paths); }
        }

        // Handle extra prompts (2 and 3)
        $extra_prompts_data = [];
        for ($ep = 2; $ep <= 3; $ep++) {
            $ep_text = trim($_POST["extra_prompt_{$ep}_text"] ?? '');
            if (empty($ep_text)) continue;
            $ep_image_path = null;
            if (isset($_FILES["extra_prompt_{$ep}_image"]) && $_FILES["extra_prompt_{$ep}_image"]["error"] === UPLOAD_ERR_OK) {
                $ep_ext = strtolower(pathinfo($_FILES["extra_prompt_{$ep}_image"]["name"], PATHINFO_EXTENSION));
                if (in_array($ep_ext, $allowed_ext)) {
                    $ep_fname = "uploads/" . uniqid("ep_") . "." . $ep_ext;
                    if (move_uploaded_file($_FILES["extra_prompt_{$ep}_image"]["tmp_name"], $ep_fname)) {
                        $ep_image_path = resizeToWebP($ep_fname);
                    }
                }
            }
            $ep_title = trim($_POST["extra_prompt_{$ep}_title"] ?? '');
            $extra_prompts_data[] = ['title' => $ep_title ?: null, 'prompt_text' => $ep_text, 'image_path' => $ep_image_path];
        }
        $extra_prompts_json = !empty($extra_prompts_data) ? json_encode($extra_prompts_data) : null;

        // Insert into DB
        require_once "slug_helper.php";
        $new_slug = uniqueSlug($pdo, $title);
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO prompts (title, slug, tag, prompt_text, unlock_code, image_path, reel_link, prompt_type, best_works_in, asset_title, asset_images, extra_prompts, is_trial, description, about_prompt, meta_keywords, solo_before_image, solo_examples) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            );
            $stmt->execute([
                $title,
                $new_slug,
                $tag,
                $prompt_text,
                $unlock_code,
                $target_file,
                $reel_link,
                $prompt_type,
                $best_works_in,
                $asset_title,
                $asset_images_json,
                $extra_prompts_json,
                $is_trial,
                $description ?: null,
                $about_prompt ?: null,
                $meta_keywords,
                $solo_before_image,
                $solo_examples_json,
            ]);

            $_SESSION["success_msg"] =
                "Prompt successfully added to the Verse!";

            // Send FCM push notification to all subscribers
            if (file_exists(__DIR__ . '/fcm_notify.php')) {
                require_once __DIR__ . '/fcm_notify.php';
                $fcm_body = match($prompt_type) {
                    'solo'    => 'A new SOLO AI photo prompt just dropped! Tap to see the transformation.',
                    'premium' => '👑 A new exclusive Premium Romantic prompt just dropped! Check it out.',
                    default   => 'A new AI couple prompt just dropped! Tap to check it out. 💫'
                };
                @sendFCMNotification(
                    '✨ New Prompt: ' . $title,
                    $fcm_body,
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

