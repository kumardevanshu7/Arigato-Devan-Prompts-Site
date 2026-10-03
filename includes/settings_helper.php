<?php
/**
 * Site-wide Settings Helper
 * Handles dynamic site settings with dual-layer storage (MySQL site_settings table + JSON file cache).
 */

function site_settings_file_path(): string
{
    return __DIR__ . '/site_settings.json';
}

function load_site_settings_from_file(): array
{
    $file = site_settings_file_path();
    if (file_exists($file)) {
        $content = @file_get_contents($file);
        if ($content) {
            $data = json_decode($content, true);
            if (is_array($data)) {
                return $data;
            }
        }
    }
    return [];
}

function save_site_settings_to_file(array $settings): bool
{
    $file = site_settings_file_path();
    return (bool) @file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function ensure_site_settings_table(PDO $pdo): void
{
    static $ensured = false;
    if ($ensured) return;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        $ensured = true;
    } catch (Exception $e) {
    }
}

function site_setting(string $key, string $default = ''): string
{
    static $cache = null;

    if ($cache === null) {
        $cache = load_site_settings_from_file();

        // If PDO is available globally, try loading from DB to sync cache
        global $pdo;
        if (isset($pdo) && $pdo instanceof PDO) {
            try {
                ensure_site_settings_table($pdo);
                $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
                $db_settings = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $db_settings[$row['setting_key']] = (string)$row['setting_value'];
                }
                if (!empty($db_settings)) {
                    $cache = array_merge($cache, $db_settings);
                    save_site_settings_to_file($cache);
                }
            } catch (Exception $e) {
                // If DB fails, rely on file cache
            }
        }
    }

    if (array_key_exists($key, $cache) && $cache[$key] !== '') {
        return (string)$cache[$key];
    }

    // Default hardcoded fallbacks
    $fallbacks = [
        'insta_follower_count' => '17K+',
        'insta_handle'         => '@arigato.devan',
        'insta_url'            => 'https://www.instagram.com/arigato.devan/',
    ];

    return $fallbacks[$key] ?? $default;
}

function update_site_setting(string $key, string $value, ?PDO $pdo = null): bool
{
    // Update file cache
    $settings = load_site_settings_from_file();
    $settings[$key] = $value;
    save_site_settings_to_file($settings);

    // Update DB if PDO available
    if ($pdo === null) {
        global $pdo;
    }
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            ensure_site_settings_table($pdo);
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            return $stmt->execute([$key, $value]);
        } catch (Exception $e) {
            return false;
        }
    }

    return true;
}

/**
 * Returns dynamic Social Links & Contact Configuration with fallback defaults.
 */
function get_contact_social_config(): array
{
    $raw = site_setting('contact_social_config', '');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && isset($decoded['social_links']) && is_array($decoded['social_links'])) {
            return $decoded;
        }
    }

    $insta_url = site_setting('insta_url', 'https://www.instagram.com/arigato.devan/');
    $insta_handle = site_setting('insta_handle', '@arigato.devan');

    return [
        'profile_name'       => 'Arigato Devan',
        'profile_title'      => 'AI Prompt Creator & Digital Artist',
        'profile_handle'     => $insta_handle,
        'profile_handle_url' => $insta_url,
        'contact_email'      => 'devansh.grow@gmail.com',
        'response_time'      => 'Within 24 hours',
        'social_links'       => [
            [
                'id'       => 'instagram',
                'name'     => 'Instagram',
                'icon'     => 'fa-brands fa-instagram',
                'url'      => $insta_url,
                'enabled'  => true,
            ],
            [
                'id'       => 'email',
                'name'     => 'Email',
                'icon'     => 'fa-solid fa-envelope',
                'url'      => 'mailto:devansh.grow@gmail.com',
                'enabled'  => true,
            ],
            [
                'id'       => 'gallery',
                'name'     => 'Gallery',
                'icon'     => 'fa-solid fa-images',
                'url'      => 'gallery.php',
                'enabled'  => true,
            ],
            [
                'id'       => 'whatsapp',
                'name'     => 'WhatsApp',
                'icon'     => 'fa-brands fa-whatsapp',
                'url'      => '',
                'enabled'  => false,
            ],
            [
                'id'       => 'telegram',
                'name'     => 'Telegram',
                'icon'     => 'fa-brands fa-telegram',
                'url'      => '',
                'enabled'  => false,
            ],
            [
                'id'       => 'youtube',
                'name'     => 'YouTube',
                'icon'     => 'fa-brands fa-youtube',
                'url'      => '',
                'enabled'  => false,
            ],
            [
                'id'       => 'twitter',
                'name'     => 'X (Twitter)',
                'icon'     => 'fa-brands fa-x-twitter',
                'url'      => '',
                'enabled'  => false,
            ],
            [
                'id'       => 'pinterest',
                'name'     => 'Pinterest',
                'icon'     => 'fa-brands fa-pinterest-p',
                'url'      => '',
                'enabled'  => false,
            ],
            [
                'id'       => 'threads',
                'name'     => 'Threads',
                'icon'     => 'fa-brands fa-threads',
                'url'      => '',
                'enabled'  => false,
            ],
            [
                'id'       => 'discord',
                'name'     => 'Discord',
                'icon'     => 'fa-brands fa-discord',
                'url'      => '',
                'enabled'  => false,
            ],
        ],
    ];
}

/**
 * Saves contact & social links configuration.
 */
function save_contact_social_config(array $config, ?PDO $pdo = null): bool
{
    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return update_site_setting('contact_social_config', $json, $pdo);
}

