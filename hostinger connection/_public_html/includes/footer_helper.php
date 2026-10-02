<?php
/**
 * Footer Configuration & Management Helper
 * Provides default links, loads dynamic config from database/JSON cache,
 * and saves updates made in the Footer Admin Manager.
 */

require_once __DIR__ . '/settings_helper.php';

function get_default_social_platforms(): array
{
    return [
        [
            'platform' => 'instagram',
            'title'    => 'Instagram',
            'icon'     => 'fa-brands fa-instagram',
            'url'      => 'https://www.instagram.com/arigato.devan/',
            'enabled'  => true
        ],
        [
            'platform' => 'x',
            'title'    => 'X (Twitter)',
            'icon'     => 'fa-brands fa-x-twitter',
            'url'      => '',
            'enabled'  => false
        ],
        [
            'platform' => 'pinterest',
            'title'    => 'Pinterest',
            'icon'     => 'fa-brands fa-pinterest-p',
            'url'      => '',
            'enabled'  => false
        ],
        [
            'platform' => 'threads',
            'title'    => 'Threads',
            'icon'     => 'fa-brands fa-threads',
            'url'      => '',
            'enabled'  => false
        ],
        [
            'platform' => 'facebook',
            'title'    => 'Facebook',
            'icon'     => 'fa-brands fa-facebook-f',
            'url'      => '',
            'enabled'  => false
        ],
        [
            'platform' => 'mail',
            'title'    => 'Mail',
            'icon'     => 'fa-solid fa-envelope',
            'url'      => 'contact.php',
            'enabled'  => true
        ],
    ];
}

function get_published_blogs_list(?PDO $pdo = null): array
{
    if ($pdo === null) {
        global $pdo;
    }
    if (!$pdo) {
        return [];
    }
    try {
        $stmt = $pdo->query("SELECT id, title, slug FROM blogs WHERE is_published = 1 ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function resolve_footer_blog_links(array $blog_ids, ?PDO $pdo = null): array
{
    if (empty($blog_ids)) {
        return [];
    }
    $all = get_published_blogs_list($pdo);
    $map = [];
    foreach ($all as $b) {
        $map[(int)$b['id']] = $b;
    }

    $links = [];
    foreach ($blog_ids as $bid) {
        $bid = (int)$bid;
        if (isset($map[$bid])) {
            $b = $map[$bid];
            $slug = trim((string)($b['slug'] ?? ''));
            $url = $slug !== '' ? ('blog.php?slug=' . urlencode($slug)) : ('blog.php?id=' . $bid);
            $links[] = [
                'id'     => $bid,
                'title'  => $b['title'] ?? 'Untitled Blog',
                'url'    => $url,
                'badge'  => '',
                'target' => '_self'
            ];
        }
    }
    return $links;
}

function get_default_footer_config(): array
{
    // Fetch latest 5 published blog IDs if available
    $latest_blogs = get_published_blogs_list();
    $default_blog_ids = array_slice(array_column($latest_blogs, 'id'), 0, 5);

    return [
        'watermark_text' => 'ARIGATO',
        'tagline'        => 'KEEP CREATING.',
        'brand_name'     => 'ARIGATO DEVAN',
        'social_links'   => get_default_social_platforms(),
        'blog_ids'       => $default_blog_ids,
        'columns'        => [
            [
                'id'    => 'explore',
                'title' => 'Explore Prompts',
                'links' => [
                    ['title' => 'Prompt Gallery', 'url' => 'gallery.php', 'badge' => '', 'target' => '_self'],
                    ['title' => 'Secret Code Prompts', 'url' => 'secret_code.php', 'badge' => '', 'target' => '_self'],
                    ['title' => 'SOLO Prompts', 'url' => 'solo_prompts.php', 'badge' => 'HOT', 'target' => '_self'],
                    ['title' => 'Unreleased Prompts', 'url' => 'unreleased.php', 'badge' => '', 'target' => '_self'],
                    ['title' => 'Direct Prompts', 'url' => 'direct_prompts.php', 'badge' => '', 'target' => '_self'],
                    ['title' => 'Curated AI Prompts', 'url' => 'curated_ai_prompts.php', 'badge' => 'PRO', 'target' => '_self'],
                ]
            ],
            [
                'id'    => 'blogs',
                'title' => 'Blogs',
                'type'  => 'blogs',
                'links' => [] // Dynamically populated via blog_ids
            ],
            [
                'id'    => 'legal',
                'title' => 'Legal & Policies',
                'links' => [
                    ['title' => 'Privacy Policy', 'url' => 'privacy.php', 'badge' => '', 'target' => '_self'],
                    ['title' => 'Disclaimer', 'url' => 'disclaimer.php', 'badge' => '', 'target' => '_self'],
                    ['title' => 'Terms & Conditions', 'url' => 'terms.php', 'badge' => '', 'target' => '_self'],
                ]
            ],
            [
                'id'    => 'community_connect',
                'title' => 'Community & Connect',
                'links' => [
                    ['title' => 'User Feedback', 'url' => 'feedback.php', 'badge' => '', 'target' => '_self'],
                    ['title' => 'Contact Us', 'url' => 'contact.php', 'badge' => '', 'target' => '_self'],
                    ['title' => 'FAQ', 'url' => 'faq.php', 'badge' => '', 'target' => '_self'],
                    ['title' => 'About Us', 'url' => 'about.php', 'badge' => '', 'target' => '_self'],
                ]
            ]
        ]
    ];
}

function get_footer_config(): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $raw = site_setting('footer_nav_config', '');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && !empty($decoded['columns'])) {
            // Ensure social_links has all default platforms merged if missing
            $defaults = get_default_social_platforms();
            $existing_soc = $decoded['social_links'] ?? [];
            $soc_map = [];
            foreach ($existing_soc as $s) {
                if (!empty($s['platform'])) {
                    $soc_map[$s['platform']] = $s;
                }
            }
            $final_soc = [];
            foreach ($defaults as $d) {
                $p = $d['platform'];
                if (isset($soc_map[$p])) {
                    $final_soc[] = array_merge($d, $soc_map[$p]);
                } else {
                    $final_soc[] = $d;
                }
            }
            $decoded['social_links'] = $final_soc;
            $cached = $decoded;
            return $cached;
        }
    }

    $cached = get_default_footer_config();
    return $cached;
}

function save_footer_config(array $config, ?PDO $pdo = null): bool
{
    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    return update_site_setting('footer_nav_config', $json, $pdo);
}

function get_available_site_pages_for_footer(): array
{
    return [
        ['title' => 'Home Page', 'url' => 'index.php', 'cat' => 'Core'],
        ['title' => 'Prompt Gallery', 'url' => 'gallery.php', 'cat' => 'Core'],
        ['title' => 'Secret Code Prompts', 'url' => 'secret_code.php', 'cat' => 'Prompts'],
        ['title' => 'SOLO Prompts', 'url' => 'solo_prompts.php', 'cat' => 'Prompts'],
        ['title' => 'Unreleased Prompts', 'url' => 'unreleased.php', 'cat' => 'Prompts'],
        ['title' => 'Direct Prompts', 'url' => 'direct_prompts.php', 'cat' => 'Prompts'],
        ['title' => 'Curated AI Prompts', 'url' => 'curated_ai_prompts.php', 'cat' => 'Prompts'],
        ['title' => 'Already Uploaded Prompts', 'url' => 'already_uploaded.php', 'cat' => 'Prompts'],
        ['title' => 'Blog Magazine Hub', 'url' => 'blogs.php', 'cat' => 'Content'],
        ['title' => 'Happy Users (Wall of Love)', 'url' => 'happy_users.php', 'cat' => 'Trust'],
        ['title' => 'About Us', 'url' => 'about.php', 'cat' => 'Info'],
        ['title' => 'Contact Us', 'url' => 'contact.php', 'cat' => 'Support'],
        ['title' => 'FAQ', 'url' => 'faq.php', 'cat' => 'Support'],
        ['title' => 'User Feedback', 'url' => 'feedback.php', 'cat' => 'Support'],
        ['title' => 'Privacy Policy', 'url' => 'privacy.php', 'cat' => 'Legal'],
        ['title' => 'Disclaimer', 'url' => 'disclaimer.php', 'cat' => 'Legal'],
        ['title' => 'Terms & Conditions', 'url' => 'terms.php', 'cat' => 'Legal'],
    ];
}
