<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();

    // Handle AJAX Add Category request
    if (isset($_POST['action']) && $_POST['action'] === 'add_category') {
        header('Content-Type: application/json');
        $cat_name = trim($_POST['category_name'] ?? '');
        if ($cat_name !== '') {
            try {
                $stmt_cat = $pdo->prepare("INSERT IGNORE INTO blog_categories (name) VALUES (?)");
                $stmt_cat->execute([$cat_name]);
                echo json_encode(['success' => true, 'name' => $cat_name]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Category name cannot be empty']);
        }
        exit();
    }

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $content = $_POST["content"] ?? "";
    $content_hindi = $_POST["content_hindi"] ?? "";
    $meta_title = trim($_POST["meta_title"] ?? "");
    $meta_desc = trim($_POST["meta_description"] ?? "");
    $tags = trim($_POST["tags"] ?? "");

    // Process selected categories
    $selected_cats = isset($_POST['categories']) && is_array($_POST['categories']) ? array_filter(array_map('trim', $_POST['categories'])) : [];
    if (empty($selected_cats)) {
        $category_str = 'Uncategorized';
    } else {
        $category_str = implode(', ', $selected_cats);
    }
    foreach ($selected_cats as $sc) {
        try {
            $pdo->prepare("INSERT IGNORE INTO blog_categories (name) VALUES (?)")->execute([$sc]);
        } catch (Exception $e) {}
    }

    $image_ratio = trim($_POST["image_ratio"] ?? "16:9");
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
            $slug = "blog-post-" . time();
        }

        // Make unique
        $exists = $pdo->prepare("SELECT id FROM blogs WHERE slug=?");
        $exists->execute([$slug]);
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

        $image_path = "";
        $image_path_landscape = "";
        $allowed = ["jpg", "jpeg", "png", "webp", "gif"];
        foreach (["image" => "image_path", "image_landscape" => "image_path_landscape"] as $fileKey => $destVar) {
            if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]["error"] !== UPLOAD_ERR_OK) {
                continue;
            }
            $ext = strtolower(pathinfo($_FILES[$fileKey]["name"], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = "Invalid file type. Allowed: JPG, PNG, WEBP, GIF.";
                break;
            }
            if ($_FILES[$fileKey]["size"] > 5 * 1024 * 1024) {
                $error = "Image too large! Maximum allowed size is 5MB.";
                break;
            }
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES[$fileKey]["tmp_name"]);
            finfo_close($finfo);
            if (!str_starts_with($mime, 'image/')) {
                $error = "Invalid image file.";
                break;
            }
            $filename = "uploads/blog_" . uniqid() . "." . $ext;
            if (!move_uploaded_file($_FILES[$fileKey]["tmp_name"], $filename)) {
                $error = "Could not save uploaded image.";
                break;
            }
            $$destVar = $filename;
        }
        if ($image_path && $image_path_landscape) {
            $image_ratio = "9:16";
        } elseif ($image_path_landscape) {
            $image_ratio = "16:9";
        } elseif ($image_path) {
            $image_ratio = "9:16";
        }

        if (empty($error)) {
            $author_id = $_SESSION["user_id"] ?? 1;
            $stmt = $pdo->prepare(
                "INSERT INTO blogs (title, slug, description, content, content_hindi, image_path, image_path_landscape, image_ratio, meta_title, meta_description, tags, category, is_published, author_id, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())"
            );
            $stmt->execute([
                $title,
                $slug,
                $description,
                $content,
                $content_hindi,
                $image_path,
                $image_path_landscape,
                $image_ratio,
                $meta_title,
                $meta_desc,
                $tags,
                $category_str,
                $publish,
                $author_id,
            ]);

            $new_id = $pdo->lastInsertId();
            $_SESSION["success_msg"] = "Article published successfully!";
            header("Location: blog_edit.php?id=" . $new_id);
            exit();
        }
    }
}

// Author info
$author_name = $_SESSION["username"] ?? "Admin";
$author_avatar = $_SESSION["profile_image"] ?? "toplogo/logo01.webp";
// Fetch site prompts for quick embedding in callout box
$site_prompts_list = [];
try {
    $stmt_spl = $pdo->query("SELECT id, title, image_path, slug FROM prompts ORDER BY id DESC LIMIT 50");
    $site_prompts_list = $stmt_spl->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Fetch all blog categories for sidebar
$all_blog_categories = [];
try {
    $all_blog_categories = $pdo->query("SELECT name FROM blog_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}
if (empty($all_blog_categories)) {
    $all_blog_categories = ['Uncategorized', 'AI Prompts', 'ChatGPT & Gemini', 'Guides & Tutorials', 'Midjourney & Image AI', 'Product Updates'];
}
$current_blog_categories = ['Uncategorized'];
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Article &ndash; Arigato Editor</title>
    
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
            width: 100% !important;
            max-width: 100% !important;
            overflow: hidden !important;
            overflow-x: hidden !important;
            background-color: var(--be-bg) !important;
            color: var(--be-text-main) !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
            display: flex !important;
            flex-direction: column !important;
            -webkit-font-smoothing: antialiased;
        }

        .be-header {
            height: 54px !important;
            width: 100% !important;
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
            background: #f1f5f9;
            color: var(--be-text-sec);
            border: 1px solid var(--be-border);
        }
        .be-status-pill .be-dot {
            width: 6px;
            height: 6px;
            background: var(--be-text-muted);
            border-radius: 50%;
        }
        .be-status-pill.be-status-published {
            background: var(--be-success-light);
            color: #065f46;
            border-color: rgba(16, 185, 129, 0.2);
        }
        .be-status-pill.be-status-published .be-dot {
            background: var(--be-success);
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
            min-width: 0 !important;
            margin: 0 auto !important;
        }

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
            min-width: 0 !important;
            max-width: 100% !important;
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
            font-size: 1.85rem !important;
            line-height: 1.25 !important;
            color: var(--be-text-main) !important;
            letter-spacing: -0.02em !important;
            margin-bottom: 6px !important;
            padding: 0 !important;
            flex-shrink: 0 !important;
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
            font-size: 0.94rem !important;
            line-height: 1.45 !important;
            color: var(--be-text-sec) !important;
            resize: none !important;
            margin-bottom: 8px !important;
            min-height: 32px;
            padding: 0 !important;
            flex-shrink: 0 !important;
        }
        .be-subtitle-input::placeholder {
            color: #cbd5e1 !important;
        }

        .be-meta-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 8px;
            margin-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.74rem;
            color: var(--be-text-muted);
            font-weight: 500;
            flex-shrink: 0 !important;
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
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 6px 8px;
            margin-bottom: 14px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            flex-shrink: 0 !important;
            min-height: 46px !important;
            box-sizing: border-box !important;
        }
        .be-tb-group {
            display: inline-flex;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 3px 4px;
            gap: 2px;
            flex-shrink: 0 !important;
            height: 32px;
            box-sizing: border-box;
        }
        .be-tb-group-accent {
            background: #f5f3ff;
            border-color: #ddd6fe;
        }
        .be-tb-divider {
            display: none;
        }
        .be-editor-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 650px;
            min-width: 0;
            max-width: 100%;
        }
        body.be-wide-mode .be-layout {
            grid-template-columns: 1fr !important;
            max-width: 100% !important;
        }
        body.be-wide-mode .be-panel {
            display: none !important;
        }
        body.be-wide-mode .be-paper {
            max-width: 1200px !important;
            margin: 0 auto !important;
            width: 100% !important;
        }

        .be-tb-btn {
            background: transparent;
            border: none;
            color: #475569;
            padding: 0;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.84rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            transition: all 0.12s ease;
            flex-shrink: 0;
            line-height: 1;
        }
        .be-tb-btn:hover {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        .be-tb-btn.active {
            background: #ffffff;
            color: #0284c7;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        /* ── Custom In-Page Dropdown UI (Replaces ugly native browser OS <select>) ── */
        .be-dropdown {
            position: relative;
            display: inline-flex;
            align-items: center;
        }
        .be-tb-dropdown-btn {
            background: transparent;
            border: 1px solid transparent;
            color: #334155;
            font-family: inherit;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 0 8px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            height: 26px;
            transition: all 0.15s ease;
            white-space: nowrap;
            flex-shrink: 0;
            line-height: 1;
        }
        .be-tb-dropdown-btn:hover,
        .be-dropdown.be-dd-open .be-tb-dropdown-btn {
            background: #ffffff;
            border-color: #cbd5e1;
            color: #0f172a;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
        }
        .be-tb-dropdown-accent {
            background: transparent !important;
            color: #7c3aed !important;
            border: none !important;
            box-shadow: none !important;
        }
        .be-tb-dropdown-accent:hover,
        .be-dropdown.be-dd-open .be-tb-dropdown-accent {
            background: transparent !important;
            color: #6d28d9 !important;
            border: none !important;
            box-shadow: none !important;
        }
        .be-tb-group-accent {
            transition: all 0.15s ease;
        }
        .be-tb-group-accent:hover,
        .be-tb-group-accent:has(.be-dd-open) {
            background: #ede9fe !important;
            border-color: #c4b5fd !important;
        }
        .be-dd-chevron {
            font-size: 0.65rem;
            color: #64748b;
            transition: transform 0.2s ease;
        }
        .be-dropdown.be-dd-open .be-dd-chevron {
            transform: rotate(180deg);
            color: #2563eb;
        }
        .be-tb-dropdown-mini {
            font-size: 0.74rem;
            color: #0284c7;
            font-weight: 700;
            padding: 2px 6px;
        }
        .be-dropdown-menu {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            min-width: 175px;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 12px 28px -4px rgba(15, 23, 42, 0.14), 0 4px 10px -2px rgba(15, 23, 42, 0.06);
            z-index: 99999;
            padding: 5px;
            display: none;
            flex-direction: column;
            gap: 2px;
            animation: beDropdownPop 0.15s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes beDropdownPop {
            from { opacity: 0; transform: translateY(-3px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .be-dropdown.be-dd-open .be-dropdown-menu {
            display: flex;
        }
        .be-dd-item {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 7px 10px;
            border: none;
            background: transparent;
            border-radius: 7px;
            font-family: inherit;
            font-size: 0.83rem;
            font-weight: 600;
            color: #334155;
            text-align: left;
            cursor: pointer;
            transition: all 0.12s ease;
            white-space: nowrap;
            box-sizing: border-box;
        }
        .be-dd-item:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .be-dd-item:active {
            background: #e2e8f0;
        }
        .be-dd-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 3px 0;
        }
        .be-tb-more-btn {
            display: none;
        }
        .be-tb-primary {
            display: contents;
        }
        .be-tb-more-drawer {
            display: contents;
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
        .be-cover-pair {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        @media (max-width: 768px) {
            .be-cover-pair { grid-template-columns: 1fr; }
        }
        .be-cover-slot-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--be-text-sec);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 6px;
        }
        .be-dropzone-text {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--be-text-sec);
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
        .be-alert-error {
            background: var(--be-danger-light);
            color: #991b1b;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .be-app {
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
            min-height: 0;
            width: 100%;
            max-width: 100%;
            align-self: stretch;
        }

        .be-breadcrumb-active {
            max-width: 280px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @media (max-width: 1024px) {
            html, body {
                height: auto !important;
                overflow-x: hidden !important;
                overflow-y: auto !important;
            }
            .be-app {
                height: auto !important;
                min-height: 100dvh;
                overflow: visible !important;
                overflow-x: hidden !important;
            }
            .be-layout {
                grid-template-columns: 1fr !important;
                height: auto !important;
                overflow: visible !important;
                overflow-x: hidden !important;
                padding: 10px 12px 28px !important;
                gap: 12px !important;
                max-width: 100% !important;
            }
            .be-paper, .be-panel {
                height: auto !important;
                overflow: visible !important;
                min-width: 0 !important;
                max-width: 100% !important;
            }
        }

        @media (max-width: 768px) {
            .be-header {
                height: 56px !important;
                min-height: 56px;
                padding: 0 12px !important;
                position: sticky;
                top: 0;
                left: 0;
                right: 0;
                width: 100% !important;
                max-width: 100% !important;
            }
            .be-header-left,
            .be-header-right {
                flex: 0 0 auto;
                gap: 8px;
                min-width: 0;
            }
            .be-breadcrumb {
                display: none !important;
            }
            .be-btn-back {
                width: 40px;
                height: 40px;
                padding: 0;
                justify-content: center;
            }
            .be-btn-back span,
            .be-btn-sec,
            .be-btn-primary span,
            .be-btn-preview span {
                display: none !important;
            }
            .be-status-pill {
                padding: 5px 10px;
                font-size: 0.72rem;
            }
            .be-btn-primary,
            .be-btn-preview {
                width: 40px;
                height: 40px;
                min-width: 40px;
                padding: 0;
                justify-content: center;
            }
            .be-paper {
                padding: 16px 14px 24px !important;
            }
            .be-panel {
                padding: 16px 14px 36px !important;
            }
            .be-title-input {
                font-size: 1.35rem !important;
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
            }
            .be-subtitle-input {
                font-size: 0.9rem !important;
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                white-space: pre-wrap !important;
                overflow-wrap: anywhere;
                line-height: 1.45 !important;
            }
            .be-meta-bar {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px 10px;
                align-items: start;
            }
            .be-meta-bar span {
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .be-toolbar {
                position: sticky;
                top: 0;
                z-index: 50;
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(10px);
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 6px 8px;
                margin-bottom: 12px;
                box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
                flex-shrink: 0 !important;
                min-height: 46px !important;
                box-sizing: border-box !important;
            }
            .be-toolbar::-webkit-scrollbar {
                display: none;
            }
            .be-tb-group {
                display: inline-flex;
                align-items: center;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 3px 4px;
                gap: 2px;
                flex-shrink: 0 !important;
                height: 32px;
                box-sizing: border-box;
            }
            .be-tb-btn {
                width: 30px;
                height: 30px;
                min-width: 30px;
                font-size: 0.82rem;
                flex-shrink: 0;
                border-radius: 6px;
                background: transparent;
            }
            .be-tb-more-btn {
                display: none !important;
            }
            .be-tb-more-drawer {
                display: none !important;
            }
            .be-editor-wrap {
                min-height: 260px;
            }
            .tox-tinymce,
            .tox-editor-container,
            .tox-sidebar-wrap,
            .tox-edit-area,
            .tox-edit-area__iframe {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
            }
        }
    </style>
</head>
<body>

<form method="POST" action="blog_create.php" enctype="multipart/form-data" id="blogForm" class="be-app">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf() ?>">

    <!-- ── Top App Bar ── -->
    <header class="be-header">
        <div class="be-header-left">
            <a href="blog_admin.php" class="be-btn-back" title="Back to All Blogs">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Blogs</span>
            </a>
            <div class="be-breadcrumb">
                <i class="fa-solid fa-chevron-right" style="font-size:0.6rem;"></i>
                <span class="be-breadcrumb-active">New Post</span>
            </div>
        </div>

        <div class="be-header-right">
            <button type="button" class="be-btn-sec" onclick="toggleWideWritingMode()" id="wideModeBtn" title="Toggle Spacious Writing Canvas">
                <i class="fa-solid fa-expand"></i>
                <span id="wideBtnLabel">Spacious View</span>
            </button>
            <div class="be-status-pill be-status-draft" id="statusIndicatorBadge">
                <span class="be-dot"></span>
                <span id="statusIndicatorText">Draft</span>
            </div>

            <button type="button" class="be-btn-preview" onclick="openLivePreview()" title="Open live draft preview in new tab">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                <span>Preview</span>
            </button>

            <a href="blog_admin.php" class="be-btn-sec">Cancel</a>
            <button type="submit" class="be-btn-primary" id="btnSubmitForm">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                <span>Publish</span>
            </button>
        </div>
    </header>

    <!-- ── Main 2-Column Full App Layout ── -->
    <div class="be-layout">
        
        <!-- ── Left Column: Writing Canvas ── -->
        <main class="be-paper">
            <?php if ($error): ?>
                <div class="be-alert be-alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Title -->
            <input type="text" 
                   class="be-title-input" 
                   id="bc-title" 
                   name="title" 
                   value="<?= htmlspecialchars($_POST["title"] ?? "") ?>" 
                   placeholder="Article title..." 
                   required 
                   autocomplete="off">

            <!-- Summary / Excerpt -->
            <textarea class="be-subtitle-input" 
                      id="bc-desc" 
                      name="description" 
                      placeholder="Write a clear, engaging excerpt or short summary for card previews..." 
                      rows="2"><?= htmlspecialchars($_POST["description"] ?? "") ?></textarea>

            <!-- Metadata info bar -->
            <div class="be-meta-bar">
                <span><i class="fa-regular fa-calendar"></i> Today (Draft)</span>
                <span><i class="fa-solid fa-user-pen"></i> <?= htmlspecialchars($author_name) ?></span>
                <span id="metaWordCount"><i class="fa-solid fa-file-lines"></i> 0 words</span>
                <span id="metaReadTime"><i class="fa-regular fa-clock"></i> ~0 min read</span>
            </div>

            <!-- Sleek Minimalist Formatting Toolbar (Single-Row Compact Bar) -->
            <div class="be-toolbar">
                <!-- Group: Style Custom Dropdown -->
                <div class="be-tb-group">
                    <div class="be-dropdown" id="beStyleDropdown">
                        <button type="button" class="be-tb-dropdown-btn" onclick="toggleBeDropdown('beStyleDropdown')" title="Headings & Callouts">
                            <span class="be-dd-label">Style</span>
                            <i class="fa-solid fa-chevron-down be-dd-chevron"></i>
                        </button>
                        <div class="be-dropdown-menu">
                            <button type="button" class="be-dd-item" onclick="selectBeStyle('p', 'Paragraph')">Paragraph</button>
                            <button type="button" class="be-dd-item" onclick="selectBeStyle('h1', 'Heading 1')"><span style="font-size:1.1rem; font-weight:800;">Heading 1</span></button>
                            <button type="button" class="be-dd-item" onclick="selectBeStyle('h2', 'Heading 2')"><span style="font-size:1.0rem; font-weight:700;">Heading 2</span></button>
                            <button type="button" class="be-dd-item" onclick="selectBeStyle('h3', 'Heading 3')"><span style="font-size:0.9rem; font-weight:700;">Heading 3</span></button>
                            <div class="be-dd-divider"></div>
                            <button type="button" class="be-dd-item" onclick="selectBeStyle('blockquote', 'Quote Callout')"><i class="fa-solid fa-quote-left" style="color:#0284c7;"></i> Quote Callout</button>
                            <button type="button" class="be-dd-item" onclick="selectBeStyle('greybox', 'Grey Note Box')"><i class="fa-solid fa-square-pen" style="color:#64748b;"></i> Grey Note Box</button>
                        </div>
                    </div>
                </div>

                <!-- Group: Basic Text Formats -->
                <div class="be-tb-group">
                    <button type="button" class="be-tb-btn" onclick="fmt('bold')" title="Bold (Ctrl+B)"><b>B</b></button>
                    <button type="button" class="be-tb-btn" onclick="fmt('italic')" title="Italic (Ctrl+I)"><i>I</i></button>
                    <button type="button" class="be-tb-btn" onclick="fmt('underline')" title="Underline (Ctrl+U)"><u>U</u></button>
                    <button type="button" class="be-tb-btn" onclick="fmt('strikethrough')" title="Strikethrough"><s>S</s></button>
                    <button type="button" class="be-tb-btn" onclick="toggleHighlight()" title="Highlight Text (Yellow Marker)"><i class="fa-solid fa-highlighter" style="color:#ca8a04;"></i></button>
                </div>

                <!-- Group: Lists -->
                <div class="be-tb-group">
                    <button type="button" class="be-tb-btn" onclick="fmt('insertUnorderedList')" title="Bullet List"><i class="fa-solid fa-list-ul"></i></button>
                    <button type="button" class="be-tb-btn" onclick="fmt('insertOrderedList')" title="Numbered List"><i class="fa-solid fa-list-ol"></i></button>
                    <button type="button" class="be-tb-btn" onclick="continueCurrentOrderedList()" title="Continue Numbering from Previous List"><i class="fa-solid fa-arrow-down-1-9" style="color:#0284c7;"></i></button>
                </div>

                <!-- Group: Alignment -->
                <div class="be-tb-group">
                    <button type="button" class="be-tb-btn" onclick="fmt('justifyLeft')" title="Align Left"><i class="fa-solid fa-align-left"></i></button>
                    <button type="button" class="be-tb-btn" onclick="fmt('justifyCenter')" title="Align Center"><i class="fa-solid fa-align-center"></i></button>
                    <button type="button" class="be-tb-btn" onclick="fmt('justifyRight')" title="Align Right"><i class="fa-solid fa-align-right"></i></button>
                </div>

                <!-- Group: Links & Media -->
                <div class="be-tb-group">
                    <button type="button" class="be-tb-btn" onclick="insertLink()" title="Insert Link (Ctrl+K)"><i class="fa-solid fa-link"></i></button>
                    <button type="button" class="be-tb-btn" onclick="openEditorImageModal()" title="Insert Image with SEO Alt Text"><i class="fa-regular fa-image" style="color:#0284c7;"></i></button>
                    <button type="button" class="be-tb-btn" onclick="openEditorCarouselModal()" title="Insert Carousel"><i class="fa-solid fa-images" style="color:#8b5cf6;"></i></button>
                    <button type="button" class="be-tb-btn" onclick="insertCodeBlock()" title="Insert Prompt Code Snippet"><i class="fa-solid fa-code"></i></button>
                </div>

                <!-- Group: Table of Contents -->
                <div class="be-tb-group" id="beTocGroup">
                    <button type="button" class="be-tb-btn" onclick="insertTableOfContents('sm')" title="Table of Contents (Auto-links H2/H3)"><i class="fa-solid fa-list-ol" style="color:#0284c7;"></i></button>
                    <div class="be-dropdown" id="beTocDropdown">
                        <button type="button" class="be-tb-dropdown-btn" onclick="toggleBeDropdown('beTocDropdown')" title="TOC Size & Options">
                            <span class="be-dd-label">TOC</span>
                            <i class="fa-solid fa-chevron-down be-dd-chevron"></i>
                        </button>
                        <div class="be-dropdown-menu">
                            <button type="button" class="be-dd-item" onclick="insertTableOfContents('sm')"><i class="fa-solid fa-arrows-rotate" style="color:#0284c7;"></i> Auto-Update / Refresh</button>
                            <div class="be-dd-divider"></div>
                            <div style="padding: 4px 12px; font-size:0.68rem; color:#94a3b8; font-weight:700; text-transform:uppercase;">Size & Columns</div>
                            <button type="button" class="be-dd-item" onclick="setTocSize('sm')"><i class="fa-solid fa-table-columns" style="color:#64748b;"></i> Small (2 Columns, Compact)</button>
                            <button type="button" class="be-dd-item" onclick="setTocSize('md')"><i class="fa-solid fa-table-columns" style="color:#64748b;"></i> Medium (2 Columns, Standard)</button>
                            <button type="button" class="be-dd-item" onclick="setTocSize('lg')"><i class="fa-solid fa-table-columns" style="color:#64748b;"></i> Large (2 Columns, Spacious)</button>
                            <div class="be-dd-divider"></div>
                            <button type="button" class="be-dd-item" onclick="removeTocFromDoc()" style="color:#ef4444;"><i class="fa-solid fa-trash-can" style="color:#ef4444;"></i> Delete TOC</button>
                        </div>
                    </div>
                </div>

                <!-- Group: + Insert Special Blocks -->
                <div class="be-tb-group be-tb-group-accent">
                    <div class="be-dropdown" id="beInsertDropdown">
                        <button type="button" class="be-tb-dropdown-btn be-tb-dropdown-accent" onclick="toggleBeDropdown('beInsertDropdown')" title="Insert Special Blocks">
                            <i class="fa-solid fa-plus" style="font-size:0.72rem;"></i>
                            <span class="be-dd-label">Insert</span>
                            <i class="fa-solid fa-chevron-down be-dd-chevron"></i>
                        </button>
                        <div class="be-dropdown-menu">
                            <button type="button" class="be-dd-item" onclick="openNoteBoxModal()"><i class="fa-regular fa-note-sticky" style="color:#f59e0b;"></i> Pro Tip / Note Box</button>
                            <button type="button" class="be-dd-item" onclick="openFaqModal()"><i class="fa-solid fa-circle-question" style="color:#6366f1;"></i> Quick FAQ Section Box</button>
                            <button type="button" class="be-dd-item" onclick="insertTableOfContents('sm')"><i class="fa-solid fa-list-ol" style="color:#0284c7;"></i> Table of Contents</button>
                            <button type="button" class="be-dd-item" onclick="openCtaModal()"><i class="fa-solid fa-bullhorn" style="color:#ec4899;"></i> CTA Banner Box</button>
                            <div class="be-dd-divider"></div>
                            <button type="button" class="be-dd-item" onclick="insertTable()"><i class="fa-solid fa-table" style="color:#64748b;"></i> Table</button>
                            <button type="button" class="be-dd-item" onclick="promptSetListStart()"><i class="fa-solid fa-hashtag" style="color:#64748b;"></i> Set Starting Number</button>
                            <button type="button" class="be-dd-item" onclick="toggleSelectedImageCaption()"><i class="fa-solid fa-closed-captioning" style="color:#64748b;"></i> Image Caption</button>
                            <div class="be-dd-divider"></div>
                            <button type="button" class="be-dd-item" onclick="insertCodeBlock('dark')"><i class="fa-solid fa-code" style="color:#38bdf8;"></i> Code: 🌙 Dark Theme</button>
                            <button type="button" class="be-dd-item" onclick="insertCodeBlock('light')"><i class="fa-solid fa-code" style="color:#64748b;"></i> Code: ☀️ Light Theme</button>
                            <button type="button" class="be-dd-item" onclick="insertCodeBlock('cyber')"><i class="fa-solid fa-code" style="color:#a855f7;"></i> Code: ⚡ Cyber Theme</button>
                        </div>
                    </div>
                </div>

                <!-- Group: Font Family -->
                <div class="be-tb-group">
                    <div class="be-dropdown" id="beFontDropdown">
                        <button type="button" class="be-tb-dropdown-btn" onclick="toggleBeDropdown('beFontDropdown')" title="Font Family">
                            <span class="be-dd-label">Font</span>
                            <i class="fa-solid fa-chevron-down be-dd-chevron"></i>
                        </button>
                        <div class="be-dropdown-menu">
                            <button type="button" class="be-dd-item" onclick="selectBeFont('Lora, Georgia, serif', 'Editorial Serif')" style="font-family:Lora, Georgia, serif;">Editorial Serif</button>
                            <button type="button" class="be-dd-item" onclick="selectBeFont('\'Playfair Display\', serif', 'Display Serif')" style="font-family:'Playfair Display', serif;">Display Serif</button>
                            <button type="button" class="be-dd-item" onclick="selectBeFont('\'Plus Jakarta Sans\', sans-serif', 'Modern Bold')" style="font-family:'Plus Jakarta Sans', sans-serif; font-weight:700;">Modern Bold</button>
                            <button type="button" class="be-dd-item" onclick="selectBeFont('\'Inter\', sans-serif', 'Clean Sans')" style="font-family:'Inter', sans-serif;">Clean Sans</button>
                            <button type="button" class="be-dd-item" onclick="selectBeFont('\'JetBrains Mono\', monospace', 'Monospace')" style="font-family:'JetBrains Mono', monospace;">Monospace</button>
                        </div>
                    </div>
                </div>

                <!-- Group: Clear Format -->
                <div class="be-tb-group">
                    <button type="button" class="be-tb-btn" onclick="fmt('removeFormat')" title="Clear Formatting"><i class="fa-solid fa-eraser"></i></button>
                </div>

                <input type="file" id="editor-inline-img" style="display:none" accept="image/*" onchange="if(this.files[0]) handleEimFileSelect(this.files[0], true)">
            </div>

            <!-- Rich Text Area -->
            <div class="be-editor-wrap">
                <textarea id="blog-editor" name="content" style="width:100%; height:100%;"><?= htmlspecialchars($_POST["content"] ?? "") ?></textarea>
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
                        <div class="be-switch-label">Publish Immediately</div>
                        <div class="be-switch-desc">Make visible to readers</div>
                    </div>
                    <label class="be-switch">
                        <input type="checkbox" id="bc-pub" name="publish" value="1" <?= isset($_POST["publish"]) && $_POST["publish"] == "1" ? "checked" : "" ?> onchange="updatePublishStatusBadge(this.checked)">
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

            <!-- Section: Categories (WordPress-Style) -->
            <div class="be-cat-section">
                <div class="be-sec-title" style="display:flex; align-items:center; justify-content:space-between; cursor:pointer; user-select:none; margin-bottom:10px;" onclick="toggleCategoryAccordion()">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-folder-tree" style="color:var(--be-accent);"></i>
                        <span style="font-weight:700;">Categories</span>
                    </div>
                    <i class="fa-solid fa-chevron-up" id="catAccordionIcon" style="font-size:0.75rem; color:#94a3b8; transition:transform 0.2s ease;"></i>
                </div>

                <div id="catAccordionBody">
                    <div class="be-cat-list" id="catListContainer" style="max-height:165px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px; background:#f8fafc; display:flex; flex-direction:column; gap:8px;">
                        <?php foreach ($all_blog_categories as $cat): 
                            $isChecked = in_array($cat, $current_blog_categories);
                        ?>
                        <label style="display:flex; align-items:center; gap:9px; font-size:0.85rem; color:#1e293b; font-weight:600; cursor:pointer;">
                            <input type="checkbox" name="categories[]" value="<?= htmlspecialchars($cat) ?>" <?= $isChecked ? 'checked' : '' ?> style="width:16px; height:16px; accent-color:#0284c7; cursor:pointer; border-radius:4px;">
                            <span><?= htmlspecialchars($cat) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- + Add Category Link & Inline Form -->
                    <div style="margin-top:10px;">
                        <button type="button" id="toggleAddCatBtn" onclick="toggleAddCategoryForm()" style="background:none; border:none; color:#0284c7; font-weight:700; font-size:0.82rem; cursor:pointer; padding:0; display:inline-flex; align-items:center; gap:5px; text-decoration:underline;">
                            <i class="fa-solid fa-plus"></i> Add Category
                        </button>
                        
                        <div id="addCatForm" style="display:none; margin-top:8px; background:#f1f5f9; padding:10px; border-radius:10px; border:1px solid #cbd5e1;">
                            <input type="text" id="newCatInput" placeholder="New category name..." style="width:100%; box-sizing:border-box; padding:7px 10px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:0.84rem; margin-bottom:8px; font-family:inherit;" onkeydown="if(event.key==='Enter'){event.preventDefault();addNewCategory();}">
                            <div style="display:flex; gap:6px;">
                                <button type="button" onclick="addNewCategory()" style="padding:6px 14px; background:#0284c7; color:#fff; border:none; border-radius:7px; font-size:0.8rem; font-weight:700; cursor:pointer;">
                                    Add
                                </button>
                                <button type="button" onclick="toggleAddCategoryForm()" style="padding:6px 12px; background:#e2e8f0; color:#475569; border:none; border-radius:7px; font-size:0.8rem; font-weight:600; cursor:pointer;">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="be-sec-divider"></div>

            <!-- Section 2: Featured Cover Image -->
            <div>
                <div class="be-sec-title">
                    <i class="fa-regular fa-image"></i>
                    <span>Cover thumbnails</span>
                </div>
                <p class="be-form-hint" style="margin-bottom:10px;">Portrait shows on phone. Landscape shows on laptop.</p>
                <div class="be-cover-pair">
                    <div>
                        <div class="be-cover-slot-label">Portrait · mobile</div>
                        <div class="be-dropzone" onclick="document.getElementById('bc-img-file').click()">
                            <img src="" id="coverPreviewImg" class="be-dropzone-preview" alt="Portrait cover" style="display:none;">
                            <div style="padding: 8px 0;">
                                <i class="fa-solid fa-mobile-screen" style="font-size:1.1rem; color:var(--be-text-muted); margin-bottom:4px;"></i>
                                <div class="be-dropzone-text">9:16 portrait</div>
                            </div>
                        </div>
                        <input type="file" id="bc-img-file" name="image" accept="image/*" style="display:none;" onchange="previewCoverImage(this, 'coverPreviewImg')">
                    </div>
                    <div>
                        <div class="be-cover-slot-label">Landscape · laptop</div>
                        <div class="be-dropzone" onclick="document.getElementById('bc-img-land-file').click()">
                            <img src="" id="coverPreviewLand" class="be-dropzone-preview" alt="Landscape cover" style="display:none;">
                            <div style="padding: 8px 0;">
                                <i class="fa-solid fa-laptop" style="font-size:1.1rem; color:var(--be-text-muted); margin-bottom:4px;"></i>
                                <div class="be-dropzone-text">16:9 landscape</div>
                            </div>
                        </div>
                        <input type="file" id="bc-img-land-file" name="image_landscape" accept="image/*" style="display:none;" onchange="previewCoverImage(this, 'coverPreviewLand')">
                    </div>
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
                                <div class="be-serp-url" id="serpUrlPreview">https://arigatodevan.com/blog.php?slug=new-post</div>
                            </div>
                        </div>
                        <div class="be-serp-title" id="serpTitlePreview">Your Article Title &ndash; Arigato Devan Prompts</div>
                        <div class="be-serp-desc" id="serpDescPreview">Read the latest AI prompt guides and creative tutorials on Arigato Devan...</div>
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
                    <input type="text" class="be-form-input" id="bc-slug" name="slug" value="<?= htmlspecialchars($_POST["slug"] ?? "") ?>" placeholder="e.g. how-to-create-cinematic-ai-prompts">
                </div>

                <!-- Meta Title -->
                <div class="be-form-group" style="margin-bottom: 8px;">
                    <div class="be-form-label">
                        <span>Meta Title</span>
                        <span id="metaTitleCharCount">0 / 60</span>
                    </div>
                    <input type="text" class="be-form-input" id="bc-mt" name="meta_title" value="<?= htmlspecialchars($_POST["meta_title"] ?? "") ?>" placeholder="Leave blank to use article title">
                    <div class="be-char-bar"><div class="be-char-fill" id="metaTitleBar"></div></div>
                </div>

                <!-- Meta Description -->
                <div class="be-form-group">
                    <div class="be-form-label">
                        <span>Meta Description</span>
                        <span id="metaDescCharCount">0 / 160</span>
                    </div>
                    <textarea class="be-form-textarea" id="bc-md" name="meta_description" rows="2" placeholder="Compelling summary shown on Google search results..."><?= htmlspecialchars($_POST["meta_description"] ?? "") ?></textarea>
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
                <input type="hidden" name="tags" id="hiddenTagsInput" value="<?= htmlspecialchars($_POST["tags"] ?? "") ?>">
                <div class="be-tags-wrapper" id="tagChipsWrapper" onclick="document.getElementById('tagInputGhost').focus()">
                    <input type="text" id="tagInputGhost" class="be-tag-input" placeholder="Add tag & press Enter...">
                </div>
            </div>

        </aside>
    </div>
</form>

<!-- ── JavaScript Engine ── -->
<script>
function escapeImgAttr(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;');
}
function applyImageSeoMeta(editor) {
    if (!editor || !editor.dom) return;
    editor.dom.select('img').forEach(function (img) {
        var alt = (img.getAttribute('alt') || '').trim();
        if (alt) img.setAttribute('title', alt);
    });
}
function getImageAlign(img) {
    var saved = (img.getAttribute('data-align') || '').toLowerCase();
    if (saved === 'left' || saved === 'right' || saved === 'center') return saved;
    var ml = (img.style.marginLeft || '').trim();
    var mr = (img.style.marginRight || '').trim();
    if (ml === '0px' || ml === '0') return 'left';
    if (mr === '0px' || mr === '0') return 'right';
    return 'center';
}
function setImageAlign(editor, img, align) {
    if (!img || img.nodeName !== 'IMG') return;
    if (align !== 'left' && align !== 'right' && align !== 'center') align = 'left';
    var keepWidth = (img.style.width || '').trim();
    if (!keepWidth && img.getAttribute('width')) keepWidth = img.getAttribute('width') + 'px';
    img.setAttribute('data-align', align);
    editor.dom.removeClass(img, 'align-left');
    editor.dom.removeClass(img, 'align-right');
    editor.dom.removeClass(img, 'align-center');
    editor.dom.addClass(img, 'align-' + align);
    editor.dom.setAttrib(img, 'align', '');
    editor.dom.setStyles(img, {
        float: 'none',
        display: 'block',
        clear: 'both',
        maxWidth: '100%',
        height: 'auto',
        marginTop: '1.5em',
        marginBottom: '1.25em',
        marginLeft: align === 'left' ? '0' : 'auto',
        marginRight: align === 'right' ? '0' : 'auto'
    });
    if (keepWidth && keepWidth !== 'auto') {
        editor.dom.setStyle(img, 'width', keepWidth);
    }
    var parent = img.parentNode;
    if (parent && (parent.nodeName === 'P' || parent.nodeName === 'FIGURE' || parent.nodeName === 'DIV')) {
        editor.dom.setStyle(parent, 'textAlign', align);
        parent.setAttribute('data-img-align', align);
    }
}
function syncImageDisplayWidth(editor, img) {
    if (!img || img.nodeName !== 'IMG') return;
    var shown = Math.round(img.getBoundingClientRect().width);
    if (shown < 8) return;
    var declared = parseInt(img.style.width, 10) || parseInt(img.getAttribute('width'), 10) || 0;
    if (!declared || declared > shown + 8) {
        editor.dom.setStyle(img, 'width', shown + 'px');
        editor.dom.setStyle(img, 'height', 'auto');
        img.removeAttribute('width');
        img.removeAttribute('height');
    }
}
function isolateImageBlock(editor, img) {
    var align = getImageAlign(img);
    var parent = img.parentNode;
    var block;
    if (parent && parent.nodeName === 'FIGURE') {
        block = parent;
    } else if (parent && parent.nodeName === 'P') {
        if (parent.childNodes.length === 1) {
            block = parent;
        } else {
            block = editor.dom.create('p');
            parent.parentNode.insertBefore(block, parent);
            block.appendChild(img);
            if (!(parent.textContent || '').replace(/\u00a0/g, '').trim() && !parent.querySelector('img')) {
                editor.dom.remove(parent);
            }
        }
    } else {
        block = editor.dom.create('p');
        img.parentNode.insertBefore(block, img);
        block.appendChild(img);
    }
    setImageAlign(editor, img, align);
    return block;
}
function insertWritePara(editor, where) {
    var node = editor.selection.getNode();
    if (node && node.nodeName !== 'IMG') node = editor.dom.getParent(node, 'img');
    if (!node || node.nodeName !== 'IMG') return;
    var block = isolateImageBlock(editor, node);
    var p = editor.dom.create('p', { style: 'margin-top:1.15em;min-height:1.8em;' }, '<br data-mce-bogus="1">');
    if (where === 'before') editor.dom.insertBefore(p, block);
    else editor.dom.insertAfter(p, block);
    editor.selection.setCursorLocation(p, 0);
    editor.nodeChanged();
    editor.focus();
}
function ensureWriteSpaceAroundImages(editor) {
    if (!editor || !editor.getBody) return;
    var body = editor.getBody();
    if (!body) return;
    var last = body.lastElementChild;
    if (!last) {
        body.appendChild(editor.dom.create('p', {}, '<br data-mce-bogus="1">'));
        return;
    }
    var hasImg = last.nodeName === 'IMG' || (last.querySelector && last.querySelector('img'));
    var empty = !(last.textContent || '').replace(/\u00a0/g, '').trim();
    if (hasImg && (last.nodeName === 'IMG' || empty)) {
        var next = last.nextElementSibling;
        if (!next || next.nodeName !== 'P') {
            body.appendChild(editor.dom.create('p', {}, '<br data-mce-bogus="1">'));
        }
    }
}
function setImageBorderWidth(editor, img, width) {
    if (!img || img.nodeName !== 'IMG') return;
    width = parseInt(width, 10);
    if (isNaN(width) || width <= 0) {
        editor.dom.removeClass(img, 'img-border');
        editor.dom.setStyle(img, 'border', '');
        editor.dom.setStyle(img, 'box-sizing', '');
        img.removeAttribute('data-border-width');
    } else {
        editor.dom.addClass(img, 'img-border');
        editor.dom.setStyle(img, 'border', width + 'px solid #000000');
        editor.dom.setStyle(img, 'border-radius', '12px');
        editor.dom.setStyle(img, 'box-sizing', 'border-box');
        img.setAttribute('data-border-width', width);
    }
    editor.nodeChanged();
}
function toggleImageBorder(editor, img) {
    if (!img || img.nodeName !== 'IMG') return;
    var currentW = parseInt(img.getAttribute('data-border-width'), 10) || 0;
    if (currentW > 0) {
        setImageBorderWidth(editor, img, 0);
    } else {
        setImageBorderWidth(editor, img, 1);
    }
}
function registerImageWriteButtons(editor) {
    editor.ui.registry.addButton('writebefore', {
        text: 'Write before',
        tooltip: 'Type above this image',
        onAction: function () { insertWritePara(editor, 'before'); }
    });
    editor.ui.registry.addButton('writeafter', {
        text: 'Write after',
        tooltip: 'Type below this image',
        onAction: function () { insertWritePara(editor, 'after'); }
    });
    function currentImg() {
        var node = editor.selection.getNode();
        if (node && node.nodeName !== 'IMG') node = editor.dom.getParent(node, 'img');
        return node && node.nodeName === 'IMG' ? node : null;
    }
    editor.ui.registry.addButton('imgalignleft', {
        icon: 'align-left',
        tooltip: 'Align left',
        onAction: function () {
            var img = currentImg();
            if (img) setImageAlign(editor, img, 'left');
        }
    });
    editor.ui.registry.addButton('imgaligncenter', {
        icon: 'align-center',
        tooltip: 'Align center',
        onAction: function () {
            var img = currentImg();
            if (img) setImageAlign(editor, img, 'center');
        }
    });
    editor.ui.registry.addButton('imgalignright', {
        icon: 'align-right',
        tooltip: 'Align right',
        onAction: function () {
            var img = currentImg();
            if (img) setImageAlign(editor, img, 'right');
        }
    });
    editor.ui.registry.addMenuButton('imgborder', {
        text: 'Border',
        tooltip: 'Border line thickness (Black)',
        fetch: function (callback) {
            var img = currentImg();
            var currentW = 0;
            if (img) {
                var declaredW = img.getAttribute('data-border-width');
                if (declaredW) {
                    currentW = parseInt(declaredW, 10);
                } else if (img.style.border && img.style.border !== 'none') {
                    var match = img.style.border.match(/(\d+)px/);
                    if (match) currentW = parseInt(match[1], 10);
                    else currentW = 1;
                }
            }
            var items = [
                {
                    type: 'menuitem',
                    text: (currentW === 0 ? '✓ ' : '   ') + 'No Border',
                    onAction: function () {
                        if (img) setImageBorderWidth(editor, img, 0);
                    }
                },
                {
                    type: 'menuitem',
                    text: (currentW === 1 ? '✓ ' : '   ') + 'Thin (1px)',
                    onAction: function () {
                        if (img) setImageBorderWidth(editor, img, 1);
                    }
                },
                {
                    type: 'menuitem',
                    text: (currentW === 2 ? '✓ ' : '   ') + 'Medium (2px)',
                    onAction: function () {
                        if (img) setImageBorderWidth(editor, img, 2);
                    }
                },
                {
                    type: 'menuitem',
                    text: (currentW === 3 ? '✓ ' : '   ') + 'Thick (3px)',
                    onAction: function () {
                        if (img) setImageBorderWidth(editor, img, 3);
                    }
                },
                {
                    type: 'menuitem',
                    text: (currentW === 4 ? '✓ ' : '   ') + 'Extra Thick (4px)',
                    onAction: function () {
                        if (img) setImageBorderWidth(editor, img, 4);
                    }
                },
                {
                    type: 'menuitem',
                    text: 'Custom Width...',
                    onAction: function () {
                        if (!img) return;
                        var val = window.prompt('Enter border width in pixels (e.g. 1, 2, 5):', currentW > 0 ? currentW : '2');
                        if (val !== null && val.trim() !== '') {
                            var num = parseInt(val, 10);
                            setImageBorderWidth(editor, img, isNaN(num) ? 0 : num);
                        }
                    }
                }
            ];
            callback(items);
        }
    });
    editor.ui.registry.addButton('imgcaption', {
        text: 'Caption',
        tooltip: 'Add / Edit Image Caption',
        onAction: function () {
            var img = currentImg();
            if (img) toggleImageCaption(editor, img);
        }
    });
    editor.on('click', function (e) {
        if (e.target && e.target.nodeName === 'IMG') {
            editor.selection.select(e.target);
            syncImageDisplayWidth(editor, e.target);
        }
    });
    editor.on('ObjectResized', function (e) {
        if (e.target && e.target.nodeName === 'IMG') {
            editor.dom.setStyle(e.target, 'height', 'auto');
        }
    });
    editor.on('SetContent NodeChange', function () {
        ensureWriteSpaceAroundImages(editor);
    });
}
function ensureTinyImageDialogStyles() {
    if (document.getElementById('blog-tinymce-image-css')) return;
    var css = document.createElement('style');
    css.id = 'blog-tinymce-image-css';
    css.textContent = `
        .tox-dialog.blog-img-dialog {
            max-width: 460px !important;
        }
        .tox-dialog.blog-img-dialog .tox-dialog__body-nav {
            display: none !important;
        }
        .tox-dialog.blog-img-dialog .tox-dialog__body {
            display: block !important;
        }
        .tox-dialog.blog-img-dialog .tox-dialog__body-content {
            padding: 4px 2px 2px !important;
            max-height: none !important;
        }
        .tox-dialog.blog-img-dialog .blog-img-stack {
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 16px !important;
            width: 100% !important;
        }
        .tox-dialog.blog-img-dialog .tox-dropzone {
            width: 100% !important;
            min-height: 150px !important;
            margin: 0 !important;
            border-radius: 12px !important;
            box-sizing: border-box !important;
        }
        .tox-dialog.blog-img-dialog .blog-img-seo,
        .tox-dialog.blog-img-dialog .blog-img-width {
            display: flex !important;
            flex-direction: column !important;
            gap: 6px !important;
            width: 100% !important;
            margin: 0 !important;
        }
        .tox-dialog.blog-img-dialog .blog-img-seo label,
        .tox-dialog.blog-img-dialog .blog-img-width label {
            font-weight: 600 !important;
            color: #334155 !important;
        }
        .tox-dialog.blog-img-dialog .blog-img-seo textarea,
        .tox-dialog.blog-img-dialog .blog-img-width input {
            width: 100% !important;
            box-sizing: border-box !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            padding: 10px 12px !important;
            font-size: 14px !important;
            font-family: inherit !important;
        }
        .tox-dialog.blog-img-dialog .blog-img-seo textarea {
            min-height: 84px !important;
            resize: vertical !important;
            line-height: 1.45 !important;
        }
        .tox-dialog.blog-img-dialog .blog-img-seo textarea:focus,
        .tox-dialog.blog-img-dialog .blog-img-width input:focus {
            outline: none !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59,130,246,.15) !important;
        }
        .tox-dialog.blog-img-dialog .blog-img-hint {
            margin: 0 !important;
            font-size: 12px !important;
            color: #64748b !important;
            line-height: 1.4 !important;
        }
        .tox-dialog.blog-img-dialog .blog-img-sizes {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }
        .tox-dialog.blog-img-dialog .blog-img-sizes button {
            height: 36px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
        }
        .tox-dialog.blog-img-dialog .blog-img-sizes button:hover {
            border-color: #3b82f6;
            color: #1d4ed8;
            background: #eff6ff;
        }
        .tox-dialog.blog-img-dialog .tox-form__controls-h-stack {
            display: block !important;
        }
    `;
    document.head.appendChild(css);
}
function simplifyTinyImageDialog() {
    ensureTinyImageDialogStyles();
    var dialog = document.querySelector('.tox-dialog');
    if (!dialog) return;
    var title = dialog.querySelector('.tox-dialog__title');
    if (!title || !/image/i.test(title.textContent || '')) return;

    var uploadTab = null;
    dialog.querySelectorAll('.tox-tab').forEach(function (tab) {
        var label = (tab.textContent || '').trim();
        if (/general|advanced/i.test(label)) tab.style.display = 'none';
        if (/upload/i.test(label)) uploadTab = tab;
    });
    if (uploadTab) uploadTab.click();

    window.setTimeout(function () {
        dialog.classList.add('blog-img-dialog');
        var seoGroup = null;
        var host = dialog.querySelector('.tox-dialog__body-content') || dialog;

        dialog.querySelectorAll('label').forEach(function (label) {
            var text = (label.textContent || '').replace(/\s+/g, ' ').trim();
            if (/^(Source|Width|Height)$/i.test(text)) {
                var box = label.closest('.tox-form__group') || label.parentElement;
                if (box) box.style.display = 'none';
            }
        });
        dialog.querySelectorAll('.tox-form__group').forEach(function (group) {
            var label = group.querySelector('label');
            var text = label ? (label.textContent || '').replace(/\s+/g, ' ').trim() : '';
            if (/source|width|height|caption|border|vspace|hspace|style|class|title/i.test(text) && !/description|seo/i.test(text)) {
                group.style.display = 'none';
            }
            if (/alternative description|description/i.test(text)) {
                seoGroup = group;
                group.style.display = '';
                group.classList.add('blog-img-seo');
                if (label) label.textContent = 'SEO description';
            }
        });

        var dropzone = dialog.querySelector('.tox-dropzone');
        var uploadPanel = dropzone ? dropzone.parentElement : host;
        uploadPanel.classList.add('blog-img-stack');

        if (seoGroup) {
            var nativeInput = seoGroup.querySelector('input:not(.blog-img-seo-field), textarea:not(.blog-img-seo-field)');
            if (nativeInput && !seoGroup.querySelector('.blog-img-seo-field')) {
                var ta = document.createElement('textarea');
                ta.className = 'blog-img-seo-field';
                ta.rows = 3;
                ta.placeholder = 'What is this image about? Used as alt text.';
                ta.value = nativeInput.value || '';
                ta.addEventListener('input', function () {
                    nativeInput.value = ta.value;
                    nativeInput.dispatchEvent(new Event('input', { bubbles: true }));
                    nativeInput.dispatchEvent(new Event('change', { bubbles: true }));
                });
                nativeInput.style.display = 'none';
                nativeInput.setAttribute('tabindex', '-1');
                seoGroup.appendChild(ta);
                var hint = document.createElement('p');
                hint.className = 'blog-img-hint';
                hint.textContent = 'Saved on the image as alt and title for search engines.';
                seoGroup.appendChild(hint);
            }
            uploadPanel.appendChild(seoGroup);
        }

        var tabNav = dialog.querySelector('.tox-dialog__body-nav');
        if (tabNav) tabNav.style.display = 'none';
    }, 50);
}

tinymce.init({
    selector: '#blog-editor',
    height: Math.max(760, window.innerHeight - 170),
    min_height: 600,
    menubar: false,
    statusbar: false,
    branding: false,
    promotion: false,
    toolbar: false,
    plugins: 'image link lists table code codesample charmap emoticons wordcount autosave visualblocks quickbars',
    quickbars_selection_toolbar: 'bold italic underline caseTransform | highlightBtn removeHighlightBtn | quicklink | alignleft aligncenter alignright | fontfamily blocks | numlist bullist continueListBtn',
    quickbars_insert_toolbar: window.matchMedia('(max-width: 768px)').matches ? false : 'image link table codesample',
    content_css: ['default', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'],
    contextmenu: 'link image table editFaqMenuItem editCarouselMenuItem continueListMenuItem setListStartMenuItem restartListMenuItem removeHighlightMenuItem',
    table_toolbar: 'tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol',
    table_default_styles: { width: '100%', 'border-collapse': 'collapse' },
    table_resize_bars: true,
    paste_webkit_styles: 'all',
    extended_valid_elements: 'div[class|style|id|contenteditable|data-ratio],button[class|style|type|aria-label],span[class|style|id|contenteditable|data-index],figure[class|style],figcaption[class|style|contenteditable],ol[class|style|start],li[class|style],h1[id|class|style|contenteditable],h2[id|class|style|contenteditable],h3[id|class|style|contenteditable],h4[id|class|style|contenteditable],mark[class|style],a[href|target|rel|title|class|id|contenteditable],img[class|src|alt|title|width|height|style|data-align|data-border-width|loading],table[border|cellpadding|cellspacing|width|class|style],thead,tbody,tfoot,tr[class|style],th[colspan|rowspan|class|style|scope|width],td[colspan|rowspan|class|style|width],caption,colgroup,col[span|width],p[class|style|id|dir|contenteditable]',
    formats: {
        highlight: { inline: 'mark', classes: 'font-highlight', styles: { 'background-color': '#fef08a', 'padding': '2px 6px', 'border-radius': '4px', 'color': '#0f172a' } }
    },
    font_family_formats: 'Editorial Serif=Lora, serif; Display Serif=Playfair Display, serif; Modern Bold=Plus Jakarta Sans, sans-serif; Inter Sans=Inter, sans-serif; Monospace=JetBrains Mono, monospace',
    quickbars_image_toolbar: 'writebefore writeafter | imgalignleft imgaligncenter imgalignright | imgborder | imgcaption | editimage',
    link_assume_external_targets: 'https',
    default_link_target: '_blank',
    link_context_toolbar: true,
    link_title: true,
    target_list: [
        { text: 'New window (External link)', value: '_blank' },
        { text: 'Same window (Internal link)', value: '' }
    ],
    rel_list: [
        { text: 'None / Normal', value: '' },
        { text: 'No Referrer / Noopener (Safe External)', value: 'noopener noreferrer' },
        { text: 'Nofollow (SEO nofollow)', value: 'nofollow' },
        { text: 'Sponsored (Paid link)', value: 'sponsored' }
    ],
    image_advtab: false,
    image_caption: false,
    image_dimensions: false,
    image_title: false,
    image_description: true,
    image_uploadtab: true,
    image_prepend_upload_tab: true,
    automatic_uploads: true,
    object_resizing: true,
    resize_img_proportional: true,
    paste_data_images: true,
    
    setup: function (editor) {
        registerImageWriteButtons(editor);

        // ── Text Case Transformation Menu (Aa Capitalize & AA UPPERCASE) ─────
        function applyCaseTransform(mode) {
            var rawHtml = editor.selection.getContent();
            if (!rawHtml) return;
            var tmp = document.createElement('div');
            tmp.innerHTML = rawHtml;
            function walk(node) {
                if (node.nodeType === 3) {
                    var v = node.nodeValue;
                    if (mode === 'upper') {
                        node.nodeValue = v.toUpperCase();
                    } else if (mode === 'lower') {
                        node.nodeValue = v.toLowerCase();
                    } else if (mode === 'title') {
                        node.nodeValue = v.replace(/\b[a-zA-Z]/g, function(c) { return c.toUpperCase(); });
                    } else if (mode === 'sentence') {
                        node.nodeValue = v.toLowerCase().replace(/(^\s*\w|[.!?]\s*\w)/g, function(c) { return c.toUpperCase(); });
                    }
                } else if (node.nodeType === 1) {
                    for (var i = 0; i < node.childNodes.length; i++) {
                        walk(node.childNodes[i]);
                    }
                }
            }
            walk(tmp);
            editor.selection.setContent(tmp.innerHTML);
            editor.nodeChanged();
        }

        editor.ui.registry.addMenuButton('caseTransform', {
            text: 'aA',
            tooltip: 'Change Case (Capitalize / UPPERCASE)',
            fetch: function (callback) {
                callback([
                    {
                        type: 'menuitem',
                        text: 'Aa  First Letter Capitalize (Title Case)',
                        onAction: function () { applyCaseTransform('title'); }
                    },
                    {
                        type: 'menuitem',
                        text: 'AA  FULL CAPITAL (UPPERCASE)',
                        onAction: function () { applyCaseTransform('upper'); }
                    },
                    {
                        type: 'menuitem',
                        text: 'aa  all lowercase',
                        onAction: function () { applyCaseTransform('lower'); }
                    },
                    {
                        type: 'menuitem',
                        text: 'A... Sentence case',
                        onAction: function () { applyCaseTransform('sentence'); }
                    }
                ]);
            }
        });

        // ── Custom Toolbar SVG Icons ──────────────────────────────────────────
        editor.ui.registry.addIcon('be-continue-list', '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 6h11M10 12h11M10 18h11M4 6h1v4M4 10h2M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"></path></svg>');
        editor.ui.registry.addIcon('be-start-num', '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>');
        editor.ui.registry.addIcon('be-restart-list', '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>');

        // ── Prompt Card Floating Context Toolbar & Handlers ──────────────────
        editor.ui.registry.addButton('swapPromptCardBtn', {
            icon: 'duplicate',
            text: 'Swap Prompt',
            tooltip: 'Swap this card with another prompt from your site',
            onAction: function () {
                var node = editor.selection.getNode();
                var card = editor.dom.getParent(node, '.blog-prompt-card');
                if (card) {
                    openNoteBoxModalForEdit(card);
                }
            }
        });

        editor.ui.registry.addButton('removePromptCardBtn', {
            icon: 'remove',
            text: 'Delete Card',
            tooltip: 'Delete this card',
            onAction: function () {
                var node = editor.selection.getNode();
                var card = editor.dom.getParent(node, '.blog-prompt-card');
                if (card) {
                    editor.dom.remove(card);
                    editor.nodeChanged();
                }
            }
        });

        editor.ui.registry.addContextToolbar('promptCardToolbar', {
            predicate: function (node) {
                return !!editor.dom.getParent(node, '.blog-prompt-card');
            },
            items: 'swapPromptCardBtn removePromptCardBtn',
            position: 'node',
            scope: 'node'
        });

        // ── FAQ Box Floating Context Toolbar & Handlers ──────────────────
        editor.ui.registry.addButton('editFaqBtn', {
            icon: 'edit-block',
            text: 'Edit FAQ',
            tooltip: 'Edit FAQ questions, answers, and theme in popup',
            onAction: function () {
                var node = editor.selection.getNode();
                var faqBox = editor.dom.getParent(node, '.blog-faq-box');
                if (faqBox) {
                    openFaqModalForEdit(faqBox);
                }
            }
        });

        editor.ui.registry.addButton('removeFaqBtn', {
            icon: 'remove',
            text: 'Delete FAQ',
            tooltip: 'Delete this FAQ section',
            onAction: function () {
                var node = editor.selection.getNode();
                var faqBox = editor.dom.getParent(node, '.blog-faq-box');
                if (faqBox) {
                    editor.dom.remove(faqBox);
                    editor.nodeChanged();
                }
            }
        });

        editor.ui.registry.addMenuItem('editFaqMenuItem', {
            text: 'Edit FAQ Section...',
            icon: 'edit-block',
            onAction: function () {
                var node = editor.selection.getNode();
                var faqBox = editor.dom.getParent(node, '.blog-faq-box');
                if (faqBox) {
                    openFaqModalForEdit(faqBox);
                }
            }
        });

        editor.ui.registry.addContextToolbar('faqBoxToolbar', {
            predicate: function (node) {
                return !!editor.dom.getParent(node, '.blog-faq-box');
            },
            items: 'editFaqBtn removeFaqBtn',
            position: 'node',
            scope: 'node'
        });

        // ── Ordered List Numbering Handlers ───────────────────────────────────
        editor.ui.registry.addButton('continueListBtn', {
            icon: 'be-continue-list',
            text: 'Continue',
            tooltip: 'Continue numbering from previous numbered list (e.g. 2, 3...)',
            onAction: function () {
                var node = editor.selection.getNode();
                var ol = editor.dom.getParent(node, 'ol');
                if (ol) continueOlNumbering(editor, ol);
                else continueCurrentOrderedList();
            }
        });

        editor.ui.registry.addButton('setListStartBtn', {
            icon: 'be-start-num',
            text: 'Start #',
            tooltip: 'Set starting number for this list',
            onAction: function () {
                var node = editor.selection.getNode();
                var ol = editor.dom.getParent(node, 'ol');
                if (ol) setOlStartNumber(editor, ol);
            }
        });

        editor.ui.registry.addButton('restartListBtn', {
            icon: 'be-restart-list',
            text: 'Restart',
            tooltip: 'Restart numbering at 1',
            onAction: function () {
                var node = editor.selection.getNode();
                var ol = editor.dom.getParent(node, 'ol');
                if (ol) restartOlAtOne(editor, ol);
            }
        });

        editor.ui.registry.addContextToolbar('orderedListToolbar', {
            predicate: function (node) {
                return !!editor.dom.getParent(node, 'ol');
            },
            items: 'continueListBtn setListStartBtn restartListBtn',
            position: 'node',
            scope: 'node'
        });

        editor.ui.registry.addMenuItem('continueListMenuItem', {
            text: 'Continue List Numbering',
            icon: 'be-continue-list',
            onAction: function () {
                var node = editor.selection.getNode();
                var ol = editor.dom.getParent(node, 'ol');
                if (ol) continueOlNumbering(editor, ol);
            }
        });

        editor.ui.registry.addMenuItem('setListStartMenuItem', {
            text: 'Set Starting Number...',
            icon: 'be-start-num',
            onAction: function () {
                var node = editor.selection.getNode();
                var ol = editor.dom.getParent(node, 'ol');
                if (ol) setOlStartNumber(editor, ol);
            }
        });

        editor.ui.registry.addMenuItem('restartListMenuItem', {
            text: 'Restart Numbering at 1',
            icon: 'be-restart-list',
            onAction: function () {
                var node = editor.selection.getNode();
                var ol = editor.dom.getParent(node, 'ol');
                if (ol) restartOlAtOne(editor, ol);
            }
        });

        // ── Highlight & Remove Highlight UI ──────────────────────────────────
        editor.ui.registry.addButton('highlightBtn', {
            icon: 'highlight-bg-color',
            tooltip: 'Highlight Text (Yellow Marker)',
            onAction: function () {
                toggleHighlight();
            }
        });

        editor.ui.registry.addButton('removeHighlightBtn', {
            icon: 'remove-formatting',
            tooltip: 'Remove Highlight',
            onAction: function () {
                removeHighlight();
            }
        });

        editor.ui.registry.addMenuItem('removeHighlightMenuItem', {
            text: 'Remove Highlight',
            icon: 'remove-formatting',
            onAction: function () {
                removeHighlight();
            }
        });

        // ── Carousel Box Floating Context Toolbar & Handlers ──────────────────
        editor.ui.registry.addButton('editCarouselBtn', {
            icon: 'image-options',
            text: 'Edit Carousel',
            tooltip: 'Edit slides, ratio, and images in popup',
            onAction: function () {
                var node = editor.selection.getNode();
                var box = editor.dom.getParent(node, '.blog-carousel-box');
                if (box) openEditorCarouselModalForEdit(box);
            }
        });

        editor.ui.registry.addButton('removeCarouselBtn', {
            icon: 'remove',
            text: 'Delete Carousel',
            tooltip: 'Delete this carousel',
            onAction: function () {
                var node = editor.selection.getNode();
                var box = editor.dom.getParent(node, '.blog-carousel-box');
                if (box) {
                    editor.dom.remove(box);
                    editor.nodeChanged();
                }
            }
        });

        editor.ui.registry.addMenuItem('editCarouselMenuItem', {
            text: 'Edit Carousel...',
            icon: 'image-options',
            onAction: function () {
                var node = editor.selection.getNode();
                var box = editor.dom.getParent(node, '.blog-carousel-box');
                if (box) openEditorCarouselModalForEdit(box);
            }
        });

        editor.ui.registry.addContextToolbar('carouselBoxToolbar', {
            predicate: function (node) {
                return !!editor.dom.getParent(node, '.blog-carousel-box');
            },
            items: 'editCarouselBtn removeCarouselBtn',
            position: 'node',
            scope: 'node'
        });

        // ── Table of Contents Floating Context Toolbar ──
        editor.ui.registry.addButton('tocRefreshBtn', {
            icon: 'reload',
            text: 'Update TOC',
            tooltip: 'Auto-update Table of Contents from H2/H3 headings',
            onAction: function () {
                insertTableOfContents();
            }
        });

        editor.ui.registry.addButton('tocSizeSmBtn', {
            text: 'Small',
            tooltip: 'Small (2 Columns, Compact)',
            onAction: function () {
                setTocSize('sm');
            }
        });

        editor.ui.registry.addButton('tocSizeMdBtn', {
            text: 'Medium',
            tooltip: 'Medium (2 Columns, Standard)',
            onAction: function () {
                setTocSize('md');
            }
        });

        editor.ui.registry.addButton('tocSizeLgBtn', {
            text: 'Large',
            tooltip: 'Large (Spacious)',
            onAction: function () {
                setTocSize('lg');
            }
        });

        editor.ui.registry.addButton('tocRemoveBtn', {
            icon: 'remove',
            tooltip: 'Delete Table of Contents',
            onAction: function () {
                var node = editor.selection.getNode();
                var box = editor.dom.getParent(node, '.blog-toc-box');
                if (box) {
                    editor.dom.remove(box);
                    editor.nodeChanged();
                }
            }
        });

        editor.ui.registry.addContextToolbar('tocBoxToolbar', {
            predicate: function (node) {
                return !!editor.dom.getParent(node, '.blog-toc-box');
            },
            items: 'tocRefreshBtn | tocSizeSmBtn tocSizeMdBtn tocSizeLgBtn | tocRemoveBtn',
            position: 'node',
            scope: 'node'
        });

        // ── Code Block Floating Context Toolbar & Theme Switcher ──
        editor.ui.registry.addButton('codeThemeDark', {
            text: '🌙 Dark',
            tooltip: 'Dark Terminal Theme (Default)',
            onAction: function () {
                var node = editor.selection.getNode();
                var pre = editor.dom.getParent(node, 'pre');
                if (pre) {
                    pre.classList.remove('code-theme-light', 'code-theme-cyber');
                    pre.classList.add('code-theme-dark');
                    editor.nodeChanged();
                }
            }
        });

        editor.ui.registry.addButton('codeThemeLight', {
            text: '☀️ Light',
            tooltip: 'Clean Light / Minimalist Paper Theme',
            onAction: function () {
                var node = editor.selection.getNode();
                var pre = editor.dom.getParent(node, 'pre');
                if (pre) {
                    pre.classList.remove('code-theme-dark', 'code-theme-cyber');
                    pre.classList.add('code-theme-light');
                    editor.nodeChanged();
                }
            }
        });

        editor.ui.registry.addButton('codeThemeCyber', {
            text: '⚡ Cyber',
            tooltip: 'Cyberpunk Neon Glow Theme',
            onAction: function () {
                var node = editor.selection.getNode();
                var pre = editor.dom.getParent(node, 'pre');
                if (pre) {
                    pre.classList.remove('code-theme-dark', 'code-theme-light');
                    pre.classList.add('code-theme-cyber');
                    editor.nodeChanged();
                }
            }
        });

        editor.ui.registry.addButton('removeCodeBlockBtn', {
            icon: 'remove',
            tooltip: 'Delete Code Block',
            onAction: function () {
                var node = editor.selection.getNode();
                var pre = editor.dom.getParent(node, 'pre');
                if (pre) {
                    editor.dom.remove(pre);
                    editor.nodeChanged();
                }
            }
        });

        editor.ui.registry.addContextToolbar('codeBlockToolbar', {
            predicate: function (node) {
                return !!editor.dom.getParent(node, 'pre');
            },
            items: 'codeThemeDark codeThemeLight codeThemeCyber | removeCodeBlockBtn',
            position: 'node',
            scope: 'node'
        });

        // ── Prevent Pasted Content from Escaping/Breaking Code Blocks ──
        editor.on('paste', function (e) {
            var node = editor.selection.getNode();
            var pre = editor.dom.getParent(node, 'pre');
            if (pre) {
                e.preventDefault();
                e.stopPropagation();
                var clipboardData = e.clipboardData || window.clipboardData;
                var text = clipboardData ? clipboardData.getData('text/plain') : '';
                if (text) {
                    var doc = editor.getDoc();
                    var textNode = doc.createTextNode(text);
                    var range = editor.selection.getRng();
                    range.deleteContents();
                    range.insertNode(textNode);
                    range.setStartAfter(textNode);
                    range.setEndAfter(textNode);
                    editor.selection.setRng(range);
                    editor.nodeChanged();
                }
            }
        });

        editor.on('keydown', function (e) {
            if (e.key === 'Enter') {
                var node = editor.selection.getNode();
                var pre = editor.dom.getParent(node, 'pre');
                if (pre) {
                    e.preventDefault();
                    editor.execCommand('InsertLineBreak');
                }
            }
        });

        // ── Multi-Word Selection with Ctrl Key ────────────────────────────────
        editor.on('click', function (e) {
            if (!e.ctrlKey && !e.metaKey) {
                var body = editor.getBody();
                if (body && body.querySelector('.mce-ctrl-multiselect')) {
                    clearMultiSelections(editor);
                }
            }
        });

        editor.on('mouseup', function (e) {
            if (e.ctrlKey || e.metaKey) {
                var selContent = editor.selection.getContent({ format: 'text' }).trim();
                if (selContent.length > 0) {
                    var rng = editor.selection.getRng();
                    if (rng && !rng.collapsed) {
                        try {
                            var span = editor.dom.create('span', {
                                'class': 'mce-ctrl-multiselect',
                                'style': 'background: rgba(59, 130, 246, 0.25) !important; outline: 1.5px solid #2563eb !important; border-radius: 3px !important;'
                            });
                            rng.surroundContents(span);
                        } catch (err) {
                            // Cross-boundary selection fallback
                        }
                    }
                }
            }
        });

        editor.on('dblclick', function (e) {
            var card = editor.dom.getParent(e.target, '.blog-prompt-card');
            if (card) {
                e.preventDefault();
                openNoteBoxModalForEdit(card);
                return;
            }
            var faqBox = editor.dom.getParent(e.target, '.blog-faq-box');
            if (faqBox) {
                e.preventDefault();
                openFaqModalForEdit(faqBox);
                return;
            }
            var carouselBox = editor.dom.getParent(e.target, '.blog-carousel-box');
            if (carouselBox) {
                e.preventDefault();
                openEditorCarouselModalForEdit(carouselBox);
                return;
            }
        });

        editor.on('contextmenu', function (e) {
            var faqBox = editor.dom.getParent(e.target, '.blog-faq-box');
            if (faqBox) {
                editor.selection.select(faqBox);
                return;
            }
            var carouselBox = editor.dom.getParent(e.target, '.blog-carousel-box');
            if (carouselBox) {
                editor.selection.select(carouselBox);
                return;
            }
        });

        // Override TinyMCE's internal shortcuts for Ctrl+A / Cmd+A
        function selectCodeBlockOnly() {
            var selNode = editor.selection.getNode();
            var targetPre = editor.dom.getParent(selNode, 'pre');
            if (targetPre) {
                var codeEl = targetPre.querySelector('code') || targetPre;
                var rng = editor.dom.createRng();
                rng.selectNodeContents(codeEl);
                editor.selection.setRng(rng);
                return true;
            }
            return false;
        }

        editor.addShortcut('meta+a', 'Select code block or all', function () {
            if (!selectCodeBlockOnly()) {
                editor.execCommand('SelectAll');
            }
        });
        editor.addShortcut('ctrl+a', 'Select code block or all', function () {
            if (!selectCodeBlockOnly()) {
                editor.execCommand('SelectAll');
            }
        });

        // Synthetic keydown fallback on editor
        editor.on('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'a' || e.key === 'A' || e.keyCode === 65)) {
                if (selectCodeBlockOnly()) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }
        });

        function updatePreWordCounts() {
            var pres = editor.dom.select('pre');
            pres.forEach(function(p) {
                var text = (p.textContent || p.innerText || '').trim();
                var count = text ? (text.match(/\S+/g) || []).length : 0;
                p.setAttribute('data-words', count + (count === 1 ? ' word' : ' words'));
            });
        }

        editor.on('init', function () {
            editor.getContainer().style.border = 'none';
            editor.getContainer().style.boxShadow = 'none';
            editor.getContainer().style.background = 'transparent';
            updateEditorialStats();
            updatePreWordCounts();

            // Close toolbar dropdowns when clicking, focusing, or typing inside the editor
            editor.on('click focus keydown', function () {
                if (typeof closeAllBeDropdowns === 'function') {
                    closeAllBeDropdowns();
                }
            });

            // Intercept at raw DOM capture phase before browser or TinyMCE can trigger native SelectAll
            var doc = editor.getDoc();
            var win = editor.getWin();

            function handleCodeCtrlACapture(e) {
                if ((e.ctrlKey || e.metaKey) && (e.key === 'a' || e.key === 'A' || e.keyCode === 65)) {
                    var sel = win ? win.getSelection() : null;
                    var anchor = sel && sel.anchorNode ? sel.anchorNode : null;
                    var el = anchor ? (anchor.nodeType === 1 ? anchor : anchor.parentElement) : null;
                    var targetPre = (el && el.closest) ? el.closest('pre') : null;
                    if (!targetPre) {
                        var selNode = editor.selection.getNode();
                        targetPre = editor.dom.getParent(selNode, 'pre');
                    }

                    if (targetPre) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        e.stopPropagation();

                        var codeEl = targetPre.querySelector('code') || targetPre;
                        
                        // Set DOM Range on window selection
                        if (doc && doc.createRange && sel) {
                            var r = doc.createRange();
                            r.selectNodeContents(codeEl);
                            sel.removeAllRanges();
                            sel.addRange(r);
                        }

                        // Also update TinyMCE's internal selection
                        var rng = editor.dom.createRng();
                        rng.selectNodeContents(codeEl);
                        editor.selection.setRng(rng);

                        return false;
                    }
                }
            }

            if (doc) {
                doc.addEventListener('keydown', handleCodeCtrlACapture, true);
            }
            if (win) {
                win.addEventListener('keydown', handleCodeCtrlACapture, true);
            }
        });
        editor.on('NodeChange keyup change input', function () {
            updateEditorialStats();
            updatePreWordCounts();
        });
        editor.on('OpenWindow', function () {
            window.setTimeout(function () {
                simplifyTinyImageDialog();
            }, 0);
        });
        editor.on('CloseWindow', function () {
            applyImageSeoMeta(editor);
            ensureWriteSpaceAroundImages(editor);
        });
    },
    
    content_style: `
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lora:ital,wght@0,400;0,500;0,600;1,400&family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@700;800&family=JetBrains+Mono:wght@400;500&display=swap');
        html, body {
            overflow-y: auto !important;
            scroll-behavior: smooth;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }
        ::-webkit-scrollbar { width: 7px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        body {
            font-family: 'Lora', Georgia, serif;
            font-size: 1.12rem;
            line-height: 1.8;
            color: #1e293b;
            padding: 8px 6px;
            background: #ffffff;
        }
        h1, h1 * {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-size: 2.35rem !important;
            font-weight: 800 !important;
            color: #0f172a !important;
            line-height: 1.25 !important;
            letter-spacing: -0.02em !important;
            margin-top: 1.6em !important;
            margin-bottom: 0.5em !important;
        }
        h2, h2 * {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-size: 1.8rem !important;
            font-weight: 800 !important;
            color: #0f172a !important;
            line-height: 1.3 !important;
            margin-top: 1.5em !important;
            margin-bottom: 0.5em !important;
        }
        h3, h3 * {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-size: 1.4rem !important;
            font-weight: 700 !important;
            color: #0f172a !important;
            line-height: 1.35 !important;
            margin-top: 1.4em !important;
            margin-bottom: 0.5em !important;
        }
        h4, h4 * {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-size: 1.2rem !important;
            font-weight: 700 !important;
            color: #0f172a !important;
            line-height: 1.4 !important;
            margin-top: 1.2em !important;
            margin-bottom: 0.4em !important;
        }
        p {
            margin-bottom: 1.3em;
        }
        a {
            color: #2563eb;
            text-decoration: underline;
            text-underline-offset: 3px;
            font-weight: 500;
            cursor: pointer;
        }
        a:hover {
            color: #1d4ed8;
        }
        mark, .font-highlight {
            background-color: #fef08a !important;
            padding: 2px 6px;
            border-radius: 4px;
            color: #0f172a;
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
            position: relative !important;
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            background: #1e1e2e !important;
            border: 2px solid #2f4156 !important;
            border-radius: 14px !important;
            padding: 46px 20px 20px 20px !important;
            color: #93c5fd !important;
            font-family: 'DM Mono', 'JetBrains Mono', Consolas, Monaco, monospace !important;
            font-size: 0.92rem !important;
            line-height: 1.7 !important;
            max-height: 220px !important;
            overflow-y: auto !important;
            overflow-x: auto !important;
            margin: 1.6em 0 !important;
            box-shadow: 4px 4px 0 rgba(47, 65, 86, 0.25) !important;
            white-space: pre-wrap !important;
            word-break: break-word !important;
        }
        pre code {
            max-height: 200px !important;
            overflow-y: auto !important;
            display: block !important;
        }
        pre::before {
            content: "PROMPT.txt" !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            height: 34px !important;
            background: 
                radial-gradient(circle 5px at 18px 17px, #ff5f57 100%, transparent 0),
                radial-gradient(circle 5px at 33px 17px, #febc2e 100%, transparent 0),
                radial-gradient(circle 5px at 48px 17px, #28c840 100%, transparent 0),
                #2f4156 !important;
            color: rgba(255, 255, 255, 0.85) !important;
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif !important;
            font-size: 0.74rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.08em !important;
            text-transform: uppercase !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            pointer-events: none !important;
            user-select: none !important;
            border-top-left-radius: 11px !important;
            border-top-right-radius: 11px !important;
        }
        pre::after {
            content: attr(data-words) !important;
            position: absolute !important;
            top: 0 !important;
            right: 16px !important;
            height: 34px !important;
            display: flex !important;
            align-items: center !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-size: 0.72rem !important;
            color: rgba(255, 255, 255, 0.65) !important;
            font-weight: 600 !important;
            pointer-events: none !important;
            user-select: none !important;
        }
        pre code {
            background: transparent !important;
            color: inherit !important;
            font-family: inherit !important;
            font-size: inherit !important;
            padding: 0 !important;
            border: none !important;
        }
        /* Theme 2: Minimalist Light */
        pre.code-theme-light {
            background: #f8fafc !important;
            border-color: #cbd5e1 !important;
            color: #0f172a !important;
            box-shadow: 4px 4px 0 rgba(203, 213, 225, 0.5) !important;
        }
        pre.code-theme-light::before {
            background: 
                radial-gradient(circle 5px at 18px 17px, #ff5f57 100%, transparent 0),
                radial-gradient(circle 5px at 33px 17px, #febc2e 100%, transparent 0),
                radial-gradient(circle 5px at 48px 17px, #28c840 100%, transparent 0),
                #e2e8f0 !important;
            color: #334155 !important;
        }
        pre.code-theme-light code {
            color: #0f172a !important;
        }
        /* Theme 3: Cyberpunk Neon */
        pre.code-theme-cyber {
            background: #090a10 !important;
            border-color: #8b5cf6 !important;
            color: #34d399 !important;
            box-shadow: 0 0 15px rgba(139, 92, 246, 0.25), 4px 4px 0 rgba(139, 92, 246, 0.3) !important;
        }
        pre.code-theme-cyber::before {
            background: 
                radial-gradient(circle 5px at 18px 17px, #ec4899 100%, transparent 0),
                radial-gradient(circle 5px at 33px 17px, #8b5cf6 100%, transparent 0),
                radial-gradient(circle 5px at 48px 17px, #06b6d4 100%, transparent 0),
                linear-gradient(90deg, #1e1035 0%, #2e1065 100%) !important;
            color: #e9d5ff !important;
        }
        pre.code-theme-cyber code {
            color: #34d399 !important;
        }
        img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin: 1.5em auto 1.35em;
            display: block;
            float: none;
            clear: both;
            cursor: pointer;
        }
        img.img-border {
            border: 1px solid #000000;
            border-radius: 12px !important;
            box-sizing: border-box !important;
        }
        img[data-align="left"], img.align-left {
            margin-left: 0 !important;
            margin-right: auto !important;
        }
        img[data-align="center"], img.align-center {
            margin-left: auto !important;
            margin-right: auto !important;
        }
        img[data-align="right"], img.align-right {
            margin-left: auto !important;
            margin-right: 0 !important;
        }
        p {
            clear: both;
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
        /* ── Callout Boxes with 3 Themes ────────────────────────────── */
        .blog-grey-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #0284c7;
            border-radius: 14px;
            padding: 16px 20px;
            margin: 1.8em 0;
            color: #334155;
            font-size: 0.95rem;
            line-height: 1.65;
            box-sizing: border-box;
        }
        .blog-grey-box p { margin: 0 !important; }
        .blog-box-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.88rem;
            font-weight: 800;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .blog-box-header .box-icon { font-size: 1.15rem; line-height: 1; }
        
        /* 1. Bulb Theme (Extra Tip / Pro Tip) */
        .blog-grey-box.blog-box-tip {
            background: #fffdf5 !important;
            border-color: #fef08a !important;
            border-left: 4px solid #f59e0b !important;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.06);
        }
        .blog-grey-box.blog-box-tip .blog-box-header { color: #b45309 !important; }
        .blog-grey-box.blog-box-tip p { color: #451a03 !important; }
        
        /* 2. Info Theme (Classic Slate / Blue) */
        .blog-grey-box.blog-box-info {
            background: #f8fafc !important;
            border-color: #e2e8f0 !important;
            border-left: 4px solid #0284c7 !important;
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.06);
        }
        .blog-grey-box.blog-box-info .blog-box-header { color: #0369a1 !important; }
        .blog-grey-box.blog-box-info p { color: #334155 !important; }
        
        /* 3. Alert / Secret Code Theme (Rose / Coral) */
        .blog-grey-box.blog-box-alert {
            background: #fff5f6 !important;
            border-color: #fecdd3 !important;
            border-left: 4px solid #f43f5e !important;
            box-shadow: 0 2px 8px rgba(244, 63, 94, 0.06);
        }
        .blog-grey-box.blog-box-alert .blog-box-header { color: #be123c !important; }
        .blog-grey-box.blog-box-alert p { color: #4c0519 !important; }

        /* ── Sleek Quick FAQ Section Box with 5 Themes & Watermark ── */
        .blog-faq-box {
            position: relative !important;
            border-radius: 18px !important;
            padding: 24px 26px !important;
            margin: 2.2em 0 !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
            transition: all 0.2s ease !important;
        }
        .blog-faq-watermark {
            position: absolute !important;
            inset: 0 !important;
            pointer-events: none !important;
            user-select: none !important;
            z-index: 0 !important;
            background-repeat: repeat !important;
            background-size: 140px 140px !important;
            opacity: 0.9 !important;
        }
        .blog-faq-inner {
            position: relative !important;
            z-index: 1 !important;
        }
        .blog-faq-head {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            padding-bottom: 16px !important;
            margin-bottom: 20px !important;
            border-bottom: 1.5px solid rgba(0,0,0,0.06) !important;
        }
        .blog-faq-icon {
            width: 36px !important;
            height: 36px !important;
            border-radius: 12px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
        }
        .blog-faq-icon svg {
            width: 20px !important;
            height: 20px !important;
            stroke: currentColor !important;
        }
        .blog-faq-title {
            font-size: 1.22rem !important;
            font-weight: 800 !important;
            margin: 0 !important;
            letter-spacing: -0.02em !important;
        }
        .blog-faq-list {
            display: flex !important;
            flex-direction: column !important;
            gap: 14px !important;
        }
        .blog-faq-item {
            border-radius: 14px !important;
            padding: 16px 18px !important;
            box-sizing: border-box !important;
            background: #ffffff !important;
            transition: transform 0.15s ease, box-shadow 0.15s ease !important;
        }
        .blog-faq-item:hover {
            transform: translateY(-1px) !important;
        }
        .faq-q-row {
            display: flex !important;
            align-items: flex-start !important;
            gap: 12px !important;
            margin-bottom: 8px !important;
        }
        .faq-num-badge {
            width: 26px !important;
            height: 26px !important;
            border-radius: 8px !important;
            font-size: 0.75rem !important;
            font-weight: 800 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
            margin-top: 1px !important;
            letter-spacing: -0.02em !important;
        }
        .faq-q-text {
            font-size: 1rem !important;
            font-weight: 700 !important;
            margin: 0 !important;
            line-height: 1.4 !important;
            flex: 1 !important;
        }
        .faq-toggle-icon {
            font-size: 0.85rem !important;
            opacity: 0.6 !important;
            margin-top: 4px !important;
        }
        .faq-a-row {
            display: flex !important;
            align-items: flex-start !important;
            gap: 12px !important;
        }
        .faq-a-spacer {
            width: 26px !important;
            flex-shrink: 0 !important;
        }
        .faq-a-text {
            font-size: 0.93rem !important;
            margin: 0 !important;
            line-height: 1.65 !important;
            flex: 1 !important;
        }

        /* 1. Theme: Nogoda Electric Violet (Default) */
        .faq-theme-nogoda {
            background: linear-gradient(145deg, #ffffff 0%, #faf5ff 100%) !important;
            border: 1.5px solid #ddd6fe !important;
            box-shadow: 0 10px 25px -5px rgba(124, 58, 237, 0.08) !important;
        }
        .faq-theme-nogoda .blog-faq-watermark {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140' viewBox='0 0 140 140'%3E%3Cfilter id='b'%3E%3CfeGaussianBlur stdDeviation='1.5'/%3E%3C/filter%3E%3Ctext x='25' y='45' font-size='24' font-family='sans-serif' font-weight='800' fill='%237c3aed' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='95' y='110' font-size='36' font-family='sans-serif' font-weight='800' fill='%237c3aed' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='100' y='35' font-size='18' font-family='sans-serif' font-weight='800' fill='%237c3aed' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='35' y='125' font-size='20' font-family='sans-serif' font-weight='800' fill='%237c3aed' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3C/svg%3E") !important;
        }
        .faq-theme-nogoda .blog-faq-head { border-bottom-color: #ede9fe !important; }
        .faq-theme-nogoda .blog-faq-icon { background: #ede9fe !important; color: #7c3aed !important; }
        .faq-theme-nogoda .blog-faq-title { color: #4c1d95 !important; }
        .faq-theme-nogoda .blog-faq-item { background: #ffffff !important; border: 1px solid #e9d5ff !important; box-shadow: 0 2px 8px rgba(124, 58, 237, 0.04) !important; }
        .faq-theme-nogoda .faq-num-badge { background: #f3e8ff !important; color: #7c3aed !important; }
        .faq-theme-nogoda .faq-q-text { color: #1e1b4b !important; }
        .faq-theme-nogoda .faq-a-text { color: #4b5563 !important; }
        .faq-theme-nogoda .faq-toggle-icon { color: #7c3aed !important; }

        /* 2. Theme: Modern Minimalist Sky (Sample Pic 2) */
        .faq-theme-sky {
            background: linear-gradient(145deg, #ffffff 0%, #f0f9ff 100%) !important;
            border: 1.5px solid #bae6fd !important;
            box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.08) !important;
        }
        .faq-theme-sky .blog-faq-watermark {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140' viewBox='0 0 140 140'%3E%3Cfilter id='b'%3E%3CfeGaussianBlur stdDeviation='1.5'/%3E%3C/filter%3E%3Ctext x='25' y='45' font-size='24' font-family='sans-serif' font-weight='800' fill='%230284c7' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='95' y='110' font-size='36' font-family='sans-serif' font-weight='800' fill='%230284c7' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='100' y='35' font-size='18' font-family='sans-serif' font-weight='800' fill='%230284c7' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='35' y='125' font-size='20' font-family='sans-serif' font-weight='800' fill='%230284c7' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3C/svg%3E") !important;
        }
        .faq-theme-sky .blog-faq-head { border-bottom-color: #e0f2fe !important; }
        .faq-theme-sky .blog-faq-icon { background: #e0f2fe !important; color: #0284c7 !important; }
        .faq-theme-sky .blog-faq-title { color: #0369a1 !important; }
        .faq-theme-sky .blog-faq-item { background: #ffffff !important; border: 1px solid #e0f2fe !important; box-shadow: 0 2px 8px rgba(2, 132, 199, 0.04) !important; }
        .faq-theme-sky .faq-num-badge { background: #e0f2fe !important; color: #0284c7 !important; }
        .faq-theme-sky .faq-q-text { color: #0f172a !important; }
        .faq-theme-sky .faq-a-text { color: #475569 !important; }
        .faq-theme-sky .faq-toggle-icon { color: #0284c7 !important; }

        /* 3. Theme: Executive Navy & Dark Slate */
        .faq-theme-navy {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%) !important;
            border: 1.5px solid #cbd5e1 !important;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.06) !important;
        }
        .faq-theme-navy .blog-faq-watermark {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140' viewBox='0 0 140 140'%3E%3Cfilter id='b'%3E%3CfeGaussianBlur stdDeviation='1.5'/%3E%3C/filter%3E%3Ctext x='25' y='45' font-size='24' font-family='sans-serif' font-weight='800' fill='%23334155' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='95' y='110' font-size='36' font-family='sans-serif' font-weight='800' fill='%23334155' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='100' y='35' font-size='18' font-family='sans-serif' font-weight='800' fill='%23334155' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='35' y='125' font-size='20' font-family='sans-serif' font-weight='800' fill='%23334155' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3C/svg%3E") !important;
        }
        .faq-theme-navy .blog-faq-head { border-bottom-color: #e2e8f0 !important; }
        .faq-theme-navy .blog-faq-icon { background: #0f172a !important; color: #ffffff !important; }
        .faq-theme-navy .blog-faq-title { color: #0f172a !important; }
        .faq-theme-navy .blog-faq-item { background: #ffffff !important; border: 1px solid #e2e8f0 !important; }
        .faq-theme-navy .faq-num-badge { background: #0f172a !important; color: #ffffff !important; }
        .faq-theme-navy .faq-q-text { color: #0f172a !important; }
        .faq-theme-navy .faq-a-text { color: #475569 !important; }
        .faq-theme-navy .faq-toggle-icon { color: #64748b !important; }

        /* 4. Theme: Warm Sunset Amber / Gold */
        .faq-theme-amber {
            background: linear-gradient(145deg, #ffffff 0%, #fffdf5 100%) !important;
            border: 1.5px solid #fde68a !important;
            box-shadow: 0 10px 25px -5px rgba(245, 158, 11, 0.08) !important;
        }
        .faq-theme-amber .blog-faq-watermark {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140' viewBox='0 0 140 140'%3E%3Cfilter id='b'%3E%3CfeGaussianBlur stdDeviation='1.5'/%3E%3C/filter%3E%3Ctext x='25' y='45' font-size='24' font-family='sans-serif' font-weight='800' fill='%23f59e0b' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='95' y='110' font-size='36' font-family='sans-serif' font-weight='800' fill='%23f59e0b' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='100' y='35' font-size='18' font-family='sans-serif' font-weight='800' fill='%23f59e0b' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='35' y='125' font-size='20' font-family='sans-serif' font-weight='800' fill='%23f59e0b' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3C/svg%3E") !important;
        }
        .faq-theme-amber .blog-faq-head { border-bottom-color: #fef3c7 !important; }
        .faq-theme-amber .blog-faq-icon { background: #fef3c7 !important; color: #d97706 !important; }
        .faq-theme-amber .blog-faq-title { color: #92400e !important; }
        .faq-theme-amber .blog-faq-item { background: #ffffff !important; border: 1px solid #fef3c7 !important; }
        .faq-theme-amber .faq-num-badge { background: #fef3c7 !important; color: #b45309 !important; }
        .faq-theme-amber .faq-q-text { color: #451a03 !important; }
        .faq-theme-amber .faq-a-text { color: #78350f !important; }
        .faq-theme-amber .faq-toggle-icon { color: #d97706 !important; }

        /* 5. Theme: Clean Emerald Mint */
        .faq-theme-emerald {
            background: linear-gradient(145deg, #ffffff 0%, #f0fdf4 100%) !important;
            border: 1.5px solid #a7f3d0 !important;
            box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.08) !important;
        }
        .faq-theme-emerald .blog-faq-watermark {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140' viewBox='0 0 140 140'%3E%3Cfilter id='b'%3E%3CfeGaussianBlur stdDeviation='1.5'/%3E%3C/filter%3E%3Ctext x='25' y='45' font-size='24' font-family='sans-serif' font-weight='800' fill='%2310b981' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='95' y='110' font-size='36' font-family='sans-serif' font-weight='800' fill='%2310b981' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='100' y='35' font-size='18' font-family='sans-serif' font-weight='800' fill='%2310b981' opacity='0.05' filter='url(%23b)'%3E%3F%3C/text%3E%3Ctext x='35' y='125' font-size='20' font-family='sans-serif' font-weight='800' fill='%2310b981' opacity='0.04' filter='url(%23b)'%3E%3F%3C/text%3E%3C/svg%3E") !important;
        }
        .faq-theme-emerald .blog-faq-head { border-bottom-color: #d1fae5 !important; }
        .faq-theme-emerald .blog-faq-icon { background: #d1fae5 !important; color: #059669 !important; }
        .faq-theme-emerald .blog-faq-title { color: #065f46 !important; }
        .faq-theme-emerald .blog-faq-item { background: #ffffff !important; border: 1px solid #d1fae5 !important; }
        .faq-theme-emerald .faq-num-badge { background: #d1fae5 !important; color: #047857 !important; }
        .faq-theme-emerald .faq-q-text { color: #064e3b !important; }
        .faq-theme-emerald .faq-a-text { color: #047857 !important; }
        .faq-theme-emerald .faq-toggle-icon { color: #059669 !important; }
        /* Single Unified Prompt Card (No nested boxes) */
        .blog-prompt-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px 20px 14px;
            margin: 1.8em 0;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }
        .bpc-item {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .bpc-item + .bpc-item {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed #e2e8f0;
        }
        .bpc-thumb {
            width: 58px;
            height: 58px;
            border-radius: 10px;
            overflow: hidden;
            flex-shrink: 0;
            background: #ffffff;
            border: 1px solid #e2e8f0;
        }
        .bpc-thumb img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            display: block !important;
            margin: 0 !important;
            border-radius: 0 !important;
        }
        .bpc-details {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .bpc-badge {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0284c7;
            line-height: 1.2;
        }
        .bpc-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.96rem;
            font-weight: 700;
            color: #0f172a !important;
            line-height: 1.3;
            margin: 0 !important;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .bpc-desc {
            font-size: 0.78rem;
            color: #64748b;
            line-height: 1.35;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .bpc-action {
            flex-shrink: 0;
            margin-left: auto;
        }
        .bpc-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #0f172a;
            color: #ffffff !important;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none !important;
            white-space: nowrap;
        }
        .bpc-note {
            margin: 12px 0 0 !important;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 0.84rem !important;
            color: #64748b !important;
            line-height: 1.55 !important;
            font-style: italic;
        }
        /* Editable cues inside the editor */
        .blog-prompt-card, .blog-grey-box {
            cursor: text;
        }
        .bpc-title:hover, .bpc-desc:hover, .bpc-badge:hover, .bpc-note:hover, .blog-grey-box:hover, .blog-grey-box p:hover {
            outline: 1.5px dashed #94a3b8;
            outline-offset: 2px;
            border-radius: 4px;
        }
        .bpc-title:focus, .bpc-desc:focus, .bpc-badge:focus, .bpc-note:focus, .blog-grey-box:focus, .blog-grey-box p:focus {
            outline: 2px solid #0284c7 !important;
            outline-offset: 2px;
            border-radius: 4px;
            background: rgba(2, 132, 199, 0.04);
        }
        figcaption, .img-caption {
            font-size: 0.86rem;
            color: #64748b;
            font-style: italic;
            margin-top: 6px;
            margin-bottom: 1.2em;
            text-align: center;
            line-height: 1.4;
        }
        .blog-toc-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #0284c7;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease;
        }
        .blog-toc-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0369a1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .blog-toc-box ol { margin: 0; }
        .blog-toc-box li { color: #0284c7; }
        .blog-toc-box a { color: #0369a1; text-decoration: none; font-weight: 500; }

        /* SIZE 1: SMALL / COMPACT */
        .blog-toc-box.blog-toc-sm, .blog-toc-box:not(.blog-toc-md):not(.blog-toc-lg) {
            padding: 10px 14px;
            margin: 1.1em 0 1.4em;
            border-radius: 10px;
        }
        .blog-toc-sm .blog-toc-title, .blog-toc-box:not(.blog-toc-md):not(.blog-toc-lg) .blog-toc-title {
            font-size: 0.82rem;
            margin-bottom: 6px;
        }
        .blog-toc-sm ol, .blog-toc-box:not(.blog-toc-md):not(.blog-toc-lg) ol {
            padding-left: 18px;
            font-size: 0.80rem;
            line-height: 1.36;
            columns: 2;
            column-gap: 24px;
        }
        .blog-toc-sm li, .blog-toc-box:not(.blog-toc-md):not(.blog-toc-lg) li {
            margin-bottom: 3px;
            break-inside: avoid;
        }

        /* SIZE 2: MEDIUM / STANDARD */
        .blog-toc-box.blog-toc-md {
            padding: 14px 18px;
            margin: 1.4em 0 1.8em;
            border-radius: 12px;
        }
        .blog-toc-md .blog-toc-title { font-size: 0.92rem; margin-bottom: 9px; }
        .blog-toc-md ol { padding-left: 20px; font-size: 0.88rem; line-height: 1.48; columns: 2; column-gap: 28px; }
        .blog-toc-md li { margin-bottom: 5px; break-inside: avoid; }

        /* SIZE 3: LARGE / SPACIOUS */
        .blog-toc-box.blog-toc-lg {
            padding: 18px 24px;
            margin: 1.8em 0 2.2em;
            border-radius: 14px;
        }
        .blog-toc-lg .blog-toc-title { font-size: 1.05rem; margin-bottom: 12px; }
        .blog-toc-lg ol { padding-left: 22px; font-size: 0.95rem; line-height: 1.62; columns: 2; column-gap: 32px; }
        .blog-toc-lg li { margin-bottom: 7px; break-inside: avoid; }
        .blog-cta-box {
            border-radius: 20px;
            padding: 30px 24px;
            margin: 2em 0;
            text-align: center;
            color: #ffffff;
        }
        .blog-cta-title { font-size: 1.45rem; font-weight: 800; margin: 0 0 10px; color: #ffffff !important; }
        .blog-cta-desc { font-size: 1rem; margin: 0 auto 20px; opacity: 0.95; color: #f1f5f9 !important; }
        .blog-cta-buttons { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; }
        .blog-cta-btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 22px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 0.92rem;
            text-decoration: none !important;
        }
        .blog-cta-violet { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 55%, #9333ea 100%); }
        .blog-cta-violet .blog-cta-btn-primary { background: #f43f5e; color: #ffffff !important; }
        .blog-cta-violet .blog-cta-btn-secondary { background: #ffffff; color: #4f46e5 !important; }
        .blog-cta-violet .blog-cta-btn-ghost { background: rgba(255,255,255,0.2); color: #ffffff !important; }
        .blog-cta-navy { background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #2F4156 100%); }
        .blog-cta-navy .blog-cta-btn-primary { background: #38bdf8; color: #0f172a !important; }
        .blog-cta-navy .blog-cta-btn-secondary { background: #ffffff; color: #0f172a !important; }
        .blog-cta-navy .blog-cta-btn-ghost { background: rgba(255,255,255,0.15); color: #e2e8f0 !important; }
        .blog-cta-rose { background: linear-gradient(135deg, #9f1239 0%, #e11d48 55%, #f43f5e 100%); }
        .blog-cta-rose .blog-cta-btn-primary { background: #fbbf24; color: #881337 !important; }
        .blog-cta-rose .blog-cta-btn-secondary { background: #ffffff; color: #be123c !important; }
        .blog-cta-rose .blog-cta-btn-ghost { background: rgba(255,255,255,0.2); color: #ffffff !important; }

        /* ── Carousel Box in Editor ── */
        .blog-carousel-box {
            position: relative !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 1.8em 0 !important;
            border-radius: 18px !important;
            background: #0f172a !important;
            overflow: hidden !important;
            box-sizing: border-box !important;
            user-select: none !important;
        }
        .blog-carousel-box.bcar-ratio-9-16 {
            background: transparent !important;
        }
        .bcar-viewport {
            position: relative !important;
            width: 100% !important;
            overflow-x: auto !important;
            scroll-behavior: smooth !important;
        }
        .bcar-track {
            display: flex !important;
            gap: 16px !important;
            width: max-content !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .bcar-ratio-16-9 .bcar-track {
            gap: 0 !important;
            width: 100% !important;
        }
        .bcar-ratio-16-9 .bcar-slide {
            flex: 0 0 100% !important;
            width: 100% !important;
            position: relative !important;
        }
        .bcar-ratio-16-9 .bcar-slide img {
            display: block !important;
            width: 100% !important;
            aspect-ratio: 16 / 9 !important;
            object-fit: cover !important;
            border-radius: 18px !important;
            margin: 0 !important;
        }
        .bcar-ratio-9-16 .bcar-track {
            padding: 8px 4px 16px !important;
            gap: 14px !important;
        }
        .bcar-ratio-9-16.bcar-no-overflow .bcar-track {
            justify-content: center !important;
        }
        .bcar-ratio-9-16 .bcar-slide {
            flex: 0 0 175px !important;
            min-width: 140px !important;
            max-width: 190px !important;
            max-height: 335px !important;
            position: relative !important;
            border-radius: 16px !important;
            overflow: hidden !important;
            box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.15) !important;
            background: #1e1e2e !important;
        }
        .bcar-ratio-9-16 .bcar-slide img {
            display: block !important;
            width: 100% !important;
            aspect-ratio: 9 / 16 !important;
            max-height: 335px !important;
            object-fit: cover !important;
            border-radius: 16px !important;
            margin: 0 !important;
        }
        .bcar-caption {
            position: absolute !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            padding: 10px 14px !important;
            background: linear-gradient(to top, rgba(15,23,42,0.9), transparent) !important;
            color: #f8fafc !important;
            font-size: 0.8rem !important;
            font-weight: 600 !important;
        }
        .bcar-nav {
            position: absolute !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            width: 36px !important;
            height: 36px !important;
            border-radius: 50% !important;
            background: rgba(255, 255, 255, 0.9) !important;
            border: 1px solid #cbd5e1 !important;
            color: #0f172a !important;
            font-size: 1.2rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            z-index: 10 !important;
        }
        .bcar-prev { left: 10px !important; }
        .bcar-next { right: 10px !important; }
        .bcar-dots {
            position: absolute !important;
            bottom: 10px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            display: flex !important;
            gap: 6px !important;
            z-index: 10 !important;
        }
        .bcar-dot {
            width: 7px !important;
            height: 7px !important;
            border-radius: 50% !important;
            background: rgba(255, 255, 255, 0.4) !important;
        }
        .bcar-dot.active {
            width: 18px !important;
            border-radius: 999px !important;
            background: #ffffff !important;
        }
        .mce-ctrl-multiselect {
            background: rgba(59, 130, 246, 0.25) !important;
            outline: 1.5px solid #2563eb !important;
            border-radius: 3px !important;
        }
    `,
    
    images_upload_handler: function (blobInfo, progress) {
        return new Promise(function (resolve, reject) {
            const formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            fetch('upload_editor_image.php', {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && data.url) resolve(data.url);
                else reject(data && data.error ? data.error : 'Upload failed');
            })
            .catch(function (err) {
                reject(err && err.message ? err.message : 'Upload error');
            });
        });
    }
});

// ── Custom Toolbar Dropdowns & Mobile More Drawer ───────────────────────────
function toggleBeDropdown(id, event) {
    if (event && event.stopPropagation) {
        event.stopPropagation();
    }
    var dd = document.getElementById(id);
    if (!dd) return;
    var isOpen = dd.classList.contains('be-dd-open');
    closeAllBeDropdowns();
    if (!isOpen) {
        dd.classList.add('be-dd-open');
    }
}

function closeAllBeDropdowns() {
    document.querySelectorAll('.be-dropdown.be-dd-open').forEach(function(d) {
        d.classList.remove('be-dd-open');
    });
}

// Close dropdowns when clicking anywhere on the document
document.addEventListener('click', function(e) {
    if (e.target.closest('.be-tb-dropdown-btn')) {
        return;
    }
    closeAllBeDropdowns();
});

// Close dropdowns on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAllBeDropdowns();
    }
});

function selectBeStyle(val, label) {
    fmtBlock(val);
    closeAllBeDropdowns();
    var dd = document.getElementById('beStyleDropdown');
    if (dd) {
        var labelEl = dd.querySelector('.be-dd-label');
        if (labelEl) labelEl.textContent = label || 'Style';
    }
}

function selectBeFont(val, label) {
    applyFontFamily(val);
    closeAllBeDropdowns();
    var dd = document.getElementById('beFontDropdown');
    if (dd) {
        var labelEl = dd.querySelector('.be-dd-label');
        if (labelEl) labelEl.textContent = label || 'Font';
    }
}

function selectBeTocSize(val, label) {
    setTocSize(val);
    closeAllBeDropdowns();
    var dd = document.getElementById('beTocSizeDropdown');
    if (dd) {
        var labelEl = dd.querySelector('.be-dd-label');
        if (labelEl) labelEl.textContent = label || 'Size';
    }
}

function selectBeBorder(val, label) {
    setSelectedImageBorder(val);
    closeAllBeDropdowns();
    var dd = document.getElementById('beImgBorderDropdown');
    if (dd) {
        var labelEl = dd.querySelector('.be-dd-label');
        if (labelEl) labelEl.textContent = label || 'Border';
    }
}

function toggleMobileTbMore() {
    var drawer = document.getElementById('beTbMoreDrawer');
    var btn = document.getElementById('beTbMoreToggleBtn');
    if (!drawer || !btn) return;
    var isExpanded = drawer.classList.contains('be-drawer-expanded');
    if (isExpanded) {
        drawer.classList.remove('be-drawer-expanded');
        btn.classList.remove('active');
        btn.innerHTML = '<i class="fa-solid fa-ellipsis"></i> More';
    } else {
        drawer.classList.add('be-drawer-expanded');
        btn.classList.add('active');
        btn.innerHTML = '<i class="fa-solid fa-chevron-up"></i> Less';
    }
}

function toggleWideWritingMode() {
    document.body.classList.toggle('be-wide-mode');
    var isWide = document.body.classList.contains('be-wide-mode');
    localStorage.setItem('be_wide_mode', isWide ? '1' : '0');
    var btn = document.getElementById('wideModeBtn');
    if (btn) {
        btn.innerHTML = isWide 
            ? '<i class="fa-solid fa-compress"></i> <span>Normal View</span>' 
            : '<i class="fa-solid fa-expand"></i> <span>Spacious View</span>';
    }
}
if (localStorage.getItem('be_wide_mode') === '1') {
    document.body.classList.add('be-wide-mode');
    window.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('wideModeBtn');
        if (btn) btn.innerHTML = '<i class="fa-solid fa-compress"></i> <span>Normal View</span>';
    });
}

function openLivePreview() {
    var title = (document.getElementById('bc-title') ? document.getElementById('bc-title').value : '').trim() || 'Untitled Draft Preview';
    var desc = (document.getElementById('bc-desc') ? document.getElementById('bc-desc').value : '').trim();
    var slug = (document.getElementById('bc-slug') ? document.getElementById('bc-slug').value : '').trim() || 'draft-preview';
    var cat = (document.getElementById('bc-cat') ? document.getElementById('bc-cat').value : '').trim();
    var tags = (document.getElementById('bc-tags') ? document.getElementById('bc-tags').value : '').trim();
    var metaTitle = (document.getElementById('bc-meta-title') ? document.getElementById('bc-meta-title').value : '').trim();
    var metaDesc = (document.getElementById('bc-meta-desc') ? document.getElementById('bc-meta-desc').value : '').trim();
    var metaKeywords = (document.getElementById('bc-meta-keywords') ? document.getElementById('bc-meta-keywords').value : '').trim();
    var content = (window.tinymce && tinymce.activeEditor) ? tinymce.activeEditor.getContent() : '';
    
    // Get cover photo URLs (preview data or existing image paths)
    var imgP = document.getElementById('coverPreviewImg');
    var coverPortrait = (imgP && imgP.style.display !== 'none' && imgP.src) ? imgP.src : '';
    var imgL = document.getElementById('coverPreviewLand');
    var coverLand = (imgL && imgL.style.display !== 'none' && imgL.src) ? imgL.src : '';
    
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'blog.php?preview=1';
    form.target = '_blank';
    form.style.display = 'none';
    
    function addField(name, val) {
        var inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = name;
        inp.value = val || '';
        form.appendChild(inp);
    }
    
    addField('is_live_preview', '1');
    addField('preview_id', '0');
    addField('preview_title', title);
    addField('preview_slug', slug);
    addField('preview_desc', desc);
    addField('preview_content', content);
    addField('preview_category', cat);
    addField('preview_tags', tags);
    addField('preview_meta_title', metaTitle);
    addField('preview_meta_desc', metaDesc);
    addField('preview_meta_keywords', metaKeywords);
    addField('preview_image_path', coverPortrait);
    addField('preview_image_path_landscape', coverLand);
    
    document.body.appendChild(form);
    form.submit();
    setTimeout(function() {
        if (form.parentNode) form.parentNode.removeChild(form);
    }, 1000);
}

function clearMultiSelections(ed) {
    if (!ed) ed = tinymce.activeEditor;
    if (!ed) return;
    var body = ed.getBody();
    if (!body) return;
    var els = body.querySelectorAll('.mce-ctrl-multiselect');
    els.forEach(function (el) {
        ed.dom.remove(el, true);
    });
    ed.nodeChanged();
}

function fmt(cmd) {
    if (tinymce.activeEditor) {
        var multi = tinymce.activeEditor.getBody().querySelectorAll('.mce-ctrl-multiselect');
        if (multi.length > 0) {
            multi.forEach(function(el) {
                tinymce.activeEditor.selection.select(el);
                tinymce.activeEditor.execCommand(cmd);
            });
            clearMultiSelections(tinymce.activeEditor);
            return;
        }
        tinymce.activeEditor.execCommand(cmd);
    }
}

function toggleHighlight() {
    if (tinymce.activeEditor) {
        tinymce.activeEditor.focus();
        var multi = tinymce.activeEditor.getBody().querySelectorAll('.mce-ctrl-multiselect');
        if (multi.length > 0) {
            multi.forEach(function(el) {
                tinymce.activeEditor.selection.select(el);
                tinymce.activeEditor.formatter.toggle('highlight');
            });
            clearMultiSelections(tinymce.activeEditor);
            return;
        }
        tinymce.activeEditor.formatter.toggle('highlight');
    }
}

function removeHighlight() {
    if (tinymce.activeEditor) {
        tinymce.activeEditor.focus();
        var multi = tinymce.activeEditor.getBody().querySelectorAll('.mce-ctrl-multiselect');
        if (multi.length > 0) {
            multi.forEach(function(el) {
                tinymce.activeEditor.selection.select(el);
                tinymce.activeEditor.formatter.remove('highlight');
            });
            clearMultiSelections(tinymce.activeEditor);
            return;
        }
        tinymce.activeEditor.formatter.remove('highlight');
    }
}

// ── Ordered List Numbering Functions ─────────────────────────────────────────
function continueCurrentOrderedList() {
    if (!tinymce.activeEditor) return;
    var editor = tinymce.activeEditor;
    var node = editor.selection.getNode();
    var ol = editor.dom.getParent(node, 'ol');
    if (!ol) {
        editor.execCommand('InsertOrderedList');
        ol = editor.dom.getParent(editor.selection.getNode(), 'ol');
    }
    if (ol) {
        continueOlNumbering(editor, ol);
    }
}

// ── In-Page Toast & Notice Modals (No native browser alerts/prompts) ──────────
function showToast(msg, type) {
    var container = document.getElementById('beToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'beToastContainer';
        container.style.cssText = 'position:fixed; bottom:24px; right:24px; z-index:9999999; display:flex; flex-direction:column; gap:8px; pointer-events:none; font-family:"Plus Jakarta Sans",sans-serif;';
        document.body.appendChild(container);
    }
    var toast = document.createElement('div');
    toast.style.cssText = 'background:#0f172a; color:#f8fafc; padding:12px 18px; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.2); font-size:0.86rem; font-weight:600; display:flex; align-items:center; gap:10px; pointer-events:auto; transition:all 0.25s ease; opacity:0; transform:translateY(10px); border:1px solid #334155;';
    var icon = '<i class="fa-solid fa-circle-check" style="color:#22c55e;"></i>';
    if (type === 'warn') icon = '<i class="fa-solid fa-triangle-exclamation" style="color:#eab308;"></i>';
    if (type === 'error') icon = '<i class="fa-solid fa-circle-xmark" style="color:#ef4444;"></i>';
    toast.innerHTML = icon + '<span>' + (msg || '') + '</span>';
    container.appendChild(toast);
    
    requestAnimationFrame(function() {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    });
    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px)';
        setTimeout(function() { toast.remove(); }, 300);
    }, 3200);
}

function showNoticeModal(title, message, type) {
    var modal = document.getElementById('customNoticeModal');
    if (!modal) {
        alert(message);
        return;
    }
    document.getElementById('cnmTitle').textContent = title || 'Notice';
    document.getElementById('cnmMessage').textContent = message || '';
    var iconWrap = document.getElementById('cnmIconWrap');
    var icon = document.getElementById('cnmIcon');
    if (type === 'warn') {
        iconWrap.style.background = '#fef3c7';
        iconWrap.style.color = '#d97706';
        icon.className = 'fa-solid fa-triangle-exclamation';
    } else if (type === 'error') {
        iconWrap.style.background = '#fee2e2';
        iconWrap.style.color = '#dc2626';
        icon.className = 'fa-solid fa-circle-xmark';
    } else {
        iconWrap.style.background = '#eff6ff';
        iconWrap.style.color = '#2563eb';
        icon.className = 'fa-solid fa-circle-info';
    }
    modal.style.display = 'flex';
}

function closeNoticeModal() {
    var modal = document.getElementById('customNoticeModal');
    if (modal) modal.style.display = 'none';
}

// ── In-Page List Numbering Continuity Modal Logic ────────────────────────────
window.currentEditingOl = null;

function openListNumberModal(olNode, suggestedNum, infoText) {
    window.currentEditingOl = olNode;
    var modal = document.getElementById('listNumberModal');
    if (!modal) return;
    document.getElementById('lnmNumberInput').value = suggestedNum || 2;
    if (infoText) {
        document.getElementById('lnmInfoText').textContent = infoText;
    } else {
        document.getElementById('lnmInfoText').textContent = 'Set the starting number for this list item:';
    }
    modal.style.display = 'flex';
    var inp = document.getElementById('lnmNumberInput');
    inp.focus();
    inp.select();
}

function closeListNumberModal() {
    var modal = document.getElementById('listNumberModal');
    if (modal) modal.style.display = 'none';
    window.currentEditingOl = null;
}

function stepListNumber(delta) {
    var inp = document.getElementById('lnmNumberInput');
    var val = parseInt(inp.value, 10) || 1;
    val = Math.max(1, val + delta);
    inp.value = val;
}

function setListNumberPreset(num) {
    var inp = document.getElementById('lnmNumberInput');
    inp.value = num;
}

function applyListNumberModal() {
    var inp = document.getElementById('lnmNumberInput');
    var val = parseInt(inp.value, 10);
    if (isNaN(val) || val < 1) val = 1;
    
    if (window.currentEditingOl && tinymce.activeEditor) {
        if (val === 1) {
            window.currentEditingOl.removeAttribute('start');
            showToast('Numbered list restarted at 1');
        } else {
            window.currentEditingOl.setAttribute('start', val);
            showToast('Numbered list set to start at ' + val);
        }
        tinymce.activeEditor.nodeChanged();
    }
    closeListNumberModal();
}

function continueOlNumbering(editor, olNode) {
    if (!olNode || olNode.nodeName !== 'OL') return;
    var allOls = Array.from(editor.getBody().querySelectorAll('ol'));
    var currentIndex = allOls.indexOf(olNode);
    if (currentIndex > 0) {
        var prevOl = allOls[currentIndex - 1];
        var prevStart = parseInt(prevOl.getAttribute('start') || '1', 10);
        var prevCount = prevOl.querySelectorAll(':scope > li').length;
        var nextStart = prevStart + prevCount;
        olNode.setAttribute('start', nextStart);
        editor.nodeChanged();
        showToast('Numbered list continued: Starting at ' + nextStart);
    } else {
        openListNumberModal(olNode, 2, 'No previous numbered list found above. Choose starting number for this list:');
    }
}

function setOlStartNumber(editor, olNode) {
    if (!olNode || olNode.nodeName !== 'OL') return;
    var currentStart = parseInt(olNode.getAttribute('start') || '1', 10);
    openListNumberModal(olNode, currentStart > 1 ? currentStart : 2, 'Choose the starting number for this list item:');
}

function restartOlAtOne(editor, olNode) {
    if (!olNode || olNode.nodeName !== 'OL') return;
    olNode.removeAttribute('start');
    editor.nodeChanged();
    showToast('Numbered list restarted at 1');
}

function promptSetListStart() {
    if (!tinymce.activeEditor) return;
    var editor = tinymce.activeEditor;
    var node = editor.selection.getNode();
    var ol = editor.dom.getParent(node, 'ol');
    if (!ol) {
        showNoticeModal('Numbered List Needed', 'Please click inside a numbered list first to adjust its starting number.', 'info');
        return;
    }
    setOlStartNumber(editor, ol);
}

// ── Single Image Modal Logic with SEO Alt & Title Description ────────────────
window.currentEimFile = null;

function openEditorImageModal() {
    window.currentEimFile = null;
    var modal = document.getElementById('editorImageModal');
    if (!modal) return;
    document.getElementById('eimFileInput').value = '';
    document.getElementById('eimSeoAlt').value = '';
    document.getElementById('eimCaption').value = '';
    document.getElementById('eimPlaceholder').style.display = 'block';
    document.getElementById('eimPreviewWrap').style.display = 'none';
    document.getElementById('eimPreviewImg').src = '';
    modal.style.display = 'flex';
}

function closeEditorImageModal() {
    var modal = document.getElementById('editorImageModal');
    if (modal) modal.style.display = 'none';
}

function handleEimFileSelect(file, autoOpenModal) {
    if (!file) return;
    window.currentEimFile = file;
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('eimPreviewImg').src = e.target.result;
        document.getElementById('eimPlaceholder').style.display = 'none';
        document.getElementById('eimPreviewWrap').style.display = 'block';
        var altInput = document.getElementById('eimSeoAlt');
        if (altInput && !altInput.value.trim()) {
            var rawName = file.name.replace(/\.[^/.]+$/, '').replace(/[-_]+/g, ' ');
            altInput.value = rawName.charAt(0).toUpperCase() + rawName.slice(1);
        }
    };
    reader.readAsDataURL(file);
    if (autoOpenModal) {
        var modal = document.getElementById('editorImageModal');
        if (modal) modal.style.display = 'flex';
    }
}

function submitEditorImageUpload() {
    if (!window.currentEimFile) {
        showToast('Please select an image file first.', 'warn');
        return;
    }
    var seo = document.getElementById('eimSeoAlt').value.trim();
    if (!seo) {
        showToast('Please enter an Image SEO Description / Alt Text.', 'warn');
        document.getElementById('eimSeoAlt').focus();
        return;
    }
    var caption = (document.getElementById('eimCaption').value || '').trim();
    var alignRadios = document.getElementsByName('eimAlign');
    var align = 'center';
    for (var i = 0; i < alignRadios.length; i++) {
        if (alignRadios[i].checked) { align = alignRadios[i].value; break; }
    }
    
    var btn = document.getElementById('eimSubmitBtn');
    var origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';
    
    var formData = new FormData();
    formData.append('file', window.currentEimFile);
    
    fetch('upload_editor_image.php', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        if (data && data.url && tinymce.activeEditor) {
            var safeSeo = escapeImgAttr(seo);
            var safeCaption = escapeImgAttr(caption);
            var imgStyle = 'max-width:100%; border-radius:12px; display:block;';
            var pStyle = 'text-align:' + (align === 'full' ? 'center' : align) + '; margin:1.5em 0;';
            if (align === 'center') imgStyle += ' margin:0 auto;';
            else if (align === 'left') imgStyle += ' margin:0;';
            else if (align === 'full') imgStyle += ' width:100%;';
            
            var html = '';
            if (caption) {
                html = '<figure style="' + pStyle + '">' +
                       '<img src="' + data.url + '" alt="' + safeSeo + '" title="' + safeSeo + '" loading="lazy" style="' + imgStyle + '">' +
                       '<figcaption style="font-size:0.84rem; color:#64748b; margin-top:8px; font-style:italic; text-align:center;">' + safeCaption + '</figcaption>' +
                       '</figure><p><br></p>';
            } else {
                html = '<p style="' + pStyle + '">' +
                       '<img src="' + data.url + '" alt="' + safeSeo + '" title="' + safeSeo + '" loading="lazy" style="' + imgStyle + '">' +
                       '</p><p><br></p>';
            }
            tinymce.activeEditor.execCommand('mceInsertContent', false, html);
            closeEditorImageModal();
        } else {
            alert('Upload failed: ' + (data && data.error ? data.error : 'Unknown error'));
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        alert('Upload error: ' + err.message);
    });
}

// ── Carousel Builder Modal Logic (16:9 Landscape & 9:16 Vertical) ─────────────
window.currentEditingCarouselBox = null;
window.carouselSlideData = [];

function getCarouselMaxSlides(ratio) {
    return ratio === '9-16' ? 5 : 4;
}

function openEditorCarouselModal() {
    window.currentEditingCarouselBox = null;
    var modal = document.getElementById('editorCarouselModal');
    if (!modal) return;
    document.getElementById('ecmModalHeading').textContent = 'Insert Image Carousel';
    document.getElementById('ecmSubmitBtn').innerHTML = '<i class="fa-solid fa-check"></i> Insert Carousel into Blog';
    
    setCarouselRatio('16-9');
    window.carouselSlideData = [];
    renderCarouselSlideCards();
    addCarouselSlideCard();
    addCarouselSlideCard();
    modal.style.display = 'flex';
}

function openEditorCarouselModalForEdit(boxEl) {
    window.currentEditingCarouselBox = boxEl;
    var modal = document.getElementById('editorCarouselModal');
    if (!modal) return;
    document.getElementById('ecmModalHeading').textContent = '✏️ Edit Image Carousel';
    document.getElementById('ecmSubmitBtn').innerHTML = '<i class="fa-solid fa-arrows-rotate"></i> Update Carousel in Blog';
    
    var is916 = boxEl.classList.contains('bcar-ratio-9-16') || boxEl.getAttribute('data-ratio') === '9:16';
    var ratio = is916 ? '9-16' : '16-9';
    setCarouselRatio(ratio);
    
    window.carouselSlideData = [];
    var slides = boxEl.querySelectorAll('.bcar-slide');
    slides.forEach(function(sl) {
        var img = sl.querySelector('img');
        var cap = sl.querySelector('.bcar-caption');
        window.carouselSlideData.push({
            url: img ? img.getAttribute('src') : '',
            alt: img ? (img.getAttribute('alt') || '') : '',
            caption: cap ? cap.textContent.trim() : ''
        });
    });
    renderCarouselSlideCards();
    modal.style.display = 'flex';
}

function closeEditorCarouselModal() {
    var modal = document.getElementById('editorCarouselModal');
    if (modal) modal.style.display = 'none';
}

function setCarouselRatio(ratio) {
    var r169 = document.querySelector('input[name="ecmRatio"][value="16-9"]');
    var r916 = document.querySelector('input[name="ecmRatio"][value="9-16"]');
    if (ratio === '9-16' && r916) r916.checked = true;
    else if (r169) r169.checked = true;
    handleCarouselRatioChange(ratio);
}

function handleCarouselRatioChange(ratio) {
    var max = getCarouselMaxSlides(ratio);
    var label169 = document.getElementById('ecmRatio169Label');
    var label916 = document.getElementById('ecmRatio916Label');
    if (ratio === '16-9') {
        if (label169) { label169.style.borderColor = '#8b5cf6'; label169.style.background = '#fbf8ff'; }
        if (label916) { label916.style.borderColor = '#e2e8f0'; label916.style.background = '#f8fafc'; }
        document.getElementById('ecmLimitTip').textContent = 'Max 4 landscape images';
    } else {
        if (label916) { label916.style.borderColor = '#8b5cf6'; label916.style.background = '#fbf8ff'; }
        if (label169) { label169.style.borderColor = '#e2e8f0'; label169.style.background = '#f8fafc'; }
        document.getElementById('ecmLimitTip').textContent = 'Max 5 vertical reel images';
    }
    if (window.carouselSlideData.length > max) {
        window.carouselSlideData = window.carouselSlideData.slice(0, max);
    }
    renderCarouselSlideCards();
}

function getSelectedCarouselRatio() {
    var radios = document.getElementsByName('ecmRatio');
    for (var i = 0; i < radios.length; i++) {
        if (radios[i].checked) return radios[i].value;
    }
    return '16-9';
}

function addCarouselSlideCard(slide) {
    var ratio = getSelectedCarouselRatio();
    var max = getCarouselMaxSlides(ratio);
    if (window.carouselSlideData.length >= max) {
        showToast('Maximum ' + max + ' slides allowed for ' + (ratio === '9-16' ? '9:16 Vertical' : '16:9 Landscape') + '.', 'warn');
        return;
    }
    window.carouselSlideData.push(slide || { url: '', alt: '', caption: '' });
    renderCarouselSlideCards();
}

function removeCarouselSlideCard(idx) {
    window.carouselSlideData.splice(idx, 1);
    renderCarouselSlideCards();
}

function handleCarouselSlideUpload(input, idx) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    var formData = new FormData();
    formData.append('file', file);
    
    var cardEl = document.getElementById('ecmCard_' + idx);
    if (cardEl) {
        cardEl.style.opacity = '0.6';
    }
    
    fetch('upload_editor_image.php', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (cardEl) cardEl.style.opacity = '1';
        if (data && data.url) {
            window.carouselSlideData[idx].url = data.url;
            if (!window.carouselSlideData[idx].alt) {
                var raw = file.name.replace(/\.[^/.]+$/, '').replace(/[-_]+/g, ' ');
                window.carouselSlideData[idx].alt = raw.charAt(0).toUpperCase() + raw.slice(1);
            }
            renderCarouselSlideCards();
        } else {
            showToast('Upload failed: ' + (data && data.error ? data.error : 'Unknown error'), 'error');
        }
    })
    .catch(function(err) {
        if (cardEl) cardEl.style.opacity = '1';
        showToast('Upload error: ' + err.message, 'error');
    });
}

function renderCarouselSlideCards() {
    var container = document.getElementById('ecmCardsContainer');
    if (!container) return;
    var ratio = getSelectedCarouselRatio();
    var max = getCarouselMaxSlides(ratio);
    
    document.getElementById('ecmSlideCountText').textContent = window.carouselSlideData.length + ' / ' + max;
    
    var html = '';
    window.carouselSlideData.forEach(function(slide, idx) {
        var cardAspect = ratio === '9-16' ? 'aspect-ratio: 9/16; max-height: 220px;' : 'aspect-ratio: 16/9; max-height: 140px;';
        html += '<div id="ecmCard_' + idx + '" style="min-width:200px; max-width:230px; flex:0 0 auto; background:#ffffff; border:1.5px solid #e2e8f0; border-radius:16px; padding:12px; display:flex; flex-direction:column; gap:8px; box-shadow:0 2px 8px rgba(0,0,0,0.04); position:relative;">';
        
        html += '<button type="button" onclick="removeCarouselSlideCard(' + idx + ')" style="position:absolute; top:6px; right:6px; width:24px; height:24px; border-radius:50%; background:#fee2e2; border:none; color:#ef4444; font-size:0.75rem; cursor:pointer; display:flex; align-items:center; justify-content:center; z-index:2;" title="Delete slide"><i class="fa-solid fa-xmark"></i></button>';
        
        html += '<div onclick="document.getElementById(\'ecmFileInput_' + idx + '\').click()" style="width:100%; ' + cardAspect + ' background:#f1f5f9; border-radius:10px; overflow:hidden; display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer; border:1px dashed #cbd5e1; position:relative;">';
        if (slide.url) {
            html += '<img src="' + slide.url + '" style="width:100%; height:100%; object-fit:cover;">';
            html += '<div style="position:absolute; inset:0; background:rgba(0,0,0,0.35); opacity:0; display:flex; align-items:center; justify-content:center; color:#fff; font-size:0.75rem; font-weight:700; transition:opacity 0.2s;" onmouseenter="this.style.opacity=\'1\'" onmouseleave="this.style.opacity=\'0\'"><i class="fa-solid fa-camera"></i>&nbsp;Change</div>';
        } else {
            html += '<i class="fa-solid fa-cloud-arrow-up" style="color:#8b5cf6; font-size:1.4rem; margin-bottom:4px;"></i><span style="font-size:0.72rem; color:#64748b; font-weight:700;">Upload Pic</span>';
        }
        html += '</div>';
        html += '<input type="file" id="ecmFileInput_' + idx + '" accept="image/*" style="display:none" onchange="handleCarouselSlideUpload(this, ' + idx + ')">';
        
        html += '<div>';
        html += '<label style="display:block; font-size:0.72rem; font-weight:800; color:#334155; margin-bottom:2px;">SEO Description (Alt Tag):</label>';
        html += '<input type="text" value="' + escapeImgAttr(slide.alt || '') + '" placeholder="e.g. Prompt photo on Midjourney" oninput="window.carouselSlideData[' + idx + '].alt = this.value" style="width:100%; padding:6px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.78rem; box-sizing:border-box;">';
        html += '</div>';
        
        html += '<div>';
        html += '<label style="display:block; font-size:0.72rem; font-weight:700; color:#64748b; margin-bottom:2px;">Caption (Optional):</label>';
        html += '<input type="text" value="' + escapeImgAttr(slide.caption || '') + '" placeholder="e.g. 85mm f/1.4 lens test" oninput="window.carouselSlideData[' + idx + '].caption = this.value" style="width:100%; padding:6px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.78rem; box-sizing:border-box;">';
        html += '</div>';
        
        html += '</div>';
    });
    
    var addBtnDisabled = window.carouselSlideData.length >= max;
    html += '<div id="ecmAddCardBtn" onclick="' + (addBtnDisabled ? 'showToast(\'Maximum ' + max + ' slides reached.\', \'warn\')' : 'addCarouselSlideCard()') + '" style="min-width:140px; min-height:180px; border:2px dashed ' + (addBtnDisabled ? '#e2e8f0' : '#8b5cf6') + '; border-radius:16px; display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:' + (addBtnDisabled ? 'not-allowed' : 'pointer') + '; background:' + (addBtnDisabled ? '#f8fafc' : '#faf5ff') + '; transition:all 0.2s; padding:14px; box-sizing:border-box; opacity:' + (addBtnDisabled ? '0.5' : '1') + ';">';
    html += '<div style="width:44px; height:44px; border-radius:50%; border:2px solid ' + (addBtnDisabled ? '#94a3b8' : '#8b5cf6') + '; display:flex; align-items:center; justify-content:center; color:' + (addBtnDisabled ? '#94a3b8' : '#8b5cf6') + '; font-size:1.25rem; margin-bottom:8px;">';
    html += '<i class="fa-solid fa-plus"></i>';
    html += '</div>';
    html += '<span style="font-size:0.8rem; font-weight:700; color:' + (addBtnDisabled ? '#94a3b8' : '#7c3aed') + ';">Add Slide</span>';
    html += '</div>';
    
    container.innerHTML = html;
}

function insertCarouselFromModal() {
    var ratio = getSelectedCarouselRatio();
    var validSlides = window.carouselSlideData.filter(function(s) { return s.url && s.url.trim() !== ''; });
    if (validSlides.length === 0) {
        showToast('Please upload at least one image for the carousel.', 'warn');
        return;
    }
    
    var ratioClass = ratio === '9-16' ? 'bcar-ratio-9-16' : 'bcar-ratio-16-9';
    var ratioData = ratio === '9-16' ? '9:16' : '16:9';
    
    var html = '<div class="blog-carousel-box ' + ratioClass + '" data-ratio="' + ratioData + '" contenteditable="false">';
    html += '<div class="bcar-viewport">';
    html += '<div class="bcar-track">';
    validSlides.forEach(function(s) {
        var alt = escapeImgAttr(s.alt || 'Arigato Devan AI prompt result');
        html += '<div class="bcar-slide">';
        html += '<img src="' + s.url + '" alt="' + alt + '" title="' + alt + '" loading="lazy">';
        if (s.caption && s.caption.trim()) {
            html += '<div class="bcar-caption">' + escapeImgAttr(s.caption.trim()) + '</div>';
        }
        html += '</div>';
    });
    html += '</div>';
    html += '</div>';
    
    html += '<button class="bcar-nav bcar-prev" type="button" aria-label="Previous slide">&lsaquo;</button>';
    html += '<button class="bcar-nav bcar-next" type="button" aria-label="Next slide">&rsaquo;</button>';
    
    html += '<div class="bcar-dots">';
    validSlides.forEach(function(_, i) {
        html += '<span class="bcar-dot' + (i === 0 ? ' active' : '') + '" data-index="' + i + '"></span>';
    });
    html += '</div>';
    html += '</div><p><br></p>';
    
    if (window.currentEditingCarouselBox && tinymce.activeEditor) {
        window.currentEditingCarouselBox.outerHTML = html;
        tinymce.activeEditor.nodeChanged();
    } else if (tinymce.activeEditor) {
        tinymce.activeEditor.execCommand('mceInsertContent', false, html);
    }
    closeEditorCarouselModal();
}

function insertLink() {
    if (tinymce.activeEditor) {
        tinymce.activeEditor.execCommand('mceLink');
    }
}
function fmtBlock(tag) {
    if (!tag) return;
    if (tag === 'greybox') {
        insertGreyBox();
        return;
    }
    if (tinymce.activeEditor) {
        tinymce.activeEditor.execCommand('FormatBlock', false, tag);
    }
}
function insertGreyBox() {
    if (!tinymce.activeEditor) return;
    const html = `
      <div class="blog-grey-box">
        <p><em>We independently review every prompt and tool we recommend. When you use links on this page, we may earn an affiliate commission. <a href="#">Learn more</a>.</em></p>
      </div>
      <p><br></p>
    `;
    tinymce.activeEditor.execCommand('mceInsertContent', false, html);
}

// ── Featured Note Box & Prompt Showcase Modal ──────────────────────────────
window.SITE_RECENT_PROMPTS = <?= json_encode($site_prompts_list) ?>;
window.currentEditingCard = null;

function openNoteBoxModal() {
    if (tinymce.activeEditor) {
        var node = tinymce.activeEditor.selection.getNode();
        var card = tinymce.activeEditor.dom.getParent(node, '.blog-prompt-card');
        if (card) {
            openNoteBoxModalForEdit(card);
            return;
        }
    }
    window.currentEditingCard = null;
    var modal = document.getElementById('noteBoxModal');
    if (!modal) return;
    var submitBtn = document.getElementById('nbModalSubmitBtn');
    if (submitBtn) submitBtn.innerHTML = '<i class="fa-solid fa-check"></i> Insert Box into Blog';
    var titleHeading = document.getElementById('nbModalTitleText');
    if (titleHeading) titleHeading.textContent = 'Callout & Prompt Showcase Box';
    
    modal.style.display = 'flex';
    var list = document.getElementById('nbModalCardsList');
    if (list && list.children.length === 0) {
        addNoteBoxPromptCardRow();
    }
}
function openNoteBoxModalForEdit(cardEl) {
    window.currentEditingCard = cardEl;
    var modal = document.getElementById('noteBoxModal');
    if (!modal) return;
    setNoteBoxModalTab('cards');
    modal.style.display = 'flex';
    
    var submitBtn = document.getElementById('nbModalSubmitBtn');
    if (submitBtn) submitBtn.innerHTML = '<i class="fa-solid fa-arrows-rotate"></i> Update / Swap Prompt in Blog';
    var titleHeading = document.getElementById('nbModalTitleText');
    if (titleHeading) titleHeading.textContent = '🔁 Edit / Swap Featured Prompt';
    
    var list = document.getElementById('nbModalCardsList');
    if (list) {
        list.innerHTML = '';
        var items = cardEl.querySelectorAll('.bpc-item');
        if (items.length > 0) {
            items.forEach(function(item) {
                var titleEl = item.querySelector('.bpc-title');
                var descEl = item.querySelector('.bpc-desc');
                var btnEl = item.querySelector('.bpc-btn');
                var imgEl = item.querySelector('.bpc-thumb img');
                
                addNoteBoxPromptCardRow({
                    title: titleEl ? titleEl.textContent.trim() : '',
                    desc: descEl ? descEl.textContent.trim() : '',
                    url: btnEl ? btnEl.getAttribute('href') : '',
                    btn: btnEl ? btnEl.textContent.replace(/[↗\s]+/g, ' ').trim() : 'Go to page',
                    img: imgEl ? imgEl.getAttribute('src') : ''
                });
            });
        } else {
            addNoteBoxPromptCardRow();
        }
    }
    var noteEl = cardEl.querySelector('.bpc-note');
    var bottomInput = document.getElementById('nbModalBottomText');
    if (noteEl && bottomInput) {
        bottomInput.value = noteEl.textContent.trim();
    }
}
function closeNoteBoxModal() {
    var modal = document.getElementById('noteBoxModal');
    if (modal) modal.style.display = 'none';
    window.currentEditingCard = null;
}
function setNoteBoxModalTab(tab) {
    var cardsTab = document.getElementById('nbCardsTabContent');
    var textTab = document.getElementById('nbTextTabContent');
    var faqTab = document.getElementById('nbFaqTabContent');
    var cardsBtn = document.getElementById('nbTabCardsBtn');
    var textBtn = document.getElementById('nbTabTextBtn');
    var faqBtn = document.getElementById('nbTabFaqBtn');
    
    [cardsTab, textTab, faqTab].forEach(function(el) { if (el) el.style.display = 'none'; });
    [cardsBtn, textBtn, faqBtn].forEach(function(btn) {
        if (btn) {
            btn.style.background = 'transparent';
            btn.style.color = '#64748b';
            btn.style.boxShadow = 'none';
        }
    });

    if (tab === 'cards') {
        if (cardsTab) cardsTab.style.display = 'block';
        if (cardsBtn) {
            cardsBtn.style.background = '#ffffff';
            cardsBtn.style.color = '#0f172a';
            cardsBtn.style.boxShadow = '0 1px 3px rgba(0,0,0,0.06)';
        }
    } else if (tab === 'text') {
        if (textTab) textTab.style.display = 'block';
        if (textBtn) {
            textBtn.style.background = '#ffffff';
            textBtn.style.color = '#0f172a';
            textBtn.style.boxShadow = '0 1px 3px rgba(0,0,0,0.06)';
        }
    } else if (tab === 'faq') {
        if (faqTab) faqTab.style.display = 'block';
        if (faqBtn) {
            faqBtn.style.background = '#ffffff';
            faqBtn.style.color = '#0f172a';
            faqBtn.style.boxShadow = '0 1px 3px rgba(0,0,0,0.06)';
        }
    }
}
function addNoteBoxPromptCardRow(preset) {
    var list = document.getElementById('nbModalCardsList');
    if (!list) return;
    var count = list.children.length;
    if (count >= 4) {
        alert('You can add up to 4 prompt cards in a single box.');
        return;
    }
    var cardIndex = count + 1;
    var row = document.createElement('div');
    row.className = 'nb-card-row';
    row.style.cssText = 'background:#f8fafc; border:1.5px solid #cbd5e1; border-radius:14px; padding:14px 16px; position:relative; margin-bottom:10px;';
    
    var promptOptions = '<option value="">✨ Choose Prompt to Swap / Embed...</option>';
    if (Array.isArray(window.SITE_RECENT_PROMPTS)) {
        window.SITE_RECENT_PROMPTS.forEach(function(p) {
            var isSelected = false;
            if (preset && preset.title && (preset.title.toLowerCase() === p.title.toLowerCase() || (preset.url && (preset.url.indexOf(p.slug) !== -1 || preset.url.indexOf('id=' + p.id) !== -1)))) {
                isSelected = true;
            }
            promptOptions += `<option value="${p.id}" data-title="${escapeHtml(p.title)}" data-img="${escapeHtml(p.image_path)}" data-slug="${escapeHtml(p.slug || '')}" ${isSelected ? 'selected' : ''}>#${p.id} &ndash; ${escapeHtml(p.title)}</option>`;
        });
    }

    var defaultTitle = preset && preset.title ? preset.title : '';
    var defaultUrl = preset && preset.url ? preset.url : '';
    var defaultImg = preset && preset.img ? preset.img : 'toplogo/logo01.webp';
    var defaultBtn = preset && preset.btn ? preset.btn : 'Go to page';
    var defaultDesc = preset && preset.desc ? preset.desc : 'One-click unlock on Arigato Devan';

    row.innerHTML = `
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; border-bottom:1px solid #e2e8f0; padding-bottom:8px;">
            <div style="display:flex; align-items:center; gap:8px;">
                <span style="font-size:0.75rem; font-weight:800; color:#0284c7; text-transform:uppercase; background:#e0f2fe; padding:2px 8px; border-radius:6px;">Prompt Card #${cardIndex}</span>
                <span style="font-size:0.75rem; color:#64748b;">(Select below to swap instantly)</span>
            </div>
            <button type="button" onclick="this.closest('.nb-card-row').remove()" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:0.85rem;" title="Remove this card"><i class="fa-solid fa-trash-can"></i></button>
        </div>
        <div style="margin-bottom:10px;">
            <label style="display:block; font-size:0.8rem; font-weight:800; color:#0f172a; margin-bottom:5px;">
                <i class="fa-solid fa-arrows-rotate" style="color:#0284c7;"></i> Select Another Prompt (Instant Swap):
            </label>
            <select class="nb-prompt-picker" onchange="onNoteBoxPromptSelect(this)" style="width:100%; padding:9px 12px; border:2px solid #38bdf8; border-radius:10px; font-size:0.88rem; background:#ffffff; color:#0f172a; font-weight:700; cursor:pointer; box-shadow:0 1px 4px rgba(56,189,248,0.2);">
                ${promptOptions}
            </select>
        </div>
        <div style="display:flex; gap:12px; align-items:center; background:#ffffff; padding:10px 12px; border-radius:10px; border:1px solid #e2e8f0; margin-bottom:10px;">
            <img class="nb-card-img-preview" src="${escapeHtml(defaultImg)}" alt="Preview" style="width:52px; height:52px; border-radius:8px; object-fit:cover; border:1px solid #cbd5e1; flex-shrink:0;">
            <div style="flex:1; min-width:0;">
                <input type="text" class="nb-card-title" value="${escapeHtml(defaultTitle)}" placeholder="Prompt Title" style="width:100%; box-sizing:border-box; padding:6px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.88rem; font-weight:700; margin-bottom:4px;">
                <input type="text" class="nb-card-desc" value="${escapeHtml(defaultDesc)}" placeholder="Short Subtitle / Tag" style="width:100%; box-sizing:border-box; padding:6px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.8rem; color:#64748b;">
            </div>
        </div>
        <div style="display:grid; grid-template-columns:1.5fr 1fr; gap:8px;">
            <input type="text" class="nb-card-url" value="${escapeHtml(defaultUrl)}" placeholder="Target URL (e.g. prompts/...)" style="padding:6px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.8rem;">
            <input type="text" class="nb-card-btn-text" value="${escapeHtml(defaultBtn)}" placeholder="Button Text (Go to page)" style="padding:6px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.8rem;">
            <input type="hidden" class="nb-card-img" value="${escapeHtml(defaultImg)}">
        </div>
    `;
    list.appendChild(row);
}
function onNoteBoxPromptSelect(selectEl) {
    var opt = selectEl.options[selectEl.selectedIndex];
    if (!opt || !opt.value) return;
    var row = selectEl.closest('.nb-card-row');
    if (!row) return;
    var title = opt.getAttribute('data-title') || '';
    var img = opt.getAttribute('data-img') || '';
    var slug = opt.getAttribute('data-slug') || '';
    var id = opt.value;
    
    var titleInput = row.querySelector('.nb-card-title');
    var urlInput = row.querySelector('.nb-card-url');
    var imgInput = row.querySelector('.nb-card-img');
    var imgPreview = row.querySelector('.nb-card-img-preview');
    
    if (titleInput) titleInput.value = title;
    if (urlInput) urlInput.value = slug ? ('prompts/' + encodeURIComponent(slug)) : ('prompt.php?id=' + id);
    if (imgInput) imgInput.value = img;
    if (imgPreview) imgPreview.src = img || 'toplogo/logo01.webp';
}
window.currentEditingFaqBox = null;

function openFaqModal() {
    window.currentEditingFaqBox = null;
    var modal = document.getElementById('faqModal');
    if (!modal) return;
    document.getElementById('faqModalHeading').textContent = 'Insert Quick FAQ Section';
    document.getElementById('faqModalSubmitBtn').innerHTML = '<i class="fa-solid fa-check"></i> Insert FAQ Section into Blog';
    
    var list = document.getElementById('faqModalItemList');
    if (list && list.children.length === 0) {
        addFaqModalRow('How do I unlock the secret codes on Arigato Devan?', 'Comment on our Instagram post to receive the secret code in your DMs, or visit our All Codes page to unlock prompts instantly.');
        addFaqModalRow('Which AI image generators are supported?', 'All prompts are tested and verified across ChatGPT (DALL-E 3), Midjourney v6, and Google Gemini with photorealistic output.');
    }
    modal.style.display = 'flex';
}

function openFaqModalForEdit(faqBox) {
    window.currentEditingFaqBox = faqBox;
    var modal = document.getElementById('faqModal');
    if (!modal) return;
    
    document.getElementById('faqModalHeading').textContent = '✏️ Edit FAQ Section';
    document.getElementById('faqModalSubmitBtn').innerHTML = '<i class="fa-solid fa-arrows-rotate"></i> Update FAQ in Blog';
    
    // 1. Read Title
    var titleEl = faqBox.querySelector('.blog-faq-title');
    if (titleEl) {
        document.getElementById('faqModalSectionTitle').value = titleEl.textContent.trim();
    }
    
    // 2. Read Theme
    var matchedTheme = 'faq-theme-nogoda';
    ['faq-theme-nogoda', 'faq-theme-sky', 'faq-theme-navy', 'faq-theme-amber', 'faq-theme-emerald'].forEach(function(t) {
        if (faqBox.classList.contains(t)) matchedTheme = t;
    });
    var themeRadio = document.querySelector(`input[name="faqBoxTheme"][value="${matchedTheme}"]`);
    if (themeRadio) {
        themeRadio.checked = true;
    }
    
    // 3. Read Badge Style
    var isQa = !!faqBox.querySelector('.faq-q-badge');
    var badgeSelect = document.getElementById('faqModalBadgeStyle');
    if (badgeSelect) badgeSelect.value = isQa ? 'qa' : 'num';
    
    // 4. Read Questions & Answers
    var list = document.getElementById('faqModalItemList');
    if (list) {
        list.innerHTML = '';
        var items = faqBox.querySelectorAll('.blog-faq-item');
        if (items.length > 0) {
            items.forEach(function(item) {
                var qEl = item.querySelector('.faq-q-text');
                var aEl = item.querySelector('.faq-a-text');
                addFaqModalRow(qEl ? qEl.textContent.trim() : '', aEl ? aEl.textContent.trim() : '');
            });
        } else {
            addFaqModalRow('', '');
        }
    }
    modal.style.display = 'flex';
}

function closeFaqModal() {
    var modal = document.getElementById('faqModal');
    if (modal) modal.style.display = 'none';
    window.currentEditingFaqBox = null;
}

function addFaqModalRow(q, a) {
    var list = document.getElementById('faqModalItemList');
    if (!list) return;
    var row = document.createElement('div');
    row.className = 'faq-modal-row';
    row.style.cssText = 'background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:12px; padding:12px 14px; position:relative;';
    
    row.innerHTML = `
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
            <span style="font-size:0.75rem; font-weight:800; color:#64748b;" class="faq-row-num">Question</span>
            <button type="button" onclick="this.closest('.faq-modal-row').remove(); updateFaqRowNumbers();" style="background:none; border:none; color:#ef4444; font-size:0.85rem; cursor:pointer; padding:2px 6px;" title="Remove this question"><i class="fa-regular fa-trash-can"></i></button>
        </div>
        <input type="text" class="faq-input-q" placeholder="Enter question..." value="${escapeHtml(q || '')}" style="width:100%; padding:8px 10px; border:1.5px solid #cbd5e1; border-radius:8px; font-family:inherit; font-size:0.88rem; font-weight:700; margin-bottom:8px; box-sizing:border-box;">
        <textarea class="faq-input-a" rows="2" placeholder="Enter answer..." style="width:100%; padding:8px 10px; border:1.5px solid #cbd5e1; border-radius:8px; font-family:inherit; font-size:0.86rem; box-sizing:border-box;">${escapeHtml(a || '')}</textarea>
    `;
    list.appendChild(row);
    updateFaqRowNumbers();
}

function updateFaqRowNumbers() {
    var rows = document.querySelectorAll('#faqModalItemList .faq-modal-row');
    rows.forEach(function(r, idx) {
        var numEl = r.querySelector('.faq-row-num');
        if (numEl) numEl.textContent = 'Question ' + (idx + 1 < 10 ? '0' + (idx + 1) : (idx + 1));
    });
}

function insertFaqFromModal() {
    if (!tinymce.activeEditor) return;
    var themeRadio = document.querySelector('input[name="faqBoxTheme"]:checked');
    var theme = themeRadio ? themeRadio.value : 'faq-theme-nogoda';
    var sectionTitle = (document.getElementById('faqModalSectionTitle').value || '').trim() || 'Frequently Asked Questions';
    var badgeStyle = document.getElementById('faqModalBadgeStyle').value || 'num';
    
    var rows = document.querySelectorAll('#faqModalItemList .faq-modal-row');
    var itemsHtml = [];
    
    rows.forEach(function(row, idx) {
        var q = (row.querySelector('.faq-input-q').value || '').trim();
        var a = (row.querySelector('.faq-input-a').value || '').trim();
        if (!q && !a) return;
        
        var numStr = (idx + 1 < 10) ? ('0' + (idx + 1)) : ('' + (idx + 1));
        var badgeHtml = (badgeStyle === 'qa') 
            ? `<span class="faq-num-badge" style="background:#7c3aed; color:#fff; font-size:0.72rem;">Q</span>` 
            : `<span class="faq-num-badge">${numStr}</span>`;
            
        itemsHtml.push(`
        <div class="blog-faq-item">
          <div class="faq-q-row">
            ${badgeHtml}
            <h4 class="faq-q-text" contenteditable="true">${escapeHtml(q || 'Question')}</h4>
            <span class="faq-toggle-icon"><i class="fa-solid fa-plus"></i></span>
          </div>
          <div class="faq-a-row">
            <div class="faq-a-spacer"></div>
            <p class="faq-a-text" contenteditable="true">${escapeHtml(a || 'Answer')}</p>
          </div>
        </div>`.trim());
    });
    
    if (itemsHtml.length === 0) {
        alert('Please enter at least one question and answer.');
        return;
    }
    
    var faqHtml = `
      <div class="blog-faq-box ${theme}">
        <div class="blog-faq-watermark"></div>
        <div class="blog-faq-inner">
          <div class="blog-faq-head">
            <div class="blog-faq-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            </div>
            <h3 class="blog-faq-title" contenteditable="true">${escapeHtml(sectionTitle)}</h3>
          </div>
          <div class="blog-faq-list">
            ${itemsHtml.join('\n            ')}
          </div>
        </div>
      </div>
    `.trim();

    if (window.currentEditingFaqBox) {
        var dummy = document.createElement('div');
        dummy.innerHTML = faqHtml;
        var newEl = dummy.firstElementChild;
        window.currentEditingFaqBox.parentNode.replaceChild(newEl, window.currentEditingFaqBox);
        tinymce.activeEditor.nodeChanged();
        window.currentEditingFaqBox = null;
    } else {
        tinymce.activeEditor.execCommand('mceInsertContent', false, faqHtml + '<p><br></p>');
    }
    closeFaqModal();
}

function insertNoteBoxFromModal() {
    if (!tinymce.activeEditor) return;
    var cardsTab = document.getElementById('nbCardsTabContent');
    var faqTab = document.getElementById('nbFaqTabContent');
    var isCardsMode = cardsTab && cardsTab.style.display !== 'none';
    var isFaqMode = faqTab && faqTab.style.display !== 'none';
    
    if (isFaqMode) {
        closeNoteBoxModal();
        openFaqModal();
        return;
    }

    if (!isCardsMode) {
        var simpleText = (document.getElementById('nbModalSimpleText').value || '').trim();
        var themeRadio = document.querySelector('input[name="nbBoxTheme"]:checked');
        var theme = themeRadio ? themeRadio.value : 'tip';
        
        var themeClass = 'blog-box-tip';
        var iconHtml = '<span class="box-icon">💡</span> <strong>Extra Tip:</strong>';
        
        if (theme === 'info') {
            themeClass = 'blog-box-info';
            iconHtml = '<span class="box-icon">ℹ️</span> <strong>Please Note:</strong>';
        } else if (theme === 'alert') {
            themeClass = 'blog-box-alert';
            iconHtml = '<span class="box-icon">🔑</span> <strong>Important Note:</strong>';
        }

        var simpleHtml = `
          <div class="blog-grey-box ${themeClass}">
            <div class="blog-box-header">${iconHtml}</div>
            <p contenteditable="true"><em>${escapeHtml(simpleText) || 'We independently review every prompt and tool we recommend.'}</em></p>
          </div>
          <p><br></p>
        `;
        tinymce.activeEditor.execCommand('mceInsertContent', false, simpleHtml);
        closeNoteBoxModal();
        return;
    }

    var cardRows = document.querySelectorAll('#nbModalCardsList .nb-card-row');
    var itemsHtml = [];
    cardRows.forEach(function(row) {
        var title = (row.querySelector('.nb-card-title').value || '').trim();
        var url = (row.querySelector('.nb-card-url').value || '').trim() || '#';
        var img = (row.querySelector('.nb-card-img').value || '').trim() || 'toplogo/logo01.webp';
        var btnText = (row.querySelector('.nb-card-btn-text').value || '').trim() || 'Go to page';
        var desc = (row.querySelector('.nb-card-desc').value || '').trim();

        if (title) {
            itemsHtml.push(`
          <div class="bpc-item">
            <div class="bpc-thumb">
              <img src="${img}" alt="${escapeHtml(title)}">
            </div>
            <div class="bpc-details">
              <div class="bpc-badge" contenteditable="true">Featured Prompt</div>
              <h4 class="bpc-title" contenteditable="true">${escapeHtml(title)}</h4>
              ${desc ? `<p class="bpc-desc" contenteditable="true">${escapeHtml(desc)}</p>` : ''}
            </div>
            <div class="bpc-action">
              <a href="${url}" class="bpc-btn" target="_blank" rel="noopener noreferrer">${escapeHtml(btnText)} <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
            </div>
          </div>`.trim());
        }
    });

    var bottomText = (document.getElementById('nbModalBottomText').value || '').trim();
    var boxHtml = `
      <div class="blog-prompt-card">
        ${itemsHtml.join('\n        ')}
        ${bottomText ? `<p class="bpc-note" contenteditable="true"><em>${escapeHtml(bottomText)}</em></p>` : ''}
      </div>
      <p><br></p>
    `;
    if (window.currentEditingCard && window.currentEditingCard.parentNode) {
        var tempDiv = tinymce.activeEditor.dom.create('div', {}, boxHtml);
        var newCard = tempDiv.firstElementChild;
        window.currentEditingCard.parentNode.replaceChild(newCard, window.currentEditingCard);
        tinymce.activeEditor.selection.select(newCard);
        tinymce.activeEditor.nodeChanged();
    } else {
        tinymce.activeEditor.execCommand('mceInsertContent', false, boxHtml);
    }
    closeNoteBoxModal();
}
function setTocSize(size) {
    if (!size || !tinymce.activeEditor) return;
    var ed = tinymce.activeEditor;
    var existingToc = ed.dom.select('.blog-toc-box');
    if (existingToc && existingToc.length > 0) {
        existingToc.forEach(function(el) {
            el.classList.remove('blog-toc-sm', 'blog-toc-md', 'blog-toc-lg');
            el.classList.add('blog-toc-' + size);
            el.setAttribute('data-toc-size', size);
        });
        ed.nodeChanged();
    } else {
        insertTableOfContents(size);
    }
}
function insertTableOfContents(size) {
    if (!tinymce.activeEditor) return;
    var ed = tinymce.activeEditor;
    var tocSize = size || 'sm';
    var headings = ed.dom.select('h2, h3');
    headings = headings.filter(function(h) {
        return !ed.dom.getParent(h, '.blog-cta-box') && !ed.dom.getParent(h, '.blog-toc-box');
    });
    if (!headings || headings.length === 0) {
        var starter = `
          <div class="blog-toc-box blog-toc-${tocSize}" data-toc-size="${tocSize}">
            <div class="blog-toc-title"><i class="fa-solid fa-list-ol" style="margin-right:6px;"></i>Table of Contents</div>
            <ol>
              <li><a href="#section-1">1. Section 1 Title</a></li>
              <li><a href="#section-2">2. Section 2 Title</a></li>
            </ol>
          </div>
          <p><br></p>
        `;
        ed.execCommand('mceInsertContent', false, starter);
        return;
    }
    var listItems = [];
    headings.forEach(function(h, idx) {
        var rawText = (h.textContent || '').replace(/\u00a0/g, ' ').trim();
        if (!rawText) return;
        var cleanId = h.getAttribute('id');
        if (!cleanId) {
            cleanId = 'toc-' + rawText.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || ('section-' + (idx + 1));
            h.setAttribute('id', cleanId);
        }
        var isSub = h.nodeName === 'H3';
        listItems.push(`<li style="${isSub ? 'margin-left:16px; list-style-type:circle;' : 'margin-bottom:4px;'}"><a href="#${cleanId}">${escapeHtml(rawText)}</a></li>`);
    });
    var tocHtml = `
      <div class="blog-toc-box blog-toc-${tocSize}" data-toc-size="${tocSize}">
        <div class="blog-toc-title"><i class="fa-solid fa-list-ol" style="margin-right:6px;"></i>Table of Contents</div>
        <ol>
          ${listItems.join('\n          ')}
        </ol>
      </div>
      <p><br></p>
    `;
    var existingToc = ed.dom.select('.blog-toc-box');
    if (existingToc && existingToc.length > 0) {
        ed.dom.setOuterHTML(existingToc[0], tocHtml);
    } else {
        ed.execCommand('mceInsertContent', false, tocHtml);
    }
}
function removeTocFromDoc() {
    if (!tinymce.activeEditor) return;
    var ed = tinymce.activeEditor;
    var existingToc = ed.dom.select('.blog-toc-box');
    if (existingToc && existingToc.length > 0) {
        existingToc.forEach(function(el) {
            ed.dom.remove(el);
        });
        ed.nodeChanged();
    }
}
function toggleImageCaption(editor, img) {
    if (!img || img.nodeName !== 'IMG') return;
    var parent = img.parentNode;
    if (parent && parent.nodeName === 'FIGURE') {
        var existingCap = parent.querySelector('figcaption, .img-caption');
        if (existingCap) {
            editor.selection.select(existingCap);
            return;
        }
        var newCap = editor.dom.create('figcaption', { class: 'img-caption' }, '(Image credit: Source name)');
        parent.appendChild(newCap);
        editor.selection.select(newCap);
    } else {
        var next = img.nextElementSibling;
        if (!next && parent && (parent.nodeName === 'P' || parent.nodeName === 'DIV')) {
            next = parent.nextElementSibling;
        }
        if (next && (next.classList.contains('img-caption') || next.nodeName === 'FIGCAPTION')) {
            editor.selection.select(next);
            return;
        }
        var capEl = editor.dom.create('p', { class: 'img-caption' }, '(Image credit: Source name)');
        if (parent && (parent.nodeName === 'P' || parent.nodeName === 'DIV') && parent.childNodes.length === 1) {
            parent.parentNode.insertBefore(capEl, parent.nextSibling);
        } else {
            img.parentNode.insertBefore(capEl, img.nextSibling);
        }
        editor.selection.select(capEl);
    }
    editor.nodeChanged();
}
function toggleSelectedImageCaption() {
    if (!tinymce.activeEditor) return;
    var ed = tinymce.activeEditor;
    var node = ed.selection.getNode();
    if (node && node.nodeName !== 'IMG') node = ed.dom.getParent(node, 'img');
    if (node && node.nodeName === 'IMG') {
        toggleImageCaption(ed, node);
    } else {
        alert('Please click on an image in the editor first to add or edit its caption.');
    }
}
function openCtaModal() {
    var modal = document.getElementById('ctaBoxModal');
    if (modal) modal.style.display = 'flex';
}
function closeCtaModal() {
    var modal = document.getElementById('ctaBoxModal');
    if (modal) modal.style.display = 'none';
}
function addCtaModalButtonRow() {
    var list = document.getElementById('ctaModalBtnList');
    if (!list) return;
    var count = list.querySelectorAll('.cta-btn-input-row').length;
    if (count >= 5) {
        alert('You can add up to 5 buttons in the Color Box.');
        return;
    }
    var row = document.createElement('div');
    row.className = 'cta-btn-input-row';
    row.style.cssText = 'display:flex; gap:8px; align-items:center;';
    row.innerHTML = `
        <input type="text" class="cta-btn-text" placeholder="Button ${count + 1} Text" style="flex:1; padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px; font-size:0.85rem;">
        <input type="text" class="cta-btn-url" placeholder="https://..." style="flex:1.5; padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px; font-size:0.85rem;">
        <select class="cta-btn-style" style="padding:8px; border:1px solid #cbd5e1; border-radius:8px; font-size:0.82rem;">
            <option value="ghost">Ghost Glass</option>
            <option value="primary">Accent Pill</option>
            <option value="secondary">White Button</option>
        </select>
        <button type="button" onclick="this.parentNode.remove()" style="background:#fee2e2; border:none; color:#ef4444; width:28px; height:28px; border-radius:6px; cursor:pointer; display:flex; align-items:center; justify-content:center;" title="Remove Button"><i class="fa-solid fa-trash-can" style="font-size:0.75rem;"></i></button>
    `;
    list.appendChild(row);
}
function insertCtaBoxFromModal() {
    if (!tinymce.activeEditor) return;
    var theme = document.getElementById('ctaModalTheme').value || 'violet';
    var title = document.getElementById('ctaModalTitle').value.trim() || 'Unlock All Secret Code AI Prompts';
    var desc = document.getElementById('ctaModalDesc').value.trim() || '';
    var btnRows = document.querySelectorAll('#ctaModalBtnList .cta-btn-input-row');
    var btnHtml = [];
    btnRows.forEach(function(row) {
        var text = (row.querySelector('.cta-btn-text').value || '').trim();
        var url = (row.querySelector('.cta-btn-url').value || '').trim() || '#';
        var style = row.querySelector('.cta-btn-style').value || 'primary';
        if (text) {
            btnHtml.push(`<a href="${url}" class="blog-cta-btn blog-cta-btn-${style}" target="_blank" rel="noopener noreferrer">${escapeHtml(text)}</a>`);
        }
    });
    var boxHtml = `
      <div class="blog-cta-box blog-cta-${theme}">
        <h3 class="blog-cta-title">${escapeHtml(title)}</h3>
        ${desc ? `<p class="blog-cta-desc">${escapeHtml(desc)}</p>` : ''}
        <div class="blog-cta-buttons">
          ${btnHtml.join('\n          ')}
        </div>
      </div>
      <p><br></p>
    `;
    tinymce.activeEditor.execCommand('mceInsertContent', false, boxHtml);
    closeCtaModal();
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
function insertCodeBlock(theme) {
    if (tinymce.activeEditor) {
        var cls = theme === 'light' ? 'code-theme-light' : (theme === 'cyber' ? 'code-theme-cyber' : 'code-theme-dark');
        const codeHtml = `
          <pre class="${cls}"><code>// Insert code here...</code></pre>
          <p><br></p>
        `;
        tinymce.activeEditor.execCommand('mceInsertContent', false, codeHtml);
    }
}
function uploadEditorImage(file) {
    const seo = window.prompt('SEO description for this image', '') || '';
    const formData = new FormData();
    formData.append('file', file);
    fetch('upload_editor_image.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.url && tinymce.activeEditor) {
            const safe = escapeImgAttr(seo.trim());
            tinymce.activeEditor.execCommand('mceInsertContent', false, `<p style="text-align:left"><img src="${data.url}" alt="${safe}" title="${safe}" data-align="left" class="align-left" style="max-width:100%; border-radius:10px; display:block; margin:1.5em 0 1.25em;"></p><p><br></p>`);
        } else {
            alert('Upload failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => alert('Upload error: ' + err.message));
}

function setSelectedImageBorder(val) {
    if (val === '') return;
    if (tinymce.activeEditor) {
        var node = tinymce.activeEditor.selection.getNode();
        if (node && node.nodeName !== 'IMG') node = tinymce.activeEditor.dom.getParent(node, 'img');
        if (node && node.nodeName === 'IMG') {
            if (val === 'custom') {
                var currentW = parseInt(node.getAttribute('data-border-width'), 10) || 2;
                var customVal = window.prompt('Enter border width in pixels (e.g. 1, 2, 5):', currentW);
                if (customVal !== null && customVal.trim() !== '') {
                    var num = parseInt(customVal, 10);
                    setImageBorderWidth(tinymce.activeEditor, node, isNaN(num) ? 0 : num);
                }
            } else {
                setImageBorderWidth(tinymce.activeEditor, node, parseInt(val, 10));
            }
        } else {
            alert('Please select an image in the editor first to change its border.');
        }
    }
}

function toggleSelectedImageBorder() {
    if (tinymce.activeEditor) {
        var node = tinymce.activeEditor.selection.getNode();
        if (node && node.nodeName !== 'IMG') node = tinymce.activeEditor.dom.getParent(node, 'img');
        if (node && node.nodeName === 'IMG') {
            toggleImageBorder(tinymce.activeEditor, node);
        } else {
            alert('Please select an image in the editor first to apply or remove the border.');
        }
    }
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

function previewCoverImage(input, previewId) {
    const img = document.getElementById(previewId || 'coverPreviewImg');
    if (input.files && input.files[0] && img) {
        const reader = new FileReader();
        reader.onload = function(e) {
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

// ── Category Sidebar Accordion & Dynamic Add ──────────────────
function toggleCategoryAccordion() {
    var body = document.getElementById('catAccordionBody');
    var icon = document.getElementById('catAccordionIcon');
    if (!body) return;
    var isHidden = body.style.display === 'none';
    body.style.display = isHidden ? 'block' : 'none';
    if (icon) icon.style.transform = isHidden ? 'rotate(0deg)' : 'rotate(180deg)';
}

function toggleAddCategoryForm() {
    var form = document.getElementById('addCatForm');
    if (!form) return;
    var isHidden = form.style.display === 'none';
    form.style.display = isHidden ? 'block' : 'none';
    if (isHidden) {
        var input = document.getElementById('newCatInput');
        if (input) { input.value = ''; input.focus(); }
    }
}

function addNewCategory() {
    var input = document.getElementById('newCatInput');
    if (!input) return;
    var name = input.value.trim();
    if (!name) {
        alert('Please enter a category name.');
        return;
    }
    
    var listContainer = document.getElementById('catListContainer');
    var existing = false;
    listContainer.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
        if (cb.value.toLowerCase() === name.toLowerCase()) {
            cb.checked = true;
            existing = true;
        }
    });
    
    if (existing) {
        input.value = '';
        toggleAddCategoryForm();
        return;
    }
    
    var csrfToken = document.querySelector('input[name="csrf_token"]') ? document.querySelector('input[name="csrf_token"]').value : '';
    var formData = new FormData();
    formData.append('action', 'add_category');
    formData.append('category_name', name);
    formData.append('csrf_token', csrfToken);
    
    fetch('blog_create.php', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        appendCategoryCheckbox(name);
    })
    .catch(function(err) {
        appendCategoryCheckbox(name);
    });
}

function appendCategoryCheckbox(name) {
    var listContainer = document.getElementById('catListContainer');
    var label = document.createElement('label');
    label.style.cssText = 'display:flex; align-items:center; gap:9px; font-size:0.85rem; color:#1e293b; font-weight:600; cursor:pointer;';
    label.innerHTML = `
        <input type="checkbox" name="categories[]" value="${escapeHtml(name)}" checked style="width:16px; height:16px; accent-color:#0284c7; cursor:pointer; border-radius:4px;">
        <span>${escapeHtml(name)}</span>
    `;
    listContainer.appendChild(label);
    listContainer.scrollTop = listContainer.scrollHeight;
    var input = document.getElementById('newCatInput');
    if (input) input.value = '';
    toggleAddCategoryForm();
}

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
    const displaySlug = slugVal || titleVal.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || "new-post";

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

<!-- CTA Color Box Builder Modal -->
<div id="ctaBoxModal" class="be-modal-backdrop" style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(15,23,42,0.65); backdrop-filter:blur(5px); align-items:center; justify-content:center; padding:16px;">
    <div class="be-modal-dialog" style="background:#ffffff; border-radius:20px; max-width:580px; width:100%; max-height:92vh; overflow-y:auto; box-shadow:0 25px 50px -12px rgba(0,0,0,0.3); border:1px solid #e2e8f0; font-family:'Plus Jakarta Sans',sans-serif; animation:modalPop 0.2s cubic-bezier(0.16,1,0.3,1);">
        <div style="padding:18px 24px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:38px; height:38px; border-radius:12px; background:#ede9fe; color:#7c3aed; display:flex; align-items:center; justify-content:center; font-size:1.15rem;">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size:1.08rem; font-weight:800; color:#0f172a;">Create CTA Color Box</h3>
                    <p style="margin:0; font-size:0.8rem; color:#64748b;">Custom callout banner matching Nogoda site theme</p>
                </div>
            </div>
            <button type="button" onclick="closeCtaModal()" style="background:none; border:none; font-size:1.25rem; color:#94a3b8; cursor:pointer; padding:6px; line-height:1;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <div style="padding:22px 24px;">
            <!-- Theme selection (3 Nogoda themes) -->
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:6px;">Color Theme (Nogoda Palette)</label>
                <select id="ctaModalTheme" style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-family:inherit; font-size:0.9rem; background:#fff; font-weight:600; color:#0f172a;">
                    <option value="violet">Nogoda Electric Violet (Default - Purple & Indigo Gradient)</option>
                    <option value="navy">Nogoda Deep Navy & Sky (Rich Dark Slate & Sky Blue)</option>
                    <option value="rose">Nogoda Sunset Rose (High-Energy Crimson Gradient)</option>
                </select>
            </div>
            
            <!-- Title -->
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:6px;">Banner Title</label>
                <input type="text" id="ctaModalTitle" value="Unlock All Secret Code AI Prompts" placeholder="e.g. Operate Multiple Accounts in Isolated Profiles" style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-family:inherit; font-size:0.92rem; box-sizing:border-box;">
            </div>
            
            <!-- Description -->
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:6px;">Description / Subtitle</label>
                <textarea id="ctaModalDesc" rows="2" placeholder="Brief enticing description..." style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-family:inherit; font-size:0.9rem; box-sizing:border-box;">Get instant copy-paste access to trending Gemini & ChatGPT prompts directly on Arigato Devan.</textarea>
            </div>
            
            <!-- Buttons Section -->
            <div style="margin-bottom:8px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                    <label style="font-size:0.85rem; font-weight:700; color:#334155;">Action Buttons (Up to 5)</label>
                    <button type="button" onclick="addCtaModalButtonRow()" style="background:#f1f5f9; border:none; border-radius:6px; padding:5px 12px; font-size:0.78rem; font-weight:700; color:#475569; cursor:pointer;"><i class="fa-solid fa-plus"></i> Add Button</button>
                </div>
                <div id="ctaModalBtnList" style="display:flex; flex-direction:column; gap:10px;">
                    <!-- Row 1 (Pre-filled Explore gallery) -->
                    <div class="cta-btn-input-row" style="display:flex; gap:8px; align-items:center;">
                        <input type="text" class="cta-btn-text" value="Explore Gallery" placeholder="Button Text" style="flex:1; padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px; font-size:0.85rem;">
                        <input type="text" class="cta-btn-url" value="https://arigatodevan.com/gallery.php" placeholder="URL" style="flex:1.5; padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px; font-size:0.85rem;">
                        <select class="cta-btn-style" style="padding:8px; border:1px solid #cbd5e1; border-radius:8px; font-size:0.82rem;">
                            <option value="primary">Accent Pill</option>
                            <option value="secondary">White Button</option>
                            <option value="ghost">Ghost Glass</option>
                        </select>
                    </div>
                    <!-- Row 2 (Pre-filled Login) -->
                    <div class="cta-btn-input-row" style="display:flex; gap:8px; align-items:center;">
                        <input type="text" class="cta-btn-text" value="Login / Sign Up" placeholder="Button Text" style="flex:1; padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px; font-size:0.85rem;">
                        <input type="text" class="cta-btn-url" value="https://arigatodevan.com/login.php" placeholder="URL" style="flex:1.5; padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px; font-size:0.85rem;">
                        <select class="cta-btn-style" style="padding:8px; border:1px solid #cbd5e1; border-radius:8px; font-size:0.82rem;">
                            <option value="secondary">White Button</option>
                            <option value="primary">Accent Pill</option>
                            <option value="ghost">Ghost Glass</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="padding:16px 24px; background:#f8fafc; border-top:1px solid #f1f5f9; display:flex; justify-content:flex-end; gap:10px; border-radius:0 0 20px 20px;">
            <button type="button" onclick="closeCtaModal()" style="padding:10px 18px; border:1px solid #cbd5e1; background:#fff; border-radius:10px; font-weight:700; font-size:0.88rem; color:#475569; cursor:pointer;">Cancel</button>
            <button type="button" onclick="insertCtaBoxFromModal()" style="padding:10px 22px; border:none; background:#7c3aed; color:#fff; border-radius:10px; font-weight:800; font-size:0.88rem; cursor:pointer; box-shadow:0 4px 12px rgba(124,58,237,0.3);"><i class="fa-solid fa-check" style="margin-right:6px;"></i> Insert Box</button>
        </div>
    </div>
</div>

<!-- Note Box & Prompt Showcase Builder Modal -->
<div id="noteBoxModal" class="be-modal-backdrop" style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(15,23,42,0.65); backdrop-filter:blur(5px); align-items:center; justify-content:center; padding:16px;">
    <div class="be-modal-dialog" style="background:#ffffff; border-radius:20px; max-width:620px; width:100%; max-height:92vh; overflow-y:auto; box-shadow:0 25px 50px -12px rgba(0,0,0,0.3); border:1px solid #e2e8f0; font-family:'Plus Jakarta Sans',sans-serif; animation:modalPop 0.2s cubic-bezier(0.16,1,0.3,1);">
        <div style="padding:18px 24px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:38px; height:38px; border-radius:12px; background:#f1f5f9; color:#475569; display:flex; align-items:center; justify-content:center; font-size:1.15rem;">
                    <i class="fa-regular fa-note-sticky"></i>
                </div>
                <div>
                    <h3 id="nbModalTitleText" style="margin:0; font-size:1.08rem; font-weight:800; color:#0f172a;">Callout & Prompt Showcase Box</h3>
                    <p style="margin:0; font-size:0.8rem; color:#64748b;">Grey container with linked prompt cards and bottom note</p>
                </div>
            </div>
            <button type="button" onclick="closeNoteBoxModal()" style="background:none; border:none; font-size:1.25rem; color:#94a3b8; cursor:pointer; padding:6px; line-height:1;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <div style="padding:20px 24px;">
            <!-- Tabs -->
            <div style="display:flex; gap:8px; margin-bottom:18px; background:#f1f5f9; padding:4px; border-radius:10px;">
                <button type="button" id="nbTabCardsBtn" onclick="setNoteBoxModalTab('cards')" style="flex:1; padding:8px 10px; border:none; border-radius:8px; font-weight:700; font-size:0.82rem; cursor:pointer; background:#ffffff; color:#0f172a; box-shadow:0 1px 3px rgba(0,0,0,0.06); transition:all 0.15s;">
                    <i class="fa-regular fa-images" style="color:#0284c7; margin-right:4px;"></i> Prompt Cards
                </button>
                <button type="button" id="nbTabTextBtn" onclick="setNoteBoxModalTab('text')" style="flex:1; padding:8px 10px; border:none; border-radius:8px; font-weight:700; font-size:0.82rem; cursor:pointer; background:transparent; color:#64748b; transition:all 0.15s;">
                    <i class="fa-solid fa-lightbulb" style="color:#f59e0b; margin-right:4px;"></i> Callout (3 Themes)
                </button>
                <button type="button" id="nbTabFaqBtn" onclick="setNoteBoxModalTab('faq')" style="flex:1; padding:8px 10px; border:none; border-radius:8px; font-weight:700; font-size:0.82rem; cursor:pointer; background:transparent; color:#64748b; transition:all 0.15s;">
                    <i class="fa-solid fa-circle-question" style="color:#6366f1; margin-right:4px;"></i> Quick FAQ Box
                </button>
            </div>

            <!-- Tab 1: Prompt Cards + Bottom Note -->
            <div id="nbCardsTabContent">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                    <label style="font-size:0.85rem; font-weight:700; color:#334155;">Prompt Cards (Top Section)</label>
                    <button type="button" onclick="addNoteBoxPromptCardRow()" style="background:#f1f5f9; border:none; border-radius:6px; padding:5px 12px; font-size:0.78rem; font-weight:700; color:#0284c7; cursor:pointer;"><i class="fa-solid fa-plus"></i> Add Another Card</button>
                </div>

                <!-- Cards Container -->
                <div id="nbModalCardsList" style="display:flex; flex-direction:column; gap:12px; margin-bottom:18px;">
                </div>

                <!-- Bottom Text -->
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:6px;">Bottom Note / Disclaimer Text</label>
                    <textarea id="nbModalBottomText" rows="2" placeholder="Write disclaimer, affiliate note, or instructions..." style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-family:inherit; font-size:0.88rem; box-sizing:border-box;">We independently review every prompt and tool we recommend. When you use links on this page, we may earn an affiliate commission.</textarea>
                </div>
            </div>

            <!-- Tab 2: Callout Note with 3 Themes -->
            <div id="nbTextTabContent" style="display:none;">
                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:0.85rem; font-weight:800; color:#0f172a; margin-bottom:8px;">Choose Callout Box Theme:</label>
                    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:10px;">
                        <label style="display:flex; flex-direction:column; align-items:center; padding:10px 6px; border:2px solid #f59e0b; background:#fffdf5; border-radius:12px; cursor:pointer; text-align:center; transition:all 0.15s;">
                            <input type="radio" name="nbBoxTheme" value="tip" checked style="accent-color:#f59e0b; margin-bottom:4px;">
                            <span style="font-size:1.3rem; line-height:1;">💡</span>
                            <strong style="font-size:0.8rem; color:#92400e; margin-top:4px;">Extra / Pro Tip</strong>
                            <span style="font-size:0.68rem; color:#b45309;">Amber bulb theme</span>
                        </label>
                        <label style="display:flex; flex-direction:column; align-items:center; padding:10px 6px; border:2px solid #e2e8f0; background:#f8fafc; border-radius:12px; cursor:pointer; text-align:center; transition:all 0.15s;">
                            <input type="radio" name="nbBoxTheme" value="info" style="accent-color:#0284c7; margin-bottom:4px;">
                            <span style="font-size:1.3rem; line-height:1;">ℹ️</span>
                            <strong style="font-size:0.8rem; color:#0f172a; margin-top:4px;">Standard Info</strong>
                            <span style="font-size:0.68rem; color:#64748b;">Classic slate blue</span>
                        </label>
                        <label style="display:flex; flex-direction:column; align-items:center; padding:10px 6px; border:2px solid #e2e8f0; background:#fff5f6; border-radius:12px; cursor:pointer; text-align:center; transition:all 0.15s;">
                            <input type="radio" name="nbBoxTheme" value="alert" style="accent-color:#f43f5e; margin-bottom:4px;">
                            <span style="font-size:1.3rem; line-height:1;">🔑</span>
                            <strong style="font-size:0.8rem; color:#9f1239; margin-top:4px;">Secret / Alert</strong>
                            <span style="font-size:0.68rem; color:#be123c;">Rose coral theme</span>
                        </label>
                    </div>
                </div>
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:6px;">Note Content</label>
                    <textarea id="nbModalSimpleText" rows="4" placeholder="Enter tip, disclaimer, or instructions..." style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-family:inherit; font-size:0.9rem; box-sizing:border-box;">Always specify lighting (like golden hour or cinematic neon reflections) in your prompt to make your photos look instantly authentic.</textarea>
                </div>
            </div>

            <!-- Tab 3: Quick FAQ Box Preview & Insert -->
            <div id="nbFaqTabContent" style="display:none;">
                <div style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:14px; padding:16px; margin-bottom:16px;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                        <span style="width:28px; height:28px; border-radius:8px; background:#e0e7ff; color:#4f46e5; display:flex; align-items:center; justify-content:center; font-size:1rem;"><i class="fa-solid fa-circle-question"></i></span>
                        <strong style="font-size:0.95rem; color:#0f172a;">Quick FAQ Section Preview</strong>
                    </div>
                    <p style="font-size:0.82rem; color:#64748b; margin:0 0 12px;">This will insert a sleek, high-readability FAQ section box with question mark badge into your blog. You can easily click and edit questions and answers directly in the editor.</p>
                    <div style="background:#fff; border:1px solid #cbd5e1; border-radius:10px; padding:10px 14px; font-size:0.85rem; color:#334155;">
                        <div style="display:flex; gap:6px; margin-bottom:4px;"><span style="background:#4f46e5; color:#fff; font-size:0.65rem; font-weight:800; padding:1px 5px; border-radius:4px;">Q</span> <strong>How do I unlock secret code prompts?</strong></div>
                        <div style="display:flex; gap:6px;"><span style="background:#059669; color:#fff; font-size:0.65rem; font-weight:800; padding:1px 5px; border-radius:4px;">A</span> <span style="color:#64748b;">Comment on Instagram @arigato.devan or check All Codes.</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div style="padding:16px 24px; border-top:1px solid #f1f5f9; display:flex; align-items:center; justify-content:flex-end; gap:10px; background:#f8fafc; border-radius:0 0 20px 20px;">
            <button type="button" onclick="closeNoteBoxModal()" style="padding:10px 18px; border:1px solid #cbd5e1; border-radius:10px; background:#fff; font-weight:700; font-size:0.86rem; color:#475569; cursor:pointer;">Cancel</button>
            <button type="button" id="nbModalSubmitBtn" onclick="insertNoteBoxFromModal()" style="padding:10px 22px; border:none; border-radius:10px; background:#0f172a; color:#fff; font-weight:700; font-size:0.86rem; cursor:pointer; display:inline-flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-check"></i> Insert Box into Blog
            </button>
        </div>
    </div>
</div>
<!-- Dedicated FAQ Builder & Editor Modal -->
<div id="faqModal" class="be-modal-backdrop" style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(15,23,42,0.65); backdrop-filter:blur(5px); align-items:center; justify-content:center; padding:16px;">
    <div class="be-modal-dialog" style="background:#ffffff; border-radius:20px; max-width:680px; width:100%; max-height:92vh; overflow-y:auto; box-shadow:0 25px 50px -12px rgba(0,0,0,0.3); border:1px solid #e2e8f0; font-family:'Plus Jakarta Sans',sans-serif; animation:modalPop 0.2s cubic-bezier(0.16,1,0.3,1);">
        <!-- Modal Header -->
        <div style="padding:18px 24px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:38px; height:38px; border-radius:12px; background:#ede9fe; color:#7c3aed; display:flex; align-items:center; justify-content:center; font-size:1.15rem;">
                    <i class="fa-solid fa-circle-question"></i>
                </div>
                <div>
                    <h3 id="faqModalHeading" style="margin:0; font-size:1.08rem; font-weight:800; color:#0f172a;">Insert Quick FAQ Section</h3>
                    <p style="margin:0; font-size:0.8rem; color:#64748b;">Custom styled Q&A section with blurred watermark background</p>
                </div>
            </div>
            <button type="button" onclick="closeFaqModal()" style="background:none; border:none; font-size:1.25rem; color:#94a3b8; cursor:pointer; padding:6px; line-height:1;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <div style="padding:20px 24px;">
            <!-- 5 Theme Selection -->
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:0.85rem; font-weight:800; color:#0f172a; margin-bottom:8px;">Choose FAQ Theme (5 Options):</label>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(115px, 1fr)); gap:8px;">
                    <!-- Theme 1: Nogoda Violet -->
                    <label style="display:flex; flex-direction:column; align-items:center; padding:10px 4px; border:2px solid #7c3aed; background:#faf5ff; border-radius:12px; cursor:pointer; text-align:center; transition:all 0.15s;">
                        <input type="radio" name="faqBoxTheme" value="faq-theme-nogoda" checked style="accent-color:#7c3aed; margin-bottom:4px;">
                        <span style="display:inline-block; width:18px; height:18px; border-radius:50%; background:#7c3aed; margin-bottom:4px;"></span>
                        <strong style="font-size:0.75rem; color:#4c1d95;">Nogoda Violet</strong>
                        <span style="font-size:0.65rem; color:#7c3aed;">Purple glow</span>
                    </label>
                    <!-- Theme 2: Minimalist Sky -->
                    <label style="display:flex; flex-direction:column; align-items:center; padding:10px 4px; border:2px solid #e2e8f0; background:#f0f9ff; border-radius:12px; cursor:pointer; text-align:center; transition:all 0.15s;">
                        <input type="radio" name="faqBoxTheme" value="faq-theme-sky" style="accent-color:#0284c7; margin-bottom:4px;">
                        <span style="display:inline-block; width:18px; height:18px; border-radius:50%; background:#0284c7; margin-bottom:4px;"></span>
                        <strong style="font-size:0.75rem; color:#0369a1;">Clean Sky</strong>
                        <span style="font-size:0.65rem; color:#0284c7;">Sample style</span>
                    </label>
                    <!-- Theme 3: Executive Navy -->
                    <label style="display:flex; flex-direction:column; align-items:center; padding:10px 4px; border:2px solid #e2e8f0; background:#f8fafc; border-radius:12px; cursor:pointer; text-align:center; transition:all 0.15s;">
                        <input type="radio" name="faqBoxTheme" value="faq-theme-navy" style="accent-color:#0f172a; margin-bottom:4px;">
                        <span style="display:inline-block; width:18px; height:18px; border-radius:50%; background:#0f172a; margin-bottom:4px;"></span>
                        <strong style="font-size:0.75rem; color:#0f172a;">Dark Slate</strong>
                        <span style="font-size:0.65rem; color:#475569;">High contrast</span>
                    </label>
                    <!-- Theme 4: Sunset Amber -->
                    <label style="display:flex; flex-direction:column; align-items:center; padding:10px 4px; border:2px solid #e2e8f0; background:#fffdf5; border-radius:12px; cursor:pointer; text-align:center; transition:all 0.15s;">
                        <input type="radio" name="faqBoxTheme" value="faq-theme-amber" style="accent-color:#f59e0b; margin-bottom:4px;">
                        <span style="display:inline-block; width:18px; height:18px; border-radius:50%; background:#f59e0b; margin-bottom:4px;"></span>
                        <strong style="font-size:0.75rem; color:#92400e;">Sunset Gold</strong>
                        <span style="font-size:0.65rem; color:#b45309;">Warm tint</span>
                    </label>
                    <!-- Theme 5: Emerald Mint -->
                    <label style="display:flex; flex-direction:column; align-items:center; padding:10px 4px; border:2px solid #e2e8f0; background:#f0fdf4; border-radius:12px; cursor:pointer; text-align:center; transition:all 0.15s;">
                        <input type="radio" name="faqBoxTheme" value="faq-theme-emerald" style="accent-color:#10b981; margin-bottom:4px;">
                        <span style="display:inline-block; width:18px; height:18px; border-radius:50%; background:#10b981; margin-bottom:4px;"></span>
                        <strong style="font-size:0.75rem; color:#065f46;">Mint Green</strong>
                        <span style="font-size:0.65rem; color:#059669;">Fresh organic</span>
                    </label>
                </div>
            </div>

            <!-- Title & Badge Type Row -->
            <div style="display:grid; grid-template-columns: 2fr 1fr; gap:12px; margin-bottom:16px;">
                <div>
                    <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:6px;">Section Heading</label>
                    <input type="text" id="faqModalSectionTitle" value="Frequently Asked Questions" placeholder="e.g. Frequently Asked Questions" style="width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-family:inherit; font-size:0.9rem; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:6px;">Badge Style</label>
                    <select id="faqModalBadgeStyle" style="width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-family:inherit; font-size:0.88rem; background:#fff; box-sizing:border-box; color:#0f172a; font-weight:600;">
                        <option value="num" selected>Numbers (01, 02...)</option>
                        <option value="qa">Letters (Q / A)</option>
                    </select>
                </div>
            </div>

            <!-- Questions Container -->
            <div style="margin-bottom:12px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                    <label style="font-size:0.85rem; font-weight:800; color:#0f172a;">Questions & Answers</label>
                    <button type="button" onclick="addFaqModalRow()" style="background:#f1f5f9; border:none; border-radius:6px; padding:5px 12px; font-size:0.78rem; font-weight:700; color:#7c3aed; cursor:pointer;"><i class="fa-solid fa-plus"></i> Add Question</button>
                </div>
                <div id="faqModalItemList" style="display:flex; flex-direction:column; gap:12px; max-height:42vh; overflow-y:auto; padding-right:4px;">
                    <!-- Dynamically populated rows -->
                </div>
            </div>
        </div>
        
        <!-- Modal Footer -->
        <div style="padding:16px 24px; border-top:1px solid #f1f5f9; display:flex; align-items:center; justify-content:flex-end; gap:10px; background:#f8fafc; border-radius:0 0 20px 20px;">
            <button type="button" onclick="closeFaqModal()" style="padding:10px 18px; border:1px solid #cbd5e1; border-radius:10px; background:#fff; font-weight:700; font-size:0.86rem; color:#475569; cursor:pointer;">Cancel</button>
            <button type="button" id="faqModalSubmitBtn" onclick="insertFaqFromModal()" style="padding:10px 22px; border:none; border-radius:10px; background:#7c3aed; color:#fff; font-weight:700; font-size:0.86rem; cursor:pointer; display:inline-flex; align-items:center; gap:8px; box-shadow:0 4px 12px rgba(124,58,237,0.25);">
                <i class="fa-solid fa-check"></i> Insert FAQ Section into Blog
            </button>
        </div>
    </div>
</div>

<!-- Dedicated Image Upload Modal with SEO Alt / Title Description -->
<div id="editorImageModal" class="be-modal-backdrop" style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(15,23,42,0.65); backdrop-filter:blur(5px); align-items:center; justify-content:center; padding:16px;">
    <div class="be-modal-dialog" style="background:#ffffff; border-radius:20px; max-width:540px; width:100%; max-height:92vh; overflow-y:auto; box-shadow:0 25px 50px -12px rgba(0,0,0,0.3); border:1px solid #e2e8f0; font-family:'Plus Jakarta Sans',sans-serif;">
        <div style="padding:18px 24px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:38px; height:38px; border-radius:12px; background:#e0f2fe; color:#0284c7; display:flex; align-items:center; justify-content:center; font-size:1.15rem;">
                    <i class="fa-regular fa-image"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size:1.08rem; font-weight:800; color:#0f172a;">Insert Image into Blog</h3>
                    <p style="margin:0; font-size:0.8rem; color:#64748b;">Upload image with Google SEO Alt & Title description</p>
                </div>
            </div>
            <button type="button" onclick="closeEditorImageModal()" style="background:none; border:none; font-size:1.25rem; color:#94a3b8; cursor:pointer; padding:6px; line-height:1;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <div style="padding:20px 24px;">
            <!-- Dropzone / File Picker -->
            <div id="eimDropzone" onclick="document.getElementById('eimFileInput').click()" style="border:2px dashed #cbd5e1; border-radius:14px; padding:24px 16px; text-align:center; cursor:pointer; background:#f8fafc; transition:all 0.2s; margin-bottom:16px;">
                <input type="file" id="eimFileInput" accept="image/*" style="display:none" onchange="handleEimFileSelect(this.files[0])">
                <div id="eimPlaceholder">
                    <i class="fa-solid fa-cloud-arrow-up" style="font-size:2rem; color:#0284c7; margin-bottom:8px;"></i>
                    <p style="margin:0; font-size:0.9rem; font-weight:700; color:#1e293b;">Click to choose an image or drag & drop</p>
                    <p style="margin:4px 0 0; font-size:0.75rem; color:#64748b;">PNG, JPG, WEBP up to 5MB</p>
                </div>
                <div id="eimPreviewWrap" style="display:none; position:relative;">
                    <img id="eimPreviewImg" src="" style="max-height:180px; max-width:100%; border-radius:10px; object-fit:contain; box-shadow:0 4px 12px rgba(0,0,0,0.08);">
                    <div style="margin-top:8px;">
                        <span style="font-size:0.75rem; color:#0284c7; font-weight:700; text-decoration:underline;">Click to change image</span>
                    </div>
                </div>
            </div>

            <!-- SEO Alt Description Field -->
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:0.85rem; font-weight:800; color:#0f172a; margin-bottom:4px;">
                    Image SEO Description / Alt Text <span style="color:#ef4444;">*</span>
                </label>
                <input type="text" id="eimSeoAlt" placeholder="e.g. Photorealistic prompt result of couple in neon city on Midjourney v6" style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-family:inherit; font-size:0.9rem; box-sizing:border-box;">
                <span style="display:block; font-size:0.72rem; color:#64748b; margin-top:3px;">Important: Google uses this for image ranking & Instagram/Twitter previews.</span>
            </div>

            <!-- Optional Caption Field -->
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:4px;">
                    Caption (Optional)
                </label>
                <input type="text" id="eimCaption" placeholder="e.g. Midjourney v6 prompt test with 85mm f/1.4 lens" style="width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-family:inherit; font-size:0.88rem; box-sizing:border-box;">
            </div>

            <!-- Image Alignment & Width -->
            <div style="margin-bottom:8px;">
                <label style="display:block; font-size:0.82rem; font-weight:700; color:#334155; margin-bottom:6px;">Alignment</label>
                <div style="display:flex; gap:10px;">
                    <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:6px; padding:8px; border:1.5px solid #0284c7; background:#f0f9ff; border-radius:10px; cursor:pointer; font-size:0.82rem; font-weight:700; color:#0369a1;">
                        <input type="radio" name="eimAlign" value="center" checked style="accent-color:#0284c7;"> Center
                    </label>
                    <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:6px; padding:8px; border:1.5px solid #e2e8f0; background:#fff; border-radius:10px; cursor:pointer; font-size:0.82rem; font-weight:700; color:#475569;">
                        <input type="radio" name="eimAlign" value="left" style="accent-color:#0284c7;"> Left
                    </label>
                    <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:6px; padding:8px; border:1.5px solid #e2e8f0; background:#fff; border-radius:10px; cursor:pointer; font-size:0.82rem; font-weight:700; color:#475569;">
                        <input type="radio" name="eimAlign" value="full" style="accent-color:#0284c7;"> Full Width
                    </label>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div style="padding:16px 24px; border-top:1px solid #f1f5f9; display:flex; align-items:center; justify-content:flex-end; gap:10px; background:#f8fafc; border-radius:0 0 20px 20px;">
            <button type="button" onclick="closeEditorImageModal()" style="padding:10px 18px; border:1px solid #cbd5e1; border-radius:10px; background:#fff; font-weight:700; font-size:0.86rem; color:#475569; cursor:pointer;">Cancel</button>
            <button type="button" id="eimSubmitBtn" onclick="submitEditorImageUpload()" style="padding:10px 22px; border:none; border-radius:10px; background:#0284c7; color:#fff; font-weight:700; font-size:0.86rem; cursor:pointer; display:inline-flex; align-items:center; gap:8px; box-shadow:0 4px 12px rgba(2,132,199,0.25);">
                <i class="fa-solid fa-check"></i> Upload & Insert Image
            </button>
        </div>
    </div>
</div>

<!-- Dedicated Carousel Builder & Editor Modal -->
<div id="editorCarouselModal" class="be-modal-backdrop" style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(15,23,42,0.65); backdrop-filter:blur(5px); align-items:center; justify-content:center; padding:16px;">
    <div class="be-modal-dialog" style="background:#ffffff; border-radius:20px; max-width:820px; width:100%; max-height:92vh; overflow-y:auto; box-shadow:0 25px 50px -12px rgba(0,0,0,0.3); border:1px solid #e2e8f0; font-family:'Plus Jakarta Sans',sans-serif;">
        <!-- Modal Header -->
        <div style="padding:18px 24px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:38px; height:38px; border-radius:12px; background:#f3e8ff; color:#9333ea; display:flex; align-items:center; justify-content:center; font-size:1.15rem;">
                    <i class="fa-solid fa-images"></i>
                </div>
                <div>
                    <h3 id="ecmModalHeading" style="margin:0; font-size:1.08rem; font-weight:800; color:#0f172a;">Insert Image Carousel</h3>
                    <p style="margin:0; font-size:0.8rem; color:#64748b;">Multi-slide interactive gallery with aspect ratio & SEO metadata</p>
                </div>
            </div>
            <button type="button" onclick="closeEditorCarouselModal()" style="background:none; border:none; font-size:1.25rem; color:#94a3b8; cursor:pointer; padding:6px; line-height:1;"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div style="padding:20px 24px;">
            <!-- Ratio Selector -->
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:0.85rem; font-weight:800; color:#0f172a; margin-bottom:8px;">Choose Aspect Ratio:</label>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <!-- 16:9 Landscape -->
                    <label id="ecmRatio169Label" style="display:flex; align-items:center; gap:12px; padding:12px 14px; border:2px solid #8b5cf6; background:#fbf8ff; border-radius:14px; cursor:pointer; transition:all 0.15s;">
                        <input type="radio" name="ecmRatio" value="16-9" checked onchange="handleCarouselRatioChange('16-9')" style="accent-color:#8b5cf6;">
                        <div>
                            <strong style="display:block; font-size:0.88rem; color:#581c87;">16:9 Landscape (Max 4 Images)</strong>
                            <span style="font-size:0.75rem; color:#7e22ce;">Cinematic wide artworks & landscape prompt comparisons</span>
                        </div>
                    </label>
                    <!-- 9:16 Vertical Reel -->
                    <label id="ecmRatio916Label" style="display:flex; align-items:center; gap:12px; padding:12px 14px; border:2px solid #e2e8f0; background:#f8fafc; border-radius:14px; cursor:pointer; transition:all 0.15s;">
                        <input type="radio" name="ecmRatio" value="9-16" onchange="handleCarouselRatioChange('9-16')" style="accent-color:#8b5cf6;">
                        <div>
                            <strong style="display:block; font-size:0.88rem; color:#1e293b;">9:16 Vertical / Reel (Max 5 Images)</strong>
                            <span style="font-size:0.75rem; color:#64748b;">Phone screen ratio, portrait shots & reel prompts</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Carousel Cards Grid (Matches User's Hand Drawing in Image 5!) -->
            <div>
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                    <label style="font-size:0.85rem; font-weight:800; color:#0f172a;">
                        Carousel Slides (<span id="ecmSlideCountText">0 / 4</span>)
                    </label>
                    <span style="font-size:0.75rem; color:#64748b;" id="ecmLimitTip">Max 4 landscape images</span>
                </div>

                <div id="ecmCardsContainer" style="display:flex; gap:14px; overflow-x:auto; padding-bottom:12px; align-items:stretch;">
                    <!-- Dynamically populated slide cards -->
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div style="padding:16px 24px; border-top:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; background:#f8fafc; border-radius:0 0 20px 20px;">
            <span style="font-size:0.78rem; color:#64748b;">Includes touch swipe, navigation arrows & dots</span>
            <div style="display:flex; gap:10px;">
                <button type="button" onclick="closeEditorCarouselModal()" style="padding:10px 18px; border:1px solid #cbd5e1; border-radius:10px; background:#fff; font-weight:700; font-size:0.86rem; color:#475569; cursor:pointer;">Cancel</button>
                <button type="button" id="ecmSubmitBtn" onclick="insertCarouselFromModal()" style="padding:10px 22px; border:none; border-radius:10px; background:#8b5cf6; color:#fff; font-weight:700; font-size:0.86rem; cursor:pointer; display:inline-flex; align-items:center; gap:8px; box-shadow:0 4px 12px rgba(139,92,246,0.25);">
                    <i class="fa-solid fa-check"></i> Insert Carousel into Blog
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Dedicated List Numbering Continuity Modal (No browser prompt) -->
<div id="listNumberModal" class="be-modal-backdrop" style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(15,23,42,0.65); backdrop-filter:blur(6px); align-items:center; justify-content:center; padding:16px;">
    <div class="be-modal-dialog" style="background:#ffffff; border-radius:20px; max-width:440px; width:100%; box-shadow:0 25px 50px -12px rgba(0,0,0,0.3); border:1px solid #e2e8f0; font-family:'Plus Jakarta Sans',sans-serif; animation:modalPop 0.2s cubic-bezier(0.16,1,0.3,1); overflow:hidden;">
        <div style="padding:18px 22px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; background:#fafafa;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:38px; height:38px; border-radius:12px; background:linear-gradient(135deg, #0284c7, #2563eb); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.1rem; box-shadow:0 4px 10px rgba(37,99,235,0.25);">
                    <i class="fa-solid fa-arrow-down-1-9"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size:1.05rem; font-weight:800; color:#0f172a;">List Numbering</h3>
                    <p id="lnmSubtitle" style="margin:0; font-size:0.78rem; color:#64748b;">Set starting number for this list</p>
                </div>
            </div>
            <button type="button" onclick="closeListNumberModal()" style="background:none; border:none; font-size:1.25rem; color:#94a3b8; cursor:pointer; padding:4px; line-height:1;"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div style="padding:22px;">
            <div id="lnmInfoBox" style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px; padding:10px 14px; margin-bottom:16px; font-size:0.82rem; color:#1e40af; display:flex; align-items:flex-start; gap:10px;">
                <i class="fa-solid fa-circle-info" style="margin-top:2px; color:#3b82f6;"></i>
                <span id="lnmInfoText">Choose the starting number for this list item:</span>
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:0.82rem; font-weight:800; color:#334155; margin-bottom:8px;">Starting Number:</label>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="button" onclick="stepListNumber(-1)" style="width:44px; height:44px; border-radius:12px; border:1.5px solid #cbd5e1; background:#f8fafc; font-size:1.1rem; font-weight:800; color:#334155; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.15s;"><i class="fa-solid fa-minus"></i></button>
                    <input type="number" id="lnmNumberInput" min="1" max="999" value="2" style="flex:1; height:44px; text-align:center; font-size:1.35rem; font-weight:800; color:#0f172a; border:2px solid #3b82f6; border-radius:12px; outline:none; background:#ffffff;">
                    <button type="button" onclick="stepListNumber(1)" style="width:44px; height:44px; border-radius:12px; border:1.5px solid #cbd5e1; background:#f8fafc; font-size:1.1rem; font-weight:800; color:#334155; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.15s;"><i class="fa-solid fa-plus"></i></button>
                </div>
            </div>

            <!-- Quick Presets -->
            <div>
                <label style="display:block; font-size:0.75rem; font-weight:700; color:#64748b; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.04em;">Quick Presets</label>
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    <button type="button" onclick="setListNumberPreset(1)" style="padding:6px 12px; border-radius:8px; border:1px solid #cbd5e1; background:#fff; font-size:0.8rem; font-weight:700; color:#475569; cursor:pointer;">Restart at 1</button>
                    <button type="button" onclick="setListNumberPreset(2)" style="padding:6px 12px; border-radius:8px; border:1px solid #cbd5e1; background:#fff; font-size:0.8rem; font-weight:700; color:#475569; cursor:pointer;">Start at 2</button>
                    <button type="button" onclick="setListNumberPreset(3)" style="padding:6px 12px; border-radius:8px; border:1px solid #cbd5e1; background:#fff; font-size:0.8rem; font-weight:700; color:#475569; cursor:pointer;">Start at 3</button>
                    <button type="button" onclick="setListNumberPreset(4)" style="padding:6px 12px; border-radius:8px; border:1px solid #cbd5e1; background:#fff; font-size:0.8rem; font-weight:700; color:#475569; cursor:pointer;">Start at 4</button>
                </div>
            </div>
        </div>

        <div style="padding:14px 22px; border-top:1px solid #f1f5f9; display:flex; align-items:center; justify-content:flex-end; gap:10px; background:#f8fafc;">
            <button type="button" onclick="closeListNumberModal()" style="padding:9px 16px; border:1px solid #cbd5e1; border-radius:10px; background:#fff; font-weight:700; font-size:0.84rem; color:#475569; cursor:pointer;">Cancel</button>
            <button type="button" onclick="applyListNumberModal()" style="padding:9px 20px; border:none; border-radius:10px; background:#2563eb; color:#fff; font-weight:700; font-size:0.84rem; cursor:pointer; display:inline-flex; align-items:center; gap:6px; box-shadow:0 4px 12px rgba(37,99,235,0.25);">
                <i class="fa-solid fa-check"></i> Apply Number
            </button>
        </div>
    </div>
</div>

<!-- Custom In-Page Alert/Notice Modal -->
<div id="customNoticeModal" class="be-modal-backdrop" style="display:none; position:fixed; inset:0; z-index:999999; background:rgba(15,23,42,0.65); backdrop-filter:blur(6px); align-items:center; justify-content:center; padding:16px;">
    <div class="be-modal-dialog" style="background:#ffffff; border-radius:20px; max-width:400px; width:100%; box-shadow:0 25px 50px -12px rgba(0,0,0,0.3); border:1px solid #e2e8f0; font-family:'Plus Jakarta Sans',sans-serif; text-align:center; padding:24px; animation:modalPop 0.2s cubic-bezier(0.16,1,0.3,1);">
        <div id="cnmIconWrap" style="width:52px; height:52px; border-radius:50%; background:#eff6ff; color:#2563eb; display:flex; align-items:center; justify-content:center; font-size:1.4rem; margin:0 auto 14px;">
            <i id="cnmIcon" class="fa-solid fa-circle-info"></i>
        </div>
        <h3 id="cnmTitle" style="margin:0 0 8px; font-size:1.1rem; font-weight:800; color:#0f172a;">Notice</h3>
        <p id="cnmMessage" style="margin:0 0 20px; font-size:0.88rem; color:#64748b; line-height:1.5;"></p>
        <button type="button" onclick="closeNoticeModal()" style="width:100%; padding:10px; border:none; border-radius:10px; background:#2563eb; color:#fff; font-weight:700; font-size:0.9rem; cursor:pointer; box-shadow:0 4px 12px rgba(37,99,235,0.25);">
            OK, Got it
        </button>
    </div>
</div>
<div id="beToastContainer" style="position:fixed; bottom:24px; right:24px; z-index:9999999; display:flex; flex-direction:column; gap:8px; pointer-events:none; font-family:'Plus Jakarta Sans',sans-serif;"></div>
</body>
</html>
