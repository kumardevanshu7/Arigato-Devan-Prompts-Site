<?php
/**
 * user_data.php — JSON endpoint for logged-in user data
 * Returns: { streak: N, new_prompts: M }
 * Used by: script.js (profile dropdown, streak badge, NEW dot)
 */
session_start();
require_once 'db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

// Guests get empty data
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['streak' => 0, 'new_prompts' => 0]);
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// ── Count new prompts (last 7 days) ─────────────────────────
$new_count = (int)$pdo->query(
    "SELECT COUNT(*) FROM prompts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
)->fetchColumn();

// ── Streak logic ─────────────────────────────────────────────
$today     = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

try {
    $stmt = $pdo->prepare("SELECT last_visit_date, streak_count FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $streak     = (int)($user['streak_count'] ?? 0);
    $last_visit = $user['last_visit_date'] ?? null;

    if ($last_visit === $today) {
        // Already updated today — keep streak as-is
    } elseif ($last_visit === $yesterday) {
        // Consecutive day — increment streak
        $streak++;
        $pdo->prepare("UPDATE users SET last_visit_date = ?, streak_count = ? WHERE id = ?")
            ->execute([$today, $streak, $user_id]);
        $_SESSION['streak'] = $streak;
    } else {
        // Missed a day or first-ever visit — reset to 1
        $streak = 1;
        $pdo->prepare("UPDATE users SET last_visit_date = ?, streak_count = 1 WHERE id = ?")
            ->execute([$today, $user_id]);
        $_SESSION['streak'] = 1;
    }
} catch (PDOException $e) {
    // Columns might not exist yet — safe fallback
    $streak = 0;
}

echo json_encode([
    'streak'      => $streak,
    'new_prompts' => $new_count,
]);
?>
