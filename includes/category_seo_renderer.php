<?php
/**
 * Category SEO Renderer Helper
 * Renders the designated SEO authority section for category pages.
 */
function render_category_seo_section(?string $category_key = null): void {
    if (empty($category_key)) {
        return;
    }

    $map = [
        'secret_code'      => __DIR__ . '/category_seo/secret_code.php',
        'unreleased'       => __DIR__ . '/category_seo/unreleased.php',
        'already_uploaded' => __DIR__ . '/category_seo/already_uploaded.php',
        'solo'             => __DIR__ . '/category_seo/solo.php',
        'direct'           => __DIR__ . '/category_seo/direct.php',
        'curated'          => __DIR__ . '/category_seo/curated.php',
    ];

    $file = $map[$category_key] ?? null;
    if ($file && file_exists($file)) {
        include $file;
    }
}
