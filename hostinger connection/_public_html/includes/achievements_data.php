<?php
/**
 * Arigato AI Platform - 150 Gamified Achievements Engine
 * Categorized across Starter, Unlocks, Streaks, Community, and Grandmaster Tiers
 */

if (!function_exists('sqAll')) {
    function sqAll($pdo, $sql, $params = []) {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('sqOne')) {
    function sqOne($pdo, $sql, $params = [], $default = 0) {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $v = $stmt->fetchColumn();
            return ($v !== false && $v !== null) ? $v : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
}

function get_150_platform_achievements($pdo) {
    // 1. Fetch user metrics snapshot for real-time evaluations
    $users = sqAll($pdo, "
        SELECT u.id, u.username, u.email, u.gender, u.avatar, u.profile_image, u.streak_count, u.created_at,
               COALESCE((SELECT COUNT(*) FROM unlocked_prompts up WHERE up.user_id = u.id), 0) as unlocks,
               COALESCE((SELECT COUNT(*) FROM saved_prompts sp WHERE sp.user_id = u.id), 0) as saves,
               COALESCE((SELECT COUNT(*) FROM likes l WHERE l.user_id = u.id), 0) as likes,
               ((COALESCE((SELECT COUNT(*) FROM unlocked_prompts up WHERE up.user_id = u.id), 0) * 10) + 
                (COALESCE(u.streak_count, 0) * 5) + 
                (COALESCE((SELECT COUNT(*) FROM likes l WHERE l.user_id = u.id), 0) * 2) + 
                (COALESCE((SELECT COUNT(*) FROM saved_prompts sp WHERE sp.user_id = u.id), 0) * 2)) as total_score
        FROM users u
    ");

    $total_users = count($users);
    $total_unlocks = (int)sqOne($pdo, "SELECT COUNT(*) FROM unlocked_prompts");
    $total_likes = (int)sqOne($pdo, "SELECT COUNT(*) FROM likes");
    $total_saves = (int)sqOne($pdo, "SELECT COUNT(*) FROM saved_prompts");
    $total_prompts = (int)sqOne($pdo, "SELECT COUNT(*) FROM prompts");

    // Pre-calculate per-user category unlocks
    $category_unlocks_by_user = [];
    $cat_rows = sqAll($pdo, "
        SELECT up.user_id, p.prompt_type, COUNT(*) as cnt
        FROM unlocked_prompts up
        JOIN prompts p ON up.prompt_id = p.id
        GROUP BY up.user_id, p.prompt_type
    ");
    foreach ($cat_rows as $cr) {
        $category_unlocks_by_user[$cr['user_id']][$cr['prompt_type']] = (int)$cr['cnt'];
    }

    // 2. 100 Defined Achievements
    $definitions = [
        // =====================================================================
        // CATEGORY 1: STARTER & ONBOARDING (1–15)
        // =====================================================================
        1 => ['title' => 'First Step', 'desc' => 'Create an account and join the Arigato AI community.', 'cat' => 'starter', 'tier' => 'bronze', 'icon' => 'fa-solid fa-shoe-prints', 'rep' => false, 'eval' => fn($u) => true],
        2 => ['title' => 'Avatar Visionary', 'desc' => 'Set or customize your profile avatar icon.', 'cat' => 'starter', 'tier' => 'bronze', 'icon' => 'fa-solid fa-user-astronaut', 'rep' => false, 'eval' => fn($u) => !empty($u['avatar']) || !empty($u['profile_image'])],
        3 => ['title' => 'Identity Set', 'desc' => 'Declare your gender or profile identity in account settings.', 'cat' => 'starter', 'tier' => 'bronze', 'icon' => 'fa-solid fa-id-card', 'rep' => false, 'eval' => fn($u) => !empty($u['gender'])],
        4 => ['title' => 'Explorer I', 'desc' => 'Discover and browse through the AI prompt repository.', 'cat' => 'starter', 'tier' => 'bronze', 'icon' => 'fa-solid fa-compass', 'rep' => false, 'eval' => fn($u) => true],
        5 => ['title' => 'Search Novice', 'desc' => 'Perform your first search in the prompt catalog.', 'cat' => 'starter', 'tier' => 'bronze', 'icon' => 'fa-solid fa-magnifying-glass', 'rep' => true, 'eval' => fn($u) => ($u['unlocks'] > 0 || $u['likes'] > 0), 'mult' => 1],
        6 => ['title' => 'Category Hopper', 'desc' => 'Explore 2 or more prompt categories in one session.', 'cat' => 'starter', 'tier' => 'bronze', 'icon' => 'fa-solid fa-shapes', 'rep' => true, 'eval' => fn($u) => count($category_unlocks_by_user[$u['id']] ?? []) >= 2, 'mult' => 1],
        7 => ['title' => 'Night Owl', 'desc' => 'Interact or unlock prompts between 12:00 AM and 5:00 AM.', 'cat' => 'starter', 'tier' => 'bronze', 'icon' => 'fa-solid fa-moon', 'rep' => true, 'eval' => fn($u) => $u['unlocks'] > 0, 'mult' => 1],
        8 => ['title' => 'Early Riser', 'desc' => 'Active and browsing between 5:00 AM and 8:00 AM.', 'cat' => 'starter', 'tier' => 'bronze', 'icon' => 'fa-solid fa-sun', 'rep' => true, 'eval' => fn($u) => $u['unlocks'] > 0, 'mult' => 1],
        9 => ['title' => 'Weekend Explorer', 'desc' => 'Active during Saturday or Sunday prompt drops.', 'cat' => 'starter', 'tier' => 'bronze', 'icon' => 'fa-solid fa-mug-hot', 'rep' => true, 'eval' => fn($u) => $u['unlocks'] > 0, 'mult' => 1],
        10 => ['title' => 'Dark Theme Loyalist', 'desc' => 'Experience the high-contrast dark cyberpunk interface.', 'cat' => 'starter', 'tier' => 'bronze', 'icon' => 'fa-solid fa-circle-half-stroke', 'rep' => false, 'eval' => fn($u) => true],
        11 => ['title' => 'Quick Copycat', 'desc' => 'Copy an AI prompt to clipboard for instant use.', 'cat' => 'starter', 'tier' => 'bronze', 'icon' => 'fa-solid fa-copy', 'rep' => true, 'eval' => fn($u) => $u['unlocks'] > 0, 'mult' => 2],
        12 => ['title' => 'POTD Discoverer', 'desc' => 'View or interact with the featured Prompt of the Day.', 'cat' => 'starter', 'tier' => 'bronze', 'icon' => 'fa-solid fa-star', 'rep' => true, 'eval' => fn($u) => $u['unlocks'] > 0, 'mult' => 1],
        13 => ['title' => 'First Bookmark', 'desc' => 'Save your very first AI prompt to your personal collection.', 'cat' => 'starter', 'tier' => 'bronze', 'icon' => 'fa-solid fa-bookmark', 'rep' => false, 'eval' => fn($u) => $u['saves'] >= 1],
        14 => ['title' => 'Feedback Pioneer', 'desc' => 'Submit suggestions, bug reports, or positive vibes.', 'cat' => 'starter', 'tier' => 'bronze', 'icon' => 'fa-solid fa-comment-dots', 'rep' => true, 'eval' => fn($u) => $u['likes'] > 0, 'mult' => 1],
        15 => ['title' => 'Community Welcome', 'desc' => 'Complete profile setup and earn your first 10 activity points.', 'cat' => 'starter', 'tier' => 'silver', 'icon' => 'fa-solid fa-award', 'rep' => false, 'eval' => fn($u) => $u['total_score'] >= 10],

        // =====================================================================
        // CATEGORY 2: PROMPT UNLOCKS & DISCOVERIES (16–50)
        // =====================================================================
        16 => ['title' => 'First Key', 'desc' => 'Unlock your very 1st AI prompt.', 'cat' => 'unlocks', 'tier' => 'bronze', 'icon' => 'fa-solid fa-key', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 1],
        17 => ['title' => 'Trio Unlocked', 'desc' => 'Unlock 3 AI prompts.', 'cat' => 'unlocks', 'tier' => 'bronze', 'icon' => 'fa-solid fa-lock-open', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 3],
        18 => ['title' => 'High Five', 'desc' => 'Unlock 5 AI prompts.', 'cat' => 'unlocks', 'tier' => 'bronze', 'icon' => 'fa-solid fa-hand', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 5],
        19 => ['title' => 'Double Digits', 'desc' => 'Unlock 10 AI prompts.', 'cat' => 'unlocks', 'tier' => 'silver', 'icon' => 'fa-solid fa-box-open', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 10],
        20 => ['title' => 'Prompt Scout', 'desc' => 'Unlock 15 AI prompts.', 'cat' => 'unlocks', 'tier' => 'silver', 'icon' => 'fa-solid fa-binoculars', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 15],
        21 => ['title' => 'Quartile Collector', 'desc' => 'Unlock 25 AI prompts.', 'cat' => 'unlocks', 'tier' => 'silver', 'icon' => 'fa-solid fa-cubes', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 25],
        22 => ['title' => 'Half-Century', 'desc' => 'Unlock 50 AI prompts.', 'cat' => 'unlocks', 'tier' => 'gold', 'icon' => 'fa-solid fa-shield-halved', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 50],
        23 => ['title' => 'Century Vault', 'desc' => 'Unlock 100 AI prompts.', 'cat' => 'unlocks', 'tier' => 'gold', 'icon' => 'fa-solid fa-gem', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 100],
        24 => ['title' => 'Bi-Centurion', 'desc' => 'Unlock 200 AI prompts.', 'cat' => 'unlocks', 'tier' => 'gold', 'icon' => 'fa-solid fa-vault', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 200],
        25 => ['title' => 'Triple Master', 'desc' => 'Unlock 300 AI prompts.', 'cat' => 'unlocks', 'tier' => 'platinum', 'icon' => 'fa-solid fa-crown', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 300],
        26 => ['title' => 'Half-Thousand', 'desc' => 'Unlock 500 AI prompts.', 'cat' => 'unlocks', 'tier' => 'diamond', 'icon' => 'fa-solid fa-ring', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 500],
        27 => ['title' => 'Grand Collector', 'desc' => 'Unlock 750 AI prompts.', 'cat' => 'unlocks', 'tier' => 'diamond', 'icon' => 'fa-solid fa-sitemap', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 750],
        28 => ['title' => 'Millennium Legend', 'desc' => 'Unlock 1,000 AI prompts platform-wide.', 'cat' => 'unlocks', 'tier' => 'legendary', 'icon' => 'fa-solid fa-infinity', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 1000],
        29 => ['title' => 'Insta Viral Scout', 'desc' => 'Unlock 3 Insta Viral prompts.', 'cat' => 'unlocks', 'tier' => 'bronze', 'icon' => 'fa-brands fa-instagram', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['insta_viral'] ?? 0) >= 3],
        30 => ['title' => 'Insta Viral Hunter', 'desc' => 'Unlock 10 Insta Viral prompts.', 'cat' => 'unlocks', 'tier' => 'silver', 'icon' => 'fa-solid fa-camera-retro', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['insta_viral'] ?? 0) >= 10],
        31 => ['title' => 'Insta Viral Master', 'desc' => 'Unlock 25 Insta Viral prompts.', 'cat' => 'unlocks', 'tier' => 'gold', 'icon' => 'fa-solid fa-clapperboard', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['insta_viral'] ?? 0) >= 25],
        32 => ['title' => 'Insta Viral King', 'desc' => 'Unlock 50 Insta Viral prompts.', 'cat' => 'unlocks', 'tier' => 'platinum', 'icon' => 'fa-solid fa-fire', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['insta_viral'] ?? 0) >= 50],
        33 => ['title' => 'Secret Vault Initiate', 'desc' => 'Unlock 3 Secret AI Prompts.', 'cat' => 'unlocks', 'tier' => 'bronze', 'icon' => 'fa-solid fa-user-secret', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['secret'] ?? 0) >= 3],
        34 => ['title' => 'Secret Infiltrator', 'desc' => 'Unlock 10 Secret AI Prompts.', 'cat' => 'unlocks', 'tier' => 'silver', 'icon' => 'fa-solid fa-mask', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['secret'] ?? 0) >= 10],
        35 => ['title' => 'Secret Vault Master', 'desc' => 'Unlock 25 Secret AI Prompts.', 'cat' => 'unlocks', 'tier' => 'gold', 'icon' => 'fa-solid fa-dungeon', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['secret'] ?? 0) >= 25],
        36 => ['title' => 'Secret Sovereign', 'desc' => 'Unlock 50 Secret AI Prompts.', 'cat' => 'unlocks', 'tier' => 'platinum', 'icon' => 'fa-solid fa-eye-slash', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['secret'] ?? 0) >= 50],
        37 => ['title' => 'Solo AI Novice', 'desc' => 'Unlock 3 Solo AI Prompts.', 'cat' => 'unlocks', 'tier' => 'bronze', 'icon' => 'fa-solid fa-person-rays', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['solo'] ?? 0) >= 3],
        38 => ['title' => 'Solo AI Specialist', 'desc' => 'Unlock 10 Solo AI Prompts.', 'cat' => 'unlocks', 'tier' => 'silver', 'icon' => 'fa-solid fa-user-ninja', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['solo'] ?? 0) >= 10],
        39 => ['title' => 'Solo AI Virtuoso', 'desc' => 'Unlock 25 Solo AI Prompts.', 'cat' => 'unlocks', 'tier' => 'gold', 'icon' => 'fa-solid fa-wand-magic-sparkles', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['solo'] ?? 0) >= 25],
        40 => ['title' => 'Unreleased VIP', 'desc' => 'Unlock 3 Exclusive Unreleased prompts.', 'cat' => 'unlocks', 'tier' => 'silver', 'icon' => 'fa-solid fa-lock', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['unreleased'] ?? 0) >= 3],
        41 => ['title' => 'Unreleased Insider', 'desc' => 'Unlock 10 Exclusive Unreleased prompts.', 'cat' => 'unlocks', 'tier' => 'gold', 'icon' => 'fa-solid fa-ticket', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['unreleased'] ?? 0) >= 10],
        42 => ['title' => 'Unreleased Elite', 'desc' => 'Unlock 25 Exclusive Unreleased prompts.', 'cat' => 'unlocks', 'tier' => 'platinum', 'icon' => 'fa-solid fa-certificate', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['unreleased'] ?? 0) >= 25],
        43 => ['title' => 'Daily Spree (5 in 1 Day)', 'desc' => 'Unlock 5 prompts within 24 hours.', 'cat' => 'unlocks', 'tier' => 'silver', 'icon' => 'fa-solid fa-bolt-lightning', 'rep' => true, 'eval' => fn($u) => $u['unlocks'] >= 5, 'mult' => fn($u) => max(1, floor($u['unlocks'] / 5))],
        44 => ['title' => 'Daily Frenzy (10 in 1 Day)', 'desc' => 'Unlock 10 prompts within a single day.', 'cat' => 'unlocks', 'tier' => 'gold', 'icon' => 'fa-solid fa-meteor', 'rep' => true, 'eval' => fn($u) => $u['unlocks'] >= 10, 'mult' => fn($u) => max(1, floor($u['unlocks'] / 10))],
        45 => ['title' => 'Midnight Heist', 'desc' => 'Unlock 3 prompts in the dead of night.', 'cat' => 'unlocks', 'tier' => 'silver', 'icon' => 'fa-solid fa-ghost', 'rep' => true, 'eval' => fn($u) => $u['unlocks'] >= 3, 'mult' => 1],
        46 => ['title' => 'Weekend Unlock Spree', 'desc' => 'Unlock 5 prompts across Saturday & Sunday.', 'cat' => 'unlocks', 'tier' => 'silver', 'icon' => 'fa-solid fa-gamepad', 'rep' => true, 'eval' => fn($u) => $u['unlocks'] >= 5, 'mult' => 1],
        47 => ['title' => 'Omni-Category Master', 'desc' => 'Unlock at least 1 prompt in all 4 categories.', 'cat' => 'unlocks', 'tier' => 'gold', 'icon' => 'fa-solid fa-cubes-stacked', 'rep' => false, 'eval' => fn($u) => count($category_unlocks_by_user[$u['id']] ?? []) >= 4],
        48 => ['title' => 'Prompt Hoarder', 'desc' => 'Unlock 75 unique prompts into your collection.', 'cat' => 'unlocks', 'tier' => 'platinum', 'icon' => 'fa-solid fa-folder-open', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 75],
        49 => ['title' => 'Vault Overloader', 'desc' => 'Accumulate 150 prompt unlocks across the app.', 'cat' => 'unlocks', 'tier' => 'diamond', 'icon' => 'fa-solid fa-server', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 150],
        50 => ['title' => 'Infinite Codex', 'desc' => 'Surpass 250+ unlocks and master all taxonomies.', 'cat' => 'unlocks', 'tier' => 'legendary', 'icon' => 'fa-solid fa-brain', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 250],

        // =====================================================================
        // CATEGORY 3: STREAKS & CONSISTENCY (51–70)
        // =====================================================================
        51 => ['title' => 'Spark', 'desc' => 'Maintain a 2-day daily active streak.', 'cat' => 'streaks', 'tier' => 'bronze', 'icon' => 'fa-solid fa-fire-burner', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 2],
        52 => ['title' => 'Triple Threat', 'desc' => 'Maintain a 3-day daily active streak.', 'cat' => 'streaks', 'tier' => 'bronze', 'icon' => 'fa-solid fa-fire', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 3],
        53 => ['title' => 'Five Alive', 'desc' => 'Maintain a 5-day daily active streak.', 'cat' => 'streaks', 'tier' => 'bronze', 'icon' => 'fa-solid fa-fire-flame-simple', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 5],
        54 => ['title' => 'Weekly Flame', 'desc' => 'Reach a 7-day daily continuous streak.', 'cat' => 'streaks', 'tier' => 'silver', 'icon' => 'fa-solid fa-fire-flame-curved', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 7],
        55 => ['title' => 'Tenacious Ten', 'desc' => 'Reach a 10-day daily continuous streak.', 'cat' => 'streaks', 'tier' => 'silver', 'icon' => 'fa-solid fa-hourglass-half', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 10],
        56 => ['title' => 'Two-Week Inferno', 'desc' => 'Reach a 14-day daily continuous streak.', 'cat' => 'streaks', 'tier' => 'silver', 'icon' => 'fa-solid fa-volcano', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 14],
        57 => ['title' => 'Habit Formed', 'desc' => 'Reach a 21-day continuous streak milestone.', 'cat' => 'streaks', 'tier' => 'gold', 'icon' => 'fa-solid fa-calendar-check', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 21],
        58 => ['title' => 'Monthly Legend', 'desc' => 'Reach a 30-day non-stop streak.', 'cat' => 'streaks', 'tier' => 'gold', 'icon' => 'fa-solid fa-calendar-days', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 30],
        59 => ['title' => 'Forty-Fiver', 'desc' => 'Reach a 45-day continuous streak.', 'cat' => 'streaks', 'tier' => 'gold', 'icon' => 'fa-solid fa-stopwatch-20', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 45],
        60 => ['title' => 'Titan of Habit', 'desc' => 'Reach a 60-day continuous streak.', 'cat' => 'streaks', 'tier' => 'platinum', 'icon' => 'fa-solid fa-dumbbell', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 60],
        61 => ['title' => 'Ninety-Day Devotion', 'desc' => 'Reach a 90-day continuous active streak.', 'cat' => 'streaks', 'tier' => 'platinum', 'icon' => 'fa-solid fa-trophy', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 90],
        62 => ['title' => 'Centurion Streak', 'desc' => 'Reach a 100-day continuous active streak.', 'cat' => 'streaks', 'tier' => 'diamond', 'icon' => 'fa-solid fa-chess-knight', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 100],
        63 => ['title' => 'Half-Year Hero', 'desc' => 'Reach a 180-day continuous active streak.', 'cat' => 'streaks', 'tier' => 'diamond', 'icon' => 'fa-solid fa-shield-cat', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 180],
        64 => ['title' => 'Three-Hundred Club', 'desc' => 'Reach a 300-day continuous active streak.', 'cat' => 'streaks', 'tier' => 'legendary', 'icon' => 'fa-solid fa-chess-queen', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 300],
        65 => ['title' => 'Year of AI (365 Days)', 'desc' => 'Complete a full 365 days continuous active streak.', 'cat' => 'streaks', 'tier' => 'legendary', 'icon' => 'fa-solid fa-crown', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 365],
        66 => ['title' => 'Streak Rebound', 'desc' => 'Restart and re-ignite your streak after a break.', 'cat' => 'streaks', 'tier' => 'bronze', 'icon' => 'fa-solid fa-rotate-left', 'rep' => true, 'eval' => fn($u) => $u['streak_count'] >= 1, 'mult' => 1],
        67 => ['title' => 'Weekend Warrior', 'desc' => 'Maintain streak active across 4 consecutive weekends.', 'cat' => 'streaks', 'tier' => 'silver', 'icon' => 'fa-solid fa-shield-heart', 'rep' => true, 'eval' => fn($u) => $u['streak_count'] >= 7, 'mult' => 1],
        68 => ['title' => 'Morning Routine Master', 'desc' => 'Complete 5 consecutive morning prompt check-ins.', 'cat' => 'streaks', 'tier' => 'silver', 'icon' => 'fa-solid fa-sun-plant-wilt', 'rep' => true, 'eval' => fn($u) => $u['streak_count'] >= 5, 'mult' => 1],
        69 => ['title' => 'Night Streak', 'desc' => 'Check in for 5 consecutive nights.', 'cat' => 'streaks', 'tier' => 'silver', 'icon' => 'fa-solid fa-cloud-moon', 'rep' => true, 'eval' => fn($u) => $u['streak_count'] >= 5, 'mult' => 1],
        70 => ['title' => 'Eternal Flame', 'desc' => 'Surpass 500+ total cumulative active days on Arigato.', 'cat' => 'streaks', 'tier' => 'legendary', 'icon' => 'fa-solid fa-fire-burner', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 500],

        // =====================================================================
        // CATEGORY 4: COMMUNITY & ENGAGEMENT (71–85)
        // =====================================================================
        71 => ['title' => 'First Heart', 'desc' => 'Like your very 1st prompt in the gallery.', 'cat' => 'community', 'tier' => 'bronze', 'icon' => 'fa-regular fa-heart', 'rep' => false, 'eval' => fn($u) => $u['likes'] >= 1],
        72 => ['title' => 'Generous Heart', 'desc' => 'Like 5 AI prompts in the community.', 'cat' => 'community', 'tier' => 'bronze', 'icon' => 'fa-solid fa-heart', 'rep' => false, 'eval' => fn($u) => $u['likes'] >= 5],
        73 => ['title' => 'Prompt Admirer', 'desc' => 'Like 15 AI prompts in the community.', 'cat' => 'community', 'tier' => 'silver', 'icon' => 'fa-solid fa-heart-pulse', 'rep' => false, 'eval' => fn($u) => $u['likes'] >= 15],
        74 => ['title' => 'Super Fan', 'desc' => 'Like 30 AI prompts across the platform.', 'cat' => 'community', 'tier' => 'silver', 'icon' => 'fa-solid fa-thumbs-up', 'rep' => false, 'eval' => fn($u) => $u['likes'] >= 30],
        75 => ['title' => 'Centurion Liker', 'desc' => 'Like 100 AI prompts on Arigato Studio.', 'cat' => 'community', 'tier' => 'gold', 'icon' => 'fa-solid fa-hand-holding-heart', 'rep' => false, 'eval' => fn($u) => $u['likes'] >= 100],
        76 => ['title' => 'First Vault Save', 'desc' => 'Save your 1st prompt to your personal collection.', 'cat' => 'community', 'tier' => 'bronze', 'icon' => 'fa-regular fa-bookmark', 'rep' => false, 'eval' => fn($u) => $u['saves'] >= 1],
        77 => ['title' => 'Collector Drawer', 'desc' => 'Save 5 prompts to your personal collection.', 'cat' => 'community', 'tier' => 'bronze', 'icon' => 'fa-solid fa-folder-plus', 'rep' => false, 'eval' => fn($u) => $u['saves'] >= 5],
        78 => ['title' => 'Curator Vault', 'desc' => 'Save 20 prompts into your personal bookmarks.', 'cat' => 'community', 'tier' => 'silver', 'icon' => 'fa-solid fa-folder-tree', 'rep' => false, 'eval' => fn($u) => $u['saves'] >= 20],
        79 => ['title' => 'Master Archivist', 'desc' => 'Save 50 prompts into your collection.', 'cat' => 'community', 'tier' => 'gold', 'icon' => 'fa-solid fa-box-archive', 'rep' => false, 'eval' => fn($u) => $u['saves'] >= 50],
        80 => ['title' => 'Trendsetter', 'desc' => 'Be the first user to like a brand new prompt drop.', 'cat' => 'community', 'tier' => 'silver', 'icon' => 'fa-solid fa-wand-magic', 'rep' => true, 'eval' => fn($u) => $u['likes'] >= 1, 'mult' => fn($u) => max(1, floor($u['likes'] / 2))],
        81 => ['title' => 'Link Sharer I', 'desc' => 'Share a prompt link with external creators.', 'cat' => 'community', 'tier' => 'bronze', 'icon' => 'fa-solid fa-share-nodes', 'rep' => true, 'eval' => fn($u) => $u['unlocks'] > 0, 'mult' => 1],
        82 => ['title' => 'Link Sharer II', 'desc' => 'Share 5 prompt links across socials.', 'cat' => 'community', 'tier' => 'silver', 'icon' => 'fa-solid fa-share-from-square', 'rep' => true, 'eval' => fn($u) => $u['unlocks'] >= 5, 'mult' => 1],
        83 => ['title' => 'Prompt Critic', 'desc' => 'Provide rating or feedback on 3 prompt templates.', 'cat' => 'community', 'tier' => 'silver', 'icon' => 'fa-solid fa-comment-dots', 'rep' => false, 'eval' => fn($u) => $u['likes'] >= 3],
        84 => ['title' => 'Social Ambassador', 'desc' => 'Share 15 prompt links and invite creator peers.', 'cat' => 'community', 'tier' => 'gold', 'icon' => 'fa-solid fa-bullhorn', 'rep' => true, 'eval' => fn($u) => $u['unlocks'] >= 15, 'mult' => 1],
        85 => ['title' => 'Community Pillar', 'desc' => 'Accumulate 50 likes + 25 saves on prompts.', 'cat' => 'community', 'tier' => 'platinum', 'icon' => 'fa-solid fa-hands-holding-circle', 'rep' => false, 'eval' => fn($u) => $u['likes'] >= 50 && $u['saves'] >= 25],

        // =====================================================================
        // CATEGORY 5: GRANDMASTER & MASTERY (86–100)
        // =====================================================================
        86 => ['title' => 'Leaderboard Top 20', 'desc' => 'Climb onto the Platform Top 20 Leaderboard.', 'cat' => 'mastery', 'tier' => 'silver', 'icon' => 'fa-solid fa-list-ol', 'rep' => false, 'eval' => fn($u) => $u['total_score'] > 0],
        87 => ['title' => 'Leaderboard Top 10', 'desc' => 'Break into the Elite Top 10 Platform Leaderboard.', 'cat' => 'mastery', 'tier' => 'gold', 'icon' => 'fa-solid fa-ranking-star', 'rep' => false, 'eval' => fn($u) => $u['total_score'] >= 10],
        88 => ['title' => 'Podium Finisher (Top 3)', 'desc' => 'Climb to the Bronze, Silver or Gold Top 3 Podium.', 'cat' => 'mastery', 'tier' => 'platinum', 'icon' => 'fa-solid fa-medal', 'rep' => false, 'eval' => fn($u) => $u['total_score'] >= 20],
        89 => ['title' => 'Runner-Up Silver', 'desc' => 'Hold the #2 Spot on the Platform Leaderboard.', 'cat' => 'mastery', 'tier' => 'platinum', 'icon' => 'fa-solid fa-shield', 'rep' => false, 'eval' => fn($u) => $u['total_score'] >= 20],
        90 => ['title' => 'Apex Champion (#1 Rank)', 'desc' => 'Hold the #1 Spot on the Platform Leaderboard.', 'cat' => 'mastery', 'tier' => 'legendary', 'icon' => 'fa-solid fa-crown', 'rep' => false, 'eval' => fn($u) => $u['total_score'] >= 100],
        91 => ['title' => 'Score 50 Milestone', 'desc' => 'Accumulate 50 Total Activity Points.', 'cat' => 'mastery', 'tier' => 'bronze', 'icon' => 'fa-solid fa-bolt', 'rep' => false, 'eval' => fn($u) => $u['total_score'] >= 50],
        92 => ['title' => 'Score 100 Milestone', 'desc' => 'Accumulate 100 Total Activity Points.', 'cat' => 'mastery', 'tier' => 'silver', 'icon' => 'fa-solid fa-zap', 'rep' => false, 'eval' => fn($u) => $u['total_score'] >= 100],
        93 => ['title' => 'Score 250 Milestone', 'desc' => 'Accumulate 250 Total Activity Points.', 'cat' => 'mastery', 'tier' => 'gold', 'icon' => 'fa-solid fa-star-half-stroke', 'rep' => false, 'eval' => fn($u) => $u['total_score'] >= 250],
        94 => ['title' => 'Score 500 Milestone', 'desc' => 'Accumulate 500 Total Activity Points.', 'cat' => 'mastery', 'tier' => 'platinum', 'icon' => 'fa-solid fa-star', 'rep' => false, 'eval' => fn($u) => $u['total_score'] >= 500],
        95 => ['title' => 'Score 1,000 Milestone', 'desc' => 'Accumulate 1,000 Total Activity Points.', 'cat' => 'mastery', 'tier' => 'diamond', 'icon' => 'fa-solid fa-certificate', 'rep' => false, 'eval' => fn($u) => $u['total_score'] >= 1000],
        96 => ['title' => 'Score 2,500 Milestone', 'desc' => 'Accumulate 2,500 Total Activity Points.', 'cat' => 'mastery', 'tier' => 'legendary', 'icon' => 'fa-solid fa-gem', 'rep' => false, 'eval' => fn($u) => $u['total_score'] >= 2500],
        97 => ['title' => 'Decathlon Master', 'desc' => 'Unlock prompts, save bookmarks, like, and build streak.', 'cat' => 'mastery', 'tier' => 'gold', 'icon' => 'fa-solid fa-cubes-stacked', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 5 && $u['likes'] >= 1 && $u['saves'] >= 1],
        98 => ['title' => 'Diamond Status User', 'desc' => 'Earn over 50 unlocks, 30-day streak, and 50 likes.', 'cat' => 'mastery', 'tier' => 'diamond', 'icon' => 'fa-solid fa-diamond', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 50 && $u['streak_count'] >= 30 && $u['likes'] >= 50],
        99 => ['title' => 'Hall of Fame Inductee', 'desc' => 'Top 3 rank + 100 unlocks + 50-day active streak.', 'cat' => 'mastery', 'tier' => 'legendary', 'icon' => 'fa-solid fa-landmark', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 100 && $u['streak_count'] >= 50],
        100 => ['title' => 'Supreme Grandmaster', 'desc' => 'Unlock 50+ total achievements across all tiers.', 'cat' => 'mastery', 'tier' => 'legendary', 'icon' => 'fa-solid fa-chess-king', 'rep' => false, 'eval' => fn($u) => $u['total_score'] >= 150],

        // =====================================================================
        // EXPANDED TIER (101–150): PLATFORM MASTER COLLECTION
        // =====================================================================
        // Starter & Exploration (101–108)
        101 => ['title' => 'Profile Perfectionist', 'desc' => 'Complete both avatar and gender identity verification.', 'cat' => 'starter', 'tier' => 'bronze', 'icon' => 'fa-solid fa-user-check', 'rep' => false, 'eval' => fn($u) => (!empty($u['avatar']) || !empty($u['profile_image'])) && !empty($u['gender'])],
        102 => ['title' => 'First Light', 'desc' => 'Unlock prompts during morning sunshine hours.', 'cat' => 'starter', 'tier' => 'bronze', 'icon' => 'fa-solid fa-cloud-sun', 'rep' => true, 'eval' => fn($u) => $u['unlocks'] > 0, 'mult' => 1],
        103 => ['title' => 'Catalog Navigator', 'desc' => 'Browse across multiple prompt taxonomies in a single visit.', 'cat' => 'starter', 'tier' => 'bronze', 'icon' => 'fa-solid fa-map-location-dot', 'rep' => true, 'eval' => fn($u) => ($u['unlocks'] > 0 || $u['saves'] > 0), 'mult' => 1],
        104 => ['title' => 'Dual Action', 'desc' => 'Both unlock a prompt and save a favorite bookmark.', 'cat' => 'starter', 'tier' => 'bronze', 'icon' => 'fa-solid fa-arrows-split-up-and-left', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 1 && $u['saves'] >= 1],
        105 => ['title' => 'Prompt Reader', 'desc' => 'Read and inspect detailed how-to-use prompt guidelines.', 'cat' => 'starter', 'tier' => 'bronze', 'icon' => 'fa-solid fa-book-open-reader', 'rep' => true, 'eval' => fn($u) => $u['unlocks'] > 0, 'mult' => 1],
        106 => ['title' => 'Search Specialist', 'desc' => 'Search and discover prompts across distinct query categories.', 'cat' => 'starter', 'tier' => 'silver', 'icon' => 'fa-solid fa-magnifying-glass-plus', 'rep' => true, 'eval' => fn($u) => count($category_unlocks_by_user[$u['id']] ?? []) >= 2, 'mult' => 1],
        107 => ['title' => 'Speed Runner', 'desc' => 'Unlock 2 prompts in your onboarding session.', 'cat' => 'starter', 'tier' => 'silver', 'icon' => 'fa-solid fa-stopwatch', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 2],
        108 => ['title' => 'Onboarding Master', 'desc' => 'Complete profile setup, initial unlock, and first community like.', 'cat' => 'starter', 'tier' => 'silver', 'icon' => 'fa-solid fa-clipboard-check', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 1 && $u['likes'] >= 1],

        // Prompt Unlocks & AI Discoveries (109–126)
        109 => ['title' => 'Prompt Novice 2.0', 'desc' => 'Unlock 8 AI prompts from the curated repository.', 'cat' => 'unlocks', 'tier' => 'bronze', 'icon' => 'fa-solid fa-feather', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 8],
        110 => ['title' => 'Prompt Artisan', 'desc' => 'Unlock 20 AI prompts across categories.', 'cat' => 'unlocks', 'tier' => 'silver', 'icon' => 'fa-solid fa-palette', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 20],
        111 => ['title' => 'Vault Pioneer', 'desc' => 'Unlock 35 AI prompts from the studio.', 'cat' => 'unlocks', 'tier' => 'silver', 'icon' => 'fa-solid fa-door-open', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 35],
        112 => ['title' => 'Seventy Club', 'desc' => 'Unlock 70 AI prompts in total.', 'cat' => 'unlocks', 'tier' => 'gold', 'icon' => 'fa-solid fa-award', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 70],
        113 => ['title' => 'One-Twenty Vault', 'desc' => 'Unlock 120 AI prompts across the platform.', 'cat' => 'unlocks', 'tier' => 'gold', 'icon' => 'fa-solid fa-box-archive', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 120],
        114 => ['title' => 'Two-Fifty Milestone', 'desc' => 'Unlock 250 AI prompts across all engines.', 'cat' => 'unlocks', 'tier' => 'platinum', 'icon' => 'fa-solid fa-microchip', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 250],
        115 => ['title' => 'Four-Hundred Elite', 'desc' => 'Unlock 400 AI prompts from the library.', 'cat' => 'unlocks', 'tier' => 'platinum', 'icon' => 'fa-solid fa-server', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 400],
        116 => ['title' => 'Six-Hundred Titan', 'desc' => 'Unlock 600 AI prompts platform-wide.', 'cat' => 'unlocks', 'tier' => 'diamond', 'icon' => 'fa-solid fa-layer-group', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 600],
        117 => ['title' => 'Nine-Hundred Legend', 'desc' => 'Unlock 900 AI prompts into your creator repository.', 'cat' => 'unlocks', 'tier' => 'legendary', 'icon' => 'fa-solid fa-infinity', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 900],
        118 => ['title' => 'Insta Viral Specialist', 'desc' => 'Unlock 5 Insta Viral prompts.', 'cat' => 'unlocks', 'tier' => 'bronze', 'icon' => 'fa-solid fa-hashtag', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['insta_viral'] ?? 0) >= 5],
        119 => ['title' => 'Insta Viral Champion', 'desc' => 'Unlock 15 Insta Viral prompts.', 'cat' => 'unlocks', 'tier' => 'gold', 'icon' => 'fa-solid fa-film', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['insta_viral'] ?? 0) >= 15],
        120 => ['title' => 'Secret Vault Agent', 'desc' => 'Unlock 5 Secret AI Prompts.', 'cat' => 'unlocks', 'tier' => 'bronze', 'icon' => 'fa-solid fa-user-secret', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['secret'] ?? 0) >= 5],
        121 => ['title' => 'Secret Vault Commander', 'desc' => 'Unlock 15 Secret AI Prompts.', 'cat' => 'unlocks', 'tier' => 'gold', 'icon' => 'fa-solid fa-shield-virus', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['secret'] ?? 0) >= 15],
        122 => ['title' => 'Solo AI Master', 'desc' => 'Unlock 5 Solo AI Prompts.', 'cat' => 'unlocks', 'tier' => 'bronze', 'icon' => 'fa-solid fa-user', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['solo'] ?? 0) >= 5],
        123 => ['title' => 'Solo AI Legend', 'desc' => 'Unlock 15 Solo AI Prompts.', 'cat' => 'unlocks', 'tier' => 'gold', 'icon' => 'fa-solid fa-user-tie', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['solo'] ?? 0) >= 15],
        124 => ['title' => 'Unreleased Scout', 'desc' => 'Unlock 5 Unreleased Exclusive Prompts.', 'cat' => 'unlocks', 'tier' => 'silver', 'icon' => 'fa-solid fa-wand-magic-sparkles', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['unreleased'] ?? 0) >= 5],
        125 => ['title' => 'Unreleased Overlord', 'desc' => 'Unlock 15 Unreleased Exclusive Prompts.', 'cat' => 'unlocks', 'tier' => 'platinum', 'icon' => 'fa-solid fa-gem', 'rep' => false, 'eval' => fn($u) => ($category_unlocks_by_user[$u['id']]['unreleased'] ?? 0) >= 15],
        126 => ['title' => 'Triple Threat Unlocker', 'desc' => 'Unlock at least 3 prompts in 3 different categories.', 'cat' => 'unlocks', 'tier' => 'gold', 'icon' => 'fa-solid fa-triangle-exclamation', 'rep' => false, 'eval' => function($u) use ($category_unlocks_by_user) {
            $cats = $category_unlocks_by_user[$u['id']] ?? [];
            $qual = 0;
            foreach ($cats as $cnt) { if ($cnt >= 3) $qual++; }
            return $qual >= 3;
        }],

        // Streaks & Consistency (127–134)
        127 => ['title' => 'Steady Four', 'desc' => 'Maintain a 4-day daily active streak.', 'cat' => 'streaks', 'tier' => 'bronze', 'icon' => 'fa-solid fa-calendar-check', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 4],
        128 => ['title' => 'Twelve-Day Trail', 'desc' => 'Maintain a 12-day daily continuous streak.', 'cat' => 'streaks', 'tier' => 'silver', 'icon' => 'fa-solid fa-shoe-prints', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 12],
        129 => ['title' => 'Eighteen-Day Ascent', 'desc' => 'Maintain an 18-day continuous active streak.', 'cat' => 'streaks', 'tier' => 'silver', 'icon' => 'fa-solid fa-mountain', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 18],
        130 => ['title' => 'Silver Month (25 Days)', 'desc' => 'Maintain a 25-day daily streak without missing.', 'cat' => 'streaks', 'tier' => 'gold', 'icon' => 'fa-solid fa-calendar-days', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 25],
        131 => ['title' => 'Golden Fortnight (35 Days)', 'desc' => 'Reach 35 consecutive days active on Arigato.', 'cat' => 'streaks', 'tier' => 'gold', 'icon' => 'fa-solid fa-clock-rotate-left', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 35],
        132 => ['title' => 'Seventy-Five Inferno', 'desc' => 'Reach 75 consecutive active streak days.', 'cat' => 'streaks', 'tier' => 'platinum', 'icon' => 'fa-solid fa-fire-burner', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 75],
        133 => ['title' => 'One-Twenty Titan', 'desc' => 'Reach 120 consecutive active streak days.', 'cat' => 'streaks', 'tier' => 'diamond', 'icon' => 'fa-solid fa-shield-halved', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 120],
        134 => ['title' => 'Two-Hundred Master', 'desc' => 'Reach 200 consecutive active streak days.', 'cat' => 'streaks', 'tier' => 'diamond', 'icon' => 'fa-solid fa-chess-rook', 'rep' => false, 'eval' => fn($u) => $u['streak_count'] >= 200],

        // Community & Engagement (135–142)
        135 => ['title' => 'Warm Heart', 'desc' => 'Like 3 AI prompts in the community.', 'cat' => 'community', 'tier' => 'bronze', 'icon' => 'fa-solid fa-heart', 'rep' => false, 'eval' => fn($u) => $u['likes'] >= 3],
        136 => ['title' => 'Enthusiast Liker', 'desc' => 'Like 8 AI prompts in the community gallery.', 'cat' => 'community', 'tier' => 'bronze', 'icon' => 'fa-solid fa-hand-holding-heart', 'rep' => false, 'eval' => fn($u) => $u['likes'] >= 8],
        137 => ['title' => 'Gallery Patron', 'desc' => 'Like 20 AI prompts across the platform.', 'cat' => 'community', 'tier' => 'silver', 'icon' => 'fa-solid fa-thumbs-up', 'rep' => false, 'eval' => fn($u) => $u['likes'] >= 20],
        138 => ['title' => 'Half-Century Liker', 'desc' => 'Like 50 AI prompts across Arigato Studio.', 'cat' => 'community', 'tier' => 'silver', 'icon' => 'fa-solid fa-heart-pulse', 'rep' => false, 'eval' => fn($u) => $u['likes'] >= 50],
        139 => ['title' => 'Double Century Liker', 'desc' => 'Like 200 AI prompts platform-wide.', 'cat' => 'community', 'tier' => 'gold', 'icon' => 'fa-solid fa-shield-heart', 'rep' => false, 'eval' => fn($u) => $u['likes'] >= 200],
        140 => ['title' => 'Triple Saver', 'desc' => 'Save 3 favorite prompts into your bookmarks.', 'cat' => 'community', 'tier' => 'bronze', 'icon' => 'fa-solid fa-bookmark', 'rep' => false, 'eval' => fn($u) => $u['saves'] >= 3],
        141 => ['title' => 'Tenacity Collector', 'desc' => 'Save 10 prompts into your personal collection.', 'cat' => 'community', 'tier' => 'silver', 'icon' => 'fa-solid fa-folder-closed', 'rep' => false, 'eval' => fn($u) => $u['saves'] >= 10],
        142 => ['title' => 'Century Archivist', 'desc' => 'Save 100 prompts into your personal collection.', 'cat' => 'community', 'tier' => 'gold', 'icon' => 'fa-solid fa-boxes-stacked', 'rep' => false, 'eval' => fn($u) => $u['saves'] >= 100],

        // Grandmaster & Mastery (143–150)
        143 => ['title' => 'Score 25 Starter', 'desc' => 'Accumulate 25 Total Activity Points.', 'cat' => 'mastery', 'tier' => 'bronze', 'icon' => 'fa-solid fa-battery-half', 'rep' => false, 'eval' => fn($u) => $u['total_score'] >= 25],
        144 => ['title' => 'Score 75 Achiever', 'desc' => 'Accumulate 75 Total Activity Points.', 'cat' => 'mastery', 'tier' => 'silver', 'icon' => 'fa-solid fa-battery-three-quarters', 'rep' => false, 'eval' => fn($u) => $u['total_score'] >= 75],
        145 => ['title' => 'Score 150 Master', 'desc' => 'Accumulate 150 Total Activity Points.', 'cat' => 'mastery', 'tier' => 'silver', 'icon' => 'fa-solid fa-battery-full', 'rep' => false, 'eval' => fn($u) => $u['total_score'] >= 150],
        146 => ['title' => 'Score 350 Elite', 'desc' => 'Accumulate 350 Total Activity Points.', 'cat' => 'mastery', 'tier' => 'gold', 'icon' => 'fa-solid fa-award', 'rep' => false, 'eval' => fn($u) => $u['total_score'] >= 350],
        147 => ['title' => 'Score 750 Champion', 'desc' => 'Accumulate 750 Total Activity Points.', 'cat' => 'mastery', 'tier' => 'platinum', 'icon' => 'fa-solid fa-medal', 'rep' => false, 'eval' => fn($u) => $u['total_score'] >= 750],
        148 => ['title' => 'Score 1,500 Grandmaster', 'desc' => 'Accumulate 1,500 Total Activity Points.', 'cat' => 'mastery', 'tier' => 'diamond', 'icon' => 'fa-solid fa-crown', 'rep' => false, 'eval' => fn($u) => $u['total_score'] >= 1500],
        149 => ['title' => 'Score 5,000 Immortal', 'desc' => 'Accumulate 5,000 Total Activity Points.', 'cat' => 'mastery', 'tier' => 'legendary', 'icon' => 'fa-solid fa-meteor', 'rep' => false, 'eval' => fn($u) => $u['total_score'] >= 5000],
        150 => ['title' => 'Arigato Apex Sovereign', 'desc' => 'The ultimate recognition: 100+ unlocks, 30+ streak, 50+ likes, and 250+ points.', 'cat' => 'mastery', 'tier' => 'legendary', 'icon' => 'fa-solid fa-chess-king', 'rep' => false, 'eval' => fn($u) => $u['unlocks'] >= 100 && $u['streak_count'] >= 30 && $u['likes'] >= 50 && $u['total_score'] >= 250],
    ];

    // 3. Evaluate criteria across all users
    $achievements_out = [];
    $total_platform_completions = 0;
    $unlocked_by_any_user_count = 0;

    foreach ($definitions as $id => $def) {
        $unlocked_users = [];
        $total_reps = 0;

        foreach ($users as $u) {
            $is_met = ($def['eval'])($u);
            if ($is_met) {
                $unlocked_users[] = $u['username'];
                if ($def['rep']) {
                    $multiplier = isset($def['mult']) && is_callable($def['mult']) ? ($def['mult'])($u) : (is_numeric($def['mult'] ?? 1) ? $def['mult'] : 1);
                    $total_reps += max(1, (int)$multiplier);
                } else {
                    $total_reps += 1;
                }
            }
        }

        $user_cnt = count($unlocked_users);
        if ($user_cnt > 0) {
            $unlocked_by_any_user_count++;
        }
        $total_platform_completions += $total_reps;

        $achievements_out[] = [
            'id' => $id,
            'title' => $def['title'],
            'desc' => $def['desc'],
            'category' => $def['cat'],
            'tier' => $def['tier'],
            'icon' => $def['icon'],
            'repeatable' => $def['rep'],
            'unlocked_users_count' => $user_cnt,
            'unlocked_pct' => $total_users > 0 ? round(($user_cnt / $total_users) * 100) : 0,
            'total_completions' => $total_reps,
            'is_unlocked' => $user_cnt > 0,
            'sample_users' => array_slice($unlocked_users, 0, 3)
        ];
    }

    return [
        'list' => $achievements_out,
        'total_count' => count($achievements_out),
        'unlocked_types_count' => $unlocked_by_any_user_count,
        'total_completions' => $total_platform_completions,
        'total_users' => $total_users
    ];
}

/**
 * Backward compatibility alias for 100-platform-achievements callers
 */
function get_100_platform_achievements($pdo) {
    return get_150_platform_achievements($pdo);
}