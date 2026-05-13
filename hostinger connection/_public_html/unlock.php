<?php
session_start();
require_once "db.php";
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["action"])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit();
}

$action = $_POST["action"];
$prompt_id = (int) ($_POST["prompt_id"] ?? 0);

// ─── RATE LIMITER — max 5 wrong attempts per prompt per 10 min ───────────────
function checkRateLimit($prompt_id)
{
    $key = "rl_" . $prompt_id;
    $now = time();
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ["count" => 0, "reset_at" => $now + 600];
    }
    // Reset window if expired
    if ($now > $_SESSION[$key]["reset_at"]) {
        $_SESSION[$key] = ["count" => 0, "reset_at" => $now + 600];
    }
    if ($_SESSION[$key]["count"] >= 5) {
        $wait = ceil(($_SESSION[$key]["reset_at"] - $now) / 60);
        echo json_encode([
            "success" => false,
            "message" => "Too many attempts! Try again in {$wait} min. ⏳",
        ]);
        exit();
    }
    $_SESSION[$key]["count"]++;
}

// ─── ACTION: get_challenge — server generates math numbers, stores answer ────
if ($action === "get_challenge") {
    if ($prompt_id <= 0) {
        echo json_encode([
            "success" => false,
            "message" => "Missing prompt ID",
        ]);
        exit();
    }
    $n1 = rand(1, 10);
    $n2 = rand(1, 10);
    $n3 = rand(1, 10);
    $n4 = rand(1, 10);
    $ans = $n1 + $n2 + $n3 + $n4;
    // Store answer in session — expires in 5 minutes
    $_SESSION["iv_ch_" . $prompt_id] = [
        "answer" => $ans,
        "exp" => time() + 300,
    ];
    echo json_encode(["n1" => $n1, "n2" => $n2, "n3" => $n3, "n4" => $n4]);
    exit();
}

// ─── ACTION: init_love — start timer for unreleased love tap ─────────────────
if ($action === "init_love") {
    if ($prompt_id <= 0) {
        echo json_encode(["success" => false]);
        exit();
    }
    $_SESSION["urp_start_" . $prompt_id] = time();
    echo json_encode(["success" => true]);
    exit();
}

// ─── ACTION: verify — Secret Code unlock ─────────────────────────────────────
if ($action === "verify") {
    $code = trim($_POST["code"] ?? "");
    if ($prompt_id <= 0 || empty($code)) {
        echo json_encode(["success" => false, "message" => "Missing data"]);
        exit();
    }

    // Rate limit: 5 attempts max per prompt per 10 min
    checkRateLimit($prompt_id);

    $stmt = $pdo->prepare(
        "SELECT prompt_text FROM prompts WHERE id = ? AND LOWER(unlock_code) = LOWER(?)",
    );
    $stmt->execute([$prompt_id, $code]);
    $prompt = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prompt) {
        // Correct! Reset rate limit
        unset($_SESSION["rl_" . $prompt_id]);

        if (isset($_SESSION["user_id"])) {
            try {
                $pdo->prepare(
                    "INSERT IGNORE INTO unlocked_prompts (user_id, prompt_id) VALUES (?, ?)",
                )->execute([$_SESSION["user_id"], $prompt_id]);
            } catch (PDOException $e) {
            }
        }
        echo json_encode([
            "success" => true,
            "prompt_text" => $prompt["prompt_text"],
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid code"]);
    }
    exit();
}

// ─── ACTION: insta_viral — verify server-side math answer ────────────────────
if ($action === "insta_viral") {
    if ($prompt_id <= 0) {
        echo json_encode(["success" => false, "message" => "Missing data"]);
        exit();
    }

    $challenge_key = "iv_ch_" . $prompt_id;
    $user_answer = isset($_POST["user_answer"])
        ? (int) $_POST["user_answer"]
        : -9999;

    // Verify challenge exists and hasn't expired
    if (
        !isset($_SESSION[$challenge_key]) ||
        time() > $_SESSION[$challenge_key]["exp"]
    ) {
        echo json_encode([
            "success" => false,
            "message" => "Challenge expired. Please try again.",
        ]);
        exit();
    }

    // Verify answer matches
    if ($user_answer !== (int) $_SESSION[$challenge_key]["answer"]) {
        echo json_encode(["success" => false, "message" => "Wrong answer!"]);
        exit();
    }

    // One-time use — clear challenge from session
    unset($_SESSION[$challenge_key]);

    $stmt = $pdo->prepare(
        "SELECT prompt_text FROM prompts WHERE id = ? AND prompt_type = 'insta_viral'",
    );
    $stmt->execute([$prompt_id]);
    $prompt = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prompt) {
        if (isset($_SESSION["user_id"])) {
            try {
                $pdo->prepare(
                    "INSERT IGNORE INTO unlocked_prompts (user_id, prompt_id) VALUES (?, ?)",
                )->execute([$_SESSION["user_id"], $prompt_id]);
            } catch (PDOException $e) {
            }
        }
        echo json_encode([
            "success" => true,
            "prompt_text" => $prompt["prompt_text"],
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Prompt not found"]);
    }
    exit();
}

// ─── ACTION: unreleased — time-based love tap verification ───────────────────
if ($action === "unreleased") {
    if ($prompt_id <= 0) {
        echo json_encode(["success" => false, "message" => "Missing data"]);
        exit();
    }

    $start_key = "urp_start_" . $prompt_id;
    if (isset($_SESSION[$start_key])) {
        $elapsed = time() - $_SESSION[$start_key];
        // Must take at least 8 seconds (20 taps × ~0.4s per tap minimum)
        if ($elapsed < 8) {
            echo json_encode([
                "success" => false,
                "message" =>
                    "Please complete the love tap challenge properly! ❤️",
            ]);
            exit();
        }
        unset($_SESSION[$start_key]);
    }
    // If session key missing (e.g. session expired), allow gracefully — don't break UX

    $stmt = $pdo->prepare(
        "SELECT prompt_text FROM prompts WHERE id = ? AND prompt_type = 'unreleased'",
    );
    $stmt->execute([$prompt_id]);
    $prompt = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prompt) {
        if (isset($_SESSION["user_id"])) {
            try {
                $pdo->prepare(
                    "INSERT IGNORE INTO unlocked_prompts (user_id, prompt_id) VALUES (?, ?)",
                )->execute([$_SESSION["user_id"], $prompt_id]);
            } catch (PDOException $e) {
            }
        }
        echo json_encode([
            "success" => true,
            "prompt_text" => $prompt["prompt_text"],
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Prompt not found"]);
    }
    exit();
}

// ─── ACTION: already_uploaded — direct unlock ────────────────────────────────
if ($action === "already_uploaded") {
    if ($prompt_id <= 0) {
        echo json_encode(["success" => false, "message" => "Missing data"]);
        exit();
    }

    $stmt = $pdo->prepare("SELECT prompt_text FROM prompts WHERE id = ?");
    $stmt->execute([$prompt_id]);
    $prompt = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prompt) {
        if (isset($_SESSION["user_id"])) {
            try {
                $pdo->prepare(
                    "INSERT IGNORE INTO unlocked_prompts (user_id, prompt_id) VALUES (?, ?)",
                )->execute([$_SESSION["user_id"], $prompt_id]);
            } catch (PDOException $e) {
            }
        }
        echo json_encode([
            "success" => true,
            "prompt_text" => $prompt["prompt_text"],
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Prompt not found"]);
    }
    exit();
}

echo json_encode(["success" => false, "message" => "Invalid request"]);
?>
