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
?>
