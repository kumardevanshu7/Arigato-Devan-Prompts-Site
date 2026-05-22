<?php
require_once "db.php";

// Output as XML
header("Content-Type: application/xml; charset=UTF-8");
echo '<?xml version="1.0" encoding="UTF-8"?>';

$base = "https://arigatodevan.com";

// Static pages — public only (no login required pages)
$static_pages = [
    [
        "url" => "/",
        "priority" => "1.0",
        "changefreq" => "daily",
        "lastmod" => "2026-05-22",
    ],
    [
        "url" => "/gallery.php",
        "priority" => "0.9",
        "changefreq" => "daily",
        "lastmod" => "2026-05-20",
    ],
    [
        "url" => "/secret_code.php",
        "priority" => "0.8",
        "changefreq" => "weekly",
        "lastmod" => "2026-05-20",
    ],
    [
        "url" => "/insta_viral.php",
        "priority" => "0.8",
        "changefreq" => "weekly",
        "lastmod" => "2026-05-20",
    ],
    [
        "url" => "/unreleased.php",
        "priority" => "0.8",
        "changefreq" => "weekly",
        "lastmod" => "2026-05-20",
    ],
    [
        "url" => "/already_uploaded.php",
        "priority" => "0.8",
        "changefreq" => "weekly",
        "lastmod" => "2026-05-20",
    ],
    [
        "url" => "/blogs.php",
        "priority" => "0.7",
        "changefreq" => "weekly",
        "lastmod" => "2026-05-20",
    ],
    [
        "url" => "/how_to_use.php",
        "priority" => "0.7",
        "changefreq" => "monthly",
        "lastmod" => "2026-05-20",
    ],
    [
        "url" => "/progress.php",
        "priority" => "0.5",
        "changefreq" => "monthly",
        "lastmod" => "2025-02-14",
    ],
    [
        "url" => "/login.php",
        "priority" => "0.4",
        "changefreq" => "monthly",
        "lastmod" => "2026-05-22",
    ],
    [
        "url" => "/terms.php",
        "priority" => "0.2",
        "changefreq" => "monthly",
        "lastmod" => "2026-05-22",
    ],
    [
        "url" => "/disclaimer.php",
        "priority" => "0.2",
        "changefreq" => "monthly",
        "lastmod" => "2026-05-22",
    ],
    [
        "url" => "/privacy.php",
        "priority" => "0.2",
        "changefreq" => "monthly",
        "lastmod" => "2026-05-22",
    ],
];

// Fetch all prompts with slugs dynamically
$prompts = [];
try {
    $stmt = $pdo->query(
        "SELECT slug, id, created_at FROM prompts WHERE slug IS NOT NULL AND slug != '' ORDER BY created_at DESC",
    );
    $prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // silently skip if error
}

// Fetch all published blog posts dynamically
$blogs = [];
try {
    $stmt = $pdo->query(
        "SELECT slug, updated_at, created_at FROM blogs WHERE is_published = 1 ORDER BY created_at DESC",
    );
    $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // silently skip if error
}

$today = date("Y-m-d");
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

<?php foreach ($static_pages as $page): ?>
  <url>
    <loc><?= $base . $page["url"] ?></loc>
    <lastmod><?= $page["lastmod"] ?></lastmod>
    <changefreq><?= $page["changefreq"] ?></changefreq>
    <priority><?= $page["priority"] ?></priority>
  </url>
<?php endforeach; ?>

<?php foreach ($prompts as $prompt): ?>
  <url>
    <loc><?= $base . "/prompts/" . htmlspecialchars($prompt["slug"]) ?></loc>
    <lastmod><?= date("Y-m-d", strtotime($prompt["created_at"])) ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.8</priority>
  </url>
<?php endforeach; ?>

<?php foreach ($blogs as $blog): ?>
  <url>
    <loc><?= $base . "/blog.php?slug=" . urlencode($blog["slug"]) ?></loc>
    <lastmod><?= date(
        "Y-m-d",
        strtotime($blog["updated_at"] ?? $blog["created_at"]),
    ) ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
  </url>
<?php endforeach; ?>

</urlset>
