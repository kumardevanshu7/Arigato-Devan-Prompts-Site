<?php
/*
 * store_auth.php — Firebase Google Sign-In handler for digital store
 * Called via AJAX from the store frontend.
 * Reuses the same Firebase token verification as firebase_auth.php
 * Sets $_SESSION['google_uid'] so admin detection works across store pages.
 */
session_start();

// Reuse parent site's DB connection
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

$input   = json_decode(file_get_contents('php://input'), true);
$idToken = $input['idToken'] ?? '';

if (empty($idToken)) {
    echo json_encode(['success' => false, 'error' => 'No token']);
    exit;
}

// ---- Verify token with Google ----
$apiKey    = 'AIzaSyBAzDxElpLX--lJ8xnvCrQBP-zYFMW_QLQ';
$verifyUrl = 'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . $apiKey;

$is_local = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
         || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false
         || strpos($_SERVER['HTTP_HOST'] ?? '', '.local') !== false;

$ch = curl_init($verifyUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode(['idToken' => $idToken]),
    CURLOPT_SSL_VERIFYPEER => !$is_local,
]);
$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || !$response) {
    echo json_encode(['success' => false, 'error' => 'Token verification failed']);
    exit;
}

$data = json_decode($response, true);
if (empty($data['users'])) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// ---- Extract user info ----
$fbUser    = $data['users'][0];
$google_id = $fbUser['localId'];
$email     = $fbUser['email']       ?? '';
$name      = $fbUser['displayName'] ?? 'User';
$avatar    = $fbUser['photoUrl']    ?? '';

$ADMIN_UID = '5RDnMAipOwZTA21JJCnkH2V4E492';
$role      = ($google_id === $ADMIN_UID) ? 'admin' : 'user';

// ---- Sync with main site DB (reuse existing users table) ----
try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $pdo->prepare('UPDATE users SET google_id = ?, role = ? WHERE id = ?')
            ->execute([$google_id, $role, $user['id']]);
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['username']      = $user['username'] ?? $name;
        $_SESSION['email']         = $user['email'];
        $_SESSION['role']          = $role;
        $_SESSION['profile_image'] = $user['avatar'] ?: $user['profile_image'] ?: $avatar;
    } else {
        // New user — register
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name)) ?: explode('@', $email)[0];
        $username = $base; $i = 1;
        while ($pdo->prepare('SELECT id FROM users WHERE username = ?')->execute([$username]) && $pdo->query("SELECT id FROM users WHERE username = '$username'")->fetch()) {
            $username = $base . $i++;
        }
        $pdo->prepare('INSERT INTO users (username,email,google_id,role,profile_image,avatar,onboarding_complete) VALUES (?,?,?,?,?,?,0)')
            ->execute([$username, $email, $google_id, $role, $avatar, $avatar]);
        $_SESSION['user_id']       = $pdo->lastInsertId();
        $_SESSION['username']      = $username;
        $_SESSION['email']         = $email;
        $_SESSION['role']          = $role;
        $_SESSION['profile_image'] = $avatar;
    }

    // ---- Store-specific session vars ----
    $_SESSION['google_uid'] = $google_id;
    $_SESSION['store_name'] = $name;
    $_SESSION['store_avatar'] = $avatar;

    echo json_encode([
        'success'  => true,
        'is_admin' => ($google_id === $ADMIN_UID),
        'name'     => $name,
        'avatar'   => $avatar,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
}
