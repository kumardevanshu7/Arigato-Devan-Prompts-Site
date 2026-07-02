<?php
$host = "localhost";
$dbname = "prompt_app";
$username = "root";
$password = "";

// Keep authenticated users logged in for long sessions.
ini_set("session.gc_maxlifetime", (string) (60 * 60 * 24 * 30));
if (session_status() === PHP_SESSION_ACTIVE && session_id() !== "") {
    $is_https = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";
    setcookie(session_name(), session_id(), [
        "expires" => time() + (60 * 60 * 24 * 30),
        "path" => "/",
        "secure" => $is_https,
        "httponly" => true,
        "samesite" => "Lax",
    ]);
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $pdo->exec("USE `$dbname`");

    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create prompts table
    $pdo->exec("CREATE TABLE IF NOT EXISTS prompts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        tag TEXT NOT NULL,
        prompt_text TEXT NOT NULL,
        unlock_code VARCHAR(6) NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        reel_link VARCHAR(255) DEFAULT '',
        prompt_type VARCHAR(20) DEFAULT 'secret',
        likes_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create likes table
    $pdo->exec("CREATE TABLE IF NOT EXISTS likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        prompt_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_like (user_id, prompt_id)
    )");

    // Create unlocked_prompts table
    $pdo->exec("CREATE TABLE IF NOT EXISTS unlocked_prompts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        prompt_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_unlock (user_id, prompt_id)
    )");

    // Blogs table
    $pdo->exec("CREATE TABLE IF NOT EXISTS blogs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        description TEXT,
        content LONGTEXT,
        image_path VARCHAR(255) DEFAULT '',
        image_ratio VARCHAR(10) DEFAULT '16:9',
        meta_title VARCHAR(255) DEFAULT '',
        meta_description TEXT,
        tags VARCHAR(500) DEFAULT '',
        likes_count INT DEFAULT 0,
        views_count INT DEFAULT 0,
        is_published TINYINT(1) DEFAULT 0,
        author_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Blog likes table
    $pdo->exec("CREATE TABLE IF NOT EXISTS blog_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        blog_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_blog_like (user_id, blog_id)
    )");

    // Blog comments table
    $pdo->exec("CREATE TABLE IF NOT EXISTS blog_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        blog_id INT NOT NULL,
        user_id INT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Custom Prompt of the Day table
    $pdo->exec("CREATE TABLE IF NOT EXISTS potd_custom (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        prompt_text TEXT NOT NULL,
        image_url VARCHAR(500) DEFAULT '',
        is_active TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // User Feedbacks table
    $pdo->exec("CREATE TABLE IF NOT EXISTS feedbacks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        feedback_text TEXT NOT NULL,
        rating TINYINT NOT NULL DEFAULT 0,
        show_on_homepage TINYINT(1) NOT NULL DEFAULT 0,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_feedback (user_id)
    )");

    // ─── Run ALTER TABLE migrations only once per session (not every page load) ─
    if (empty($_SESSION['_db_migrations_done'])) {
        $blog_alters = [
            "ALTER TABLE blogs ADD COLUMN image_ratio VARCHAR(10) DEFAULT '16:9'",
            "ALTER TABLE blogs ADD COLUMN views_count INT DEFAULT 0",
        ];
        foreach ($blog_alters as $sql) {
            try { $pdo->exec($sql); } catch (PDOException $e) {}
        }

        $user_alters = [
            "ALTER TABLE users ADD COLUMN email VARCHAR(100) UNIQUE",
            "ALTER TABLE users ADD COLUMN google_id VARCHAR(100) UNIQUE",
            "ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'",
            "ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL",
            "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL",
            "ALTER TABLE users ADD COLUMN gender VARCHAR(20) DEFAULT NULL",
            "ALTER TABLE users ADD COLUMN onboarding_complete TINYINT(1) DEFAULT 0",
            "ALTER TABLE users MODIFY password_hash VARCHAR(255) NULL",
            "ALTER TABLE users MODIFY username VARCHAR(50) NULL",
            "ALTER TABLE users ADD UNIQUE KEY username (username)",
            "ALTER TABLE users ADD COLUMN last_visit_date DATE DEFAULT NULL",
            "ALTER TABLE users ADD COLUMN streak_count INT DEFAULT 0",
        ];
        foreach ($user_alters as $sql) {
            try { $pdo->exec($sql); } catch (PDOException $e) {}
        }

        $prompt_alters = [
            "ALTER TABLE prompts ADD COLUMN reel_link VARCHAR(255) DEFAULT ''",
            "ALTER TABLE prompts ADD COLUMN likes_count INT DEFAULT 0",
            "ALTER TABLE prompts CHANGE description tag TEXT NOT NULL",
            "ALTER TABLE prompts ADD COLUMN prompt_type VARCHAR(20) DEFAULT 'secret'",
            "ALTER TABLE prompts ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0",
            "ALTER TABLE prompts ADD COLUMN best_works_in VARCHAR(50) DEFAULT NULL",
            "ALTER TABLE prompts ADD COLUMN asset_title VARCHAR(255) DEFAULT NULL",
            "ALTER TABLE prompts ADD COLUMN asset_images TEXT DEFAULT NULL",
            "ALTER TABLE prompts ADD COLUMN slug VARCHAR(255) DEFAULT NULL",
            "ALTER TABLE prompts ADD COLUMN is_trending TINYINT(1) NOT NULL DEFAULT 0",
            "ALTER TABLE prompts ADD COLUMN trending_order INT NOT NULL DEFAULT 0",
        ];
        foreach ($prompt_alters as $sql) {
            try { $pdo->exec($sql); } catch (PDOException $e) {}
        }

        $_SESSION['_db_migrations_done'] = true;
    }

    // Newer columns — always try (idempotent); session flag may skip older batch
    $prompt_alters_late = [
        "ALTER TABLE prompts ADD COLUMN is_trending TINYINT(1) NOT NULL DEFAULT 0",
        "ALTER TABLE prompts ADD COLUMN trending_order INT NOT NULL DEFAULT 0",
    ];
    foreach ($prompt_alters_late as $sql) {
        try { $pdo->exec($sql); } catch (PDOException $e) {}
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Navigation counts using prompt_type column (accurate)
$nav_counts = ["secret_code" => 0, "unreleased" => 0, "insta_viral" => 0, "already_uploaded" => 0, "direct" => 0];
try {
    $stmt = $pdo->query(
        "SELECT prompt_type, COUNT(*) as cnt FROM prompts GROUP BY prompt_type",
    );
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row["prompt_type"] === "secret") {
            $nav_counts["secret_code"] += $row["cnt"];
        }
        if ($row["prompt_type"] === "unreleased") {
            $nav_counts["unreleased"] += $row["cnt"];
        }
        if ($row["prompt_type"] === "insta_viral") {
            $nav_counts["insta_viral"] += $row["cnt"];
        }
        if ($row["prompt_type"] === "already_uploaded") {
            $nav_counts["already_uploaded"] += $row["cnt"];
        }
        if ($row["prompt_type"] === "direct") {
            $nav_counts["direct"] += $row["cnt"];
        }
    }
} catch (PDOException $e) {
}

// Global Avatar Rendering Helper Function with WebP <picture> fallback
function renderAvatar(
    $src,
    $class = "admin-avatar",
    $alt = "Profile",
    $extra_attrs = "",
) {
    $src_clean = htmlspecialchars($src ?? "");
    $seed = isset($_SESSION["username"])
        ? urlencode($_SESSION["username"])
        : "user";
    if ($alt === "Admin") {
        $seed = "Admin";
    }
    $default_fallback =
        "https://api.dicebear.com/7.x/avataaars/svg?seed=" . $seed;
    $onerror = "this.onerror=null;this.src='$default_fallback'";

    if (empty($src_clean)) {
        return "<img src=\"$default_fallback\" class=\"$class\" alt=\"$alt\" referrerpolicy=\"no-referrer\" loading=\"lazy\" $extra_attrs>";
    }

    if (strpos($src_clean, "profiledp/") === 0) {
        $webp = str_replace(".png", ".webp", $src_clean);
        $png = str_replace(".webp", ".png", $src_clean);
        return "<picture>
            <source srcset=\"$webp\" type=\"image/webp\">
            <img src=\"$png\" class=\"$class\" alt=\"$alt\" referrerpolicy=\"no-referrer\" onerror=\"$onerror\" loading=\"lazy\" $extra_attrs>
        </picture>";
    } else {
        return "<img src=\"$src_clean\" class=\"$class\" alt=\"$alt\" referrerpolicy=\"no-referrer\" onerror=\"$onerror\" loading=\"lazy\" $extra_attrs>";
    }
}
// CSRF Protection Functions
function generate_csrf() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf() {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        header('HTTP/1.1 403 Forbidden');
        die('Invalid CSRF token. Please refresh the page and try again.');
    }
}
?>
