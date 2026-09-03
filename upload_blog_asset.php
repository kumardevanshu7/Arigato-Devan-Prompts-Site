<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once "db.php";

header("Content-Type: application/json");

// Verify admin permission
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    echo json_encode(["error" => "Unauthorized access. Admin privileges required."]);
    exit();
}

if (isset($_FILES["file"]) && $_FILES["file"]["error"] === UPLOAD_ERR_OK) {
    $maxBytes = 50 * 1024 * 1024; // 50MB
    if ($_FILES["file"]["size"] > $maxBytes) {
        echo json_encode(["error" => "File too large! Maximum allowed size is 50MB."]);
        exit();
    }

    $origName = $_FILES["file"]["name"];
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $rawBase = pathinfo($origName, PATHINFO_FILENAME);

    $allowedExts = [
        // Documents
        'pdf', 'doc', 'docx', 'txt', 'csv', 'json',
        // Images & Graphics
        'png', 'jpg', 'jpeg', 'webp', 'gif', 'svg', 'psd', 'ai',
        // Audio
        'mp3', 'wav', 'm4a', 'ogg', 'aac',
        // Video
        'mp4', 'webm', 'mov', 'mkv',
        // Archives
        'zip', 'rar', '7z', 'tar', 'gz'
    ];

    if (!in_array($ext, $allowedExts)) {
        echo json_encode(["error" => "File type '." . $ext . "' is not allowed for security reasons."]);
        exit();
    }

    // Determine category icon type
    $typeCategory = 'file';
    if (in_array($ext, ['pdf'])) {
        $typeCategory = 'pdf';
    } elseif (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) {
        $typeCategory = 'archive';
    } elseif (in_array($ext, ['mp3', 'wav', 'm4a', 'ogg', 'aac'])) {
        $typeCategory = 'audio';
    } elseif (in_array($ext, ['mp4', 'webm', 'mov', 'mkv'])) {
        $typeCategory = 'video';
    } elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg', 'psd', 'ai'])) {
        $typeCategory = 'image';
    }

    // Clean slug for SEO-friendly filename
    $cleanSlug = preg_replace('/[^a-zA-Z0-9\-_]/', '-', strtolower($rawBase));
    $cleanSlug = preg_replace('/-+/', '-', trim($cleanSlug, '-'));
    if ($cleanSlug === '') {
        $cleanSlug = 'asset-' . uniqid();
    }

    $uploadDir = __DIR__ . '/blogassets';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    $targetFile = $uploadDir . '/' . $cleanSlug . '.' . $ext;
    $publicPath = 'blogassets/' . $cleanSlug . '.' . $ext;

    // Avoid overwriting if identical file exists
    if (file_exists($targetFile)) {
        $suffix = substr(uniqid(), -4);
        $targetFile = $uploadDir . '/' . $cleanSlug . '-' . $suffix . '.' . $ext;
        $publicPath = 'blogassets/' . $cleanSlug . '-' . $suffix . '.' . $ext;
    }

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
        // Format readable file size
        $bytes = filesize($targetFile);
        $formattedSize = '0 KB';
        if ($bytes >= 1048576) {
            $formattedSize = number_format($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            $formattedSize = number_format($bytes / 1024, 0) . ' KB';
        } else {
            $formattedSize = $bytes . ' B';
        }

        echo json_encode([
            "success" => true,
            "url" => $publicPath,
            "filename" => $origName,
            "size" => $formattedSize,
            "ext" => strtoupper($ext),
            "type" => $typeCategory
        ]);
    } else {
        echo json_encode(["error" => "Failed to save uploaded asset file to server."]);
    }
} else {
    echo json_encode(["error" => "No file uploaded or upload error occurred."]);
}
