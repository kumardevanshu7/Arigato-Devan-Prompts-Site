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

/**
 * @return array{path: string, width: int, height: int}|null
 */
function curated_upload_image(array $file, string $prefix = 'nm', int $maxW = 900, int $maxH = 1600): ?string
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
    $dir     = __DIR__ . '/../uploads/curated_ai_prompts/';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        return null;
    }
    $target = $dir . $prefix . '_' . uniqid('', true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return null;
    }
    $saved = resizeToWebP($target, $maxW, $maxH, 85);
    return 'uploads/curated_ai_prompts/' . basename($saved);
}

/** Alias for leftover live Not Mine admin during transition. */
function not_mine_upload_image(array $file, string $prefix = 'nm', int $maxW = 900, int $maxH = 1600): ?string
{
    return curated_upload_image($file, $prefix, $maxW, $maxH);
}

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

/**
 * Center-crop / cover-resize an image to exact 16:9 WebP (gallery carousel banners).
 */
function cropCoverToWebP(string $src, int $outW = 1920, int $outH = 1080, int $quality = 86): string
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
    if ($origW < 1 || $origH < 1) {
        return $src;
    }

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

    $targetRatio = $outW / $outH;
    $srcRatio    = $origW / $origH;
    if ($srcRatio > $targetRatio) {
        $cropH = $origH;
        $cropW = (int) round($origH * $targetRatio);
        $srcX  = (int) floor(($origW - $cropW) / 2);
        $srcY  = 0;
    } else {
        $cropW = $origW;
        $cropH = (int) round($origW / $targetRatio);
        $srcX  = 0;
        $srcY  = (int) floor(($origH - $cropH) / 2);
    }

    $canvas = imagecreatetruecolor($outW, $outH);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    imagecopyresampled($canvas, $img, 0, 0, $srcX, $srcY, $outW, $outH, $cropW, $cropH);
    imagedestroy($img);

    if ($webpSupport) {
        $dest = preg_replace('/\.[^.]+$/', '.webp', $src);
        imagewebp($canvas, $dest, $quality);
    } else {
        $dest = preg_replace('/\.[^.]+$/', '.jpg', $src);
        imagejpeg($canvas, $dest, $quality);
    }
    imagedestroy($canvas);
    if ($dest !== $src && file_exists($src)) {
        @unlink($src);
    }
    return $dest;
}

/**
 * Upload one gallery carousel banner image as 16:9 WebP.
 * Returns relative web path or null on failure.
 */
function gallery_carousel_upload_image(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    if (($file['size'] ?? 0) > 8 * 1024 * 1024) {
        return null;
    }
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime    = mime_content_type($file['tmp_name']) ?: ($file['type'] ?? '');
    if (!in_array($mime, $allowed, true)) {
        return null;
    }
    $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $ext     = $ext_map[$mime] ?? 'jpg';
    $dir     = __DIR__ . '/../uploads/gallery_carousel/';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        return null;
    }
    $target = $dir . 'gc_' . uniqid('', true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return null;
    }
    $saved = cropCoverToWebP($target, 1920, 1080, 86);
    return 'uploads/gallery_carousel/' . basename($saved);
}
