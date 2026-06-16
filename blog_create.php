<?php
session_start();
require_once "db.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $content = $_POST["content"] ?? ""; // HTML from editor
    $content_hindi = $_POST["content_hindi"] ?? ""; // HTML from hindi editor
    $meta_title = trim($_POST["meta_title"] ?? "");
    $meta_desc = trim($_POST["meta_description"] ?? "");
    $tags = trim($_POST["tags"] ?? "");
    $image_ratio = trim($_POST["image_ratio"] ?? "16:9");
    $publish = isset($_POST["publish"]) ? 1 : 0;

    if (!$title) {
        $error = "Title is required.";
    } else {
        // Custom Slug or Auto-generated Slug
        $custom_slug = trim($_POST["slug"] ?? "");
        if ($custom_slug !== "") {
            $slug = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "-", $custom_slug));
        } else {
            $slug = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "-", $title));
        }
        $slug = trim($slug, "-");

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

        // Automatic SEO Meta Description Fallback (strips HTML and decodes entities up to 150 chars)
        if (empty($meta_desc)) {
            $plain_content = strip_tags($content);
            $plain_content = html_entity_decode($plain_content, ENT_QUOTES, 'UTF-8');
            $plain_content = preg_replace('/\s+/', ' ', $plain_content);
            $meta_desc = mb_substr(trim($plain_content), 0, 150);
            if (mb_strlen($plain_content) > 150) {
                $meta_desc .= '...';
            }
        }

        // Image upload
        $image_path = "";
        if (
            isset($_FILES["image"]) &&
            $_FILES["image"]["error"] === UPLOAD_ERR_OK
        ) {
            $allowed = ["image/jpeg", "image/png", "image/gif", "image/webp"];
            if (in_array($_FILES["image"]["type"], $allowed)) {
                $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
                $filename = "uploads/blog_" . uniqid() . "." . $ext;
                if (
                    move_uploaded_file($_FILES["image"]["tmp_name"], $filename)
                ) {
                    $image_path = $filename;
                }
            }
        }

        $pdo->prepare(
            "INSERT INTO blogs (title,slug,description,content,content_hindi,image_path,image_ratio,meta_title,meta_description,tags,is_published,author_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
        )->execute([
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
            $_SESSION["user_id"],
        ]);
        $_SESSION["success_msg"] =
            "<i class='fa-solid fa-check'></i> Blog " .
            ($publish ? "published" : "saved as draft") .
            "!";
        header("Location: blog_admin.php");
        exit();
    }
}
?><!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create Blog &ndash; Admin | Arigato Devan Prompts</title><link rel="stylesheet" href="style.min.css?v=20260601">
<style>
/* Global Modern Reset for Scribe Editor */
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:ital,wght@0,700;0,800;0,900;1,700&display=swap');

body {
    background-color: #f1f5f9 !important; /* Neutral light-gray base */
    font-family: 'Inter', sans-serif !important;
    color: #1e293b !important;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
    position: relative !important;
}

/* Animated Ambient Aurora Background */
.aurora-bg {
    position: fixed !important;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100vw !important;
    height: 100vh !important;
    z-index: -2 !important;
    background: 
        radial-gradient(circle at 15% 15%, rgba(193, 232, 255, 0.75) 0%, transparent 45%),
        radial-gradient(circle at 85% 15%, rgba(224, 218, 254, 0.75) 0%, transparent 45%),
        radial-gradient(circle at 50% 85%, rgba(245, 238, 253, 0.85) 0%, transparent 50%),
        radial-gradient(circle at 80% 85%, rgba(193, 232, 255, 0.5) 0%, transparent 40%) !important;
    filter: blur(80px) !important;
    animation: auroraShift 30s ease infinite alternate !important;
}

@keyframes auroraShift {
    0% {
        transform: translate(0px, 0px) scale(1);
    }
    50% {
        transform: translate(40px, -30px) scale(1.08);
    }
    100% {
        transform: translate(-30px, 20px) scale(0.92);
    }
}

/* Interactive Dynamic Mouse-Following Glow */
.back-glow {
    position: fixed !important;
    top: 0;
    left: 0;
    width: 100vw !important;
    height: 100vh !important;
    pointer-events: none !important;
    z-index: -1 !important;
    background: radial-gradient(700px circle at var(--x, 50vw) var(--y, 50vh), rgba(99, 102, 241, 0.08), rgba(168, 85, 247, 0.04) 50%, transparent 80%) !important;
    transition: background 0.1s ease !important;
}
@media (max-width: 768px) {
    .back-glow {
        display: none !important; /* Disabled on mobile for performance/page-speed boost! */
    }
}

/* Hide Main Website wallpaper in Blog */
.scroll-bg-container, body::before {
    display: none !important;
}

/* Override Header to be stunningly premium & clean (0% comic, 100% professional) */
header {
    background: rgba(255, 255, 255, 0.85) !important;
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    border: 1px solid rgba(226, 232, 240, 0.8) !important;
    border-radius: 24px !important;
    margin: 15px 24px 0 !important;
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.02) !important;
    padding: 10px 30px !important;
    position: sticky !important;
    top: 15px !important;
    z-index: 1000 !important;
    box-sizing: border-box !important;
    height: 64px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    transform: none !important;
}
header .logo-area {
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
}
header .logo-text {
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    font-weight: 800 !important;
    font-size: 0.95rem !important;
    letter-spacing: -0.5px !important;
    color: #0f172a !important;
    line-height: 1.1 !important;
}
header .logo-flipper {
    display: block !important;
    width: 44px !important;
    height: 44px !important;
    position: relative !important;
    transform-style: preserve-3d !important;
    transition: transform 0.6s !important;
    flex-shrink: 0 !important;
}
header .logo-front, header .logo-back {
    position: absolute !important;
    width: 100% !important;
    height: 100% !important;
    backface-visibility: hidden !important;
    border-radius: 50% !important;
    overflow: hidden !important;
}
header .logo-back {
    transform: rotateY(180deg) !important;
}
header .logo-front img, header .logo-back img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    border-radius: 50% !important;
}
header .logo-area:hover .logo-flipper {
    transform: rotateY(180deg) !important;
}
header nav.nav-links {
    gap: 24px !important;
    border: none !important;
    background: transparent !important;
}
header nav.nav-links a {
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    font-size: 0.85rem !important;
    font-weight: 700 !important;
    color: #475569 !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 16px !important;
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
    border-radius: 12px !important;
    transition: all 0.2s;
}
header nav.nav-links a:hover, header nav.nav-links a.active {
    color: #6366f1 !important;
    background: rgba(99, 102, 241, 0.05) !important;
}
header .header-right {
    gap: 15px !important;
}
header .header-right .logout {
    border: none !important;
    background: #f1f5f9 !important;
    color: #475569 !important;
    border-radius: 12px !important;
    padding: 8px 16px !important;
    font-size: 0.8rem !important;
    font-weight: 700 !important;
    box-shadow: none !important;
}
header .header-right .logout:hover {
    background: #6366f1 !important;
    color: #ffffff !important;
}
header .admin-avatar {
    border: 2px solid #e2e8f0 !important;
}

/* Robust responsive floating header styling for mobile */
@media (max-width: 768px) {
    header {
        padding: 8px 16px !important;
        margin: 10px 12px 0 !important;
        border-radius: 16px !important;
        height: auto !important;
        min-height: 56px !important;
        flex-wrap: wrap !important;
        gap: 12px !important;
        justify-content: space-between !important;
    }
    header .logo-text {
        font-size: 0.8rem !important;
    }
    header .logo-flipper {
        width: 32px !important;
        height: 32px !important;
    }
    header nav.nav-links {
        order: 3 !important;
        width: 100% !important;
        justify-content: center !important;
        gap: 10px !important;
        border-top: 1px solid #f1f5f9 !important;
        padding-top: 10px !important;
        margin-top: 4px !important;
    }
    header nav.nav-links a {
        font-size: 0.72rem !important;
        padding: 6px 12px !important;
        border-radius: 8px !important;
    }
    header .header-right {
        gap: 10px !important;
    }
    header .header-right .logout {
        padding: 6px 12px !important;
        font-size: 0.72rem !important;
    }
}

/* Scribe App Layout Wrapper */
.scribe-container {
    display: grid;
    grid-template-columns: 260px 1fr 360px;
    min-height: calc(100vh - 95px);
    background: transparent; /* Seamless transparency to show the gorgeous animated aurora backdrop */
    position: relative;
    z-index: 10;
    gap: 24px;
    padding: 24px;
    box-sizing: border-box;
}
@media (max-width: 1100px) {
    .scribe-container {
        grid-template-columns: 1fr;
        padding: 16px;
        gap: 16px;
    }
    .scribe-sidebar {
        display: none !important;
    }
    .scribe-controls {
        border-left: none !important;
        border-top: 1px solid #e2e8f0 !important;
        height: auto !important;
        position: static !important;
        overflow-y: visible !important;
        padding: 30px 20px 80px !important;
        border-radius: 20px !important;
    }
}

/* LEFT SIDEBAR */
.scribe-sidebar {
    background: #ffffff;
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 20px;
    padding: 30px 20px;
    display: flex;
    flex-direction: column;
    gap: 25px;
    height: calc(100vh - 124px); /* Fills screen perfectly */
    position: sticky !important;
    top: 94px !important; /* 15px (header top) + 64px (header height) + 15px (gap) = 94px */
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0,0,0,0.01);
    box-sizing: border-box;
}
.sidebar-section-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: #94a3b8;
    margin-bottom: 10px;
}
.sidebar-links {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.sidebar-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border: 1px solid transparent;
    border-radius: 12px;
    color: #475569;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.2s;
}
.sidebar-link:hover {
    background: #f8fafc;
    color: #6366f1;
}
.sidebar-link.active {
    background: rgba(99, 102, 241, 0.08);
    color: #6366f1;
    font-weight: 700;
}

/* CENTER WORKSPACE */
.scribe-workspace {
    padding: 0;
    display: flex;
    justify-content: center;
    background: transparent;
}
.scribe-paper {
    background: #ffffff;
    width: 100%;
    max-width: 780px;
    min-height: 850px;
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 24px;
    padding: 50px 60px;
    box-shadow: 0 10px 30px rgba(113, 140, 251, 0.04);
    display: flex;
    flex-direction: column;
    gap: 24px;
    box-sizing: border-box;
}
@media (max-width: 640px) {
    .scribe-paper {
        padding: 30px 20px;
    }
}

/* Custom Paper Inputs */
.scribe-title-input {
    border: none !important;
    outline: none !important;
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    font-size: 2.2rem !important;
    font-weight: 900 !important;
    color: #0f172a !important;
    padding: 0 !important;
    margin-bottom: 5px;
    width: 100%;
    box-shadow: none !important;
    background: transparent !important;
}
.scribe-title-input::placeholder {
    color: #cbd5e1;
}
.scribe-desc-input {
    border: none !important;
    outline: none !important;
    resize: none !important;
    font-family: 'Inter', sans-serif !important;
    font-size: 1.1rem !important;
    line-height: 1.6;
    color: #64748b;
    padding: 0 !important;
    width: 100%;
    min-height: 50px !important;
    box-shadow: none !important;
    background: transparent !important;
}
.scribe-desc-input::placeholder {
    color: #cbd5e1;
}
.divider-line {
    height: 1px;
    border-bottom: 1px dashed #e2e8f0;
    margin-bottom: 10px;
}

/* RIGHT CONTROLS PANEL */
.scribe-controls {
    background: #ffffff;
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 20px;
    padding: 30px 24px;
    display: flex;
    flex-direction: column;
    gap: 22px;
    overflow-y: auto;
    height: calc(100vh - 124px); /* Perfectly fits screen */
    position: sticky !important;
    top: 94px !important; /* Spacing below floating header */
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.01);
    box-sizing: border-box;
}
.control-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.015);
}
.control-card h3 {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.82rem;
    font-weight: 800;
    margin-bottom: 16px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #0f172a;
}
.control-card h3 i {
    color: #6366f1;
}

/* Text Editor Grid Layout */
.editor-toolbar-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    margin-bottom: 12px;
}
.editor-toolbar-grid button {
    height: 38px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 700;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.editor-toolbar-grid button:hover {
    background: #f8fafc;
    color: #6366f1;
    border-color: #cbd5e1;
    transform: translateY(-1px);
}
.editor-toolbar-grid button:active {
    transform: translateY(0);
}
.editor-toolbar-grid .span-2 {
    grid-column: span 2;
}

/* Editor Area inside Paper */
.editor-area {
    min-height: 500px;
    border: none !important;
    outline: none !important;
    background: transparent !important;
    font-family: var(--font-blog-body), 'Lora', Georgia, serif !important;
    font-size: 1.15rem !important;
    line-height: 1.85;
    color: #2d2a35 !important;
    box-shadow: none !important;
    overflow-y: visible;
    padding: 0 !important;
}
.editor-area[data-placeholder]:not(:focus):empty:before {
    content: attr(data-placeholder);
    color: #cbd5e1;
    font-style: italic;
}
.editor-area h1, .editor-area h2, .editor-area h3 {
    font-family: var(--font-blog-heading), 'Playfair Display', Georgia, serif !important;
    font-weight: 900 !important;
    color: #2d2a35 !important;
    margin: 30px 0 14px;
}
.editor-area p {
    margin-bottom: 18px;
}
.editor-area blockquote {
    border-left: 4px solid #6366f1;
    padding: 16px 20px;
    margin: 24px 0;
    background: #f8fafc;
    border-radius: 0 12px 12px 0;
    font-style: italic;
    color: #475569;
}
.editor-area img {
    max-width: 100%;
    height: auto;
    border-radius: 16px;
    border: 2px solid transparent;
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
    margin: 20px auto;
    display: block;
    cursor: pointer;
    transition: box-shadow 0.2s, border-color 0.2s, transform 0.2s;
}
.editor-area img.img-selected {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15), 0 12px 35px rgba(99, 102, 241, 0.2) !important;
    transform: scale(1.01);
}

/* Professional Editor Guideline Columns Grid overlay */
#editor-grid-guidelines {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    pointer-events: none !important;
    z-index: 5 !important;
    display: none;
    grid-template-columns: repeat(6, 1fr);
    box-sizing: border-box !important;
    padding: 50px 60px !important; /* Matches .scribe-paper padding exactly! */
}
.grid-guideline-line {
    border-right: 1.5px dashed rgba(99, 102, 241, 0.35) !important;
    height: 100% !important;
}
.grid-guideline-line:last-child {
    border-right: none !important;
}

/* Image resize floating toolbar */
#img-toolbar {
    display: none;
    position: fixed;
    background: #ffffff;
    border: 1px solid #e2e8f0 !important;
    border-radius: 12px;
    padding: 8px 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05) !important;
    z-index: 9999;
    gap: 6px;
    flex-wrap: wrap;
    align-items: center;
}
#img-toolbar button {
    padding: 6px 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.75rem;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s;
}
#img-toolbar button:hover {
    background: #f8fafc;
    color: #6366f1;
}

/* Circular/Pill style buttons */
.font-style-btns {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.font-style-btns button {
    padding: 8px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 30px;
    font-size: 0.78rem;
    font-weight: 700;
    color: #475569;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.15s;
}
.font-style-btns button:hover {
    background: #f8fafc;
    color: #6366f1;
    transform: translateY(-1px);
}

/* File Upload Minimal */
.img-preview {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: #fbfbfe;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 14px;
}
.img-preview img {
    width: 60px;
    height: 40px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid var(--text-color);
}
.img-preview span {
    font-size: 0.75rem;
    font-weight: 700;
    color: #6a6775;
}
.file-upload-wrapper {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.file-upload-btn {
    background: rgba(99, 102, 241, 0.08) !important;
    color: #6366f1 !important;
    padding: 12px 18px;
    border: 1px solid rgba(99, 102, 241, 0.1);
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
    text-align: center;
    font-size: 0.85rem;
    transition: all 0.2s;
}
.file-upload-btn:hover {
    background: rgba(99, 102, 241, 0.12) !important;
    transform: translateY(-1px);
}
.file-upload-name {
    font-weight: 700;
    color: #8c8994;
    font-size: 0.8rem;
    text-align: center;
    word-break: break-all;
}
.control-card select {
    width: 100%;
    padding: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-weight: 600;
    background: #ffffff;
    color: #475569;
}

/* SEO inputs */
.scribe-input, .scribe-textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-family: 'Inter', sans-serif;
    font-size: 0.88rem;
    font-weight: 600;
    background: #ffffff;
    color: #475569;
    outline: none;
    box-sizing: border-box;
    margin-bottom: 12px;
    transition: all 0.2s;
}
.scribe-input:focus, .scribe-textarea:focus {
    border-color: #6366f1;
}

/* Collapsible Box style */
.collapsible-header {
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Toggle Checkbox Modern */
.pub-toggle {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: rgba(168, 85, 247, 0.05);
    border: 1px solid rgba(168, 85, 247, 0.1);
    border-radius: 12px;
    font-weight: 700;
    color: #a855f7;
    font-size: 0.85rem;
    cursor: pointer;
}
.pub-toggle input[type=checkbox] {
    width: 20px;
    height: 20px;
    accent-color: #a855f7;
    cursor: pointer;
}

/* Buttons */
.btn-save-post {
    width: 100%;
    padding: 14px;
    background: #6366f1;
    color: #ffffff;
    border: none;
    border-radius: 14px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.2);
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-save-post:hover {
    background: #4f46e5;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
}
.btn-cancel-post {
    width: 100%;
    padding: 12px;
    background: #ffffff;
    color: #475569;
    border: 1px solid #cbd5e1;
    border-radius: 14px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    font-size: 0.88rem;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 8px;
}
.btn-cancel-post:hover {
    background: #f8fafc;
    color: #0f172a;
    transform: translateY(-1px);
}

.flash-error {
    background: #fee2e2;
    color: #991b1b;
    padding: 14px;
    border: 1px solid #fca5a5;
    border-radius: 12px;
    font-weight: 700;
    margin-bottom: 18px;
}

/* Language Tabs styling */
.lang-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: -15px;
    border-bottom: 2px solid var(--text-color);
    padding-bottom: 10px;
    z-index: 10;
    position: relative;
}
.lang-tab {
    padding: 8px 16px;
    background: transparent;
    border: none;
    font-family: var(--font-main);
    font-weight: 800;
    font-size: 0.9rem;
    color: #8c8994;
    cursor: pointer;
    transition: all 0.2s;
    border-radius: 12px 12px 0 0;
}
.lang-tab.active {
    color: var(--primary-dark);
    background: var(--primary-color);
    border: 2px solid var(--text-color);
    border-bottom: none;
}
.editor-wrapper {
    display: none;
}
.editor-wrapper.active {
    display: block;
}
body::before, body::after { display: none !important; background-image: none !important; }
</style>
<noscript>
body {
    background: #f6f5f9;
    font-family: var(--font-main);
    color: var(--text-color);
    margin: 0;
    padding: 0;
    overflow-x: hidden;
}
/* Scribe Layout Wrapper */
.scribe-container {
    display: grid;
    grid-template-columns: 240px 1fr 360px;
    min-height: calc(100vh - 70px);
    background: #f6f5f9;
    position: relative;
    z-index: 10;
}
@media (max-width: 1024px) {
    .scribe-container {
        grid-template-columns: 1fr;
    }
    .scribe-sidebar {
        display: none;
    }
}

/* LEFT SIDEBAR */
.scribe-sidebar {
    background: #ffffff;
    border-right: 2px solid var(--text-color);
    padding: 30px 20px;
    display: flex;
    flex-direction: column;
    gap: 25px;
    height: calc(100vh - 70px);
    position: sticky;
    top: 70px;
}
.sidebar-section-title {
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: #8c8994;
    margin-bottom: 10px;
}
.sidebar-links {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.sidebar-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border: 2px solid transparent;
    border-radius: 12px;
    color: var(--text-color);
    text-decoration: none;
    font-weight: 700;
    font-size: 0.9rem;
    transition: all 0.2s;
}
.sidebar-link:hover {
    background: var(--primary-color);
    border-color: var(--text-color);
    box-shadow: 2px 2px 0px var(--text-color);
}
.sidebar-link.active {
    background: var(--secondary-color);
    border-color: var(--text-color);
    box-shadow: 3px 3px 0px var(--text-color);
}

/* CENTER WORKSPACE */
.scribe-workspace {
    padding: 40px 30px;
    display: flex;
    justify-content: center;
    overflow-y: auto;
    background: #fbfbfe;
}
.scribe-paper {
    background: #ffffff;
    width: 100%;
    max-width: 720px;
    min-height: 800px;
    border: 2px solid var(--text-color);
    border-radius: 20px;
    padding: 50px 60px;
    box-shadow: 6px 6px 0px var(--text-color);
    display: flex;
    flex-direction: column;
    gap: 24px;
    position: relative !important;
}
@media (max-width: 640px) {
    .scribe-paper {
        padding: 30px 20px;
    }
}

/* Custom Paper Inputs */
.scribe-title-input {
    border: none !important;
    outline: none !important;
    font-family: var(--font-blog-heading), Georgia, serif;
    font-size: 2.4rem;
    font-weight: 900;
    color: var(--text-color);
    padding: 0 !important;
    margin-bottom: 5px;
    width: 100%;
    box-shadow: none !important;
    background: transparent !important;
}
.scribe-title-input::placeholder {
    color: #cbc8d4;
}
.scribe-desc-input {
    border: none !important;
    outline: none !important;
    resize: none !important;
    font-family: var(--font-blog-body), Georgia, serif;
    font-size: 1.15rem;
    line-height: 1.6;
    color: #6a6775;
    padding: 0 !important;
    width: 100%;
    min-height: 50px !important;
    box-shadow: none !important;
    background: transparent !important;
}
.scribe-desc-input::placeholder {
    color: #dbdae5;
}
.divider-line {
    height: 2px;
    border-bottom: 2px dashed #dbdae5;
    margin-bottom: 10px;
}

/* RIGHT CONTROLS PANEL */
.scribe-controls {
    background: #ffffff;
    border-left: 2px solid var(--text-color);
    padding: 30px 24px;
    display: flex;
    flex-direction: column;
    gap: 22px;
    overflow-y: auto;
    height: calc(100vh - 70px);
    position: sticky;
    top: 70px;
}
.control-card {
    background: #ffffff;
    border: 2px solid var(--text-color);
    border-radius: 16px;
    padding: 20px;
    box-shadow: 3px 3px 0px var(--text-color);
}
.control-card h3 {
    font-size: 0.95rem;
    font-weight: 900;
    margin-bottom: 14px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.control-card h3 i {
    color: var(--primary-dark);
}

/* Text Editor Grid Layout */
.editor-toolbar-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    margin-bottom: 12px;
}
.editor-toolbar-grid button {
    height: 38px;
    background: #ffffff;
    border: 2px solid var(--text-color);
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.editor-toolbar-grid button:hover {
    background: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: 2px 2px 0px var(--text-color);
}
.editor-toolbar-grid button:active {
    transform: translateY(0);
    box-shadow: none;
}
.editor-toolbar-grid .span-2 {
    grid-column: span 2;
}

/* Editor Area inside Paper */
.editor-area {
    min-height: 500px;
    border: none !important;
    outline: none !important;
    background: transparent !important;
    font-family: var(--font-blog-body), Georgia, serif;
    font-size: 1.15rem;
    line-height: 1.85;
    color: var(--text-color);
    box-shadow: none !important;
    overflow-y: visible;
    padding: 0 !important;
}
.editor-area[data-placeholder]:not(:focus):empty:before {
    content: attr(data-placeholder);
    color: #dbdae5;
    font-style: italic;
}
.editor-area h1, .editor-area h2, .editor-area h3 {
    font-family: var(--font-blog-heading);
    font-weight: 900;
    margin: 28px 0 12px;
}
.editor-area p {
    margin-bottom: 18px;
}
.editor-area blockquote {
    border-left: 4px solid var(--primary-dark);
    padding: 12px 20px;
    margin: 20px 0;
    background: var(--primary-color);
    border-radius: 0 10px 10px 0;
    font-style: italic;
}
.editor-area img {
    max-width: 100%;
    height: auto;
    border-radius: 12px;
    border: 2px solid var(--text-color);
    box-shadow: 4px 4px 0px var(--text-color);
    margin: 15px auto;
    display: block;
    cursor: pointer;
}
.editor-area img.img-selected {
    box-shadow: 0 0 0 4px var(--primary-dark);
}

/* Image resize floating toolbar */
#img-toolbar {
    display: none;
    position: fixed;
    background: #ffffff;
    border: 2px solid var(--text-color) !important;
    border-radius: 12px;
    padding: 8px 12px;
    box-shadow: 4px 4px 0px var(--text-color) !important;
    z-index: 9999;
    gap: 6px;
    flex-wrap: wrap;
    align-items: center;
}
#img-toolbar button {
    padding: 4px 10px;
    background: #ffffff;
    border: 1.5px solid var(--text-color);
    border-radius: 8px;
    font-weight: 800;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.15s;
}
#img-toolbar button:hover {
    background: var(--primary-color);
}

/* Circular/Pill style buttons */
.font-style-btns {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.font-style-btns button {
    padding: 6px 14px;
    border: 2px solid var(--text-color);
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 800;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.15s;
}
.font-style-btns button:hover {
    background: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: 2px 2px 0px var(--text-color);
}

/* File Upload Minimal */
.file-upload-wrapper {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.file-upload-btn {
    background: var(--primary-color);
    color: var(--text-color);
    padding: 10px 15px;
    border: 2px solid var(--text-color);
    border-radius: 12px;
    font-weight: 800;
    cursor: pointer;
    text-align: center;
    font-size: 0.85rem;
    box-shadow: 2px 2px 0px var(--text-color);
    transition: all 0.15s;
}
.file-upload-btn:hover {
    transform: translateY(-2px);
    box-shadow: 4px 4px 0px var(--text-color);
}
.file-upload-name {
    font-weight: 700;
    color: #8c8994;
    font-size: 0.8rem;
    text-align: center;
    word-break: break-all;
}
.control-card select {
    width: 100%;
    padding: 10px;
    border: 2px solid var(--text-color);
    border-radius: 12px;
    font-weight: 800;
    background: #ffffff;
    color: var(--text-color);
}

/* SEO inputs */
.scribe-input, .scribe-textarea {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid var(--text-color);
    border-radius: 12px;
    font-family: var(--font-main);
    font-size: 0.88rem;
    font-weight: 700;
    background: #ffffff;
    color: var(--text-color);
    outline: none;
    box-sizing: border-box;
    margin-bottom: 12px;
}
.scribe-input:focus, .scribe-textarea:focus {
    background: #faf9ff;
}

/* Collapsible Box style */
.collapsible-header {
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Toggle Checkbox Modern */
.pub-toggle {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--secondary-color);
    border: 2px solid var(--text-color);
    border-radius: 12px;
    font-weight: 800;
    font-size: 0.85rem;
    cursor: pointer;
}
.pub-toggle input[type=checkbox] {
    width: 20px;
    height: 20px;
    accent-color: var(--text-color);
    cursor: pointer;
}

/* Buttons */
.btn-save-post {
    width: 100%;
    padding: 14px;
    background: var(--secondary-color);
    color: var(--text-color);
    border: 2px solid var(--text-color);
    border-radius: 14px;
    font-family: var(--font-main);
    font-weight: 900;
    font-size: 1rem;
    cursor: pointer;
    box-shadow: 3px 3px 0px var(--text-color);
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-save-post:hover {
    transform: translateY(-2px);
    box-shadow: 5px 5px 0px var(--text-color);
}
.btn-cancel-post {
    width: 100%;
    padding: 12px;
    background: #ffffff;
    color: var(--text-color);
    border: 2px solid var(--text-color);
    border-radius: 14px;
    font-family: var(--font-main);
    font-weight: 800;
    font-size: 0.88rem;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    box-shadow: 2px 2px 0px var(--text-color);
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 8px;
}
.btn-cancel-post:hover {
    transform: translateY(-2px);
    box-shadow: 4px 4px 0px var(--text-color);
}

.flash-error {
    background: #ffe6e6;
    color: #a70000;
    padding: 14px;
    border: 2px solid var(--text-color);
    border-radius: 12px;
    font-weight: 800;
    margin-bottom: 18px;
    box-shadow: 3px 3px 0 var(--text-color);
}
</noscript>
    <link rel='preload' href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' as='style' onload='this.onload=null;this.rel="stylesheet"'>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800;900&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
    <?php include_once "gtag.php"; ?>
</head><body>
<div class="aurora-bg"></div>
<div class="back-glow" id="back-glow"></div>
<header>
  <div class="logo-area"  style="cursor:pointer">
    <div class="logo-flipper"><div class="logo-front"><img src="toplogo/logo01.webp" alt="Logo" id="profile-logo"></div><div class="logo-back"><img loading="lazy" src="toplogo/logo02.webp" alt=""></div></div>
    <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
  </div>
  <nav class="nav-links"><a href="digital_store/index.php">SHOP</a><a href="dashboard.php">DASHBOARD</a><a href="blog_admin.php" style="background:var(--primary-color);border:2px solid var(--text-color);box-shadow:3px 3px 0 var(--text-color);border-radius:20px;"><i class="fa-solid fa-pencil"></i> BLOGS</a></nav>
  <div class="header-right">
    <div class="header-divider"></div>
    <div style="display:flex; align-items:center; gap:8px;"><a href="profile.php" title="Edit Profile"><?= renderAvatar(
        $_SESSION["profile_image"] ?? "",
        "admin-avatar",
        "Admin",
        'style="transition: transform 0.2s;" onmouseover="this.style.transform=\'scale(1.1) rotate(-5deg)\'" onmouseout="this.style.transform=\'\'"',
    ) ?></a><a href="dashboard.php" style="color:var(--text-color); font-weight:800;">ADMIN</a></div>
    <a href="login.php?logout=1" class="logout"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg> LOGOUT</a>
  </div>
</header>

<?php if ($error): ?>
  <div class="bc-wrap" style="padding: 20px 20px 0;"><div class="flash-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="scribe-container">
  <!-- LEFT SIDEBAR -->
  <aside class="scribe-sidebar">
    <div>
      <div class="sidebar-section-title">Navigation</div>
      <div class="sidebar-links">
        <a href="blog_admin.php" class="sidebar-link"><i class="fa-solid fa-folder-open"></i> All Blogs</a>
        <a href="blog_create.php" class="sidebar-link active"><i class="fa-solid fa-plus"></i> Create Post</a>
        <a href="dashboard.php" class="sidebar-link"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
        <a href="index.php" class="sidebar-link"><i class="fa-solid fa-house"></i> View Live Site</a>
      </div>
    </div>
    
    <div>
      <div class="sidebar-section-title">Need Help?</div>
      <div style="font-size: 0.8rem; font-weight: 600; color: #8c8994; line-height: 1.5; padding: 0 10px;">
        Use the formatting options in the right-hand panel to style your document. Drag and drop images directly into the paper canvas to insert them inside your article.
      </div>
    </div>
  </aside>

  <!-- CENTER WORKSPACE (THE CANVAS) -->
  <main class="scribe-workspace">
    <div class="scribe-paper">
      <!-- Title Input (Notion style, borderless) -->
      <input type="text" class="scribe-title-input" id="bc-title" name="title" placeholder="Your amazing blog title..." required autocomplete="off">
      
      <!-- Short Description/Summary (borderless) -->
      <textarea class="scribe-desc-input" id="bc-desc" name="description" placeholder="Write a short summary shown on blog list cards..." rows="1" oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
      
      <!-- Divider Line -->
      <div class="divider-line"></div>

      <!-- Rich Editor Area (Powered by TinyMCE) -->
      <div class="lang-tabs">
          <button type="button" class="lang-tab active" data-target="wrapper-en">English</button>
          <button type="button" class="lang-tab" data-target="wrapper-hi">Hindi / Hinglish</button>
        </div>
        <div class="editor-wrapper active" id="wrapper-en">
          <textarea id="blog-editor" name="content" placeholder="Start writing your English blog story here..." style="width:100%; min-height:600px; border:none; outline:none; font-size:1.15rem;"></textarea>
        </div>
        <div class="editor-wrapper" id="wrapper-hi">
          <textarea id="blog-editor-hi" name="content_hindi" placeholder="Start writing your Hindi/Hinglish blog story here..." style="width:100%; min-height:600px; border:none; outline:none; font-size:1.15rem;"></textarea>
        </div>
    </div>
  </main>

  <!-- RIGHT CONTROLS PANEL -->
  <aside class="scribe-controls">
    <!-- Publish Action Card -->
    <div class="control-card" style="border-color: var(--text-color); background: #faf9ff;">
      <h3><i class="fa-solid fa-rocket"></i> Actions</h3>
      <div class="fg" style="margin-bottom: 12px;">
        <label class="pub-toggle" for="bc-pub">
          <input type="checkbox" id="bc-pub" name="publish" value="1">
          Publish immediately
        </label>
      </div>
      <button type="submit" class="btn-save-post">Save Post <i class="fa-solid fa-check"></i></button>
      <a href="blog_admin.php" class="btn-cancel-post"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
    </div>

    <!-- Text Formatting Toolbar -->
    <div class="control-card">
      <h3><i class="fa-solid fa-paragraph"></i> Text Formatting</h3>
      <div class="editor-toolbar-grid">
        <button type="button" onclick="fmt('bold')" title="Bold"><b>B</b></button>
        <button type="button" onclick="fmt('italic')" title="Italic"><i>I</i></button>
        <button type="button" onclick="fmt('underline')" title="Underline"><u>U</u></button>
        <button type="button" onclick="fmtBlock('blockquote')" title="Quote"><i class="fa-solid fa-quote-left"></i></button>
        
        <button type="button" onclick="fmtBlock('h1')">H1</button>
        <button type="button" onclick="fmtBlock('h2')">H2</button>
        <button type="button" onclick="fmtBlock('h3')">H3</button>
        <button type="button" onclick="insertCodeBlock()" title="Insert Code Block" style="color:#6366f1; background:#eeefff !important;"><i class="fa-solid fa-code"></i> Code</button>
        
        <button type="button" onclick="fmt('insertUnorderedList')" title="Bullet List"><i class="fa-solid fa-list-ul"></i></button>
        <button type="button" onclick="fmt('insertOrderedList')" title="Numbered List"><i class="fa-solid fa-list-ol"></i></button>
        <button type="button" class="span-2" onclick="document.getElementById('editor-img-upload').click()" title="Insert Image"><i class="fa-solid fa-image"></i> Image</button>
        <input type="file" id="editor-img-upload" style="display:none" accept="image/*" onchange="if(this.files[0]) uploadEditorImage(this.files[0])">
      </div>
    </div>

    <!-- SEO Content Audit Card -->
    <div class="control-card">
      <h3><i class="fa-solid fa-square-check"></i> SEO Content Audit</h3>
      <div style="margin-bottom: 12px;">
        <label style="font-size:0.75rem; font-weight:800; color:#8c8994; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:5px;">Focus Keyword</label>
        <input type="text" id="seo-focus-keyword" name="focus_keyword" placeholder="e.g. prompt, ai, design..." style="width:100%; padding:10px 12px; border:2px solid var(--text-color); border-radius:10px; font-family:'Plus Jakarta Sans',sans-serif; font-size:0.85rem; font-weight:700;" value="" autocomplete="off">
      </div>
      
      <div style="display:flex; flex-direction:column; gap:10px; margin-top:15px; font-size:0.85rem; font-weight:700; color:#475569;">
        <div id="seo-check-title" style="display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-circle-xmark" style="color:#ef4444;"></i> Keyword in Title
        </div>
        <div id="seo-check-intro" style="display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-circle-xmark" style="color:#ef4444;"></i> Keyword in First 100 Words
        </div>
        <div id="seo-check-headings" style="display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-circle-xmark" style="color:#ef4444;"></i> Keyword in H2/H3 headings
        </div>
        <div id="seo-check-wordcount" style="display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-circle-xmark" style="color:#ef4444;"></i> 0 words (Aim for 800+)
        </div>
        <div id="seo-check-readingtime" style="display:flex; align-items:center; gap:8px; color:#6366f1;">
          <i class="fa-solid fa-clock"></i> 0 min read
        </div>
      </div>
    </div>

    <!-- Beautiful Font Style Options Guide Card -->
    <div class="control-card">
      <h3><i class="fa-solid fa-wand-magic-sparkles"></i> Premium Typography Sets</h3>
      <p style="font-size:0.75rem; color:#8c8994; margin-top:-6px; margin-bottom:12px; font-weight:600; line-height:1.4;">Highlight text inside the paper, then click any button to apply the font style instantly:</p>
      
      <div style="display:flex; flex-direction:column; gap:14px; font-size:0.8rem; line-height:1.4;">
        <!-- SET A -->
        <div style="padding:12px; background:#fbfcfe; border-radius:12px; border:1px solid #cbd5e1; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
          <strong style="color:#6366f1; display:flex; align-items:center; gap:6px; margin-bottom:8px; font-family:'Plus Jakarta Sans', sans-serif; font-size:0.85rem; font-weight:800;">
            <i class="fa-solid fa-feather-pointed"></i> SET A: EDITORIAL SERIF
          </strong>
          
          <div style="display:flex; flex-direction:column; gap:8px;">
            <div>
              <span style="font-size:0.65rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:3px;">Heading Options (H1/H2)</span>
              <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
                <button type="button" onclick="applyFontFamily('Playfair Display, Georgia, serif')" style="font-family:'Playfair Display', serif; font-weight:800; padding:5px; font-size:0.7rem; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; cursor:pointer;">Playfair Serif</button>
                <button type="button" onclick="applyFontFamily('Georgia, serif')" style="font-family:Georgia, serif; font-weight:bold; padding:5px; font-size:0.7rem; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; cursor:pointer;">Classic Georgia</button>
              </div>
            </div>
            
            <div>
              <span style="font-size:0.65rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:3px;">Body Options</span>
              <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
                <button type="button" onclick="applyFontFamily('Lora, Georgia, serif')" style="font-family:'Lora', serif; padding:5px; font-size:0.7rem; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; cursor:pointer;">Elegant Lora</button>
                <button type="button" onclick="applyFontFamily('Times New Roman, serif')" style="font-family:'Times New Roman', serif; padding:5px; font-size:0.7rem; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; cursor:pointer;">Times Book</button>
              </div>
            </div>

            <div>
              <span style="font-size:0.65rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:3px;">Points & Bullet Options</span>
              <button type="button" onclick="applyFontFamily('Lora, Georgia, serif')" style="font-family:'Lora', serif; width:100%; padding:5px; font-size:0.7rem; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; cursor:pointer;">Lora Points ▢</button>
            </div>
          </div>
        </div>
        
        <!-- SET B -->
        <div style="padding:12px; background:#fbfcfe; border-radius:12px; border:1px solid #cbd5e1; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
          <strong style="color:#0ea5e9; display:flex; align-items:center; gap:6px; margin-bottom:8px; font-family:'Plus Jakarta Sans', sans-serif; font-size:0.85rem; font-weight:800;">
            <i class="fa-solid fa-desktop"></i> SET B: MODERN SANS-SERIF
          </strong>
          
          <div style="display:flex; flex-direction:column; gap:8px;">
            <div>
              <span style="font-size:0.65rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:3px;">Heading Options (H1/H2)</span>
              <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
                <button type="button" onclick="applyFontFamily('Plus Jakarta Sans, sans-serif')" style="font-family:'Plus Jakarta Sans', sans-serif; font-weight:800; padding:5px; font-size:0.7rem; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; cursor:pointer;">Jakarta Bold</button>
                <button type="button" onclick="applyFontFamily('Outfit, sans-serif')" style="font-family:'Outfit', sans-serif; font-weight:800; padding:5px; font-size:0.7rem; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; cursor:pointer;">Outfit Bold</button>
              </div>
            </div>
            
            <div>
              <span style="font-size:0.65rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:3px;">Body Options</span>
              <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
                <button type="button" onclick="applyFontFamily('Inter, sans-serif')" style="font-family:'Inter', sans-serif; padding:5px; font-size:0.7rem; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; cursor:pointer;">Inter Sans</button>
                <button type="button" onclick="applyFontFamily('Arial, sans-serif')" style="font-family:Arial, sans-serif; padding:5px; font-size:0.7rem; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; cursor:pointer;">Clean Arial</button>
              </div>
            </div>

            <div>
              <span style="font-size:0.65rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:3px;">Points & Bullet Options</span>
              <button type="button" onclick="applyFontFamily('Inter, sans-serif')" style="font-family:'Inter', sans-serif; width:100%; padding:5px; font-size:0.7rem; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; cursor:pointer;">Inter Points ▢</button>
            </div>
          </div>
        </div>

        <!-- SET C -->
        <div style="padding:12px; background:#fbfcfe; border-radius:12px; border:1px solid #cbd5e1; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
          <strong style="color:#10b981; display:flex; align-items:center; gap:6px; margin-bottom:8px; font-family:'Plus Jakarta Sans', sans-serif; font-size:0.85rem; font-weight:800;">
            <i class="fa-solid fa-code"></i> SET C: TECH & MONOSPACE
          </strong>
          
          <div style="display:flex; flex-direction:column; gap:8px;">
            <div>
              <span style="font-size:0.65rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:3px;">Heading Options (H1/H2)</span>
              <button type="button" onclick="applyFontFamily('Plus Jakarta Sans, sans-serif')" style="font-family:'Plus Jakarta Sans', sans-serif; font-weight:800; width:100%; padding:5px; font-size:0.7rem; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; cursor:pointer;">Modern Tech Heading</button>
            </div>
            
            <div>
              <span style="font-size:0.65rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:3px;">Body & Bullet Options</span>
              <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
                <button type="button" onclick="applyFontFamily('Courier New, monospace')" style="font-family:'Courier New', monospace; padding:5px; font-size:0.7rem; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; cursor:pointer;">Courier Code</button>
                <button type="button" onclick="applyFontFamily('monospace')" style="font-family:monospace; padding:5px; font-size:0.7rem; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; cursor:pointer;">Fira Mono</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Cover Image Card -->
    <div class="control-card">
      <h3><i class="fa-solid fa-image"></i> Cover Image</h3>
      <div class="file-upload-wrapper">
        <label for="bc-img" class="file-upload-btn"><i class="fa-solid fa-cloud-arrow-up"></i> Choose Image</label>
        <span class="file-upload-name" id="bc-fname">No file chosen</span>
        <input type="file" id="bc-img" name="image" accept="image/*" style="display:none" onchange="document.getElementById('bc-fname').textContent=this.files[0]?.name||'No file chosen'">
      </div>
      <div style="margin-top: 14px;">
        <label style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #8c8994; display: block; margin-bottom: 6px;">Aspect Ratio</label>
        <select name="image_ratio">
          <option value="16:9">Landscape (16:9)</option>
          <option value="9:16">Portrait (9:16)</option>
        </select>
      </div>
    </div>

    <!-- SEO Options Card (Collapsible) -->
    <div class="control-card">
      <div class="collapsible-header" onclick="const s = document.getElementById('seo-fields'); const i = this.querySelector('.chevron-icon'); s.style.display = s.style.display==='none'?'block':'none'; i.style.transform = s.style.display==='none'?'rotate(0deg)':'rotate(180deg)';">
        <h3 style="margin-bottom: 0;"><i class="fa-solid fa-magnifying-glass"></i> SEO Settings</h3>
        <i class="fa-solid fa-chevron-down chevron-icon" style="transition: transform 0.2s; font-size: 0.8rem; color: #8c8994;"></i>
      </div>
      <div id="seo-fields" style="display: none; margin-top: 15px; border-top: 1px dashed #e1e0eb; padding-top: 15px;">
        <div class="fg">
          <label style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: #8c8994; display: block; margin-bottom: 5px;">Custom URL Slug</label>
          <input type="text" class="scribe-input" id="bc-slug" name="slug" placeholder="e.g. creative-couple-prompts-guide">
          <span style="font-size:0.65rem; color:#8c8994; font-weight:600; display:block; margin-top:3px; margin-bottom:10px; line-height:1.3;">Edit the page URL. Lowercase letters, numbers, and hyphens only. Leave blank to auto-generate from title.</span>
        </div>
        <div class="fg">
          <label style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: #8c8994; display: block; margin-bottom: 5px;">Meta Title</label>
          <input type="text" class="scribe-input" id="bc-mt" name="meta_title" placeholder="Leave blank to use post title">
        </div>
        <div class="fg">
          <label style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: #8c8994; display: block; margin-bottom: 5px;">Meta Description</label>
          <textarea class="scribe-textarea" id="bc-md" name="meta_description" rows="3" placeholder="Short SEO description (150 chars recommended)"></textarea>
        </div>
        <div class="fg">
          <label style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: #8c8994; display: block; margin-bottom: 5px;">Tags / Keywords</label>
          <input type="text" class="scribe-input" id="bc-tags" name="tags" placeholder="AI, couple, prompts, creative (comma separated)">
        </div>
      </div>
    </div>
  </aside>
</form>

<script>
// ─── TinyMCE 6 Borderless Editor Integration ───────────────────────────────────
tinymce.init({
    selector: '#blog-editor',
    height: 650,
    menubar: false,
    statusbar: true,
    branding: false,
    promotion: false,
    toolbar: false,
    plugins: 'image link lists code codesample charmap emoticons wordcount fullscreen autosave visualblocks quickbars',
    quickbars_selection_toolbar: 'bold italic underline | alignleft aligncenter alignright | fontfamily blocks | numlist bullist',
    quickbars_insert_toolbar: 'image link codesample',
    font_family_formats: 'Elegant Lora=Lora, serif; Editorial Serif=Playfair Display, serif; Modern Bold=Plus Jakarta Sans, sans-serif; Inter Sans=Inter, sans-serif; Fira Mono=monospace',
    quickbars_image_toolbar: 'alignleft aligncenter alignright | rotateleft rotateright | flipv fliph | editimage imageoptions',
    image_advtab: true,
    image_caption: true,
    image_dimensions: true,
    paste_data_images: true, // Allow pasting images directly from clipboard!
    
    // Hide native borders so TinyMCE matches our Scribe Paper style 100% borderless!
    setup: function (editor) {
        editor.on('init', function () {
            editor.getContainer().style.border = 'none';
            editor.getContainer().style.boxShadow = 'none';
            editor.getContainer().style.background = 'transparent';
            runRealtimeSEOAudit();
        });
        editor.on('NodeChange keyup change', function () {
            runRealtimeSEOAudit();
        });
    },
    
    // Custom editor styles inside the iframe matching our exact blog body styles
    content_style: `
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800&family=Inter:wght@400;600;700&family=Lora:ital,wght@0,400;0,600;0,700;1,400&family=Playfair+Display:ital,wght@0,700;0,800;1,700&display=swap');
        body {
            font-family: 'Lora', Georgia, serif;
            font-size: 1.15rem;
            line-height: 1.85;
            color: #2d2a35;
            padding: 10px;
            background: #ffffff;
        }
        h1, h2, h3 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: #1e1b24;
            margin-top: 1.4em;
            margin-bottom: 0.6em;
            line-height: 1.35;
        }
        p {
            margin-bottom: 1.25em;
        }
        pre {
            background: #0f172a;
            border-radius: 12px;
            padding: 16px 20px;
            color: #cbd5e1;
            font-family: monospace;
            overflow-x: auto;
        }
        img {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
        }
    `,
    
    // Secure Drag-and-Drop / Paste Image Uploader
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

tinymce.init({
    selector: '#blog-editor-hi',
    height: 650,
    menubar: false,
    statusbar: true,
    branding: false,
    promotion: false,
    toolbar: false,
    plugins: 'image link lists code codesample charmap emoticons wordcount fullscreen autosave visualblocks quickbars',
    quickbars_selection_toolbar: 'bold italic underline | alignleft aligncenter alignright | fontfamily blocks | numlist bullist',
    quickbars_insert_toolbar: 'image link codesample',
    font_family_formats: 'Elegant Lora=Lora, serif; Editorial Serif=Playfair Display, serif; Modern Bold=Plus Jakarta Sans, sans-serif; Inter Sans=Inter, sans-serif; Fira Mono=monospace',
    quickbars_image_toolbar: 'alignleft aligncenter alignright | rotateleft rotateright | flipv fliph | editimage imageoptions',
    image_advtab: true,
    image_caption: true,
    image_dimensions: true,
    paste_data_images: true, // Allow pasting images directly from clipboard!
    
    // Hide native borders so TinyMCE matches our Scribe Paper style 100% borderless!
    setup: function (editor) {
        editor.on('init', function () {
            editor.getContainer().style.border = 'none';
            editor.getContainer().style.boxShadow = 'none';
            editor.getContainer().style.background = 'transparent';
        });
        editor.on('change', function() {
            tinymce.triggerSave();
        });
    },
    // Secure Drag-and-Drop / Paste Image Uploader
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

// ─── Real-time Yoast/RankMath-Style SEO Auditor ──────────────────────────────────
function runRealtimeSEOAudit() {
    const keywordInput = document.getElementById('seo-focus-keyword');
    const titleInput = document.getElementById('bc-title');
    if (!keywordInput || !titleInput) return;
    
    const keyword = keywordInput.value.toLowerCase().trim();
    const titleVal = titleInput.value.toLowerCase().trim();
    
    const editorInstance = tinymce.get('blog-editor');
    if (!editorInstance) return;
    
    const text = editorInstance.getContent({ format: 'text' }).trim();
    const html = editorInstance.getContent();
    
    // 1. Word Count Check
    const words = text ? text.split(/\s+/).filter(w => w.length > 0).length : 0;
    const wCheck = document.getElementById('seo-check-wordcount');
    if (words >= 800) {
        wCheck.innerHTML = `<i class="fa-solid fa-circle-check" style="color:#10b981;"></i> ${words} words (800+ limit reached!)`;
        wCheck.style.color = '#10b981';
    } else if (words >= 400) {
        wCheck.innerHTML = `<i class="fa-solid fa-circle-exclamation" style="color:#f59e0b;"></i> ${words} words (Aim for 800+)`;
        wCheck.style.color = '#f59e0b';
    } else {
        wCheck.innerHTML = `<i class="fa-solid fa-circle-xmark" style="color:#ef4444;"></i> ${words} words (Aim for 800+)`;
        wCheck.style.color = '#ef4444';
    }
    
    // 2. Reading Time Estimation (Avg 200 words per minute)
    const readTime = Math.ceil(words / 200);
    document.getElementById('seo-check-readingtime').innerHTML = `<i class="fa-solid fa-clock" style="color:#6366f1;"></i> ${readTime} min read`;
    
    // 3. Focus Keyword in H1/Title Check
    const tCheck = document.getElementById('seo-check-title');
    if (keyword && titleVal.includes(keyword)) {
        tCheck.innerHTML = `<i class="fa-solid fa-circle-check" style="color:#10b981;"></i> Focus keyword in Title!`;
        tCheck.style.color = '#10b981';
    } else {
        tCheck.innerHTML = `<i class="fa-solid fa-circle-xmark" style="color:#ef4444;"></i> Keyword not in Title`;
        tCheck.style.color = '#64748b';
    }
    
    // 4. Focus Keyword in First 100 Words Check
    const introText = text.slice(0, 500).toLowerCase(); // Check first 500 characters of the content
    const iCheck = document.getElementById('seo-check-intro');
    if (keyword && introText.includes(keyword)) {
        iCheck.innerHTML = `<i class="fa-solid fa-circle-check" style="color:#10b981;"></i> Keyword in First 100 Words!`;
        iCheck.style.color = '#10b981';
    } else {
        iCheck.innerHTML = `<i class="fa-solid fa-circle-xmark" style="color:#ef4444;"></i> Keyword not in First 100 Words`;
        iCheck.style.color = '#64748b';
    }
    
    // 5. Focus Keyword in H2/H3 headings Check
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    const headings = Array.from(doc.querySelectorAll('h2, h3')).map(h => h.textContent.toLowerCase());
    const hCheck = document.getElementById('seo-check-headings');
    let headingHasKeyword = false;
    for (let hText of headings) {
        if (keyword && hText.includes(keyword)) {
            headingHasKeyword = true;
            break;
        }
    }
    if (keyword && headingHasKeyword) {
        hCheck.innerHTML = `<i class="fa-solid fa-circle-check" style="color:#10b981;"></i> Keyword in subheadings!`;
        hCheck.style.color = '#10b981';
    } else {
        hCheck.innerHTML = `<i class="fa-solid fa-circle-xmark" style="color:#ef4444;"></i> Keyword not in H2/H3 tags`;
        hCheck.style.color = '#64748b';
    }
}

// Bind live audits to Title and Keyword changes
document.getElementById('bc-title').addEventListener('input', runRealtimeSEOAudit);
document.getElementById('seo-focus-keyword').addEventListener('input', runRealtimeSEOAudit);

// Live Code Copy Engine helper for code snippets
function copyCodeText(btn) {
  const block = btn.closest('.blog-code-block');
  if (!block) return;
  let pre = block.querySelector('.code-content');
  if (!pre) pre = block.querySelector('pre');
  if (!pre) return;
  const code = pre.innerText;

  function showCopied(button) {
    const originalText = button.innerHTML;
    button.innerHTML = `<i class="fa-solid fa-check" style="color:#10b981;"></i> Copied!`;
    button.style.background = '#065f46';
    button.style.color = '#ffffff';
    setTimeout(() => {
      button.innerHTML = originalText;
      button.style.background = '#1e293b';
      button.style.color = '#cbd5e1';
    }, 2000);
  }

  function fallbackCopy(text, button) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand('copy');
      showCopied(button);
    } catch(e) {
      alert('Could not copy code.');
    }
    document.body.removeChild(ta);
  }

  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(code).then(() => showCopied(btn)).catch(() => fallbackCopy(code, btn));
  } else {
    fallbackCopy(code, btn);
  }
}

// Sidebar Formatting Helpers to Bridge with TinyMCE
function applyFontFamily(family) {
    if (tinymce.activeEditor) {
        tinymce.activeEditor.focus();
        tinymce.activeEditor.execCommand('FontName', false, family);
    }
}
function fmt(cmd) {
    if (tinymce.activeEditor) {
        tinymce.activeEditor.execCommand(cmd);
    }
}
function fmtBlock(tag) {
    if (tinymce.activeEditor) {
        tinymce.activeEditor.execCommand('FormatBlock', false, tag);
    }
}
function insertCodeBlock() {
    if (tinymce.activeEditor) {
        const codeBlockHtml = `
          <div class="blog-code-block" contenteditable="false" style="position:relative; margin:24px 0; background:#0f172a; border-radius:12px; padding:16px 20px; font-family:monospace; color:#cbd5e1; box-sizing:border-box; text-align:left;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #1e293b; padding-bottom:8px; font-size:0.75rem; text-transform:uppercase; color:#94a3b8; font-weight:800; letter-spacing:1.5px; user-select:none;">
              <span>Code Snippet</span>
              <button type="button" onclick="copyCodeText(this)" style="background:#1e293b; border:none; border-radius:6px; color:#cbd5e1; padding:4px 10px; cursor:pointer; font-weight:bold; font-size:0.75rem; transition:all 0.15s; display:inline-flex; align-items:center; gap:4px; flex-shrink:0; white-space:nowrap;">
                <i class="fa-solid fa-copy"></i> Copy
              </button>
            </div>
            <pre class="code-content" contenteditable="true" style="margin:0; outline:none; border:none; background:transparent; color:#cbd5e1; font-family:'Courier New',Courier,monospace; font-size:0.9rem; line-height:1.5; white-space:pre-wrap; overflow-x:auto; text-align:left;">// Paste your code here...</pre>
          </div>
          <p><br></p>
        `;
        tinymce.activeEditor.execCommand('mceInsertContent', false, codeBlockHtml);
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
            tinymce.activeEditor.execCommand('mceInsertContent', false, `<img src="${data.url}" style="max-width:75%; border-radius:16px; display:block; margin:20px auto; height:auto;" alt="">`);
        } else {
            alert('Upload failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => alert('Upload error'));
}



// ─── Enhanced Image Handling for TinyMCE ────────────────────────────────────
let scribeImgToolbar = null;
let scribeSelectedImg = null;
let scribeGuideLines = null;

function scribeInitImgToolbar() {
    if (scribeImgToolbar) return;
    scribeImgToolbar = document.createElement('div');
    scribeImgToolbar.id = 'scribe-img-toolbar';
    scribeImgToolbar.style.cssText = 'display:none;position:fixed;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:8px 12px;box-shadow:0 10px 25px rgba(0,0,0,0.08);z-index:9999;gap:8px;flex-wrap:wrap;align-items:center;';
    scribeImgToolbar.innerHTML = `
      <span style="font-family:&quot;Plus Jakarta Sans&quot;,sans-serif;font-size:0.75rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;">Size:</span>
      <button type="button" onclick="scribeResizeImg(25)" style="background:#f1f5f9;border:none;padding:6px 10px;border-radius:6px;cursor:pointer;font-family:&quot;Plus Jakarta Sans&quot;,sans-serif;font-size:0.75rem;font-weight:700;">25%</button>
      <button type="button" onclick="scribeResizeImg(50)" style="background:#f1f5f9;border:none;padding:6px 10px;border-radius:6px;cursor:pointer;font-family:&quot;Plus Jakarta Sans&quot;,sans-serif;font-size:0.75rem;font-weight:700;">50%</button>
      <button type="button" onclick="scribeResizeImg(75)" style="background:#f1f5f9;border:none;padding:6px 10px;border-radius:6px;cursor:pointer;font-family:&quot;Plus Jakarta Sans&quot;,sans-serif;font-size:0.75rem;font-weight:700;">75%</button>
      <button type="button" onclick="scribeResizeImg(100)" style="background:#f1f5f9;border:none;padding:6px 10px;border-radius:6px;cursor:pointer;font-family:&quot;Plus Jakarta Sans&quot;,sans-serif;font-size:0.75rem;font-weight:700;">100%</button>
      <div style="width:1px;height:18px;background:#e2e8f0;margin:0 4px;"></div>
      <span style="font-family:&quot;Plus Jakarta Sans&quot;,sans-serif;font-size:0.75rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;">Align:</span>
      <button type="button" onclick="scribeAlignImg('left')" title="Float Left" style="background:#f1f5f9;border:none;padding:6px 10px;border-radius:6px;cursor:pointer;font-family:&quot;Plus Jakarta Sans&quot;,sans-serif;font-size:0.75rem;font-weight:700;"><i class="fa-solid fa-align-left"></i></button>
      <button type="button" onclick="scribeAlignImg('center')" title="Center" style="background:#f1f5f9;border:none;padding:6px 10px;border-radius:6px;cursor:pointer;font-family:&quot;Plus Jakarta Sans&quot;,sans-serif;font-size:0.75rem;font-weight:700;"><i class="fa-solid fa-align-center"></i></button>
      <button type="button" onclick="scribeAlignImg('right')" title="Float Right" style="background:#f1f5f9;border:none;padding:6px 10px;border-radius:6px;cursor:pointer;font-family:&quot;Plus Jakarta Sans&quot;,sans-serif;font-size:0.75rem;font-weight:700;"><i class="fa-solid fa-align-right"></i></button>
      <div style="width:1px;height:18px;background:#e2e8f0;margin:0 4px;"></div>
      <button type="button" onclick="scribeRemoveImg()" title="Remove" style="color:#ef4444;background:#fee2e2;border:none;padding:6px 10px;border-radius:6px;cursor:pointer;font-weight:700;"><i class="fa-solid fa-trash-can"></i></button>
    `;
    document.body.appendChild(scribeImgToolbar);
}

function scribeGetSelectedImg() {
    const ed = tinymce.activeEditor;
    if (!ed) return null;
    const node = ed.selection.getNode();
    if (node && node.nodeName === 'IMG') { scribeSelectedImg = node; return node; }
    scribeSelectedImg = null;
    return null;
}

function scribeShowImgToolbar() {
    const img = scribeGetSelectedImg();
    if (!img) { scribeHideImgToolbar(); return; }
    if (!scribeImgToolbar) scribeInitImgToolbar();
    const ed = tinymce.activeEditor;
    const iframe = ed.getContentAreaContainer().querySelector('iframe');
    if (!iframe) return;
    const imgRect = img.getBoundingClientRect();
    const iframeRect = iframe.getBoundingClientRect();
    const top = iframeRect.top + imgRect.top - 50;
    const left = iframeRect.left + imgRect.left;
    scribeImgToolbar.style.display = 'flex';
    scribeImgToolbar.style.top = Math.max(5, top) + 'px';
    scribeImgToolbar.style.left = left + 'px';
}

function scribeHideImgToolbar() {
    if (scribeImgToolbar) scribeImgToolbar.style.display = 'none';
    scribeSelectedImg = null;
}

function scribeResizeImg(pct) {
    if (!scribeSelectedImg) scribeSelectedImg = scribeGetSelectedImg();
    if (!scribeSelectedImg) return;
    const ed = tinymce.activeEditor;
    const body = ed.getBody();
    const maxWidth = body.clientWidth || body.scrollWidth;
    const newWidth = Math.round(maxWidth * (pct / 100));
    scribeSelectedImg.style.width = newWidth + 'px';
    scribeSelectedImg.style.height = 'auto';
    scribeSelectedImg.removeAttribute('width');
    scribeSelectedImg.removeAttribute('height');
    ed.fire('change');
    setTimeout(scribeShowImgToolbar, 50);
}

function scribeAlignImg(align) {
    if (!scribeSelectedImg) scribeSelectedImg = scribeGetSelectedImg();
    if (!scribeSelectedImg) return;
    const ed = tinymce.activeEditor;
    const doc = ed.getDoc();
    
    scribeSelectedImg.style.display = 'block';
    scribeSelectedImg.style.margin = '0';
    scribeSelectedImg.style.position = 'static';
    scribeSelectedImg.style.left = '';
    scribeSelectedImg.style.top = '';
    
    if (align === 'left') {
        scribeSelectedImg.style.float = 'left';
        scribeSelectedImg.style.marginRight = '16px';
        scribeSelectedImg.style.marginBottom = '8px';
    } else if (align === 'right') {
        scribeSelectedImg.style.float = 'right';
        scribeSelectedImg.style.marginLeft = '16px';
        scribeSelectedImg.style.marginBottom = '8px';
    } else {
        scribeSelectedImg.style.float = 'none';
        scribeSelectedImg.style.margin = '16px auto';
        scribeSelectedImg.style.display = 'block';
    }
    
    // Insert clean paragraph below floated image so user can type there
    if (align === 'left' || align === 'right') {
        let p = scribeSelectedImg.parentNode;
        while (p && p.tagName !== 'P' && p.tagName !== 'DIV' && p.tagName !== 'BODY') {
            p = p.parentNode;
        }
        if (p && p.nextElementSibling) {
            const next = p.nextElementSibling;
            const html = next.innerHTML.trim().toLowerCase();
            const isEmpty = next.tagName === 'P' && (html === '' || html === '<br>' || html === '<br/>' || html === '<br />');
            if (!isEmpty) {
                const clr = doc.createElement('div');
                clr.style.clear = 'both';
                clr.style.height = '1px';
                p.parentNode.insertBefore(clr, next);
                const newP = doc.createElement('p');
                newP.innerHTML = '<br>';
                p.parentNode.insertBefore(newP, next);
            }
        } else if (p) {
            const clr = doc.createElement('div');
            clr.style.clear = 'both';
            clr.style.height = '1px';
            p.parentNode.appendChild(clr);
            const newP = doc.createElement('p');
            newP.innerHTML = '<br>';
            p.parentNode.appendChild(newP);
        }
    }
    
    ed.fire('change');
    setTimeout(scribeShowImgToolbar, 50);
}

function scribeRemoveImg() {
    if (!scribeSelectedImg) scribeSelectedImg = scribeGetSelectedImg();
    if (!scribeSelectedImg) return;
    scribeSelectedImg.remove();
    scribeHideImgToolbar();
    tinymce.activeEditor.fire('change');
}

function scribeInitGuideLines() {
    const ed = tinymce.activeEditor;
    if (!ed) return;
    const body = ed.getBody();
    if (!body) return;
    const existing = body.querySelector('.scribe-guide-lines');
    if (existing) existing.remove();
    scribeGuideLines = ed.dom.create('div', {
        class: 'scribe-guide-lines',
        style: 'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9997;display:none;'
    },
    '<div style="position:absolute;top:33%;left:0;width:100%;height:1px;background:rgba(99,102,241,0.25);"></div>' +
    '<div style="position:absolute;top:66%;left:0;width:100%;height:1px;background:rgba(99,102,241,0.25);"></div>' +
    '<div style="position:absolute;left:33%;top:0;height:100%;width:1px;background:rgba(99,102,241,0.25);"></div>' +
    '<div style="position:absolute;left:66%;top:0;height:100%;width:1px;background:rgba(99,102,241,0.25);"></div>' +
    '<div style="position:absolute;left:50%;top:0;height:100%;width:1px;background:rgba(99,102,241,0.4);"></div>' +
    '<div style="position:absolute;top:50%;left:0;width:100%;height:1px;background:rgba(99,102,241,0.4);"></div>');
    body.appendChild(scribeGuideLines);
}

if (typeof tinymce !== 'undefined') {
    tinymce.on('AddEditor', function(e) {
        if (e.editor.id === 'blog-editor') {
            e.editor.on('init', function() {
                scribeInitGuideLines();
            });
            e.editor.on('NodeChange SelectionChange', function() {
                const img = scribeGetSelectedImg();
                if (img) { scribeShowImgToolbar(); } else { scribeHideImgToolbar(); }
            });
            e.editor.on('click', function(edEvt) {
                if (edEvt.target.nodeName === 'IMG') {
                    scribeShowImgToolbar();
                } else {
                    setTimeout(function() {
                        if (!scribeGetSelectedImg()) scribeHideImgToolbar();
                    }, 100);
                }
            });
        }
    });
}
// ─── End Enhanced Image Handling ─────────────────────────────────────────────
// ─── Language Tab Switcher ─────────────────────────────────────────────────────
document.querySelectorAll('.lang-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        // Update active tab
        document.querySelectorAll('.lang-tab').forEach(function(t) { t.classList.remove('active'); });
        this.classList.add('active');

        // Show matching editor wrapper
        var target = this.getAttribute('data-target');
        document.querySelectorAll('.editor-wrapper').forEach(function(w) { w.classList.remove('active'); });
        var targetEl = document.getElementById(target);
        if (targetEl) targetEl.classList.add('active');
    });
});
// ─── End Language Tab Switcher ─────────────────────────────────────────────────

// Interactive Ambient Mouse Glow Tracker
document.addEventListener('mousemove', (e) => {
    const glow = document.getElementById('back-glow');
    if (glow) {
        glow.style.setProperty('--x', e.clientX + 'px');
        glow.style.setProperty('--y', e.clientY + 'px');
    }
});
</script>
</body></html>
