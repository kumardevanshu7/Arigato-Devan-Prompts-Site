<?php
/**
 * Gallery banner slides + trending prompt queries.
 */

function gallery_banner_slides(): array {
    $dir = __DIR__ . '/../banner';
    $slides = [];
    $exts = ['webp', 'jpg', 'jpeg', 'png'];
    if (is_dir($dir)) {
        foreach (scandir($dir) as $file) {
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
    // Placeholders until admin adds images to /banner/
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
