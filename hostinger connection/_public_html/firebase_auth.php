<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$idToken = $input['idToken'] ?? '';

if (empty($idToken)) {
    echo json_encode(['success' => false, 'error' => 'No token provided']);
    exit();
}

// 🔥 FIXED API KEY (IMPORTANT - check this matches Firebase exactly)
$apiKey = 'AIzaSyBAzDxElpLX--lJ8xnvCrQBP-zYFMW_QLQ';

// Verify token
$verifyUrl = "https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=" . $apiKey;

$ch = curl_init($verifyUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['idToken' => $idToken]));

// 🔥 IMPORTANT FIX (hosting ke liye)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// 🔥 DEBUG (agar fail hua to reason milega)
if (!$response) {
    echo json_encode([
        'success' => false,
        'error' => 'Curl error: ' . curl_error($ch)
    ]);
    exit();
}

curl_close($ch);

if ($http_code !== 200) {
    echo json_encode([
        'success' => false,
        'error' => 'HTTP ' . $http_code,
        'response' => $response
    ]);
    exit();
}

$data = json_decode($response, true);

if (!isset($data['users']) || count($data['users']) === 0) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit();
}

// 🔥 Extract user
$firebaseUser = $data['users'][0];

$firebase_uid = $firebaseUser['localId'];
$email = $firebaseUser['email'] ?? '';
$name = $firebaseUser['displayName'] ?? 'User';
$avatar = $firebaseUser['photoUrl'] ?? '';

if (empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Email required']);
    exit();
}

try {
    // 🔥 Check user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE firebase_uid = ? OR email = ?");
    $stmt->execute([$firebase_uid, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Update
        $stmt = $pdo->prepare("UPDATE users SET firebase_uid=?, avatar=?, profile_image=? WHERE id=?");
        $stmt->execute([$firebase_uid, $avatar, $avatar, $user['id']]);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'] ?? $name;
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['profile_image'] = $avatar;
        $_SESSION['onboarding_complete'] = $user['onboarding_complete'];

    } else {
        // Username generate
        $base_username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));

        if (empty($base_username)) {
            $base_username = explode('@', $email)[0];
            $base_username = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($base_username));
        }

        $username = $base_username;
        $counter = 1;

        while (true) {
            $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $chk->execute([$username]);
            if (!$chk->fetch()) break;
            $username = $base_username . $counter;
            $counter++;
        }

        // Insert
        $stmt = $pdo->prepare("INSERT INTO users (username, email, firebase_uid, role, profile_image, avatar, onboarding_complete) VALUES (?, ?, ?, 'user', ?, ?, 0)");
        $stmt->execute([$username, $email, $firebase_uid, $avatar, $avatar]);

        $new_user_id = $pdo->lastInsertId();

        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = 'user';
        $_SESSION['profile_image'] = $avatar;
        $_SESSION['onboarding_complete'] = 0;
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>