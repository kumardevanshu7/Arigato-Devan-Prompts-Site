<?php
/**
 * Landing orbit circles — slot layout + equal shuffle mapping.
 */

function heroines_orbit_slots(): array
{
    return [
        /* Left edge */
        ['top' => '10%', 'left' => '5%',   'size' => 88, 'opacity' => 1,    'bg' => '#F5D0E0', 'delay' => '0s',   'mobile' => true],
        ['top' => '6%',  'left' => '14%',  'size' => 56, 'opacity' => 0.8,  'bg' => '#FFD6E8', 'delay' => '0.5s', 'mobile_hide' => true],
        ['top' => '24%', 'left' => '8%',   'size' => 44, 'opacity' => 0.55, 'bg' => '#F0E6F5', 'delay' => '1.1s'],
        ['top' => '42%', 'left' => '6%',   'size' => 68, 'opacity' => 0.75, 'bg' => '#FFD6E8', 'delay' => '0.4s', 'mobile' => true],
        ['top' => '58%', 'left' => '12%',  'size' => 38, 'opacity' => 0.45, 'bg' => '#FFF4F8', 'delay' => '2s'],
        ['top' => '74%', 'left' => '7%',   'size' => 76, 'opacity' => 0.9,  'bg' => '#F0C4D8', 'delay' => '0.6s', 'mobile' => true],
        ['top' => '88%', 'left' => '14%',  'size' => 50, 'opacity' => 0.65, 'bg' => '#FFE4EC', 'delay' => '1s', 'mobile_hide' => true],
        /* Right edge */
        ['top' => '8%',  'left' => '92%',  'size' => 84, 'opacity' => 1,    'bg' => '#F5D0E8', 'delay' => '0.7s', 'mobile' => true],
        ['top' => '5%',  'left' => '82%',  'size' => 52, 'opacity' => 0.7,  'bg' => '#FFC9D8', 'delay' => '0.3s'],
        ['top' => '22%', 'left' => '90%',  'size' => 46, 'opacity' => 0.6,  'bg' => '#E8D4F0', 'delay' => '1.5s', 'mobile' => true],
        ['top' => '40%', 'left' => '93%',  'size' => 40, 'opacity' => 0.5,  'bg' => '#FFE8F0', 'delay' => '1.8s'],
        ['top' => '56%', 'left' => '86%',  'size' => 62, 'opacity' => 0.8,  'bg' => '#FFD6E8', 'delay' => '0.9s', 'mobile' => true],
        ['top' => '70%', 'left' => '91%',  'size' => 82, 'opacity' => 0.95, 'bg' => '#FAD4E4', 'delay' => '0.5s', 'mobile' => true],
        ['top' => '86%', 'left' => '84%',  'size' => 48, 'opacity' => 0.62, 'bg' => '#F5D0E0', 'delay' => '1.3s', 'mobile_hide' => true],
        /* Middle feature — spaced away from center */
        ['top' => '24%', 'left' => '22%', 'size' => 88, 'opacity' => 0.65, 'bg' => '#F5D0E0', 'delay' => '0.2s',  'zone' => 'mid-feature', 'mobile' => true],
        ['top' => '26%', 'left' => '78%', 'size' => 84, 'opacity' => 0.62, 'bg' => '#FFD6E8', 'delay' => '0.7s',  'zone' => 'mid-feature', 'mobile' => true],
        ['top' => '48%', 'left' => '16%', 'size' => 76, 'opacity' => 0.58, 'bg' => '#F0E6F5', 'delay' => '1.1s',  'zone' => 'mid-feature', 'mobile_hide' => true],
        ['top' => '46%', 'left' => '84%', 'size' => 80, 'opacity' => 0.6,  'bg' => '#FFE4EC', 'delay' => '0.9s',  'zone' => 'mid-feature', 'mobile_hide' => true],
        ['top' => '62%', 'left' => '24%', 'size' => 72, 'opacity' => 0.54, 'bg' => '#FAD4E4', 'delay' => '1.5s',  'zone' => 'mid-feature', 'mobile_hide' => true],
        ['top' => '60%', 'left' => '76%', 'size' => 74, 'opacity' => 0.56, 'bg' => '#FFF0F5', 'delay' => '1.3s',  'zone' => 'mid-feature', 'mobile_hide' => true],
        ['top' => '18%', 'left' => '38%', 'size' => 72,  'opacity' => 0.48, 'bg' => '#E8D4F0', 'delay' => '0.4s',  'zone' => 'mid-feature', 'mobile_hide' => true],
        ['top' => '20%', 'left' => '62%', 'size' => 76,  'opacity' => 0.5,  'bg' => '#FFD6E8', 'delay' => '1.0s',  'zone' => 'mid-feature', 'mobile_hide' => true],
        ['top' => '70%', 'left' => '40%', 'size' => 68,  'opacity' => 0.46, 'bg' => '#FFE8F0', 'delay' => '1.7s',  'zone' => 'mid-feature', 'mobile_hide' => true],
        ['top' => '72%', 'left' => '60%', 'size' => 72,  'opacity' => 0.48, 'bg' => '#F5D0E8', 'delay' => '2.0s',  'zone' => 'mid-feature', 'mobile_hide' => true],
        /* Top middle band */
        ['top' => '5%',  'left' => '28%',  'size' => 52, 'opacity' => 0.5,  'bg' => '#FFE4EC', 'delay' => '1.4s', 'mobile_hide' => true],
        ['top' => '7%',  'left' => '40%',  'size' => 48, 'opacity' => 0.45, 'bg' => '#FFF0F5', 'delay' => '0.8s'],
        ['top' => '4%',  'left' => '52%',  'size' => 46, 'opacity' => 0.42, 'bg' => '#FAD4E4', 'delay' => '1.2s'],
        ['top' => '6%',  'left' => '64%',  'size' => 50, 'opacity' => 0.48, 'bg' => '#FFE4EC', 'delay' => '1.6s', 'mobile_hide' => true],
        ['top' => '8%',  'left' => '76%',  'size' => 54, 'opacity' => 0.52, 'bg' => '#E8D4F0', 'delay' => '1.9s', 'mobile_hide' => true],
        /* Bottom middle band */
        ['top' => '88%', 'left' => '26%',  'size' => 54, 'opacity' => 0.5,  'bg' => '#F8E8F0', 'delay' => '1.6s', 'mobile_hide' => true],
        ['top' => '90%', 'left' => '38%',  'size' => 48, 'opacity' => 0.44, 'bg' => '#FFC9D8', 'delay' => '2.2s'],
        ['top' => '86%', 'left' => '50%',  'size' => 52, 'opacity' => 0.46, 'bg' => '#FFD6E8', 'delay' => '0.9s', 'mobile_hide' => true],
        ['top' => '91%', 'left' => '62%',  'size' => 50, 'opacity' => 0.48, 'bg' => '#FFE4EC', 'delay' => '1.1s', 'mobile_hide' => true],
        ['top' => '84%', 'left' => '74%',  'size' => 56, 'opacity' => 0.52, 'bg' => '#F5D0E0', 'delay' => '1.7s', 'mobile' => true],
        /* Upper-inner sides */
        ['top' => '18%', 'left' => '20%',  'size' => 58, 'opacity' => 0.48, 'bg' => '#FFF0F5', 'delay' => '2.1s', 'mobile_hide' => true],
        ['top' => '16%', 'left' => '80%',  'size' => 62, 'opacity' => 0.5,  'bg' => '#FFD6E8', 'delay' => '1.8s', 'mobile_hide' => true],
        /* Lower-inner sides */
        ['top' => '72%', 'left' => '22%',  'size' => 56, 'opacity' => 0.46, 'bg' => '#E8D4F0', 'delay' => '2.3s', 'mobile_hide' => true],
        ['top' => '74%', 'left' => '78%',  'size' => 60, 'opacity' => 0.48, 'bg' => '#FFE8F0', 'delay' => '2s', 'mobile_hide' => true],
        /* Far corners small */
        ['top' => '32%', 'left' => '3%',   'size' => 32, 'opacity' => 0.35, 'bg' => '#D4F5F0', 'delay' => '2.4s'],
        ['top' => '48%', 'left' => '95%',  'size' => 30, 'opacity' => 0.32, 'bg' => '#FFF0F5', 'delay' => '2.5s'],
        ['top' => '65%', 'left' => '4%',   'size' => 28, 'opacity' => 0.3,  'bg' => '#FFE4EC', 'delay' => '2.6s'],
        ['top' => '35%', 'left' => '94%',  'size' => 34, 'opacity' => 0.36, 'bg' => '#F0E6F5', 'delay' => '2.7s'],
    ];
}

function heroines_orbit_slot_count(): int
{
    return count(heroines_orbit_slots());
}

/** Mobile-only: 10 fixed, well-spaced circle positions (no overlap with center text). */
function heroines_orbit_mobile_slots(): array
{
    return [
        ['top' => '11%', 'left' => '11%', 'size' => 64, 'opacity' => 0.92, 'bg' => '#F5D0E0', 'delay' => '0s'],
        ['top' => '11%', 'left' => '89%', 'size' => 64, 'opacity' => 0.92, 'bg' => '#FFD6E8', 'delay' => '0.4s'],
        ['top' => '24%', 'left' => '6%',  'size' => 56, 'opacity' => 0.85, 'bg' => '#F0E6F5', 'delay' => '0.8s'],
        ['top' => '24%', 'left' => '94%', 'size' => 56, 'opacity' => 0.85, 'bg' => '#FFE4EC', 'delay' => '1.1s'],
        ['top' => '46%', 'left' => '9%',  'size' => 58, 'opacity' => 0.88, 'bg' => '#FFD6E8', 'delay' => '0.5s'],
        ['top' => '46%', 'left' => '91%', 'size' => 58, 'opacity' => 0.88, 'bg' => '#FAD4E4', 'delay' => '0.9s'],
        ['top' => '68%', 'left' => '10%', 'size' => 60, 'opacity' => 0.9,  'bg' => '#F0C4D8', 'delay' => '1.3s'],
        ['top' => '68%', 'left' => '90%', 'size' => 60, 'opacity' => 0.9,  'bg' => '#FFF0F5', 'delay' => '1.6s'],
        ['top' => '84%', 'left' => '28%', 'size' => 54, 'opacity' => 0.86, 'bg' => '#FFE8F0', 'delay' => '1.9s'],
        ['top' => '84%', 'left' => '72%', 'size' => 54, 'opacity' => 0.86, 'bg' => '#F5D0E8', 'delay' => '2.2s'],
    ];
}

/** Tablet: ~14 spaced circles between mobile density and full laptop layout. */
function heroines_orbit_tablet_slots(): array
{
    return [
        ['top' => '10%', 'left' => '9%',  'size' => 72, 'opacity' => 0.9,  'bg' => '#F5D0E0', 'delay' => '0s'],
        ['top' => '10%', 'left' => '91%', 'size' => 72, 'opacity' => 0.9,  'bg' => '#FFD6E8', 'delay' => '0.4s'],
        ['top' => '22%', 'left' => '5%',  'size' => 58, 'opacity' => 0.82, 'bg' => '#F0E6F5', 'delay' => '0.7s'],
        ['top' => '22%', 'left' => '95%', 'size' => 58, 'opacity' => 0.82, 'bg' => '#FFE4EC', 'delay' => '1s'],
        ['top' => '38%', 'left' => '8%',  'size' => 64, 'opacity' => 0.85, 'bg' => '#FFD6E8', 'delay' => '0.5s'],
        ['top' => '38%', 'left' => '92%', 'size' => 64, 'opacity' => 0.85, 'bg' => '#FAD4E4', 'delay' => '0.9s'],
        ['top' => '52%', 'left' => '12%', 'size' => 60, 'opacity' => 0.8,  'bg' => '#FFF0F5', 'delay' => '1.2s'],
        ['top' => '52%', 'left' => '88%', 'size' => 60, 'opacity' => 0.8,  'bg' => '#F5D0E8', 'delay' => '1.5s'],
        ['top' => '66%', 'left' => '7%',  'size' => 66, 'opacity' => 0.88, 'bg' => '#F0C4D8', 'delay' => '0.6s'],
        ['top' => '66%', 'left' => '93%', 'size' => 66, 'opacity' => 0.88, 'bg' => '#FFE8F0', 'delay' => '1.8s'],
        ['top' => '80%', 'left' => '18%', 'size' => 56, 'opacity' => 0.84, 'bg' => '#FFE4EC', 'delay' => '2s'],
        ['top' => '80%', 'left' => '82%', 'size' => 56, 'opacity' => 0.84, 'bg' => '#FFD6E8', 'delay' => '2.3s'],
        ['top' => '88%', 'left' => '38%', 'size' => 52, 'opacity' => 0.78, 'bg' => '#F5D0E0', 'delay' => '1.1s'],
        ['top' => '88%', 'left' => '62%', 'size' => 52, 'opacity' => 0.78, 'bg' => '#F0E6F5', 'delay' => '1.4s'],
    ];
}

/** @return 'laptop'|'tablet'|'mobile' */
function heroines_orbit_viewport_keys(): array
{
    return ['laptop', 'tablet', 'mobile'];
}

function heroines_orbit_default_slots(string $viewport): array
{
    return match ($viewport) {
        'mobile' => heroines_orbit_mobile_slots(),
        'tablet' => heroines_orbit_tablet_slots(),
        default  => heroines_orbit_slots(),
    };
}

function heroines_layout_setting_key(string $viewport): string
{
    return 'orbit_layout_' . $viewport;
}

function heroines_load_viewport_layout(PDO $pdo, string $viewport): ?array
{
    if (!in_array($viewport, heroines_orbit_viewport_keys(), true)) {
        return null;
    }
    $json = heroines_get_setting($pdo, heroines_layout_setting_key($viewport));
    if ($json === null || $json === '') {
        return null;
    }
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return null;
    }
    $out = [];
    foreach ($data as $row) {
        if (!is_array($row)) {
            continue;
        }
        $circle = heroines_sanitize_layout_circle($row);
        if ($circle !== null) {
            $out[] = $circle;
        }
    }
    return $out === [] ? null : $out;
}

function heroines_has_custom_layout(PDO $pdo, string $viewport): bool
{
    return heroines_load_viewport_layout($pdo, $viewport) !== null;
}

/**
 * @param array<string, mixed> $raw
 * @return array<string, mixed>|null
 */
function heroines_sanitize_layout_circle(array $raw): ?array
{
    $left = isset($raw['left']) ? (float) str_replace('%', '', (string) $raw['left']) : null;
    $top  = isset($raw['top']) ? (float) str_replace('%', '', (string) $raw['top']) : null;
    if ($left === null || $top === null) {
        return null;
    }

    $left = max(0, min(100, $left));
    $top  = max(0, min(100, $top));
    $size = max(24, min(180, (int) ($raw['size'] ?? 56)));
    $opacity = max(0.15, min(1, (float) ($raw['opacity'] ?? 0.85)));
    $rotate  = max(0, min(360, (int) round((float) ($raw['rotate'] ?? 0))));
    $bg = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($raw['bg'] ?? '')) ? $raw['bg'] : '#FFE4EC';
    $delay = is_numeric(str_replace('s', '', (string) ($raw['delay'] ?? '0'))) ? ($raw['delay'] ?? '0s') : '0s';
    $heroineId = isset($raw['heroine_id']) && $raw['heroine_id'] !== '' && $raw['heroine_id'] !== null
        ? max(0, (int) $raw['heroine_id'])
        : 0;

    $circle = [
        'left'    => $left . '%',
        'top'     => $top . '%',
        'size'    => $size,
        'opacity' => round($opacity, 2),
        'rotate'  => $rotate,
        'bg'      => $bg,
        'delay'   => is_string($delay) && str_ends_with($delay, 's') ? $delay : ((float) $delay . 's'),
    ];
    if ($heroineId > 0) {
        $circle['heroine_id'] = $heroineId;
    }
    if (!empty($raw['uid']) && is_string($raw['uid'])) {
        $circle['uid'] = preg_replace('/[^a-zA-Z0-9_-]/', '', $raw['uid']);
    }
    return $circle;
}

/**
 * @param array<int, array<string, mixed>> $circles
 */
function heroines_save_viewport_layout(PDO $pdo, string $viewport, array $circles): void
{
    if (!in_array($viewport, heroines_orbit_viewport_keys(), true)) {
        return;
    }
    $clean = [];
    foreach ($circles as $row) {
        if (!is_array($row)) {
            continue;
        }
        $circle = heroines_sanitize_layout_circle($row);
        if ($circle !== null) {
            $clean[] = $circle;
        }
    }
    heroines_set_setting($pdo, heroines_layout_setting_key($viewport), json_encode($clean));
    heroines_set_setting($pdo, 'orbit_layout_custom', '1');
}

function heroines_clear_viewport_layout(PDO $pdo, ?string $viewport = null): void
{
    if ($viewport === null) {
        foreach (heroines_orbit_viewport_keys() as $vp) {
            $pdo->prepare('DELETE FROM heroine_settings WHERE setting_key = ?')->execute([heroines_layout_setting_key($vp)]);
        }
        $pdo->exec("DELETE FROM heroine_settings WHERE setting_key = 'orbit_layout_custom'");
        return;
    }
    if (in_array($viewport, heroines_orbit_viewport_keys(), true)) {
        $pdo->prepare('DELETE FROM heroine_settings WHERE setting_key = ?')->execute([heroines_layout_setting_key($viewport)]);
    }
    $any = false;
    foreach (heroines_orbit_viewport_keys() as $vp) {
        if (heroines_load_viewport_layout($pdo, $vp) !== null) {
            $any = true;
            break;
        }
    }
    if (!$any) {
        $pdo->exec("DELETE FROM heroine_settings WHERE setting_key = 'orbit_layout_custom'");
    }
}

/**
 * Slots for a viewport — custom saved layout or built-in defaults.
 *
 * @return array<int, array<string, mixed>>
 */
function heroines_resolve_viewport_slots(PDO $pdo, string $viewport): array
{
    $custom = heroines_load_viewport_layout($pdo, $viewport);
    if ($custom !== null) {
        return $custom;
    }
    return heroines_orbit_default_slots($viewport);
}

/**
 * @param array<int, array<string, mixed>> $heroines
 */
function heroines_heroine_for_layout_slot(array $heroines, array $slot, int $slotIndex, ?array $orbitMap): ?array
{
    $byId = [];
    foreach ($heroines as $h) {
        $byId[(int) $h['id']] = $h;
    }

    if (!empty($slot['heroine_id'])) {
        $id = (int) $slot['heroine_id'];
        return $byId[$id] ?? null;
    }

    if ($orbitMap !== null && isset($orbitMap[$slotIndex])) {
        $id = (int) $orbitMap[$slotIndex];
        return $byId[$id] ?? null;
    }

    return $heroines[$slotIndex] ?? null;
}

/**
 * Editor bootstrap: merge defaults with auto-assigned heroine IDs when no custom layout.
 *
 * @param array<int, array<string, mixed>> $heroines
 * @return array<string, array<int, array<string, mixed>>>
 */
function heroines_editor_bootstrap_layouts(PDO $pdo, array $heroines): array
{
    $active = [];
    foreach ($heroines as $h) {
        if (!empty($h['is_active']) && !empty($h['circle_image'])) {
            $active[] = $h;
        }
    }

    $out = [];
    foreach (heroines_orbit_viewport_keys() as $viewport) {
        $custom = heroines_load_viewport_layout($pdo, $viewport);
        if ($custom !== null) {
            $out[$viewport] = $custom;
            continue;
        }

        $slots = heroines_orbit_default_slots($viewport);
        $map = heroines_build_equal_shuffle_map($active, count($slots), 'editor-' . $viewport);
        foreach ($slots as $i => &$slot) {
            if (!empty($map[$i])) {
                $slot['heroine_id'] = (int) $map[$i];
            }
            $slot['uid'] = 'c' . ($i + 1);
        }
        unset($slot);
        $out[$viewport] = $slots;
    }
    return $out;
}

function heroines_orbit_zone(string $left, string $top): string
{
    $l = (float) str_replace('%', '', $left);
    $t = (float) str_replace('%', '', $top);
    if ($l < 20) {
        return 'orbit-zone-left';
    }
    if ($l > 80) {
        return 'orbit-zone-right';
    }
    if ($t < 14 || $t > 68) {
        return 'orbit-zone-mid-band';
    }
    return 'orbit-zone-mid-inner';
}

function heroines_get_setting(PDO $pdo, string $key, ?string $default = null): ?string
{
    $stmt = $pdo->prepare('SELECT setting_value FROM heroine_settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return $val === false ? $default : (string) $val;
}

function heroines_set_setting(PDO $pdo, string $key, string $value): void
{
    $pdo->prepare(
        'INSERT INTO heroine_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    )->execute([$key, $value]);
}

function heroines_orbit_fill_mode(PDO $pdo): string
{
    return heroines_get_setting($pdo, 'orbit_fill_mode', 'sequential') ?: 'sequential';
}

function heroines_load_orbit_map(PDO $pdo): ?array
{
    if (heroines_orbit_fill_mode($pdo) !== 'equal_shuffle') {
        return null;
    }
    $json = heroines_get_setting($pdo, 'orbit_equal_map');
    if ($json === null || $json === '') {
        return null;
    }
    $map = json_decode($json, true);
    return is_array($map) ? array_values($map) : null;
}

function heroines_save_orbit_map(PDO $pdo, array $map): void
{
    heroines_set_setting($pdo, 'orbit_fill_mode', 'equal_shuffle');
    heroines_set_setting($pdo, 'orbit_equal_map', json_encode(array_values($map)));
}

/**
 * Equally assign photos to every circle on laptop, tablet & mobile.
 * Keeps positions / size / rotation — only updates heroine_id on each circle.
 *
 * @param array<int, array<string, mixed>> $active
 * @return array{laptop: int, tablet: int, mobile: int, pic_count: int}
 */
function heroines_redistribute_landing_photos(PDO $pdo, array $active, bool $randomShuffle = true): array
{
    $stats = ['laptop' => 0, 'tablet' => 0, 'mobile' => 0, 'pic_count' => count($active)];

    if ($active === []) {
        return $stats;
    }

    $ids = array_map(static fn($h) => (int) $h['id'], $active);
    sort($ids);
    $idsKey = implode('.', $ids);

    foreach (heroines_orbit_viewport_keys() as $viewport) {
        $slots = heroines_resolve_viewport_slots($pdo, $viewport);
        $slotCount = count($slots);
        if ($slotCount === 0) {
            continue;
        }

        $seed = $randomShuffle
            ? 'shuffle-' . $viewport . '-' . uniqid('', true)
            : 'stable-' . $viewport . '-' . $idsKey;

        $map = heroines_build_equal_shuffle_map($active, $slotCount, $seed);

        foreach ($slots as $i => &$slot) {
            if (isset($map[$i])) {
                $slot['heroine_id'] = (int) $map[$i];
            } else {
                unset($slot['heroine_id']);
            }
            if (empty($slot['uid'])) {
                $slot['uid'] = 'c' . ($i + 1);
            }
        }
        unset($slot);

        heroines_save_viewport_layout($pdo, $viewport, $slots);
        $stats[$viewport] = $slotCount;
    }

    $laptopSlots = heroines_resolve_viewport_slots($pdo, 'laptop');
    $laptopMap = heroines_build_equal_shuffle_map(
        $active,
        count($laptopSlots),
        $randomShuffle ? 'shuffle-map-' . uniqid('', true) : 'stable-map-' . $idsKey
    );
    heroines_save_orbit_map($pdo, $laptopMap);

    return $stats;
}

function heroines_clear_orbit_map(PDO $pdo): void
{
    $pdo->exec("DELETE FROM heroine_settings WHERE setting_key IN ('orbit_fill_mode', 'orbit_equal_map')");
}

/**
 * Build slot list of heroine IDs — each photo appears equally, order shuffled.
 *
 * @param array<int, array<string, mixed>> $heroines
 * @return array<int, int>
 */
function heroines_build_equal_shuffle_map(array $heroines, int $slotCount, ?string $seed = null): array
{
    $ids = [];
    foreach ($heroines as $h) {
        if (!empty($h['circle_image'])) {
            $ids[] = (int) $h['id'];
        }
    }
    $ids = array_values(array_unique($ids));
    $count = count($ids);
    if ($count === 0 || $slotCount <= 0) {
        return [];
    }

    $base = intdiv($slotCount, $count);
    $remainder = $slotCount % $count;
    $pool = [];

    foreach ($ids as $idx => $id) {
        $times = $base + ($idx < $remainder ? 1 : 0);
        for ($n = 0; $n < $times; $n++) {
            $pool[] = $id;
        }
    }

    if ($seed !== null) {
        mt_srand(crc32($seed));
    }
    shuffle($pool);
    if ($seed !== null) {
        mt_srand();
    }

    return $pool;
}

function heroines_orbit_distribution_summary(array $map): array
{
    $counts = [];
    foreach ($map as $id) {
        $id = (int) $id;
        $counts[$id] = ($counts[$id] ?? 0) + 1;
    }
    return $counts;
}

/**
 * @param array<int, array<string, mixed>> $heroines
 */
function heroines_orbit_item_style(array $slot): string
{
    $left = (float) str_replace('%', '', $slot['left']);
    $top  = (float) str_replace('%', '', $slot['top']);
    $size = (int) $slot['size'];
    $rotate = max(0, min(360, (int) ($slot['rotate'] ?? 0)));

    return sprintf(
        '--ol:%s;--ot:%s;--os:%d;--orbit-rotate:%ddeg;left:%s%%;top:%s%%;width:%dpx;height:%dpx;opacity:%s;animation-delay:%s;--orbit-bg:%s',
        $left,
        $top,
        $size,
        $rotate,
        $left,
        $top,
        $size,
        $size,
        $slot['opacity'],
        $slot['delay'],
        $slot['bg']
    );
}

function heroines_orbit_mobile_on(array $slot, bool $has_img, ?array $orbit_map): bool
{
    if (!empty($slot['mobile_hide'])) {
        return false;
    }
    return $has_img;
}

/**
 * Saved admin shuffle, or auto equal-fill so every circle gets a photo.
 *
 * @param array<int, array<string, mixed>> $heroines
 * @return array<int, int>|null
 */
function heroines_resolve_orbit_map(PDO $pdo, array $heroines, int $slotCount): ?array
{
    $eligible = [];
    foreach ($heroines as $h) {
        if (!empty($h['circle_image'])) {
            $eligible[] = $h;
        }
    }
    if ($eligible === [] || $slotCount <= 0) {
        return null;
    }

    $saved = heroines_load_orbit_map($pdo);
    if ($saved !== null) {
        if (count($saved) === $slotCount) {
            return $saved;
        }
        $ids = array_map(static fn($h) => (int) $h['id'], $eligible);
        sort($ids);
        return heroines_build_equal_shuffle_map($eligible, $slotCount, 'map-fallback-' . $slotCount . '-' . implode('.', $ids));
    }

    $ids = array_map(static fn($h) => (int) $h['id'], $eligible);
    sort($ids);
    $seed = 'heroines-auto-' . $slotCount . '-' . implode('.', $ids);

    return heroines_build_equal_shuffle_map($eligible, $slotCount, $seed);
}

/**
 * Equal-fill photos for the 10 mobile circle slots.
 *
 * @param array<int, array<string, mixed>> $heroines
 * @return array<int, array<string, mixed>|null>
 */
function heroines_mobile_slot_heroines(array $heroines): array
{
    $slots = heroines_orbit_mobile_slots();
    $eligible = [];
    foreach ($heroines as $h) {
        if (!empty($h['circle_image'])) {
            $eligible[] = $h;
        }
    }
    if ($eligible === []) {
        return array_fill(0, count($slots), null);
    }

    $ids = array_map(static fn($h) => (int) $h['id'], $eligible);
    sort($ids);
    $idPool = heroines_build_equal_shuffle_map($eligible, count($slots), 'heroines-mobile-' . implode('.', $ids));

    $byId = [];
    foreach ($heroines as $h) {
        $byId[(int) $h['id']] = $h;
    }

    $out = [];
    foreach ($idPool as $id) {
        $out[] = $byId[(int) $id] ?? null;
    }
    return $out;
}

/**
 * @param array<int, int>|null $map
 * @param array<int, array<string, mixed>> $heroines
 */
function heroines_heroine_for_orbit_slot(array $heroines, ?array $map, int $slotIndex): ?array
{
    $byId = [];
    foreach ($heroines as $h) {
        $byId[(int) $h['id']] = $h;
    }

    if ($map !== null && isset($map[$slotIndex])) {
        $id = (int) $map[$slotIndex];
        return $byId[$id] ?? null;
    }

    return $heroines[$slotIndex] ?? null;
}
