<?php
session_start();
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

    $ext = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
    $filename = "blogpostimg/" . uniqid() . "." . $ext;

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $filename)) {
        echo json_encode(["url" => $filename]);
    } else {
        echo json_encode(["error" => "Failed to save file"]);
    }
} else {
    echo json_encode(["error" => "No file uploaded"]);
}
