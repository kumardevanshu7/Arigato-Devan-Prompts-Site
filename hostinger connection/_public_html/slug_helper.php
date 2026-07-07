<?php
function makeSlug(string $title): string {
    $s = strtolower(trim($title));
    $s = preg_replace('/[^a-z0-9\s-]/', '', $s);
    $s = preg_replace('/[\s-]+/', '-', $s);
    return trim($s, '-') ?: 'prompt';
}

function uniqueSlug(PDO $pdo, string $title, ?int $excludeId = null): string {
    $base = makeSlug($title);
    $slug = $base;
    $i    = 2;
    while (true) {
        $sql = "SELECT id FROM prompts WHERE slug = ?" . ($excludeId !== null ? " AND id != ?" : "");
        $q   = $pdo->prepare($sql);
        $q->execute($excludeId !== null ? [$slug, $excludeId] : [$slug]);
        if (!$q->fetch()) break;
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

function uniqueNotMineSlug(PDO $pdo, string $title, ?int $excludeId = null): string
{
    $base = makeSlug($title);
    $slug = $base;
    $i = 2;
    while (true) {
        $sql = "SELECT id FROM not_mine_prompts WHERE slug = ?" . ($excludeId !== null ? " AND id != ?" : "");
        $q = $pdo->prepare($sql);
        $q->execute($excludeId !== null ? [$slug, $excludeId] : [$slug]);
        if (!$q->fetch()) {
            break;
        }
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

function nm_is_local(): bool
{
    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    if (in_array($host, ['localhost', '127.0.0.1', 'arigato.local'], true)) {
        return true;
    }
    return str_ends_with($host, '.local');
}

function nm_local_base(): string
{
    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    if ($host === 'arigato.local' || str_ends_with($host, '.local')) {
        return '';
    }
    return '/Arigato%20Development%20Site';
}

/** Public path for Not Mine prompt links (not-mine only — does not affect /prompts/). */
function nm_prompt_url(array $p): string
{
    $slug = trim($p['slug'] ?? '');
    if ($slug === '') {
        return 'not_mine_prompt.php?id=' . (int) ($p['id'] ?? 0);
    }
    if (nm_is_local()) {
        return nm_local_base() . '/not-mine/' . rawurlencode($slug);
    }
    return '/not-mine/' . rawurlencode($slug);
}

/** Canonical URL for Not Mine prompt pages only. */
function nm_prompt_canonical(array $p): string
{
    $slug = trim($p['slug'] ?? '');
    if ($slug === '') {
        return 'https://arigatodevan.com/not_mine_prompt.php?id=' . (int) ($p['id'] ?? 0);
    }
    return 'https://arigatodevan.com/not-mine/' . rawurlencode($slug);
}

/** Full shareable URL (for admin copy — uses current host). */
function nm_prompt_share_url(array $p): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'arigatodevan.com';
    $slug = trim($p['slug'] ?? '');
    if ($slug === '') {
        return $scheme . '://' . $host . '/not_mine_prompt.php?id=' . (int) ($p['id'] ?? 0);
    }
    return $scheme . '://' . $host . nm_prompt_url($p);
}
?>
