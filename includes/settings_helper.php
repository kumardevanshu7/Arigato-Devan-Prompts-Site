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
