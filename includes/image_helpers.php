<?php
/**
 * Shared image resize + WebP conversion for uploads.
 */
function resizeToWebP(string $src, int $maxW = 800, int $maxH = 800, int $quality = 82): string
{
    if (!file_exists($src)) {
        return $src;
    }
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatetruecolor')) {
        return $src;
    }
    $info = @getimagesize($src);
    if (!$info) {
        return $src;
    }
    [$origW, $origH, $type] = [$info[0], $info[1], $info[2]];

    $gdInfo      = function_exists('gd_info') ? gd_info() : [];
    $webpSupport = !empty($gdInfo['WebP Support']);

    if ($type === IMAGETYPE_JPEG) {
        $img = @imagecreatefromjpeg($src);
    } elseif ($type === IMAGETYPE_PNG) {
        $img = @imagecreatefrompng($src);
    } elseif ($type === IMAGETYPE_GIF) {
        $img = @imagecreatefromgif($src);
    } elseif ($type === IMAGETYPE_WEBP && $webpSupport) {
        $img = @imagecreatefromwebp($src);
    } else {
        return $src;
    }
    if (!$img) {
        return $src;
    }

    $ratio   = min($maxW / $origW, $maxH / $origH, 1.0);
    $newW    = (int) round($origW * $ratio);
    $newH    = (int) round($origH * $ratio);
    $resized = imagecreatetruecolor($newW, $newH);
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
    imagedestroy($img);

    if ($webpSupport) {
        $dest = preg_replace('/\.[^.]+$/', '.webp', $src);
        imagewebp($resized, $dest, $quality);
    } else {
        $dest = preg_replace('/\.[^.]+$/', '.jpg', $src);
        imagejpeg($resized, $dest, $quality);
    }
    imagedestroy($resized);
    if ($dest !== $src && file_exists($src)) {
        @unlink($src);
    }
    return $dest;
}

function heroine_upload_image(array $file, string $prefix, int $maxW = 600, int $maxH = 600): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime    = mime_content_type($file['tmp_name']) ?: ($file['type'] ?? '');
    if (!in_array($mime, $allowed, true)) {
        return null;
    }
    $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $ext     = $ext_map[$mime] ?? 'jpg';
    $dir     = __DIR__ . '/../uploads/heroines/';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        return null;
    }
    $target = $dir . $prefix . '_' . uniqid('', true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return null;
    }
    $saved = resizeToWebP($target, $maxW, $maxH);
    return 'uploads/heroines/' . basename($saved);
}

/**
 * @return array{path: string, width: int, height: int}|null
 */
function happy_users_upload_image(array $file): ?array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime    = mime_content_type($file['tmp_name']) ?: ($file['type'] ?? '');
    if (!in_array($mime, $allowed, true)) {
        return null;
    }
    $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $ext     = $ext_map[$mime] ?? 'jpg';
    $dir     = __DIR__ . '/../uploads/happy_users/';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        return null;
    }
    $target = $dir . 'hu_' . uniqid('', true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return null;
    }
    $saved = resizeToWebP($target, 1400, 2400, 86);
    $rel   = 'uploads/happy_users/' . basename($saved);
    $info  = @getimagesize(__DIR__ . '/../' . $rel);
    return [
        'path'   => $rel,
        'width'  => $info ? (int) $info[0] : 0,
        'height' => $info ? (int) $info[1] : 0,
    ];
}
