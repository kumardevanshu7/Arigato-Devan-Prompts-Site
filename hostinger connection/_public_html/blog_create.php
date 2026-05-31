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
            "INSERT INTO blogs (title,slug,description,content,image_path,image_ratio,meta_title,meta_description,tags,is_published,author_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
        )->execute([
            $title,
            $slug,
            $description,
            $content,
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
<title>Create Blog &ndash; Admin | Arigato Devan Prompts</title><link rel="stylesheet" href="style.css?v=2026052201">
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
    <?php include_once "gtag.php"; ?>
</head><body>
<div class="aurora-bg"></div>
<div class="back-glow" id="back-glow"></div>
<header>
  <div class="logo-area"  style="cursor:pointer">
    <div class="logo-flipper"><div class="logo-front"><img src="toplogo/logo01.webp" alt="Logo" id="profile-logo"></div><div class="logo-back"><img loading="lazy" src="toplogo/logo02.webp" alt=""></div></div>
    <div class="logo-text">ARIGATO<br>DEVAN PROMPTS</div>
  </div>
  <nav class="nav-links"><a href="index.php">HOME</a><a href="dashboard.php">DASHBOARD</a><a href="blog_admin.php" style="background:var(--primary-color);border:2px solid var(--text-color);box-shadow:3px 3px 0 var(--text-color);border-radius:20px;"><i class="fa-solid fa-pencil"></i> BLOGS</a></nav>
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

      <!-- Rich Editor Area -->
      <div class="editor-area" id="blog-editor" contenteditable="true" spellcheck="true" data-placeholder="Start writing your blog story here..."></div>
      <input type="hidden" name="content" id="blog-content-input">
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
      
      <!-- Alignment Row -->
      <div class="editor-toolbar-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 0;">
        <button type="button" onclick="fmt('justifyLeft')" title="Align Left"><i class="fa-solid fa-align-left"></i></button>
        <button type="button" onclick="fmt('justifyCenter')" title="Align Center"><i class="fa-solid fa-align-center"></i></button>
        <button type="button" onclick="fmt('justifyRight')" title="Align Right"><i class="fa-solid fa-align-right"></i></button>
      </div>
    </div>

    <!-- Theme & Typography Card -->
    <div class="control-card">
      <h3><i class="fa-solid fa-font"></i> Typography Guide</h3>
      <p style="font-size:0.75rem; color:#8c8994; margin-top:-6px; margin-bottom:12px; font-weight:600; line-height:1.4;">Select text inside the paper first, then click a style to apply it:</p>
      
      <div style="display:flex; flex-direction:column; gap:12px;">
        <!-- Heading Styles -->
        <div>
          <span style="font-size:0.68rem; font-weight:800; color:#8c8994; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:5px;">For Headings (H1/H2)</span>
          <div class="font-style-btns" style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
            <button type="button" onclick="applyFontFamily('Playfair Display, Georgia, serif')" style="font-family:'Playfair Display', serif; font-weight:800; padding:6px 10px; font-size:0.72rem; border-radius:8px; border:1px solid #cbd5e1; cursor:pointer;">Editorial Serif</button>
            <button type="button" onclick="applyFontFamily('Plus Jakarta Sans, sans-serif')" style="font-family:'Plus Jakarta Sans', sans-serif; font-weight:800; padding:6px 10px; font-size:0.72rem; border-radius:8px; border:1px solid #cbd5e1; cursor:pointer;">Modern Bold</button>
          </div>
        </div>

        <!-- Body & Subheadings -->
        <div>
          <span style="font-size:0.68rem; font-weight:800; color:#8c8994; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:5px;">For Body & Subheadings (H3)</span>
          <div class="font-style-btns" style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
            <button type="button" onclick="applyFontFamily('Lora, Georgia, serif')" style="font-family:'Lora', serif; padding:6px 10px; font-size:0.72rem; border-radius:8px; border:1px solid #cbd5e1; cursor:pointer;">Elegant Lora</button>
            <button type="button" onclick="applyFontFamily('Inter, sans-serif')" style="font-family:'Inter', sans-serif; padding:6px 10px; font-size:0.72rem; border-radius:8px; border:1px solid #cbd5e1; cursor:pointer;">Inter Sans</button>
          </div>
        </div>

        <!-- Inline Enhancements -->
        <div>
          <span style="font-size:0.68rem; font-weight:800; color:#8c8994; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:5px;">Inline Highlights & Styles</span>
          <div class="font-style-btns" style="display:grid; grid-template-columns:repeat(3, 1fr); gap:6px;">
            <button type="button" onclick="fmt('bold')" style="font-weight:900; padding:6px; font-size:0.72rem; border-radius:8px; border:1px solid #cbd5e1; cursor:pointer;">Bold</button>
            <button type="button" onclick="applyFontStyle('font-light')" style="color:#888; padding:6px; font-size:0.72rem; border-radius:8px; border:1px solid #cbd5e1; cursor:pointer;">Light</button>
            <button type="button" onclick="applyFontStyle('font-highlight')" style="background:#FFF1B8; padding:6px; font-size:0.72rem; border-radius:8px; border:none; cursor:pointer; color:#78350f; font-weight:bold;">Highlight</button>
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
const editor = document.getElementById('blog-editor');
const input  = document.getElementById('blog-content-input');

// Sync hidden input before submit
document.querySelector('form').addEventListener('submit', () => { input.value = editor.innerHTML; });

function fmt(cmd) { document.execCommand(cmd, false, null); editor.focus(); }

// ─── Image Resize, Drag & Crop System ─────────────────────────────────────────
const imgToolbar = document.createElement('div');
imgToolbar.id = 'img-toolbar';
imgToolbar.style.cssText = 'display:none;position:fixed;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:8px 12px;box-shadow:0 10px 25px rgba(0,0,0,0.08);z-index:9999;gap:8px;flex-wrap:wrap;align-items:center;';

// Standard Image Actions Toolbar
function resetToolbar() {
    imgToolbar.innerHTML = `
      <span class="tb-label" style="font-family:'Plus Jakarta Sans',sans-serif;font-size:0.75rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;">Size:</span>
      <button type="button" onclick="resizeImg(25)">25%</button>
      <button type="button" onclick="resizeImg(50)">50%</button>
      <button type="button" onclick="resizeImg(75)">75%</button>
      <button type="button" onclick="resizeImg(100)">100%</button>
      <div class="tb-sep" style="width:1px;height:18px;background:#e2e8f0;margin:0 4px;"></div>
      <span class="tb-label" style="font-family:'Plus Jakarta Sans',sans-serif;font-size:0.75rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;">Align:</span>
      <button type="button" onclick="alignImg('left')" title="Float Left & Wrap Text"><i class="fa-solid fa-align-left"></i> Wrap Left</button>
      <button type="button" onclick="alignImg('center')" title="Center Block"><i class="fa-solid fa-align-center"></i> Center</button>
      <button type="button" onclick="alignImg('right')" title="Float Right & Wrap Text">Wrap Right <i class="fa-solid fa-align-right"></i></button>
      <div class="tb-sep" style="width:1px;height:18px;background:#e2e8f0;margin:0 4px;"></div>
      <button type="button" onclick="startCropping()" title="Crop Image" style="color:#10b981; background:#e6fbf3 !important; border:none !important; font-weight:800 !important;"><i class="fa-solid fa-crop-simple"></i> Crop</button>
      <div class="tb-sep" style="width:1px;height:18px;background:#e2e8f0;margin:0 4px;"></div>
      <button type="button" onclick="removeImg()" style="color:#ef4444;background:#fee2e2 !important;border:none !important;"><i class="fa-solid fa-trash-can"></i></button>
    `;
}
resetToolbar();

// Add premium styles dynamically
const styleTag = document.createElement('style');
styleTag.innerHTML = `
  #img-toolbar button {
    background: #f1f5f9 !important;
    border: none !important;
    color: #475569 !important;
    padding: 6px 12px !important;
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    font-size: 0.8rem !important;
    font-weight: 700 !important;
    border-radius: 8px !important;
    cursor: pointer !important;
    transition: all 0.2s !important;
    box-shadow: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
  }
  #img-toolbar button:hover {
    background: #6366f1 !important;
    color: #ffffff !important;
  }
  
  /* Bounding Box Resizer styles */
  #editor-image-resizer {
    position: absolute !important;
    border: 2px solid #6366f1 !important;
    pointer-events: none !important;
    z-index: 9998 !important;
    box-sizing: border-box !important;
    display: none;
    box-shadow: 0 0 0 1px rgba(255,255,255,0.5), 0 10px 30px rgba(99,102,241,0.15);
  }
  .resize-handle {
    position: absolute !important;
    width: 12px !important;
    height: 12px !important;
    background: #ffffff !important;
    border: 2px solid #6366f1 !important;
    border-radius: 50% !important;
    pointer-events: auto !important;
    box-shadow: 0 2px 5px rgba(0,0,0,0.25) !important;
    z-index: 10000 !important;
    box-sizing: border-box !important;
    transition: transform 0.1s, background 0.1s !important;
  }
  .resize-handle:hover {
    transform: scale(1.3) !important;
    background: #6366f1 !important;
  }
  
  .resize-handle.tl { top: -7px; left: -7px; cursor: nwse-resize; }
  .resize-handle.tr { top: -7px; right: -7px; cursor: nesw-resize; }
  .resize-handle.bl { bottom: -7px; left: -7px; cursor: nesw-resize; }
  .resize-handle.br { bottom: -7px; right: -7px; cursor: nwse-resize; }
  
  .resize-handle.l { top: calc(50% - 6px); left: -7px; cursor: ew-resize; }
  .resize-handle.r { top: calc(50% - 6px); right: -7px; cursor: ew-resize; }
  .resize-handle.t { top: -7px; left: calc(50% - 6px); cursor: ns-resize; }
  .resize-handle.b { bottom: -7px; left: calc(50% - 6px); cursor: ns-resize; }

  /* Cropper overlay styles */
  #editor-image-cropper {
    position: absolute !important;
    border: 2px dashed #10b981 !important;
    background: rgba(16, 185, 129, 0.08) !important;
    pointer-events: auto !important;
    z-index: 10001 !important;
    box-sizing: border-box !important;
    display: none;
    cursor: move;
  }
  .crop-handle {
    position: absolute !important;
    width: 14px !important;
    height: 14px !important;
    background: #ffffff !important;
    border: 2.5px solid #10b981 !important;
    box-shadow: 0 2px 6px rgba(0,0,0,0.25) !important;
    z-index: 10002 !important;
    box-sizing: border-box !important;
  }
  .crop-handle.tl { top: -8px; left: -8px; cursor: nwse-resize; }
  .crop-handle.tr { top: -8px; right: -8px; cursor: nesw-resize; }
  .crop-handle.bl { bottom: -8px; left: -8px; cursor: nesw-resize; }
  .crop-handle.br { bottom: -8px; right: -8px; cursor: nwse-resize; }
`;
document.head.appendChild(styleTag);
document.body.appendChild(imgToolbar);

let selectedImg = null;
let resizeTimeout = null;
let activeHandle = null;
let startX, startY, startWidth, startHeight, startRatio;
let isCropping = false;

// Dynamic Grid Guidelines creation inside Scribe Paper
const paperElement = document.querySelector('.scribe-paper');
let guidelines = document.getElementById('editor-grid-guidelines');
if (paperElement && !guidelines) {
    guidelines = document.createElement('div');
    guidelines.id = 'editor-grid-guidelines';
    guidelines.innerHTML = `
        <div class="grid-guideline-line"></div>
        <div class="grid-guideline-line"></div>
        <div class="grid-guideline-line"></div>
        <div class="grid-guideline-line"></div>
        <div class="grid-guideline-line"></div>
    `;
    paperElement.appendChild(guidelines);
}

// Bounding Box Resizer element creation
let resizerBox = document.getElementById('editor-image-resizer');
if (paperElement && !resizerBox) {
    resizerBox = document.createElement('div');
    resizerBox.id = 'editor-image-resizer';
    resizerBox.innerHTML = `
        <!-- 4 Corners -->
        <div class="resize-handle tl" data-handle="tl"></div>
        <div class="resize-handle tr" data-handle="tr"></div>
        <div class="resize-handle bl" data-handle="bl"></div>
        <div class="resize-handle br" data-handle="br"></div>
        <!-- 4 Edges -->
        <div class="resize-handle l" data-handle="l"></div>
        <div class="resize-handle r" data-handle="r"></div>
        <div class="resize-handle t" data-handle="t"></div>
        <div class="resize-handle b" data-handle="b"></div>
    `;
    paperElement.appendChild(resizerBox);
}

function showGuidelines() {
    if (guidelines) {
        guidelines.style.display = 'grid';
        guidelines.style.opacity = '0.25';
    }
}
function hideGuidelines() {
    if (guidelines) {
        guidelines.style.display = 'none';
    }
}

function updateResizerPosition() {
    if (!selectedImg || isCropping) {
        if (resizerBox) resizerBox.style.display = 'none';
        return;
    }
    const paperRect = paperElement.getBoundingClientRect();
    const imgRect = selectedImg.getBoundingClientRect();
    
    const top = imgRect.top - paperRect.top;
    const left = imgRect.left - paperRect.left;
    
    resizerBox.style.top = top + 'px';
    resizerBox.style.left = left + 'px';
    resizerBox.style.width = imgRect.width + 'px';
    resizerBox.style.height = imgRect.height + 'px';
    resizerBox.style.display = 'block';
}

function showImgToolbar(img, e) {
  if (isCropping) return;
  selectedImg = img;
  document.querySelectorAll('.editor-area img').forEach(i => i.classList.remove('img-selected'));
  img.classList.add('img-selected');
  
  updateResizerPosition();
  showGuidelines();
  clearTimeout(resizeTimeout);
  resizeTimeout = setTimeout(hideGuidelines, 1000);
  
  const rect = img.getBoundingClientRect();
  const top = Math.max(8, rect.top - 60);
  const left = Math.min(window.innerWidth - 450, rect.left);
  imgToolbar.style.top = top + 'px';
  imgToolbar.style.left = left + 'px';
  imgToolbar.style.display = 'flex';
}

function resizeImg(pct) {
  if (!selectedImg) return;
  selectedImg.style.width = pct + '%';
  selectedImg.style.height = 'auto';
  selectedImg.style.maxWidth = '100%';
  
  setTimeout(() => {
    updateResizerPosition();
    const rect = selectedImg.getBoundingClientRect();
    imgToolbar.style.top = Math.max(8, rect.top - 60) + 'px';
  }, 50);
  
  showGuidelines();
  clearTimeout(resizeTimeout);
  resizeTimeout = setTimeout(hideGuidelines, 1200);
}

function alignImg(dir) {
  if (!selectedImg) return;
  selectedImg.style.float = 'none';
  selectedImg.style.display = 'inline-block';
  selectedImg.style.marginLeft = '0';
  selectedImg.style.marginRight = '0';
  selectedImg.style.marginTop = '12px';
  selectedImg.style.marginBottom = '12px';
  
  if (dir === 'left') { 
    selectedImg.style.float = 'left'; 
    selectedImg.style.marginRight = '20px'; 
    selectedImg.style.marginBottom = '15px'; 
  }
  else if (dir === 'right') { 
    selectedImg.style.float = 'right'; 
    selectedImg.style.marginLeft = '20px'; 
    selectedImg.style.marginBottom = '15px'; 
  }
  else { 
    selectedImg.style.display = 'block';
    selectedImg.style.marginLeft = 'auto'; 
    selectedImg.style.marginRight = 'auto'; 
  }
  
  setTimeout(() => {
    updateResizerPosition();
    const rect = selectedImg.getBoundingClientRect();
    imgToolbar.style.top = Math.max(8, rect.top - 60) + 'px';
    imgToolbar.style.left = Math.min(window.innerWidth - 450, rect.left) + 'px';
  }, 50);
  
  showGuidelines();
  clearTimeout(resizeTimeout);
  resizeTimeout = setTimeout(hideGuidelines, 1200);
}

function removeImg() {
  if (!selectedImg) return;
  selectedImg.parentNode.removeChild(selectedImg);
  selectedImg = null;
  imgToolbar.style.display = 'none';
  if (resizerBox) resizerBox.style.display = 'none';
  hideGuidelines();
}

// Resizer Handles Dragging Logic
if (resizerBox) {
    resizerBox.addEventListener('mousedown', e => {
        const handle = e.target.closest('.resize-handle');
        if (!handle || !selectedImg) return;
        e.preventDefault();
        e.stopPropagation();
        
        activeHandle = handle.dataset.handle;
        startX = e.clientX;
        startY = e.clientY;
        startWidth = selectedImg.offsetWidth;
        startHeight = selectedImg.offsetHeight;
        startRatio = startWidth / startHeight;
        
        selectedImg.setAttribute('draggable', 'false');
        showGuidelines();
        
        document.addEventListener('mousemove', onResizeMove);
        document.addEventListener('mouseup', onResizeMouseUp);
    });
}

function onResizeMove(e) {
    if (!activeHandle || !selectedImg) return;
    
    const dx = e.clientX - startX;
    const dy = e.clientY - startY;
    
    let newWidth = startWidth;
    let newHeight = startHeight;
    
    if (activeHandle === 'br') {
        newWidth = startWidth + dx;
        newHeight = newWidth / startRatio;
    } else if (activeHandle === 'bl') {
        newWidth = startWidth - dx;
        newHeight = newWidth / startRatio;
    } else if (activeHandle === 'tr') {
        newWidth = startWidth + dx;
        newHeight = newWidth / startRatio;
    } else if (activeHandle === 'tl') {
        newWidth = startWidth - dx;
        newHeight = newWidth / startRatio;
    } else if (activeHandle === 'r') {
        newWidth = startWidth + dx;
    } else if (activeHandle === 'l') {
        newWidth = startWidth - dx;
    } else if (activeHandle === 'b') {
        newHeight = startHeight + dy;
    } else if (activeHandle === 't') {
        newHeight = startHeight - dy;
    }
    
    if (newWidth > 35) {
        selectedImg.style.width = newWidth + 'px';
    }
    if (newHeight > 35 && (activeHandle === 'b' || activeHandle === 't' || activeHandle === 'r' || activeHandle === 'l')) {
        selectedImg.style.height = newHeight + 'px';
    } else if (activeHandle === 'br' || activeHandle === 'bl' || activeHandle === 'tr' || activeHandle === 'tl') {
        selectedImg.style.height = 'auto';
    }
    
    updateResizerPosition();
    
    const rect = selectedImg.getBoundingClientRect();
    imgToolbar.style.top = Math.max(8, rect.top - 60) + 'px';
    imgToolbar.style.left = Math.min(window.innerWidth - 450, rect.left) + 'px';
}

function onResizeMouseUp() {
    activeHandle = null;
    if (selectedImg) {
        selectedImg.setAttribute('draggable', 'true');
    }
    hideGuidelines();
    document.removeEventListener('mousemove', onResizeMove);
    document.removeEventListener('mouseup', onResizeMouseUp);
}

// ✂️ Image Cropper Engine
let activeCropHandle = null;
let cropStartX, cropStartY, cropStartTop, cropStartLeft, cropStartWidth, cropStartHeight;

function startCropping() {
    if (!selectedImg) return;
    isCropping = true;
    
    if (resizerBox) resizerBox.style.display = 'none';
    
    let cropOverlay = document.getElementById('editor-image-cropper');
    if (!cropOverlay) {
        cropOverlay = document.createElement('div');
        cropOverlay.id = 'editor-image-cropper';
        cropOverlay.innerHTML = `
            <div class="crop-handle tl" data-handle="tl"></div>
            <div class="crop-handle tr" data-handle="tr"></div>
            <div class="crop-handle bl" data-handle="bl"></div>
            <div class="crop-handle br" data-handle="br"></div>
        `;
        paperElement.appendChild(cropOverlay);
        cropOverlay.addEventListener('mousedown', onCropMouseDown);
    }
    
    const paperRect = paperElement.getBoundingClientRect();
    const imgRect = selectedImg.getBoundingClientRect();
    
    cropOverlay.style.top = (imgRect.top - paperRect.top) + 'px';
    cropOverlay.style.left = (imgRect.left - paperRect.left) + 'px';
    cropOverlay.style.width = imgRect.width + 'px';
    cropOverlay.style.height = imgRect.height + 'px';
    cropOverlay.style.display = 'block';
    
    imgToolbar.innerHTML = `
      <span class="tb-label" style="font-family:'Plus Jakarta Sans',sans-serif;font-size:0.75rem;font-weight:800;color:#10b981;text-transform:uppercase;letter-spacing:0.5px;"><i class="fa-solid fa-crop-simple"></i> Crop Mode:</span>
      <button type="button" onclick="confirmCrop()" style="background:#10b981 !important; color:#ffffff !important; font-weight:800 !important; font-size:0.8rem !important; padding:6px 12px !important; border-radius:8px !important; cursor:pointer !important;"><i class="fa-solid fa-check"></i> Crop & Apply</button>
      <button type="button" onclick="cancelCrop()" style="background:#ef4444 !important; color:#ffffff !important; font-weight:800 !important; font-size:0.8rem !important; padding:6px 12px !important; border-radius:8px !important; cursor:pointer !important;"><i class="fa-solid fa-xmark"></i> Cancel</button>
    `;
}

function onCropMouseDown(e) {
    const handle = e.target.closest('.crop-handle');
    e.preventDefault();
    e.stopPropagation();
    
    if (handle) {
        activeCropHandle = handle.dataset.handle;
    } else {
        activeCropHandle = 'move';
    }
    
    cropStartX = e.clientX;
    cropStartY = e.clientY;
    
    const cropOverlay = document.getElementById('editor-image-cropper');
    cropStartTop = parseFloat(cropOverlay.style.top);
    cropStartLeft = parseFloat(cropOverlay.style.left);
    cropStartWidth = cropOverlay.offsetWidth;
    cropStartHeight = cropOverlay.offsetHeight;
    
    document.addEventListener('mousemove', onCropMouseMove);
    document.addEventListener('mouseup', onCropMouseUp);
}

function onCropMouseMove(e) {
    const cropOverlay = document.getElementById('editor-image-cropper');
    if (!cropOverlay || !selectedImg) return;
    
    const dx = e.clientX - cropStartX;
    const dy = e.clientY - cropStartY;
    
    const paperRect = paperElement.getBoundingClientRect();
    const imgRect = selectedImg.getBoundingClientRect();
    const imgTop = imgRect.top - paperRect.top;
    const imgLeft = imgRect.left - paperRect.left;
    
    if (activeCropHandle === 'move') {
        let newLeft = cropStartLeft + dx;
        let newTop = cropStartTop + dy;
        
        newLeft = Math.max(imgLeft, Math.min(newLeft, imgLeft + imgRect.width - cropOverlay.offsetWidth));
        newTop = Math.max(imgTop, Math.min(newTop, imgTop + imgRect.height - cropOverlay.offsetHeight));
        
        cropOverlay.style.left = newLeft + 'px';
        cropOverlay.style.top = newTop + 'px';
    } else {
        let newWidth = cropStartWidth;
        let newHeight = cropStartHeight;
        let newLeft = cropStartLeft;
        let newTop = cropStartTop;
        
        if (activeCropHandle === 'br') {
            newWidth = Math.min(cropStartWidth + dx, imgLeft + imgRect.width - cropStartLeft);
            newHeight = Math.min(cropStartHeight + dy, imgTop + imgRect.height - cropStartTop);
        } else if (activeCropHandle === 'bl') {
            const maxDx = cropStartLeft - imgLeft;
            const safeDx = Math.max(-maxDx, dx);
            newLeft = cropStartLeft + safeDx;
            newWidth = cropStartWidth - safeDx;
            newHeight = Math.min(cropStartHeight + dy, imgTop + imgRect.height - cropStartTop);
        } else if (activeCropHandle === 'tr') {
            const maxDy = cropStartTop - imgTop;
            const safeDy = Math.max(-maxDy, dy);
            newTop = cropStartTop + safeDy;
            newHeight = cropStartHeight - safeDy;
            newWidth = Math.min(cropStartWidth + dx, imgLeft + imgRect.width - cropStartLeft);
        } else if (activeCropHandle === 'tl') {
            const maxDx = cropStartLeft - imgLeft;
            const safeDx = Math.max(-maxDx, dx);
            const maxDy = cropStartTop - imgTop;
            const safeDy = Math.max(-maxDy, dy);
            
            newLeft = cropStartLeft + safeDx;
            newWidth = cropStartWidth - safeDx;
            newTop = cropStartTop + safeDy;
            newHeight = cropStartHeight - safeDy;
        }
        
        if (newWidth > 30) {
            cropOverlay.style.width = newWidth + 'px';
            cropOverlay.style.left = newLeft + 'px';
        }
        if (newHeight > 30) {
            cropOverlay.style.height = newHeight + 'px';
            cropOverlay.style.top = newTop + 'px';
        }
    }
}

function onCropMouseUp() {
    activeCropHandle = null;
    document.removeEventListener('mousemove', onCropMouseMove);
    document.removeEventListener('mouseup', onCropMouseUp);
}

function cancelCrop() {
    isCropping = false;
    const cropOverlay = document.getElementById('editor-image-cropper');
    if (cropOverlay) cropOverlay.style.display = 'none';
    
    resetToolbar();
    updateResizerPosition();
}

function confirmCrop() {
    const cropOverlay = document.getElementById('editor-image-cropper');
    if (!cropOverlay || !selectedImg) return;
    
    const paperRect = paperElement.getBoundingClientRect();
    const imgRect = selectedImg.getBoundingClientRect();
    const cropRect = cropOverlay.getBoundingClientRect();
    
    const scaleX = selectedImg.naturalWidth / imgRect.width;
    const scaleY = selectedImg.naturalHeight / imgRect.height;
    
    const sourceX = (cropRect.left - imgRect.left) * scaleX;
    const sourceY = (cropRect.top - imgRect.top) * scaleY;
    const sourceWidth = cropRect.width * scaleX;
    const sourceHeight = cropRect.height * scaleY;
    
    const canvas = document.createElement('canvas');
    canvas.width = sourceWidth;
    canvas.height = sourceHeight;
    const ctx = canvas.getContext('2d');
    
    const tempImg = new Image();
    tempImg.crossOrigin = 'anonymous';
    tempImg.onload = function() {
        ctx.drawImage(tempImg, sourceX, sourceY, sourceWidth, sourceHeight, 0, 0, sourceWidth, sourceHeight);
        
        try {
            const croppedDataUrl = canvas.toDataURL('image/jpeg', 0.92);
            selectedImg.src = croppedDataUrl;
            selectedImg.style.width = cropRect.width + 'px';
            selectedImg.style.height = 'auto';
        } catch (err) {
            alert('Could not crop this image due to CORS restrictions on the external domain.');
        }
        cancelCrop();
    };
    tempImg.src = selectedImg.src;
}

editor.addEventListener('click', e => {
  if (e.target.tagName === 'IMG') {
    e.preventDefault();
    showImgToolbar(e.target, e);
  } else {
    if (isCropping) cancelCrop();
    imgToolbar.style.display = 'none';
    document.querySelectorAll('.editor-area img').forEach(i => i.classList.remove('img-selected'));
    selectedImg = null;
    if (resizerBox) resizerBox.style.display = 'none';
    hideGuidelines();
  }
});

document.addEventListener('scroll', () => {
  if (selectedImg && imgToolbar.style.display !== 'none' && !isCropping) {
    const rect = selectedImg.getBoundingClientRect();
    imgToolbar.style.top = Math.max(8, rect.top - 60) + 'px';
    imgToolbar.style.left = Math.min(window.innerWidth - 450, rect.left) + 'px';
    updateResizerPosition();
  }
}, true);

// ─── Native-like Draggable Repositioning and Formatting ───────────────────────
let draggedImg = null;

function makeImgDraggable(img) {
  img.setAttribute('draggable', 'true');
  img.style.cursor = 'grab';
}

// Observe editor for newly inserted images
const observer = new MutationObserver((mutations) => {
  mutations.forEach(mutation => {
    mutation.addedNodes.forEach(node => {
      if (node.tagName === 'IMG') {
        makeImgDraggable(node);
      } else if (node.querySelectorAll) {
        node.querySelectorAll('img').forEach(makeImgDraggable);
      }
    });
  });
});
observer.observe(editor, { childList: true, subtree: true });
editor.querySelectorAll('img').forEach(makeImgDraggable);

editor.addEventListener('dragstart', (e) => {
  if (e.target.tagName === 'IMG') {
    draggedImg = e.target;
    if (resizerBox) resizerBox.style.display = 'none'; // Hide resizer box so it doesn't block dropping!
    e.dataTransfer.effectAllowed = 'move';
    e.target.style.opacity = '0.4';
    showGuidelines(); // Show guidelines when dragging image!
  }
});

editor.addEventListener('dragend', (e) => {
  if (e.target.tagName === 'IMG') {
    e.target.style.opacity = '1';
    setTimeout(() => {
      if (selectedImg) {
        updateResizerPosition();
      }
    }, 50);
  }
  draggedImg = null;
  hideGuidelines(); // Hide guidelines on drag end!
});

editor.addEventListener('dragover', (e) => {
  e.preventDefault();
});

editor.addEventListener('drop', (e) => {
  e.preventDefault();
  hideGuidelines(); // Hide guidelines on drop!
  
  if (draggedImg) {
    let range = null;
    if (document.caretRangeFromPoint) {
      range = document.caretRangeFromPoint(e.clientX, e.clientY);
    } else if (e.rangeParent) {
      range = document.createRange();
      range.setStart(e.rangeParent, e.rangeOffset);
    }
    
    if (range) {
      let containerNode = range.startContainer;
      if (containerNode.nodeType === Node.TEXT_NODE) {
          containerNode = containerNode.parentNode;
      }
      
      // Prevent nesting inside other paragraph structures! Split paragraphs smoothly.
      if (containerNode && containerNode !== editor && editor.contains(containerNode)) {
          let blockNode = containerNode;
          while (blockNode.parentNode && blockNode.parentNode !== editor) {
              blockNode = blockNode.parentNode;
          }
          editor.insertBefore(draggedImg, blockNode.nextSibling);
      } else {
          range.insertNode(draggedImg);
      }
      
      // Guarantee focusable line below dropped image
      if (!draggedImg.nextElementSibling || draggedImg.nextElementSibling.tagName !== 'P') {
          const p = document.createElement('p');
          p.innerHTML = '<br>';
          editor.insertBefore(p, draggedImg.nextSibling);
      }
      
      selectedImg = draggedImg;
      setTimeout(() => {
          updateResizerPosition();
          showImgToolbar(selectedImg, e);
      }, 50);
    }
    draggedImg = null;
    return;
  }
  
  const files = e.dataTransfer.files;
  if (files.length > 0) {
    for (let i = 0; i < files.length; i++) {
      if (files[i].type.startsWith('image/')) {
        uploadEditorImage(files[i]);
      }
    }
  }
});

function fmtBlock(tag) { document.execCommand('formatBlock', false, '<' + tag + '>'); editor.focus(); }

function applyFontFamily(family) {
  const sel = window.getSelection();
  if (!sel.rangeCount) return;
  const range = sel.getRangeAt(0);
  if (range.collapsed) return;
  const span = document.createElement('span');
  span.style.fontFamily = family;
  try {
    range.surroundContents(span);
  } catch (e) {
    document.execCommand('fontName', false, family);
  }
  editor.focus();
}

function insertCodeBlock() {
  editor.focus();
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
  document.execCommand('insertHTML', false, codeBlockHtml);
}

function copyCodeText(btn) {
  const block = btn.closest('.blog-code-block');
  if (!block) return;
  let pre = block.querySelector('.code-content');
  if (!pre) pre = block.querySelector('pre');
  if (!pre) return;
  const code = pre.innerText;
  function showCopied(button){
    const originalText = button.innerHTML;
    button.innerHTML = `<i class=\"fa-solid fa-check\" style=\"color:#10b981;\"></i> Copied!`;
    button.style.background = '#065f46';
    button.style.color = '#ffffff';
    setTimeout(()=>{ button.innerHTML = originalText; button.style.background = '#1e293b'; button.style.color = '#cbd5e1'; }, 2000);
  }
  function fallbackCopy(text, button){
    const ta = document.createElement('textarea'); ta.value = text; ta.style.position='fixed'; ta.style.opacity='0'; document.body.appendChild(ta); ta.select();
    try { document.execCommand('copy'); showCopied(button); } catch(e){ alert('Could not copy code.'); }
    document.body.removeChild(ta);
  }
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(code).then(() => showCopied(btn)).catch(() => fallbackCopy(code, btn));
  } else {
    fallbackCopy(code, btn);
  }
}

function applyFontStyle(cls) {
  const sel = window.getSelection();
  if (!sel.rangeCount) return;
  const range = sel.getRangeAt(0);
  if (range.collapsed) return;
  const span = document.createElement('span');
  span.className = cls;
  try { range.surroundContents(span); } catch(e) {}
  editor.focus();
}

// Scope Ctrl+A inside Code Blocks and handle placeholders
editor.addEventListener('keydown', (e) => {
    if (e.ctrlKey && (e.key === 'a' || e.key === 'A')) {
        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0) {
            let node = sel.anchorNode;
            if (node) {
                if (node.nodeType === Node.TEXT_NODE) {
                    node = node.parentNode;
                }
                const codePre = node.closest('.code-content');
                if (codePre) {
                    e.preventDefault();
                    const range = document.createRange();
                    range.selectNodeContents(codePre);
                    sel.removeAllRanges();
                    sel.addRange(range);
                }
            }
        }
    }
});

// Placeholder
editor.addEventListener('focus', () => { if(editor.textContent.trim()==='') editor.innerHTML=''; });
editor.addEventListener('blur',  () => {});

function insertImagePrompt() {
    const url = prompt("Enter image URL:");
    if (url) {
        editor.focus();
        // Insert clean block-level image on its own line followed by a blank paragraph, with zero invalid nested paragraphs!
        const imgHtml = `<img src="${url}" style="max-width:75%; border-radius:16px; display:block; margin:20px auto; height:auto;" alt=""><p><br></p>`;
        document.execCommand('insertHTML', false, imgHtml);
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
        if (data.url) {
            editor.focus();
            // Insert clean block-level image on its own line followed by a blank paragraph, with zero invalid nested paragraphs!
            const imgHtml = `<img src="${data.url}" style="max-width:75%; border-radius:16px; display:block; margin:20px auto; height:auto;" alt=""><p><br></p>`;
            document.execCommand('insertHTML', false, imgHtml);
        } else {
            alert('Upload failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => alert('Upload error'));
}

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
