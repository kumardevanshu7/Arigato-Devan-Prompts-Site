<?php
/**
 * Gallery banner slides + trending prompt queries.
 */

function gallery_banner_slides(): array {
    global $pdo;

    // Prefer admin-managed carousel rows when the table exists and has active slides.
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            $rows = $pdo->query(
                "SELECT image_path, alt_text FROM gallery_carousel WHERE is_active = 1 ORDER BY sort_order ASC, id ASC"
            )->fetchAll(PDO::FETCH_ASSOC);
            $slides = [];
            foreach ($rows as $row) {
                $path = trim((string) ($row['image_path'] ?? ''));
                if ($path === '' || !is_file(__DIR__ . '/../' . ltrim($path, '/'))) {
                    continue;
                }
                $alt = trim((string) ($row['alt_text'] ?? ''));
                $slides[] = [
                    'image'    => $path,
                    'alt'      => $alt !== '' ? $alt : 'Gallery banner',
                    'title'    => $alt !== '' ? $alt : 'Featured Prompt',
                    'subtitle' => 'Discover viral AI prompts',
                    'cta'      => 'Explore Gallery',
                    'href'     => '#card-stack',
                ];
            }
            if (!empty($slides)) {
                return $slides;
            }
        } catch (Throwable $e) {
            // Fall through to legacy folder scan.
        }
    }

    $dir = __DIR__ . '/../banner';
    $slides = [];
    $exts = ['webp', 'jpg', 'jpeg', 'png'];
    if (is_dir($dir)) {
        $files = scandir($dir) ?: [];
        natsort($files);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.gitkeep') {
                continue;
            }
            $lower = strtolower($file);
            $ok = false;
            foreach ($exts as $ext) {
                if (str_ends_with($lower, '.' . $ext)) {
                    $ok = true;
                    break;
                }
            }
            if ($ok) {
                $slides[] = [
                    'image' => 'banner/' . $file,
                    'title' => 'Featured Prompt',
                    'subtitle' => 'Discover viral AI couple prompts',
                    'cta' => 'Explore Gallery',
                    'href' => '#card-stack',
                ];
            }
        }
    }
    if (!empty($slides)) {
        return $slides;
    }
    // Placeholders until admin adds images
    return [
        [
            'image' => '',
            'title' => 'Viral Couple Prompts',
            'subtitle' => 'Unlock premium AI prompts — trending on Instagram',
            'cta' => 'Browse Now',
            'href' => '#card-stack',
            'gradient' => 'linear-gradient(135deg, #6D2D52 0%, #2F4156 40%, #567C8D 100%)',
        ],
        [
            'image' => '',
            'title' => 'Golden Hour Aesthetic',
            'subtitle' => 'New drops every week — tap to unlock',
            'cta' => 'See Trending',
            'href' => '#gal-trending',
            'gradient' => 'linear-gradient(135deg, #F5709D 0%, #11FFC9 50%, #2FA6C6 100%)',
        ],
        [
            'image' => '',
            'title' => 'Secret Code Reels',
            'subtitle' => 'Watch the reel, grab the code, unlock the prompt',
            'cta' => 'Get Started',
            'href' => 'secret_code.php',
            'gradient' => 'linear-gradient(135deg, #204162 0%, #567C8D 50%, #11FFC9 100%)',
        ],
    ];
}

function fetch_trending_prompts(PDO $pdo, ?int $user_id = null, int $limit = 12): array {
    $published = "(p.is_trial = 0 OR p.is_trial IS NULL)";
    if ($user_id) {
        $sql = "SELECT p.*, IF(u.id IS NOT NULL, 1, 0) as is_unlocked,
                       IF(l.id IS NOT NULL, 1, 0) as is_liked,
                       IF(sv.id IS NOT NULL, 1, 0) as is_saved
                FROM prompts p
                LEFT JOIN unlocked_prompts u ON p.id = u.prompt_id AND u.user_id = ?
                LEFT JOIN likes l ON p.id = l.prompt_id AND l.user_id = ?
                LEFT JOIN saved_prompts sv ON p.id = sv.prompt_id AND sv.user_id = ?
                WHERE {$published} AND p.is_trending = 1
                ORDER BY p.trending_order DESC, p.likes_count DESC, p.created_at DESC
                LIMIT {$limit}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $user_id, $user_id]);
    } else {
        $sql = "SELECT *, 0 as is_unlocked, 0 as is_liked, 0 as is_saved
                FROM prompts p
                WHERE {$published} AND p.is_trending = 1
                ORDER BY p.trending_order DESC, p.likes_count DESC, p.created_at DESC
                LIMIT {$limit}";
        $stmt = $pdo->query($sql);
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * @return array{prompts: array, total: int, total_pages: int, page: int, tag_filter: string}
 */
function gallery_fetch_prompts(PDO $pdo, ?int $user_id, array $opts = []): array {
    $page       = max(1, (int) ($opts['page'] ?? 1));
    $per_page   = max(1, (int) ($opts['per_page'] ?? 20));
    $tag_filter = trim(strtolower($opts['tag'] ?? ''));
    $tag_param  = ($tag_filter && $tag_filter !== 'all') ? '%' . addcslashes($tag_filter, '%_') . '%' : null;
    $offset     = ($page - 1) * $per_page;
    $tag_where  = $tag_param ? ' AND LOWER(tag) LIKE ?' : '';

    $count_sql  = $tag_param
        ? 'SELECT COUNT(*) FROM prompts WHERE (is_trial = 0 OR is_trial IS NULL) AND LOWER(tag) LIKE ?'
        : 'SELECT COUNT(*) FROM prompts WHERE (is_trial = 0 OR is_trial IS NULL)';
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($tag_param ? [$tag_param] : []);
    $total       = (int) $count_stmt->fetchColumn();
    $total_pages = max(1, (int) ceil($total / $per_page));

    if ($user_id) {
        $sql = "SELECT p.*, IF(u.id IS NOT NULL, 1, 0) as is_unlocked,
                   IF(l.id IS NOT NULL, 1, 0) as is_liked,
                   IF(sv.id IS NOT NULL, 1, 0) as is_saved
            FROM prompts p
            LEFT JOIN unlocked_prompts u ON p.id = u.prompt_id AND u.user_id = ?
            LEFT JOIN likes l ON p.id = l.prompt_id AND l.user_id = ?
            LEFT JOIN saved_prompts sv ON p.id = sv.prompt_id AND sv.user_id = ?
            WHERE (p.is_trial = 0 OR p.is_trial IS NULL){$tag_where}
            ORDER BY p.created_at DESC LIMIT {$per_page} OFFSET {$offset}";
        $params = [$user_id, $user_id, $user_id];
        if ($tag_param) {
            $params[] = $tag_param;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        $sql = "SELECT *, 0 as is_unlocked, 0 as is_liked, 0 as is_saved
            FROM prompts WHERE (is_trial = 0 OR is_trial IS NULL){$tag_where}
            ORDER BY created_at DESC LIMIT {$per_page} OFFSET {$offset}";
        $params = [];
        if ($tag_param) {
            $params[] = $tag_param;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    return [
        'prompts'     => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total'       => $total,
        'total_pages' => $total_pages,
        'page'        => $page,
        'tag_filter'  => $tag_filter,
    ];
}

function render_gallery_prompt_cards(array $prompts): string {
    if (!function_exists('prompt_resolve_type')) {
        require_once __DIR__ . '/prompt_cards.php';
    }
    ob_start();
    foreach ($prompts as $p) {
        $db_type  = $p['prompt_type'] ?? 'secret';
        $type     = prompt_resolve_type($db_type);
        $ptype    = $type['ptype'];
        $tinfo    = ['label' => $type['label'], 'cls' => $type['cls']];
        $tags_arr = array_map('trim', explode(',', strtolower($p['tag'] ?? '')));
        $blur_style = ($ptype === 'unreleased' && empty($p['is_unlocked'])) ? 'filter:blur(5px);transform:scale(1.05);' : '';
        ?>
        <div class="product-card prompt-card skeleton"
             data-id="<?= (int) $p['id'] ?>"
             data-slug="<?= htmlspecialchars($p['slug'] ?? '') ?>"
             data-created="<?= htmlspecialchars($p['created_at'] ?? '') ?>"
             data-image="<?= htmlspecialchars($p['image_path']) ?>"
             data-title="<?= htmlspecialchars($p['title']) ?>"
             data-reel="<?= htmlspecialchars($p['reel_link'] ?? '') ?>"
             data-unlocked="<?= !empty($p['is_unlocked']) ? 'true' : 'false' ?>"
             data-saved="<?= !empty($p['is_saved']) ? 'true' : 'false' ?>"
             data-prompt-type="<?= htmlspecialchars($ptype) ?>"
             data-tags="<?= htmlspecialchars(implode(',', $tags_arr)) ?>"
             data-best-works-in="<?= htmlspecialchars($p['best_works_in'] ?? '') ?>"
             data-asset-title="<?= htmlspecialchars($p['asset_title'] ?? '') ?>"
             data-asset-images="<?= htmlspecialchars($p['asset_images'] ?? '[]') ?>"
             <?= !empty($p['is_unlocked']) ? 'data-prompt-text="' . htmlspecialchars($p['prompt_text']) . '"' : '' ?>>
            <div class="card-image-wrap">
                <img loading="lazy"
                     src="<?= htmlspecialchars($p['image_path']) ?>"
                     class="skeleton-img"
                     alt="<?= htmlspecialchars($p['title']) ?>"
                     style="<?= $blur_style ?>">
                <span class="card-badge <?= $tinfo['cls'] ?>"><?= $tinfo['label'] ?></span>
                <?php if (empty($p['is_unlocked'])): ?>
                    <div class="card-lock-icon"><i class="fa-solid fa-lock"></i></div>
                <?php else: ?>
                    <div class="card-lock-icon unlocked"><i class="fa-solid fa-check"></i></div>
                <?php endif; ?>
                <div class="card-overlay">
                    <span class="quick-view-btn">View Prompt &rarr;</span>
                </div>
            </div>
            <div class="card-info">
                <p class="card-title"><?= htmlspecialchars($p['title']) ?></p>
                <div class="card-like-display"
                     data-liked="<?= !empty($p['is_liked']) ? 'true' : 'false' ?>"
                     data-prompt-id="<?= (int) $p['id'] ?>">
                    <i class="fa-solid fa-heart <?= !empty($p['is_liked']) ? 'liked-heart' : '' ?>"></i>
                    <span class="like-count"><?= (int) ($p['likes_count'] ?? 0) ?></span>
                </div>
            </div>
        </div>
        <?php
    }
    return (string) ob_get_clean();
}
