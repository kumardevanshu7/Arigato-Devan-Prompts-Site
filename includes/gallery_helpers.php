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
 * @return array{prompts: array, total: int, total_pages: int, page: int, tag_filter: string, search_q: string}
 */
function gallery_fetch_prompts(PDO $pdo, ?int $user_id, array $opts = []): array {
    $page         = max(1, (int) ($opts['page'] ?? 1));
    $per_page     = max(1, (int) ($opts['per_page'] ?? 20));
    $tag_filter   = trim(strtolower($opts['tag'] ?? ''));
    $search_query = trim((string) ($opts['q'] ?? $opts['search'] ?? ''));

    $where_clauses = ["(p.is_trial = 0 OR p.is_trial IS NULL)"];
    $where_params  = [];

    if ($tag_filter !== '' && $tag_filter !== 'all') {
        $where_clauses[] = "LOWER(p.tag) LIKE ?";
        $where_params[]  = '%' . addcslashes($tag_filter, '%_') . '%';
    }

    if ($search_query !== '') {
        $clean_q   = strtolower($search_query);
        $all_words = array_values(array_filter(preg_split('/\s+/', $clean_q)));

        // Common filler words in AI prompt keywords that shouldn't block finding matches
        $stop_words = [
            'ai', 'prompt', 'prompts', 'photo', 'photos', 'image', 'images',
            'free', 'download', 'copy', 'paste', 'for', 'in', 'and', 'with',
            'the', 'a', 'an', 'of', 'to', 'on', 'at', 'by'
        ];

        // Significant distinctive words
        $sig_words = array_values(array_filter($all_words, function($w) use ($stop_words) {
            return !in_array($w, $stop_words, true) && mb_strlen($w) > 1;
        }));

        $match_words = !empty($sig_words) ? $sig_words : $all_words;

        if (count($all_words) > 1) {
            $phrase_param = '%' . addcslashes($clean_q, '%_') . '%';
            $word_clauses = [];
            $word_params  = [];
            foreach ($match_words as $w) {
                $wp = '%' . addcslashes($w, '%_') . '%';
                $word_clauses[] = "(LOWER(p.title) LIKE ? OR LOWER(p.tag) LIKE ? OR LOWER(p.meta_keywords) LIKE ? OR LOWER(p.about_prompt) LIKE ?)";
                $word_params[] = $wp;
                $word_params[] = $wp;
                $word_params[] = $wp;
                $word_params[] = $wp;
            }
            $where_clauses[] = "((LOWER(p.title) LIKE ? OR LOWER(p.tag) LIKE ? OR LOWER(p.meta_keywords) LIKE ? OR LOWER(p.about_prompt) LIKE ?) OR (" . implode(' AND ', $word_clauses) . "))";
            $where_params[] = $phrase_param;
            $where_params[] = $phrase_param;
            $where_params[] = $phrase_param;
            $where_params[] = $phrase_param;
            $where_params   = array_merge($where_params, $word_params);
        } else {
            $sp = '%' . addcslashes($clean_q, '%_') . '%';
            $where_clauses[] = "(LOWER(p.title) LIKE ? OR LOWER(p.tag) LIKE ? OR LOWER(p.meta_keywords) LIKE ? OR LOWER(p.about_prompt) LIKE ?)";
            $where_params[] = $sp;
            $where_params[] = $sp;
            $where_params[] = $sp;
            $where_params[] = $sp;
        }
    }

    $where_sql = implode(' AND ', $where_clauses);
    $offset    = ($page - 1) * $per_page;

    $count_sql  = "SELECT COUNT(*) FROM prompts p WHERE {$where_sql}";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($where_params);
    $total       = (int) $count_stmt->fetchColumn();
    $total_pages = max(1, (int) ceil($total / $per_page));

    // Order clause: Prioritize title match, then tag match, then keyword match, then likes & date
    $order_sql = "p.created_at DESC";
    $order_params = [];
    if ($search_query !== '') {
        $sp = '%' . addcslashes(strtolower($search_query), '%_') . '%';
        $order_sql = "(CASE WHEN LOWER(p.title) LIKE ? THEN 1 WHEN LOWER(p.tag) LIKE ? THEN 2 WHEN LOWER(p.meta_keywords) LIKE ? THEN 3 ELSE 4 END), p.likes_count DESC, p.created_at DESC";
        $order_params = [$sp, $sp, $sp];
    }

    if ($user_id) {
        $sql = "SELECT p.*, IF(u.id IS NOT NULL, 1, 0) as is_unlocked,
                   IF(l.id IS NOT NULL, 1, 0) as is_liked,
                   IF(sv.id IS NOT NULL, 1, 0) as is_saved
            FROM prompts p
            LEFT JOIN unlocked_prompts u ON p.id = u.prompt_id AND u.user_id = ?
            LEFT JOIN likes l ON p.id = l.prompt_id AND l.user_id = ?
            LEFT JOIN saved_prompts sv ON p.id = sv.prompt_id AND sv.user_id = ?
            WHERE {$where_sql}
            ORDER BY {$order_sql} LIMIT {$per_page} OFFSET {$offset}";
        $exec_params = array_merge([$user_id, $user_id, $user_id], $where_params, $order_params);
    } else {
        $sql = "SELECT p.*, 0 as is_unlocked, 0 as is_liked, 0 as is_saved
            FROM prompts p
            WHERE {$where_sql}
            ORDER BY {$order_sql} LIMIT {$per_page} OFFSET {$offset}";
        $exec_params = array_merge($where_params, $order_params);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($exec_params);

    return [
        'prompts'     => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total'       => $total,
        'total_pages' => $total_pages,
        'page'        => $page,
        'tag_filter'  => $tag_filter,
        'search_q'    => $search_query,
    ];
}

function render_gallery_prompt_cards(array $prompts): string {
    if (!function_exists('render_prompt_card')) {
        require_once __DIR__ . '/prompt_cards.php';
    }
    ob_start();
    foreach ($prompts as $index => $p) {
        render_prompt_card($p, $index);
    }
    return (string) ob_get_clean();
}
