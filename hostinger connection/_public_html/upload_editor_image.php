<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once "db.php";

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

if (isset($_FILES["file"]) && $_FILES["file"]["error"] === UPLOAD_ERR_OK) {
    // Use finfo to check ACTUAL file bytes — not browser-supplied type (which can be faked)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMime = finfo_file($finfo, $_FILES["file"]["tmp_name"]);
    finfo_close($finfo);

    $allowed = ["image/jpeg", "image/png", "image/gif", "image/webp"];
    if (!in_array($realMime, $allowed)) {
        echo json_encode(["error" => "Invalid file type"]);
        exit();
    }

    $ext = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));
    $rawBase = pathinfo($_FILES["file"]["name"], PATHINFO_FILENAME);
    $cleanSlug = preg_replace('/[^a-zA-Z0-9\-_]/', '-', strtolower($rawBase));
    $cleanSlug = preg_replace('/-+/', '-', trim($cleanSlug, '-'));
    if ($cleanSlug === '') {
        $cleanSlug = 'blog-img-' . uniqid();
    }

    $uploadDir = __DIR__ . '/blogpostimg';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    $targetFile = $uploadDir . '/' . $cleanSlug . '.' . $ext;
    $filename = "blogpostimg/" . $cleanSlug . "." . $ext;

    if (file_exists($targetFile)) {
        $suffix = substr(uniqid(), -4);
        $targetFile = $uploadDir . '/' . $cleanSlug . '-' . $suffix . '.' . $ext;
        $filename = "blogpostimg/" . $cleanSlug . '-' . $suffix . "." . $ext;
    }

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
        echo json_encode(["url" => $filename]);
    } else {
        echo json_encode(["error" => "Failed to save file"]);
    }
} else {
    echo json_encode(["error" => "No file uploaded"]);
}
