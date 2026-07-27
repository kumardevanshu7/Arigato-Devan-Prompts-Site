<?php
$host = "localhost";
$dbname = "prompt_app";
$username = "root";
$password = "";

// Refresh session cookie on each authenticated request (rolling 365-day expiry).
require_once __DIR__ . '/includes/session_config.php';
if (session_status() === PHP_SESSION_ACTIVE && session_id() !== '' && !empty($_SESSION['user_id'])) {
    $lifetime = session_lifetime_seconds();
    setcookie(session_name(), session_id(), [
        'expires'  => time() + $lifetime,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 5);

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

    // My Heroines — profiles featured in prompts/content
    $pdo->exec("CREATE TABLE IF NOT EXISTS heroines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        heroine_type ENUM('real', 'ai') NOT NULL DEFAULT 'ai',
        circle_image VARCHAR(255) NOT NULL,
        card_image VARCHAR(255) NOT NULL,
        times_used INT NOT NULL DEFAULT 0,
        country VARCHAR(80) DEFAULT NULL,
        instagram_username VARCHAR(80) DEFAULT NULL,
        instagram_url VARCHAR(255) DEFAULT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS heroine_settings (
        setting_key VARCHAR(64) PRIMARY KEY,
        setting_value TEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS happy_users_screenshots (
        id INT AUTO_INCREMENT PRIMARY KEY,
        image_path VARCHAR(255) NOT NULL,
        img_width INT UNSIGNED DEFAULT NULL,
        img_height INT UNSIGNED DEFAULT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_visible TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS not_mine_prompts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category ENUM('boys','girls','couple','family','creativity') NOT NULL,
        title VARCHAR(200) NOT NULL,
        slug VARCHAR(220) DEFAULT NULL,
        tags VARCHAR(120) DEFAULT '',
        prompt_text TEXT NOT NULL,
        meta_description TEXT DEFAULT NULL,
        meta_keywords VARCHAR(500) DEFAULT '',
        thumbnail_image VARCHAR(255) NOT NULL,
        chatgpt_image VARCHAR(255) DEFAULT NULL,
        chatgpt_failed TINYINT(1) NOT NULL DEFAULT 0,
        gemini_image VARCHAR(255) DEFAULT NULL,
        gemini_failed TINYINT(1) NOT NULL DEFAULT 0,
        is_visible TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_nm_slug (slug)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS not_mine_votes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        prompt_id INT NOT NULL,
        voted_for ENUM('chatgpt','gemini') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_vote (user_id, prompt_id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS not_mine_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        prompt_id INT NOT NULL,
        like_type ENUM('unlock','manual') NOT NULL DEFAULT 'manual',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_nm_like (user_id, prompt_id, like_type)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS user_revealed_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        prompt_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_code_reveal (user_id, prompt_id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS web_stories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        slug VARCHAR(200) NOT NULL UNIQUE,
        description TEXT,
        meta_keywords VARCHAR(500) DEFAULT '',
        poster_image VARCHAR(255) DEFAULT '',
        publisher_name VARCHAR(120) DEFAULT 'Arigato Devan',
        noindex TINYINT(1) NOT NULL DEFAULT 0,
        is_published TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS web_story_pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        story_id INT NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        bg_image VARCHAR(255) DEFAULT '',
        bg_color VARCHAR(20) DEFAULT '#2F4156',
        title VARCHAR(255) DEFAULT '',
        body_text TEXT,
        cta_label VARCHAR(120) DEFAULT '',
        cta_url VARCHAR(500) DEFAULT '',
        text_align VARCHAR(12) DEFAULT 'left',
        animate_in VARCHAR(32) DEFAULT 'fade-in',
        auto_advance_sec INT NOT NULL DEFAULT 0,
        INDEX idx_story_order (story_id, sort_order)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS web_stories_settings (
        setting_key VARCHAR(64) PRIMARY KEY,
        setting_value TEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // ─── Schema migrations: run once, never block every page load ─────────────
    $schema_flag = __DIR__ . '/cache/.schema_ready_v10';
    if (!is_file($schema_flag)) {
        $cache_dir = __DIR__ . '/cache';
        if (!is_dir($cache_dir)) {
            @mkdir($cache_dir, 0775, true);
        }
        $lock_fp = @fopen($cache_dir . '/.schema.lock', 'c');
        $got_lock = $lock_fp && flock($lock_fp, LOCK_EX);

        if ($got_lock && !is_file($schema_flag)) {
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
                "ALTER TABLE users ADD COLUMN last_active DATETIME DEFAULT NULL",
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
                "ALTER TABLE prompts ADD COLUMN meta_keywords VARCHAR(500) DEFAULT ''",
            ];
            foreach ($prompt_alters as $sql) {
                try { $pdo->exec($sql); } catch (PDOException $e) {}
            }

            $saved_alters = [
                "ALTER TABLE saved_prompts ADD COLUMN source VARCHAR(20) NOT NULL DEFAULT 'prompt'",
            ];
            foreach ($saved_alters as $sql) {
                try { $pdo->exec($sql); } catch (PDOException $e) {}
            }

            $nm_alters = [
                "ALTER TABLE not_mine_likes ADD COLUMN like_type ENUM('unlock','manual') NOT NULL DEFAULT 'manual'",
                "ALTER TABLE not_mine_likes DROP INDEX unique_nm_like",
                "ALTER TABLE not_mine_likes ADD UNIQUE KEY unique_nm_like (user_id, prompt_id, like_type)",
                "ALTER TABLE not_mine_prompts MODIFY category ENUM('boys','girls','couple','family','creativity') NOT NULL",
                "ALTER TABLE not_mine_prompts ADD COLUMN slug VARCHAR(220) DEFAULT NULL",
                "ALTER TABLE not_mine_prompts ADD COLUMN meta_description TEXT DEFAULT NULL",
                "ALTER TABLE not_mine_prompts ADD COLUMN meta_keywords VARCHAR(500) DEFAULT ''",
                "ALTER TABLE not_mine_prompts ADD UNIQUE KEY unique_nm_slug (slug)",
            ];
            foreach ($nm_alters as $sql) {
                try { $pdo->exec($sql); } catch (PDOException $e) {}
            }
            try {
                $pdo->exec("INSERT IGNORE INTO not_mine_likes (user_id, prompt_id, like_type)
                    SELECT user_id, prompt_id, 'unlock' FROM not_mine_votes");
            } catch (PDOException $e) {}
            if (is_file(__DIR__ . '/slug_helper.php')) {
                require_once __DIR__ . '/slug_helper.php';
                try {
                    $nm_rows = $pdo->query("SELECT id, title FROM not_mine_prompts WHERE slug IS NULL OR slug = ''")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($nm_rows as $nm_row) {
                        $nm_slug = uniqueNotMineSlug($pdo, $nm_row['title'], (int) $nm_row['id']);
                        $pdo->prepare('UPDATE not_mine_prompts SET slug = ? WHERE id = ?')->execute([$nm_slug, $nm_row['id']]);
                    }
                } catch (PDOException $e) {}
            }

            $ws_alters = [
                "ALTER TABLE web_stories ADD COLUMN meta_keywords VARCHAR(500) DEFAULT ''",
                "ALTER TABLE web_stories ADD COLUMN noindex TINYINT(1) NOT NULL DEFAULT 0",
                "ALTER TABLE web_story_pages ADD COLUMN text_align VARCHAR(12) DEFAULT 'left'",
                "ALTER TABLE web_story_pages ADD COLUMN animate_in VARCHAR(32) DEFAULT 'fade-in'",
                "ALTER TABLE web_story_pages ADD COLUMN auto_advance_sec INT NOT NULL DEFAULT 0",
            ];
            foreach ($ws_alters as $sql) {
                try { $pdo->exec($sql); } catch (PDOException $e) {}
            }
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS web_stories_settings (
                    setting_key VARCHAR(64) PRIMARY KEY,
                    setting_value TEXT NOT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )");
            } catch (PDOException $e) {}

            @file_put_contents($schema_flag, date('c'));
        }

        if ($got_lock) {
            flock($lock_fp, LOCK_UN);
        }
        if ($lock_fp) {
            fclose($lock_fp);
        }
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
