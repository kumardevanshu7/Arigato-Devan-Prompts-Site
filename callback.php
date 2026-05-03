<?php
session_start();
require_once 'db.php';
require_once 'google_config.php';

if (isset($_GET['code']) && isset($_GET['state'])) {
    if ($_GET['state'] !== $_SESSION['oauth_state']) {
        $_SESSION['error_msg'] = "Invalid CSRF token. Please try again.";
        header("Location: index.php");
        exit();
    }

    $code = $_GET['code'];

    // Exchange code for access token
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'code' => $code,
        'client_id' => $google_client_id,
        'client_secret' => $google_client_secret,
        'redirect_uri' => $google_redirect_uri,
        'grant_type' => 'authorization_code'
    ]));
    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);

    if (isset($token_data['access_token'])) {
        // Get user profile
        $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token_data['access_token']]);
        $profile_response = curl_exec($ch);
        curl_close($ch);

        $profile_data = json_decode($profile_response, true);

        if (isset($profile_data['email'])) {
            $email = $profile_data['email'];
            $google_id = $profile_data['id'];
            $name = $profile_data['name'] ?? 'User';
            $profile_image = $profile_data['picture'] ?? null;
            
            // Check if admin
            $role = ($email === $admin_email) ? 'admin' : 'user';

            // Check if user exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
            $stmt->execute([$google_id, $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Login existing user
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'] ?: $name;
                $_SESSION['role'] = $role;
                // Always prefer onboarding avatar; never show Google pic if avatar is set
                $_SESSION['profile_image'] = !empty($user['avatar'])
                    ? $user['avatar']
                    : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($user['email'] ?? 'user');
                $_SESSION['onboarding_complete'] = (int)($user['onboarding_complete'] ?? 0);

                // Only update role in DB; never overwrite avatar with Google pic
                $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$role, $user['id']]);

                // Route based on onboarding status
                if (!$_SESSION['onboarding_complete']) {
                    header("Location: onboarding.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                // Register new user — mark onboarding as incomplete
                // Store Google picture only as profile_image (not avatar). Avatar will be set in onboarding.
                $stmt = $pdo->prepare("INSERT INTO users (username, email, google_id, role, profile_image, onboarding_complete) VALUES (?, ?, ?, ?, ?, 0)");
                $stmt->execute([$name, $email, $google_id, $role, null]);

                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['username'] = $name;
                $_SESSION['role'] = $role;
                // New users always use DiceBear until onboarding sets avatar
                $_SESSION['profile_image'] = 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($email);
                $_SESSION['onboarding_complete'] = 0;

                header("Location: onboarding.php");
                exit();
            }
        } else {
            $_SESSION['error_msg'] = "Profile fetch error: " . ($profile_response ? $profile_response : "No response");
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['error_msg'] = "Token fetch error: " . ($response ? $response : "No response");
        header("Location: login.php");
        exit();
    }
}

$_SESSION['error_msg'] = "Google Login Failed. No code or state provided.";
header("Location: login.php");
exit();
?>



