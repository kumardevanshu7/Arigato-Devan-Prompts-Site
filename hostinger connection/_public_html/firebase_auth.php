<?php
session_start();
require_once "db.php";

header("Content-Type: application/json");

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "error" => "Invalid request method"]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);
$idToken = $input["idToken"] ?? "";

if (empty($idToken)) {
    echo json_encode(["success" => false, "error" => "No token provided"]);
    exit();
}

// Your Firebase API Key (from the config)
$apiKey = "AIzaSyBAzDxElpLX--lJ8xnvCrQBP-zYFMW_QLQ";

// Verify the token with Google Identity Toolkit API
$verifyUrl =
    "https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=" . $apiKey;

$ch = curl_init($verifyUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["idToken" => $idToken]));
// Auto-detect environment: disable SSL verify only on localhost (XAMPP)
// On Hostinger/production, SSL verify stays ON for security
$is_local =
    isset($_SERVER["HTTP_HOST"]) &&
    (strpos($_SERVER["HTTP_HOST"], "localhost") !== false ||
        strpos($_SERVER["HTTP_HOST"], "127.0.0.1") !== false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, !$is_local);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || !$response) {
    echo json_encode([
        "success" => false,
        "error" => "Invalid or expired token",
    ]);
    exit();
}

$data = json_decode($response, true);

if (!isset($data["users"]) || count($data["users"]) === 0) {
    echo json_encode([
        "success" => false,
        "error" => "User not found in token",
    ]);
    exit();
}

// Extract user details securely verified by Google
$firebaseUser = $data["users"][0];
$google_id = $firebaseUser["localId"]; // Firebase UID
$email = $firebaseUser["email"] ?? "";
$name = $firebaseUser["displayName"] ?? "User";
$avatar = $firebaseUser["photoUrl"] ?? "";

if (empty($email)) {
    echo json_encode([
        "success" => false,
        "error" => "Email is required for login",
    ]);
    exit();
}

try {
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Enforce admin role for specific UID
    $user_role =
        $google_id === "5RDnMAipOwZTA21JJCnkH2V4E492" ? "admin" : "user";

    if ($user) {
        // Update user's avatar and google_id if missing/changed, and strictly enforce role
        $stmt = $pdo->prepare(
            "UPDATE users SET google_id = ?, avatar = ?, profile_image = ?, role = ? WHERE id = ?",
        );
        $stmt->execute([$google_id, $avatar, $avatar, $user_role, $user["id"]]);

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"] ?? $name;
        $_SESSION["email"] = $user["email"];
        $_SESSION["role"] = $user_role;
        $_SESSION["profile_image"] = $avatar;
        $_SESSION["onboarding_complete"] = $user["onboarding_complete"];
    } else {
        // Register new user
        // Extract base username from email or name
        $base_username = strtolower(preg_replace("/[^a-zA-Z0-9]/", "", $name));
        if (empty($base_username)) {
            $base_username = explode("@", $email)[0];
            $base_username = preg_replace(
                "/[^a-zA-Z0-9]/",
                "",
                strtolower($base_username),
            );
        }

        // Ensure unique username
        $username = $base_username;
        $counter = 1;
        while (true) {
            $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $chk->execute([$username]);
            if (!$chk->fetch()) {
                break;
            }
            $username = $base_username . $counter;
            $counter++;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO users (username, email, google_id, role, profile_image, avatar, onboarding_complete) VALUES (?, ?, ?, ?, ?, ?, 0)",
        );
        $stmt->execute([
            $username,
            $email,
            $google_id,
            $user_role,
            $avatar,
            $avatar,
        ]);
        $new_user_id = $pdo->lastInsertId();

        $_SESSION["user_id"] = $new_user_id;
        $_SESSION["username"] = $username;
        $_SESSION["email"] = $email;
        $_SESSION["role"] = $user_role;
        $_SESSION["profile_image"] = $avatar;
        $_SESSION["onboarding_complete"] = 0;
    }

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "error" => "Database error: " . $e->getMessage(),
    ]);
}
?>
