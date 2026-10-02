<?php
/**
 * Step Small Pics Helper
 * Handles storage, optimization, library tracking, and formatting for "How to use" step sample images.
 */

if (!function_exists('get_steps_small_pics_dir')) {
    function get_steps_small_pics_dir(): string
    {
        $dir = __DIR__ . '/../uploads/steps small pics';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }
}

if (!function_exists('save_step_pic_from_upload')) {
    function save_step_pic_from_upload(array $file, ?string $point_text = null, ?PDO $pdo = null): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload failed or no file selected.'];
        }

        // Limit size to 5MB
        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'Image too large. Maximum size is 5MB.'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
        if ($finfo) finfo_close($finfo);

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        if (!$mime || !isset($allowed[$mime])) {
            return ['success' => false, 'message' => 'Invalid image format. Allowed: JPG, PNG, WEBP, GIF.'];
        }

        $dir = get_steps_small_pics_dir();
        $ext = $allowed[$mime];
        $filename = 'step_' . bin2hex(random_bytes(6)) . '_' . time() . '.' . $ext;
        $target_path = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            return ['success' => false, 'message' => 'Could not move uploaded file.'];
        }

        // Optimize / convert to WebP
        $final_path = $target_path;
        if (function_exists('gd_info')) {
            $info = @getimagesize($target_path);
            if ($info) {
                [$w, $h, $type] = [$info[0], $info[1], $info[2]];
                $gdInfo = gd_info();
                $webpSupport = !empty($gdInfo['WebP Support']);

                $img = match ($type) {
                    IMAGETYPE_JPEG => @imagecreatefromjpeg($target_path),
                    IMAGETYPE_PNG  => @imagecreatefrompng($target_path),
                    IMAGETYPE_WEBP => $webpSupport ? @imagecreatefromwebp($target_path) : null,
                    default        => null,
                };

                if ($img) {
                    $maxDim = 600;
                    $ratio = min($maxDim / max($w, 1), $maxDim / max($h, 1), 1.0);
                    $newW = (int)round($w * $ratio);
                    $newH = (int)round($h * $ratio);

                    $resized = imagecreatetruecolor($newW, $newH);
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                    imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
                    imagedestroy($img);

                    if ($webpSupport) {
                        $webpPath = preg_replace('/\.[^.]+$/', '.webp', $target_path);
                        imagewebp($resized, $webpPath, 85);
                        imagedestroy($resized);
                        if ($webpPath !== $target_path && file_exists($target_path)) {
                            @unlink($target_path);
                        }
                        $final_path = $webpPath;
                    } else {
                        imagedestroy($resized);
                    }
                }
            }
        }

        $rel_path = 'uploads/steps small pics/' . basename($final_path);

        // Record in step_small_pics library table
        if ($pdo === null) {
            global $pdo;
        }
        if ($pdo) {
            try {
                $clean_text = trim((string)$point_text);
                $stmt = $pdo->prepare("SELECT id, times_used, point_text FROM step_small_pics WHERE image_path = ? LIMIT 1");
                $stmt->execute([$rel_path]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    $upd = $pdo->prepare("UPDATE step_small_pics SET times_used = times_used + 1, point_text = COALESCE(NULLIF(?, ''), point_text) WHERE id = ?");
                    $upd->execute([$clean_text, $existing['id']]);
                } else {
                    $ins = $pdo->prepare("INSERT INTO step_small_pics (image_path, point_text, times_used) VALUES (?, ?, 1)");
                    $ins->execute([$rel_path, $clean_text !== '' ? $clean_text : null]);
                }
            } catch (Exception $e) {
                // Table might not exist yet; gracefully ignore
            }
        }

        return [
            'success'    => true,
            'image_path' => $rel_path,
            'point_text' => trim((string)$point_text),
            'filename'   => basename($final_path),
        ];
    }
}

if (!function_exists('get_library_step_pics')) {
    function get_library_step_pics(?PDO $pdo = null, string $search = ''): array
    {
        if ($pdo === null) {
            global $pdo;
        }
        if (!$pdo) {
            return [];
        }

        try {
            $sql = "SELECT id, image_path, point_text, times_used, created_at FROM step_small_pics";
            $params = [];
            $search = trim($search);
            if ($search !== '') {
                $sql .= " WHERE point_text LIKE ? OR image_path LIKE ?";
                $params[] = '%' . $search . '%';
                $params[] = '%' . $search . '%';
            }
            $sql .= " ORDER BY times_used DESC, id DESC LIMIT 100";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Filter out any broken files
            $valid = [];
            $base_dir = dirname(__DIR__);
            foreach ($rows as $r) {
                $full = $base_dir . '/' . ltrim($r['image_path'], '/');
                if (file_exists($full)) {
                    $valid[] = $r;
                }
            }
            return $valid;
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('record_step_pic_usage')) {
    function record_step_pic_usage(string $image_path, ?string $point_text = null, ?PDO $pdo = null): void
    {
        $image_path = trim($image_path);
        if ($image_path === '') return;

        if ($pdo === null) {
            global $pdo;
        }
        if (!$pdo) return;

        try {
            $clean_text = trim((string)$point_text);
            $stmt = $pdo->prepare("SELECT id FROM step_small_pics WHERE image_path = ? LIMIT 1");
            $stmt->execute([$image_path]);
            $id = $stmt->fetchColumn();

            if ($id) {
                $upd = $pdo->prepare("UPDATE step_small_pics SET times_used = times_used + 1, point_text = CASE WHEN (point_text IS NULL OR point_text = '') AND ? <> '' THEN ? ELSE point_text END WHERE id = ?");
                $upd->execute([$clean_text, $clean_text, $id]);
            } else {
                $ins = $pdo->prepare("INSERT INTO step_small_pics (image_path, point_text, times_used) VALUES (?, ?, 1)");
                $ins->execute([$image_path, $clean_text !== '' ? $clean_text : null]);
            }
        } catch (Exception $e) {
        }
    }
}

if (!function_exists('parse_how_to_use_steps')) {
    /**
     * Parses legacy strings, JSON arrays, and new structured objects.
     * Always returns an array of standard objects:
     * [
     *   ['text' => '...', 'images' => ['uploads/steps small pics/...', ...]],
     *   ...
     * ]
     */
    function parse_how_to_use_steps($raw_how): array
    {
        if (empty($raw_how)) {
            return [];
        }

        $decoded = null;
        if (is_string($raw_how)) {
            $raw_how = trim($raw_how);
            if ($raw_how === '') {
                return [];
            }
            $decoded = json_decode($raw_how, true);
        } elseif (is_array($raw_how)) {
            $decoded = $raw_how;
        }

        $steps = [];
        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                if (is_array($item)) {
                    $text = trim((string)($item['text'] ?? ''));
                    $imgs = [];
                    if (!empty($item['images']) && is_array($item['images'])) {
                        foreach ($item['images'] as $img) {
                            $img = trim((string)$img);
                            if ($img !== '' && count($imgs) < 5) {
                                $imgs[] = $img;
                            }
                        }
                    }
                    if ($text !== '' || !empty($imgs)) {
                        $steps[] = [
                            'text'   => $text,
                            'images' => $imgs,
                        ];
                    }
                } elseif (is_string($item)) {
                    $text = trim($item);
                    if ($text !== '') {
                        $steps[] = [
                            'text'   => $text,
                            'images' => [],
                        ];
                    }
                }
            }
        } else {
            // Fallback for legacy plain text separated by newlines
            $lines = preg_split('/\r\n|\r|\n/', (string)$raw_how);
            foreach ($lines as $line) {
                $line = trim((string)$line);
                if ($line !== '') {
                    $steps[] = [
                        'text'   => $line,
                        'images' => [],
                    ];
                }
            }
        }

        return $steps;
    }
}
