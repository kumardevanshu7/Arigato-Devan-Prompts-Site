<?php
/**
 * Profile avatar pools — anime DPs + real-person photos in profiledp/real-persons/.
 */

function profile_avatars_anime_male(): array
{
    return [
        'profiledp/b1.webp',
        'profiledp/b2.webp',
        'profiledp/b3.webp',
        'profiledp/b4.webp',
        'profiledp/b5.webp',
        'profiledp/b6.webp',
        'profiledp/b7.webp',
        'profiledp/b8.webp',
        'profiledp/b9.webp',
        'profiledp/b10.webp',
        'profiledp/b11.webp',
        'profiledp/b12.webp',
        'profiledp/b13.webp',
        'profiledp/b14.webp',
    ];
}

function profile_avatars_anime_female(): array
{
    return [
        'profiledp/g1.webp',
        'profiledp/g2.webp',
        'profiledp/g3.webp',
        'profiledp/g4.webp',
        'profiledp/g5.webp',
        'profiledp/g6.webp',
        'profiledp/g7.webp',
        'profiledp/g8.webp',
        'profiledp/g9.webp',
        'profiledp/g10.webp',
        'profiledp/g11.webp',
        'profiledp/g12.webp',
        'profiledp/g13.webp',
        'profiledp/g14.webp',
    ];
}

function profile_avatars_scan_real(string $prefix): array
{
    $dir = __DIR__ . '/../profiledp/real-persons';
    if (!is_dir($dir)) {
        return [];
    }

    $pattern = $dir . '/' . $prefix . '_profile_dp_arigato_prompts-*.webp';
    $files   = glob($pattern) ?: [];
    usort($files, static fn($a, $b) => strnatcmp(basename($a), basename($b)));

    return array_map(
        static fn($path) => 'profiledp/real-persons/' . basename($path),
        $files
    );
}

function profile_avatars_real_male(): array
{
    return profile_avatars_scan_real('boy');
}

function profile_avatars_real_female(): array
{
    return profile_avatars_scan_real('girl');
}

function profile_avatars_all(): array
{
    return array_merge(
        profile_avatars_anime_male(),
        profile_avatars_anime_female(),
        profile_avatars_real_male(),
        profile_avatars_real_female()
    );
}

function profile_avatar_is_valid(string $avatar): bool
{
    return in_array($avatar, profile_avatars_all(), true);
}
