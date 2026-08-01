-- Gallery carousel slides (admin-managed)
-- Local XAMPP database: prompt_app
--
-- Run on live BEFORE uploading PHP files for this feature.
--
-- Storage contract:
-- image_path = relative web path, e.g. uploads/gallery_carousel/gc_xxx.webp
-- alt_text   = SEO/accessibility alt text for the banner image
-- is_active  = 1 means shown in gallery hero carousel
-- sort_order = lower number appears first

CREATE TABLE IF NOT EXISTS gallery_carousel (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  image_path VARCHAR(255) NOT NULL,
  alt_text VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_active_order (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- If table already exists without alt_text, run:
-- ALTER TABLE gallery_carousel ADD COLUMN alt_text VARCHAR(255) NULL AFTER image_path;
