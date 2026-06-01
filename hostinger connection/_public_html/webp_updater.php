<?php
function updateFile($filepath) {
    if (!file_exists($filepath)) return;
    $content = file_get_contents($filepath);
    $original = $content;

    // 1. Replace all landingpics/lan*.png with .webp in array definitions
    $content = preg_replace('/\'landingpics\/lan(\d+)\.png\'/', '\'landingpics/lan$1.webp\'', $content);
    $content = preg_replace('/"landingpics\/lan\$i\.png"/', '"landingpics/lan$i.webp"', $content);

    // 2. Replace filmstrip-frame img tags with picture wrappers
    $imgRegex = '/<div class="filmstrip-frame">\s*<img src="(<\?= \$img \?>)" alt="" loading="lazy">\s*<\/div>/is';
    $pictureReplacement = '<div class="filmstrip-frame">
                            <picture>
                                <source srcset="$1" type="image/webp">
                                <img src="<?= str_replace(\'.webp\', \'.png\', $img) ?>" alt="" loading="lazy">
                            </picture>
                        </div>';
    $content = preg_replace($imgRegex, $pictureReplacement, $content);
    
    // For index.php where the HTML might differ
    $imgRegexIndex = '/<div class="filmstrip-frame">\s*<img src="(<\?= htmlspecialchars\(\$img\) \?>)" alt="Filmstrip Image" loading="lazy">\s*<\/div>/is';
    $pictureReplacementIndex = '<div class="filmstrip-frame">
                        <picture>
                            <source srcset="$1" type="image/webp">
                            <img src="<?= htmlspecialchars(str_replace(\'.webp\', \'.png\', $img)) ?>" alt="Filmstrip Image" loading="lazy">
                        </picture>
                    </div>';
    $content = preg_replace($imgRegexIndex, $pictureReplacementIndex, $content);

    // 3. Update avatars arrays
    $content = preg_replace('/\'profiledp\/([bg]\d+)\.png\'/', '\'profiledp/$1.webp\'', $content);

    if ($original !== $content) {
        file_put_contents($filepath, $content);
        echo "Updated $filepath\n";
    }
}

$files = ['login.php', 'index.php', 'terms.php', 'disclaimer.php', 'onboarding.php', 'profile.php'];
foreach ($files as $f) {
    updateFile($f);
}
echo "Done.\n";
