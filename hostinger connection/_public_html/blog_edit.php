<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

$id = (int) ($_GET["id"] ?? 0);
if (!$id) {
    header("Location: blog_admin.php");
    exit();
}

$error = "";
$success = $_SESSION["success_msg"] ?? "";
unset($_SESSION["success_msg"]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST["description"] ?? "");
    $content = $_POST["content"] ?? "";
    $content_hindi = $_POST["content_hindi"] ?? "";
    $meta_title = trim($_POST["meta_title"] ?? "");
    $meta_desc = trim($_POST["meta_description"] ?? "");
    $tags = trim($_POST["tags"] ?? "");
    $image_ratio = $_POST["image_ratio"] ?? "16:9";
    $publish = isset($_POST["publish"]) && $_POST["publish"] == "1" ? 1 : 0;
    
    if (!$title) {
        $error = "Blog title is required.";
    } else {
        // Custom Slug or Auto-generated Slug
        $custom_slug = trim($_POST["slug"] ?? "");
        if ($custom_slug !== "") {
            $slug = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "-", $custom_slug));
        } else {
            $slug = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "-", $title));
        }
        $slug = trim($slug, "-");
        if ($slug === "") {
            $slug = "blog-post-" . $id;
        }

        // Make unique (excluding current post ID)
        $exists = $pdo->prepare("SELECT id FROM blogs WHERE slug=? AND id != ?");
        $exists->execute([$slug, $id]);
        if ($exists->fetch()) {
            $slug .= "-" . rand(100, 999);
        }

        // Automatic SEO Meta Title Fallback
        if (empty($meta_title)) {
            $meta_title = $title;
        }

        // Automatic SEO Meta Description Fallback
        if (empty($meta_desc)) {
            $plain_content = strip_tags($content);
            $plain_content = html_entity_decode($plain_content, ENT_QUOTES, 'UTF-8');
            $plain_content = preg_replace('/\s+/', ' ', $plain_content);
            $meta_desc = mb_substr(trim($plain_content), 0, 155);
            if (mb_strlen($plain_content) > 155) {
                $meta_desc .= '...';
            }
        }

        $image_path = $_POST["current_image"] ?? "";
        if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $allowed = ["jpg", "jpeg", "png", "webp", "gif"];
            
            if (!in_array($ext, $allowed)) {
                $error = "Invalid file type. Allowed types: JPG, PNG, WEBP, GIF.";
            } elseif ($_FILES["image"]["size"] > 5 * 1024 * 1024) {
                $error = "Image too large! Maximum allowed size is 5MB.";
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES["image"]["tmp_name"]);
                finfo_close($finfo);
                
                if (!str_starts_with($mime, 'image/')) {
                    $error = "Invalid image file.";
                } else {
                    $fn = "uploads/blog_" . uniqid() . "." . $ext;
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $fn)) {
                        $image_path = $fn;
                    } else {
                        $error = "Could not save uploaded image.";
                    }
                }
            }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare(
                "UPDATE blogs SET title=?, slug=?, description=?, content=?, content_hindi=?, image_path=?, image_ratio=?, meta_title=?, meta_description=?, tags=?, is_published=?, updated_at=NOW() WHERE id=?"
            );
            $stmt->execute([
                $title,
                $slug,
                $description,
                $content,
                $content_hindi,
                $image_path,
                $image_ratio,
                $meta_title,
                $meta_desc,
                $tags,
                $publish,
                $id,
            ]);

            $_SESSION["success_msg"] = "Article updated successfully!";
            header("Location: blog_edit.php?id=" . $id);
            exit();
        }
    }
}

// Fetch existing blog post
$stmt = $pdo->prepare("SELECT * FROM blogs WHERE id=?");
$stmt->execute([$id]);
$bl = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$bl) {
    header("Location: blog_admin.php");
    exit();
}

$edit_error = $error ?: ($_SESSION["edit_error"] ?? "");
unset($_SESSION["edit_error"]);

// Author info
$author_name = $_SESSION["username"] ?? "Admin";
$author_avatar = $_SESSION["profile_image"] ?? "toplogo/logo01.webp";

// Format last updated date
$last_updated = !empty($bl["updated_at"]) ? date("M j, Y, g:i A", strtotime($bl["updated_at"])) : date("M j, Y, g:i A", strtotime($bl["created_at"]));
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit: <?= htmlspecialchars($bl["title"]) ?> &ndash; Arigato Editor</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lora:ital,wght@0,400;0,500;0,600;1,400&family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- TinyMCE 6 -->
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>

    <style>
        :root {
            --be-bg: #f1f5f9;
            --be-surface: #ffffff;
            --be-border: #e2e8f0;
            --be-border-focus: #3b82f6;
            --be-text-main: #0f172a;
            --be-text-sec: #475569;
            --be-text-muted: #94a3b8;
            --be-primary: #3b82f6;
            --be-primary-hover: #2563eb;
            --be-primary-light: #eff6ff;
            --be-success: #10b981;
            --be-success-light: #ecfdf5;
            --be-warning: #f59e0b;
            --be-danger: #ef4444;
            --be-danger-light: #fef2f2;
            --be-radius-sm: 8px;
            --be-radius-md: 12px;
            --be-radius-lg: 14px;
        }

        *, *::before, *::after {
            box-sizing: border-box !important;
            margin: 0;
            padding: 0;
        }

        html, body {
            height: 100vh !important;
            overflow: hidden !important;
            background-color: var(--be-bg) !important;
            color: var(--be-text-main) !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
            display: flex !important;
            flex-direction: column !important;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Top Header ── */
        .be-header {
            height: 54px !important;
            flex-shrink: 0 !important;
            background: #ffffff !important;
            border-bottom: 1px solid var(--be-border) !important;
            padding: 0 24px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            z-index: 100 !important;
        }

        .be-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .be-btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--be-text-sec);
            text-decoration: none;
            font-size: 0.84rem;
            font-weight: 600;
            padding: 6px 10px;
            border-radius: var(--be-radius-sm);
            background: #f1f5f9;
            transition: all 0.15s ease;
        }
        .be-btn-back:hover {
            color: var(--be-text-main);
            background: #e2e8f0;
        }

        .be-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.84rem;
            color: var(--be-text-muted);
        }
        .be-breadcrumb-active {
            color: var(--be-text-main);
            font-weight: 600;
            max-width: 320px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .be-header-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .be-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .be-status-published {
            background: var(--be-success-light);
            color: #065f46;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        .be-status-published .be-dot {
            width: 6px;
            height: 6px;
            background: var(--be-success);
            border-radius: 50%;
        }
        .be-status-draft {
            background: #f1f5f9;
            color: var(--be-text-sec);
            border: 1px solid var(--be-border);
        }
        .be-status-draft .be-dot {
            width: 6px;
            height: 6px;
            background: var(--be-text-muted);
            border-radius: 50%;
        }

        .be-btn-preview {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--be-text-sec);
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 600;
            padding: 6px 10px;
            border-radius: var(--be-radius-sm);
            border: 1px solid var(--be-border);
            background: #fff;
            transition: all 0.15s;
        }
        .be-btn-preview:hover {
            color: var(--be-primary);
            border-color: var(--be-primary);
            background: var(--be-primary-light);
        }

        .be-btn-sec {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            border-radius: var(--be-radius-sm);
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--be-text-sec);
            background: #ffffff;
            border: 1px solid var(--be-border);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
        }
        .be-btn-sec:hover {
            background: #f8fafc;
            color: var(--be-text-main);
        }

        .be-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: var(--be-radius-sm);
            font-size: 0.84rem;
            font-weight: 600;
            color: #ffffff;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border: none;
            cursor: pointer;
            box-shadow: 0 1px 4px rgba(37, 99, 235, 0.25);
            transition: all 0.15s ease;
        }
        .be-btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            transform: translateY(-1px);
        }

        /* ── Full-Height 2-Column App Grid ── */
        .be-layout {
            flex: 1 !important;
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) 360px !important;
            gap: 16px !important;
            padding: 14px 20px 14px !important;
            height: calc(100vh - 54px) !important;
            overflow: hidden !important;
            max-width: 1600px !important;
            width: 100% !important;
            margin: 0 auto !important;
        }

        /* ── Left Writing Canvas (Own Clean Scroll) ── */
        .be-paper {
            background: #ffffff !important;
            border: 1px solid var(--be-border) !important;
            border-radius: var(--be-radius-md) !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03) !important;
            height: 100% !important;
            overflow-y: auto !important;
            padding: 32px 42px !important;
            display: flex !important;
            flex-direction: column !important;
        }

        /* ── Right Inspector Panel (Own Clean Scroll) ── */
        .be-panel {
            background: #ffffff !important;
            border: 1px solid var(--be-border) !important;
            border-radius: var(--be-radius-md) !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03) !important;
            height: 100% !important;
            overflow-y: auto !important;
            padding: 24px 20px !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 18px !important;
        }

        /* Modern Subtle Scrollbars */
        .be-paper::-webkit-scrollbar,
        .be-panel::-webkit-scrollbar {
            width: 5px;
        }
        .be-paper::-webkit-scrollbar-thumb,
        .be-panel::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }
        .be-paper::-webkit-scrollbar-thumb:hover,
        .be-panel::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .be-title-input {
            width: 100%;
            border: none !important;
            outline: none !important;
            background: transparent !important;
            box-shadow: none !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-weight: 800 !important;
            font-size: 2rem !important;
            line-height: 1.25 !important;
            color: var(--be-text-main) !important;
            letter-spacing: -0.02em !important;
            margin-bottom: 10px !important;
            padding: 0 !important;
        }
        .be-title-input::placeholder {
            color: #cbd5e1 !important;
        }

        .be-subtitle-input {
            width: 100%;
            border: none !important;
            outline: none !important;
            background: transparent !important;
            box-shadow: none !important;
            font-family: 'Inter', sans-serif !important;
            font-size: 0.98rem !important;
            line-height: 1.5 !important;
            color: var(--be-text-sec) !important;
            resize: none !important;
            margin-bottom: 14px !important;
            min-height: 40px;
            padding: 0 !important;
        }
        .be-subtitle-input::placeholder {
            color: #cbd5e1 !important;
        }

        .be-meta-bar {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 14px;
            margin-bottom: 14px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.78rem;
            color: var(--be-text-muted);
            font-weight: 500;
        }
        .be-meta-bar span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Sleek Formatting Toolbar */
        .be-toolbar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(8px);
            border: 1px solid var(--be-border);
            border-radius: var(--be-radius-sm);
            padding: 5px 8px;
            margin-bottom: 18px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            display: flex;
            align-items: center;
            gap: 3px;
            flex-wrap: wrap;
        }

        .be-tb-btn {
            background: transparent;
            border: none;
            color: var(--be-text-sec);
            padding: 5px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            height: 28px;
            transition: all 0.12s;
        }
        .be-tb-btn:hover {
            background: #f1f5f9;
            color: var(--be-primary);
        }

        .be-tb-divider {
            width: 1px;
            height: 16px;
            background: var(--be-border);
            margin: 0 3px;
        }

        .be-tb-select {
            border: 1px solid transparent;
            background: transparent;
            color: var(--be-text-sec);
            font-family: inherit;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 4px 6px;
            border-radius: 4px;
            cursor: pointer;
            outline: none;
            transition: all 0.12s;
        }
        .be-tb-select:hover, .be-tb-select:focus {
            background: #f1f5f9;
            border-color: var(--be-border);
            color: var(--be-text-main);
        }

        /* Inspector Compact Sections */
        .be-sec-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--be-text-main);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 14px;
        }
        .be-sec-title i {
            color: var(--be-primary);
            font-size: 0.85rem;
        }

        .be-sec-divider {
            height: 1px;
            background: var(--be-border);
            margin: 6px 0 16px;
        }

        .be-form-group {
            display: flex !important;
            flex-direction: column !important;
            gap: 7px !important;
            margin-bottom: 16px !important;
        }

        .be-form-label {
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            color: var(--be-text-sec) !important;
            text-transform: uppercase !important;
            letter-spacing: 0.4px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            margin-bottom: 0 !important;
        }

        .be-form-input, .be-form-textarea, .be-form-select {
            width: 100% !important;
            background: #ffffff !important;
            border: 1px solid var(--be-border) !important;
            border-radius: var(--be-radius-sm) !important;
            padding: 9px 12px !important;
            font-family: inherit !important;
            font-size: 0.85rem !important;
            color: var(--be-text-main) !important;
            outline: none !important;
            box-shadow: none !important;
            transition: all 0.15s !important;
        }
        .be-form-input:focus, .be-form-textarea:focus, .be-form-select:focus {
            border-color: var(--be-primary) !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.12) !important;
        }

        .be-form-hint {
            font-size: 0.72rem;
            color: var(--be-text-muted);
            line-height: 1.35;
        }

        .be-switch-row {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 4px 0 !important;
        }
        .be-switch-label {
            font-size: 0.84rem;
            font-weight: 600;
            color: var(--be-text-main);
        }
        .be-switch-desc {
            font-size: 0.68rem;
            color: var(--be-text-muted);
        }

        .be-switch {
            position: relative;
            display: inline-block;
            width: 36px;
            height: 20px;
            flex-shrink: 0;
        }
        .be-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .be-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .2s;
            border-radius: 20px;
        }
        .be-slider:before {
            position: absolute;
            content: "";
            height: 14px;
            width: 14px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .2s;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        }
        .be-switch input:checked + .be-slider {
            background-color: var(--be-primary);
        }
        .be-switch input:checked + .be-slider:before {
            transform: translateX(16px);
        }

        .be-serp-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: var(--be-radius-sm);
            padding: 13px 14px;
            font-family: arial, sans-serif;
            margin-bottom: 6px;
        }
        .be-serp-site {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 5px;
        }
        .be-serp-favicon {
            width: 16px;
            height: 16px;
            border-radius: 50%;
        }
        .be-serp-sitename {
            font-size: 0.73rem;
            color: #202124;
            font-weight: 500;
        }
        .be-serp-url {
            font-size: 0.7rem;
            color: #4d5156;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .be-serp-title {
            color: #1a0dab;
            font-size: 0.95rem;
            font-weight: 400;
            line-height: 1.35;
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .be-serp-desc {
            color: #4d5156;
            font-size: 0.78rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .be-char-bar {
            height: 4px;
            width: 100%;
            background: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 5px;
        }
        .be-char-fill {
            height: 100%;
            width: 0%;
            background: var(--be-primary);
            transition: width 0.2s, background-color 0.2s;
        }
        .be-char-fill.good { background: var(--be-success); }
        .be-char-fill.warn { background: var(--be-warning); }
        .be-char-fill.bad { background: var(--be-danger); }

        .be-checklist {
            display: flex;
            flex-direction: column;
            gap: 7px;
            background: #f8fafc;
            border: 1px solid var(--be-border);
            border-radius: var(--be-radius-sm);
            padding: 11px 13px;
            margin: 6px 0 16px;
        }
        .be-check-item {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 0.76rem;
            font-weight: 600;
            color: var(--be-text-sec);
        }
        .be-check-item i {
            font-size: 0.82rem;
        }
        .be-check-pass { color: #065f46; }
        .be-check-pass i { color: var(--be-success); }
        .be-check-fail { color: var(--be-text-muted); }
        .be-check-fail i { color: #cbd5e1; }
        .be-check-warn { color: #b45309; }
        .be-check-warn i { color: var(--be-warning); }

        .be-tags-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 6px 8px;
            border: 1px solid var(--be-border);
            border-radius: var(--be-radius-sm);
            background: #fff;
            min-height: 42px;
            align-items: center;
        }
        .be-tag-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--be-primary-light);
            color: var(--be-primary);
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.76rem;
            font-weight: 600;
        }
        .be-tag-remove {
            cursor: pointer;
            font-size: 0.85rem;
            opacity: 0.7;
        }
        .be-tag-remove:hover {
            opacity: 1;
            color: var(--be-danger);
        }
        .be-tag-input {
            flex: 1;
            min-width: 90px;
            border: none !important;
            outline: none !important;
            font-family: inherit !important;
            font-size: 0.84rem !important;
            padding: 3px !important;
            box-shadow: none !important;
            background: transparent !important;
        }

        .be-dropzone {
            border: 2px dashed #cbd5e1;
            border-radius: var(--be-radius-sm);
            padding: 14px;
            text-align: center;
            cursor: pointer;
            background: #fafbfc;
            transition: all 0.15s;
        }
        .be-dropzone:hover {
            border-color: var(--be-primary);
            background: var(--be-primary-light);
        }
        .be-dropzone-preview {
            width: 100%;
            max-height: 140px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 6px;
            display: block;
        }
        .be-dropzone-text {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--be-text-sec);
        }

        .be-btn-delete {
            width: 100%;
            padding: 7px 10px;
            background: #fff;
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--be-danger);
            border-radius: var(--be-radius-sm);
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.15s;
        }
        .be-btn-delete:hover {
            background: var(--be-danger-light);
            border-color: var(--be-danger);
        }

        .be-alert {
            padding: 10px 14px;
            border-radius: var(--be-radius-sm);
            font-size: 0.84rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }
        .be-alert-success {
            background: var(--be-success-light);
            color: #065f46;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .be-alert-error {
            background: var(--be-danger-light);
            color: #991b1b;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        @media (max-width: 1024px) {
            html, body {
                height: auto !important;
                overflow: auto !important;
            }
            .be-layout {
                grid-template-columns: 1fr !important;
                height: auto !important;
                overflow: visible !important;
            }
            .be-paper, .be-panel {
                height: auto !important;
                overflow: visible !important;
            }
        }
    </style>
</head>
<body>

<form method="POST" action="blog_edit.php?id=<?= $id ?>" enctype="multipart/form-data" id="blogForm" style="display:flex; flex-direction:column; height:100vh; overflow:hidden;">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">
    <input type="hidden" name="current_image" value="<?= htmlspecialchars($bl["image_path"]) ?>">

    <!-- ── App Top Navigation Bar ── -->
    <header class="be-header">
        <div class="be-header-left">
            <a href="blog_admin.php" class="be-btn-back" title="Back to All Blogs">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Blogs</span>
            </a>
            <div class="be-breadcrumb">
                <i class="fa-solid fa-chevron-right" style="font-size:0.6rem;"></i>
                <span class="be-breadcrumb-active" title="<?= htmlspecialchars($bl["title"]) ?>">
                    <?= htmlspecialchars($bl["title"]) ?>
                </span>
            </div>
        </div>

        <div class="be-header-right">
            <div class="be-status-pill <?= $bl["is_published"] ? 'be-status-published' : 'be-status-draft' ?>" id="statusIndicatorBadge">
                <span class="be-dot"></span>
                <span id="statusIndicatorText"><?= $bl["is_published"] ? 'Published' : 'Draft' ?></span>
            </div>

            <?php if (!empty($bl["slug"])): ?>
                <a href="blog.php?slug=<?= urlencode($bl["slug"]) ?>" target="_blank" class="be-btn-preview" title="Open preview in new tab">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Preview</span>
                </a>
            <?php endif; ?>

            <a href="blog_admin.php" class="be-btn-sec">Cancel</a>
            <button type="submit" class="be-btn-primary" id="btnSubmitForm">
                <i class="fa-solid fa-check"></i>
                <span>Update</span>
            </button>
        </div>
    </header>

    <!-- ── Main 2-Column Full App Layout ── -->
    <div class="be-layout">
        
        <!-- ── Left Column: Writing Canvas ── -->
        <main class="be-paper">
            <?php if ($success): ?>
                <div class="be-alert be-alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($edit_error): ?>
                <div class="be-alert be-alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= htmlspecialchars($edit_error) ?>
                </div>
            <?php endif; ?>

            <!-- Title -->
            <input type="text" 
                   class="be-title-input" 
                   id="bc-title" 
                   name="title" 
                   value="<?= htmlspecialchars($bl["title"]) ?>" 
                   placeholder="Article title..." 
                   required 
                   autocomplete="off">

            <!-- Summary / Excerpt -->
            <textarea class="be-subtitle-input" 
                      id="bc-desc" 
                      name="description" 
                      placeholder="Write a clear, engaging excerpt or short summary for card previews..." 
                      rows="2"><?= htmlspecialchars($bl["description"] ?? "") ?></textarea>

            <!-- Metadata info bar -->
            <div class="be-meta-bar">
                <span><i class="fa-regular fa-calendar"></i> <?= $last_updated ?></span>
                <span><i class="fa-solid fa-user-pen"></i> <?= htmlspecialchars($author_name) ?></span>
                <span id="metaWordCount"><i class="fa-solid fa-file-lines"></i> 0 words</span>
                <span id="metaReadTime"><i class="fa-regular fa-clock"></i> ~0 min read</span>
            </div>

            <!-- Sleek Minimalist Formatting Toolbar -->
            <div class="be-toolbar">
                <select class="be-tb-select" onchange="fmtBlock(this.value); this.selectedIndex=0;" title="Headings">
                    <option value="">Style</option>
                    <option value="p">Paragraph</option>
                    <option value="h1">Heading 1</option>
                    <option value="h2">Heading 2</option>
                    <option value="h3">Heading 3</option>
                    <option value="blockquote">Quote</option>
                </select>

                <div class="be-tb-divider"></div>

                <select class="be-tb-select" onchange="applyFontFamily(this.value); this.selectedIndex=0;" title="Font Family">
                    <option value="">Font</option>
                    <option value="Lora, Georgia, serif">Editorial Serif (Lora)</option>
                    <option value="'Playfair Display', serif">Display Serif (Playfair)</option>
                    <option value="'Plus Jakarta Sans', sans-serif">Modern Bold (Jakarta)</option>
                    <option value="'Inter', sans-serif">Clean Sans (Inter)</option>
                    <option value="'JetBrains Mono', monospace">Monospace (Code)</option>
                </select>

                <div class="be-tb-divider"></div>

                <button type="button" class="be-tb-btn" onclick="fmt('bold')" title="Bold (Ctrl+B)"><b>B</b></button>
                <button type="button" class="be-tb-btn" onclick="fmt('italic')" title="Italic (Ctrl+I)"><i>I</i></button>
                <button type="button" class="be-tb-btn" onclick="fmt('underline')" title="Underline (Ctrl+U)"><u>U</u></button>
                <button type="button" class="be-tb-btn" onclick="fmt('strikethrough')" title="Strikethrough"><s>S</s></button>

                <div class="be-tb-divider"></div>

                <button type="button" class="be-tb-btn" onclick="fmt('insertUnorderedList')" title="Bullet List"><i class="fa-solid fa-list-ul"></i></button>
                <button type="button" class="be-tb-btn" onclick="fmt('insertOrderedList')" title="Numbered List"><i class="fa-solid fa-list-ol"></i></button>

                <div class="be-tb-divider"></div>

                <button type="button" class="be-tb-btn" onclick="fmt('justifyLeft')" title="Align Left"><i class="fa-solid fa-align-left"></i></button>
                <button type="button" class="be-tb-btn" onclick="fmt('justifyCenter')" title="Align Center"><i class="fa-solid fa-align-center"></i></button>
                <button type="button" class="be-tb-btn" onclick="fmt('justifyRight')" title="Align Right"><i class="fa-solid fa-align-right"></i></button>

                <div class="be-tb-divider"></div>

                <button type="button" class="be-tb-btn" onclick="insertTable()" title="Insert Table"><i class="fa-solid fa-table"></i></button>
                <button type="button" class="be-tb-btn" onclick="insertCodeBlock()" title="Insert Code Snippet"><i class="fa-solid fa-code"></i></button>
                <button type="button" class="be-tb-btn" onclick="document.getElementById('editor-inline-img').click()" title="Insert Image into Content"><i class="fa-regular fa-image"></i></button>
                <input type="file" id="editor-inline-img" style="display:none" accept="image/*" onchange="if(this.files[0]) uploadEditorImage(this.files[0])">
                
                <button type="button" class="be-tb-btn" onclick="fmt('removeFormat')" title="Clear Formatting"><i class="fa-solid fa-eraser"></i></button>
            </div>

            <!-- Rich Text Area -->
            <div style="flex:1; display:flex; flex-direction:column; min-height:400px;">
                <textarea id="blog-editor" name="content" style="width:100%; height:100%;"><?= htmlspecialchars($bl["content"]) ?></textarea>
            </div>
        </main>

        <!-- ── Right Column: Compact Unified Inspector Panel ── -->
        <aside class="be-panel">

            <!-- Section 1: Publishing Settings -->
            <div>
                <div class="be-sec-title">
                    <i class="fa-solid fa-toggle-on"></i>
                    <span>Publishing & Status</span>
                </div>
                <div class="be-switch-row" style="margin-bottom: 10px;">
                    <div>
                        <div class="be-switch-label">Publish Article</div>
                        <div class="be-switch-desc">Make visible to readers</div>
                    </div>
                    <label class="be-switch">
                        <input type="checkbox" id="bc-pub" name="publish" value="1" <?= $bl["is_published"] ? "checked" : "" ?> onchange="updatePublishStatusBadge(this.checked)">
                        <span class="be-slider"></span>
                    </label>
                </div>

                <div class="be-form-group">
                    <label class="be-form-label">Author</label>
                    <div style="display:flex; align-items:center; gap:8px; padding:6px 10px; background:#f8fafc; border:1px solid var(--be-border); border-radius:var(--be-radius-sm);">
                        <img src="<?= htmlspecialchars($author_avatar) ?>" alt="Author" style="width:22px; height:22px; border-radius:50%; object-fit:cover;">
                        <span style="font-size:0.82rem; font-weight:600; color:var(--be-text-main);"><?= htmlspecialchars($author_name) ?></span>
                    </div>
                </div>
            </div>

            <div class="be-sec-divider"></div>

            <!-- Section 2: Featured Cover Image -->
            <div>
                <div class="be-sec-title">
                    <i class="fa-regular fa-image"></i>
                    <span>Cover Image</span>
                </div>
                <div class="be-dropzone" onclick="document.getElementById('bc-img-file').click()">
                    <?php if (!empty($bl["image_path"])): ?>
                        <img src="<?= htmlspecialchars($bl["image_path"]) ?>" id="coverPreviewImg" class="be-dropzone-preview" alt="Cover Image">
                        <div class="be-dropzone-text"><i class="fa-solid fa-arrow-rotate-right"></i> Replace Cover</div>
                    <?php else: ?>
                        <img src="" id="coverPreviewImg" class="be-dropzone-preview" alt="Cover Image" style="display:none;">
                        <div style="padding: 8px 0;">
                            <i class="fa-solid fa-cloud-arrow-up" style="font-size:1.3rem; color:var(--be-text-muted); margin-bottom:4px;"></i>
                            <div class="be-dropzone-text">Click to upload cover</div>
                        </div>
                    <?php endif; ?>
                </div>
                <input type="file" id="bc-img-file" name="image" accept="image/*" style="display:none;" onchange="previewCoverImage(this)">

                <div class="be-form-group" style="margin-top: 8px;">
                    <label class="be-form-label">Aspect Ratio</label>
                    <select name="image_ratio" class="be-form-select">
                        <option value="16:9" <?= ($bl["image_ratio"] ?? "") === "16:9" ? "selected" : "" ?>>Landscape (16:9)</option>
                        <option value="9:16" <?= ($bl["image_ratio"] ?? "") === "9:16" ? "selected" : "" ?>>Portrait (9:16)</option>
                    </select>
                </div>
            </div>

            <div class="be-sec-divider"></div>

            <!-- Section 3: Real-Time SEO Studio -->
            <div>
                <div class="be-sec-title">
                    <i class="fa-solid fa-magnifying-glass-chart"></i>
                    <span>SEO & SERP Preview</span>
                </div>
                
                <!-- Google SERP Snippet Preview -->
                <div class="be-form-group" style="margin-bottom: 10px;">
                    <div class="be-serp-card">
                        <div class="be-serp-site">
                            <img src="toplogo/logo01.webp" class="be-serp-favicon" alt="">
                            <div>
                                <div class="be-serp-sitename">arigatodevan.com</div>
                                <div class="be-serp-url" id="serpUrlPreview">https://arigatodevan.com/blog.php?slug=<?= htmlspecialchars($bl["slug"] ?? "") ?></div>
                            </div>
                        </div>
                        <div class="be-serp-title" id="serpTitlePreview"><?= htmlspecialchars($bl["meta_title"] ?: $bl["title"]) ?> &ndash; Arigato Devan Prompts</div>
                        <div class="be-serp-desc" id="serpDescPreview"><?= htmlspecialchars($bl["meta_description"] ?: $bl["description"] ?: "Read the latest AI prompt guides and creative tutorials on Arigato Devan...") ?></div>
                    </div>
                </div>

                <!-- Focus Keyword -->
                <div class="be-form-group" style="margin-bottom: 8px;">
                    <label class="be-form-label">Focus Keyword</label>
                    <input type="text" class="be-form-input" id="seo-focus-keyword" placeholder="e.g. couple prompt, midjourney..." autocomplete="off">
                </div>

                <!-- Live SEO Health Checklist -->
                <div class="be-checklist" style="margin-bottom: 8px;">
                    <div class="be-check-item be-check-fail" id="check-title">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span>Keyword in Title</span>
                    </div>
                    <div class="be-check-item be-check-fail" id="check-intro">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span>Keyword in First 100 Words</span>
                    </div>
                    <div class="be-check-item be-check-fail" id="check-headings">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span>Keyword in Headings (H2/H3)</span>
                    </div>
                    <div class="be-check-item be-check-fail" id="check-words">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span>Word count (Aim for 600+)</span>
                    </div>
                </div>

                <!-- Custom Slug -->
                <div class="be-form-group" style="margin-bottom: 8px;">
                    <label class="be-form-label">URL Slug</label>
                    <input type="text" class="be-form-input" id="bc-slug" name="slug" value="<?= htmlspecialchars($bl["slug"] ?? "") ?>" placeholder="e.g. how-to-create-cinematic-ai-prompts">
                </div>

                <!-- Meta Title -->
                <div class="be-form-group" style="margin-bottom: 8px;">
                    <div class="be-form-label">
                        <span>Meta Title</span>
                        <span id="metaTitleCharCount">0 / 60</span>
                    </div>
                    <input type="text" class="be-form-input" id="bc-mt" name="meta_title" value="<?= htmlspecialchars($bl["meta_title"] ?? "") ?>" placeholder="Leave blank to use article title">
                    <div class="be-char-bar"><div class="be-char-fill" id="metaTitleBar"></div></div>
                </div>

                <!-- Meta Description -->
                <div class="be-form-group">
                    <div class="be-form-label">
                        <span>Meta Description</span>
                        <span id="metaDescCharCount">0 / 160</span>
                    </div>
                    <textarea class="be-form-textarea" id="bc-md" name="meta_description" rows="2" placeholder="Compelling summary shown on Google search results..."><?= htmlspecialchars($bl["meta_description"] ?? "") ?></textarea>
                    <div class="be-char-bar"><div class="be-char-fill" id="metaDescBar"></div></div>
                </div>
            </div>

            <div class="be-sec-divider"></div>

            <!-- Section 4: Tags & Keywords -->
            <div>
                <div class="be-sec-title">
                    <i class="fa-solid fa-tags"></i>
                    <span>Tags & Keywords</span>
                </div>
                <input type="hidden" name="tags" id="hiddenTagsInput" value="<?= htmlspecialchars($bl["tags"] ?? "") ?>">
                <div class="be-tags-wrapper" id="tagChipsWrapper" onclick="document.getElementById('tagInputGhost').focus()">
                    <input type="text" id="tagInputGhost" class="be-tag-input" placeholder="Add tag & press Enter...">
                </div>
            </div>

            <div class="be-sec-divider"></div>

            <!-- Section 5: Danger Zone -->
            <div>
                <a href="blog_delete.php?id=<?= $id ?>" class="be-btn-delete" onclick="return confirm('Are you sure you want to permanently delete this blog post?');">
                    <i class="fa-solid fa-trash-can"></i> Delete Article
                </a>
            </div>

        </aside>
    </div>
</form>

<!-- ── JavaScript Engine ── -->
<script>
// --- TinyMCE Editor Initialization ------------------------------------------
tinymce.init({
    selector: '#blog-editor',
    min_height: 380,
    height: 440,
    menubar: false,
    statusbar: false,
    branding: false,
    promotion: false,
    toolbar: false,
    plugins: 'image link lists table code codesample charmap emoticons wordcount autosave visualblocks quickbars',
    quickbars_selection_toolbar: 'bold italic underline | alignleft aligncenter alignright | fontfamily blocks | numlist bullist',
    quickbars_insert_toolbar: 'image link table codesample',
    table_toolbar: 'tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol',
    table_default_styles: { width: '100%', 'border-collapse': 'collapse' },
    table_resize_bars: true,
    paste_webkit_styles: 'all',
    extended_valid_elements: 'table[border|cellpadding|cellspacing|width|class|style],thead,tbody,tfoot,tr[class|style],th[colspan|rowspan|class|style|scope|width],td[colspan|rowspan|class|style|width],caption,colgroup,col[span|width]',
    font_family_formats: 'Editorial Serif=Lora, serif; Display Serif=Playfair Display, serif; Modern Bold=Plus Jakarta Sans, sans-serif; Inter Sans=Inter, sans-serif; Monospace=JetBrains Mono, monospace',
    quickbars_image_toolbar: 'alignleft aligncenter alignright | rotateleft rotateright | editimage imageoptions',
    image_advtab: true,
    image_caption: true,
    image_dimensions: true,
    paste_data_images: true,
    
    setup: function (editor) {
        editor.on('init', function () {
            editor.getContainer().style.border = 'none';
            editor.getContainer().style.boxShadow = 'none';
            editor.getContainer().style.background = 'transparent';
            updateEditorialStats();
        });
        editor.on('NodeChange keyup change input', function () {
            updateEditorialStats();
        });
    },
    
    content_style: `
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lora:ital,wght@0,400;0,500;0,600;1,400&family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@700;800&family=JetBrains+Mono:wght@400;500&display=swap');
        body {
            font-family: 'Lora', Georgia, serif;
            font-size: 1.12rem;
            line-height: 1.8;
            color: #1e293b;
            padding: 6px 2px;
            background: #ffffff;
        }
        h1, h2, h3, h4 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            color: #0f172a;
            margin-top: 1.5em;
            margin-bottom: 0.5em;
            line-height: 1.35;
        }
        h1 { font-size: 1.8rem; }
        h2 { font-size: 1.45rem; }
        h3 { font-size: 1.2rem; }
        p {
            margin-bottom: 1.3em;
        }
        blockquote {
            border-left: 4px solid #3b82f6;
            margin: 1.5em 0;
            padding: 10px 18px;
            background: #eff6ff;
            border-radius: 0 10px 10px 0;
            font-style: italic;
            color: #1e3a8a;
        }
        pre {
            background: #0f172a;
            border-radius: 10px;
            padding: 14px 18px;
            color: #cbd5e1;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
            margin: 1.5em 0;
        }
        img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin: 1.4em auto;
            display: block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.4em 0;
            font-size: 0.92em;
        }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            text-align: left;
            vertical-align: top;
        }
        th { background: #f8fafc; font-weight: 700; }
    `,
    
    images_upload_handler: function (blobInfo, success, failure, progress) {
        const formData = new FormData();
        formData.append('file', blobInfo.blob(), blobInfo.filename());
        
        fetch('upload_editor_image.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.url) {
                success(data.url);
            } else {
                failure('Upload failed: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => {
            failure('Upload error: ' + err.message);
        });
    }
});

function fmt(cmd) {
    if (tinymce.activeEditor) {
        tinymce.activeEditor.execCommand(cmd);
    }
}
function fmtBlock(tag) {
    if (!tag) return;
    if (tinymce.activeEditor) {
        tinymce.activeEditor.execCommand('FormatBlock', false, tag);
    }
}
function applyFontFamily(family) {
    if (!family) return;
    if (tinymce.activeEditor) {
        tinymce.activeEditor.focus();
        tinymce.activeEditor.execCommand('FontName', false, family);
    }
}
function insertTable() {
    if (tinymce.activeEditor) {
        tinymce.activeEditor.execCommand('mceInsertTable', false, { rows: 3, columns: 2 });
    }
}
function insertCodeBlock() {
    if (tinymce.activeEditor) {
        const codeHtml = `
          <pre><code>// Insert code here...</code></pre>
          <p><br></p>
        `;
        tinymce.activeEditor.execCommand('mceInsertContent', false, codeHtml);
    }
}
function uploadEditorImage(file) {
    const formData = new FormData();
    formData.append('file', file);
    fetch('upload_editor_image.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.url && tinymce.activeEditor) {
            tinymce.activeEditor.execCommand('mceInsertContent', false, `<img src="${data.url}" alt="" style="max-width:100%; border-radius:10px;">`);
        } else {
            alert('Upload failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => alert('Upload error: ' + err.message));
}

function updatePublishStatusBadge(isPublished) {
    const badge = document.getElementById('statusIndicatorBadge');
    const text = document.getElementById('statusIndicatorText');
    if (isPublished) {
        badge.className = 'be-status-pill be-status-published';
        text.textContent = 'Published';
    } else {
        badge.className = 'be-status-pill be-status-draft';
        text.textContent = 'Draft';
    }
}

function previewCoverImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById('coverPreviewImg');
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Tag Chips
const hiddenTagsInput = document.getElementById('hiddenTagsInput');
const tagChipsWrapper = document.getElementById('tagChipsWrapper');
const tagInputGhost = document.getElementById('tagInputGhost');
let tagsList = hiddenTagsInput.value.split(',').map(t => t.trim()).filter(t => t.length > 0);

function renderTags() {
    tagChipsWrapper.querySelectorAll('.be-tag-chip').forEach(el => el.remove());
    tagsList.forEach((tag, idx) => {
        const chip = document.createElement('span');
        chip.className = 'be-tag-chip';
        chip.innerHTML = `${escapeHtml(tag)} <span class="be-tag-remove" onclick="removeTag(${idx})">&times;</span>`;
        tagChipsWrapper.insertBefore(chip, tagInputGhost);
    });
    hiddenTagsInput.value = tagsList.join(', ');
}

function removeTag(index) {
    tagsList.splice(index, 1);
    renderTags();
}

function addTag(val) {
    const clean = val.trim().replace(/,/g, '');
    if (clean && !tagsList.includes(clean)) {
        tagsList.push(clean);
        renderTags();
    }
    tagInputGhost.value = '';
}

tagInputGhost.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        addTag(this.value);
    } else if (e.key === 'Backspace' && this.value === '' && tagsList.length > 0) {
        removeTag(tagsList.length - 1);
    }
});

function escapeHtml(str) {
    return str.replace(/[&<>"']/g, function(m) {
        return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[m];
    });
}
renderTags();

// Live SEO & Editorial Stats
function updateEditorialStats() {
    const titleVal = document.getElementById('bc-title').value.trim();
    const descVal = document.getElementById('bc-desc').value.trim();
    const slugVal = document.getElementById('bc-slug').value.trim();
    const metaTitleVal = document.getElementById('bc-mt').value.trim();
    const metaDescVal = document.getElementById('bc-md').value.trim();
    const focusKeyword = document.getElementById('seo-focus-keyword').value.toLowerCase().trim();

    let textContent = "";
    let htmlContent = "";
    if (tinymce.get('blog-editor')) {
        textContent = tinymce.get('blog-editor').getContent({ format: 'text' }).trim();
        htmlContent = tinymce.get('blog-editor').getContent();
    }

    const words = textContent ? textContent.split(/\s+/).filter(w => w.length > 0).length : 0;
    const readTime = Math.max(1, Math.ceil(words / 200));

    document.getElementById('metaWordCount').innerHTML = `<i class="fa-solid fa-file-lines"></i> ${words} words`;
    document.getElementById('metaReadTime').innerHTML = `<i class="fa-regular fa-clock"></i> ~${readTime} min read`;

    const displayTitle = metaTitleVal || titleVal || "Your Article Title";
    const displayDesc = metaDescVal || descVal || (textContent.slice(0, 150) + "...");
    const displaySlug = slugVal || titleVal.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || "article-url";

    document.getElementById('serpTitlePreview').textContent = displayTitle + " – Arigato Devan Prompts";
    document.getElementById('serpDescPreview').textContent = displayDesc;
    document.getElementById('serpUrlPreview').textContent = `https://arigatodevan.com/blog.php?slug=${encodeURIComponent(displaySlug)}`;

    const mtLen = (metaTitleVal || titleVal).length;
    const mtCharCount = document.getElementById('metaTitleCharCount');
    const mtBar = document.getElementById('metaTitleBar');
    mtCharCount.textContent = `${mtLen} / 60`;
    const mtPct = Math.min(100, (mtLen / 60) * 100);
    mtBar.style.width = mtPct + '%';
    if (mtLen >= 40 && mtLen <= 60) {
        mtBar.className = 'be-char-fill good';
    } else if (mtLen > 60) {
        mtBar.className = 'be-char-fill bad';
    } else {
        mtBar.className = 'be-char-fill warn';
    }

    const mdLen = (metaDescVal || descVal).length;
    const mdCharCount = document.getElementById('metaDescCharCount');
    const mdBar = document.getElementById('metaDescBar');
    mdCharCount.textContent = `${mdLen} / 160`;
    const mdPct = Math.min(100, (mdLen / 160) * 100);
    mdBar.style.width = mdPct + '%';
    if (mdLen >= 120 && mdLen <= 160) {
        mdBar.className = 'be-char-fill good';
    } else if (mdLen > 160) {
        mdBar.className = 'be-char-fill bad';
    } else {
        mdBar.className = 'be-char-fill warn';
    }

    updateCheckItem('check-title', focusKeyword && titleVal.toLowerCase().includes(focusKeyword), 'Keyword in Title');
    
    const introSnippet = textContent.slice(0, 500).toLowerCase();
    updateCheckItem('check-intro', focusKeyword && introSnippet.includes(focusKeyword), 'Keyword in First 100 Words');

    const parser = new DOMParser();
    const doc = parser.parseFromString(htmlContent, 'text/html');
    const headings = Array.from(doc.querySelectorAll('h1, h2, h3')).map(h => h.textContent.toLowerCase());
    const hasInHeading = focusKeyword && headings.some(h => h.includes(focusKeyword));
    updateCheckItem('check-headings', hasInHeading, 'Keyword in Headings (H2/H3)');

    if (words >= 600) {
        setCheckPass('check-words', `Great length: ${words} words`);
    } else if (words >= 300) {
        setCheckWarn('check-words', `${words} words (Aim for 600+)`);
    } else {
        setCheckFail('check-words', `${words} words (Aim for 600+)`);
    }
}

function updateCheckItem(id, passed, label) {
    if (passed) {
        setCheckPass(id, label);
    } else {
        setCheckFail(id, label);
    }
}
function setCheckPass(id, text) {
    const el = document.getElementById(id);
    el.className = 'be-check-item be-check-pass';
    el.innerHTML = `<i class="fa-solid fa-circle-check"></i> <span>${escapeHtml(text)}</span>`;
}
function setCheckFail(id, text) {
    const el = document.getElementById(id);
    el.className = 'be-check-item be-check-fail';
    el.innerHTML = `<i class="fa-solid fa-circle-xmark"></i> <span>${escapeHtml(text)}</span>`;
}
function setCheckWarn(id, text) {
    const el = document.getElementById(id);
    el.className = 'be-check-item be-check-warn';
    el.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> <span>${escapeHtml(text)}</span>`;
}

['bc-title', 'bc-desc', 'bc-slug', 'bc-mt', 'bc-md', 'seo-focus-keyword'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
        el.addEventListener('input', updateEditorialStats);
    }
});

const descTextarea = document.getElementById('bc-desc');
if (descTextarea) {
    descTextarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    descTextarea.style.height = (descTextarea.scrollHeight) + 'px';
}
</script>
</body>
</html>
