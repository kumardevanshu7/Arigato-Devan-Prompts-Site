<?php
/**
 * Indexable gallery collections.
 *
 * gallery.php accepts any ?tag= value, so the ~115 tags in the database would
 * otherwise expose that many near-identical faceted URLs. Only the tags listed
 * here get unique metadata and an index directive; every other ?tag= value is
 * served noindex by gallery.php.
 *
 * Keys are matched against the raw ?tag= value. gallery_fetch_prompts() filters
 * with LIKE '%tag%', so the key 'kiss' also collects 'cheek kiss',
 * 'forehead kiss', 'soft kiss' and so on. Keep a tag out of this list until it
 * has enough prompts to make a page worth landing on.
 */

function gallery_seo_collections(): array
{
    static $collections = null;
    if ($collections !== null) {
        return $collections;
    }

    $collections = [
        'romantic' => [
            'label' => 'Romantic',
            'h1'    => 'Romantic Couple Prompts <em>for AI</em>',
            'title' => 'Romantic Couple Prompts for Gemini AI (Free) | Arigato Devan',
            'desc'  => 'Romantic AI couple prompts for Gemini and ChatGPT. Copy ready-made prompts for soft, dreamy couple photos that keep both faces looking like you.',
            'intro' => 'These are the soft, warm couple prompts &mdash; the ones built for close moments, gentle light and expressions that actually look affectionate instead of posed. Every prompt is written to hold both faces steady, so the two people in the photo still look like you after the AI is done.',
        ],
        'selfie' => [
            'label' => 'Selfie',
            'h1'    => 'Couple Selfie Prompts <em>for AI</em>',
            'title' => 'Couple Selfie Prompts for Gemini AI | Free Copy &amp; Paste',
            'desc'  => 'Ready-to-use couple selfie prompts for Gemini AI and ChatGPT: mirror selfies, night selfies and close-up selfie angles with consistent faces.',
            'intro' => 'Selfie framing is the hardest thing to get right in AI photos &mdash; arms bend the wrong way, faces stretch, and the phone disappears. These prompts handle the camera angle for you, covering mirror selfies, night selfies and arm-length close-ups.',
        ],
        'candid' => [
            'label' => 'Candid',
            'h1'    => 'Candid Couple Prompts <em>for AI</em>',
            'title' => 'Candid Couple Prompts for Natural AI Photos | Arigato Devan',
            'desc'  => 'Candid, unposed couple prompts for Gemini AI and ChatGPT. Laughing, walking and in-between moments that look like real photos, not stiff poses.',
            'intro' => 'Candid prompts are for when you want the photo to look caught, not staged. Think mid-laugh, looking away, walking and talking &mdash; the in-between moments that make a couple photo feel real rather than assembled.',
        ],
        'closeup' => [
            'label' => 'Close-up',
            'h1'    => 'Close-Up Couple Prompts <em>for AI</em>',
            'title' => 'Close-Up Couple Prompts for Gemini AI | Arigato Devan',
            'desc'  => 'Close-up and extreme close-up couple prompts for AI images. Tight framing that keeps facial detail sharp and both faces clearly recognisable.',
            'intro' => 'Close-up framing is where face accuracy matters most, because there is nowhere for the AI to hide a mistake. This is our largest collection, covering tight portraits, extreme close-ups and shots where the two faces nearly touch.',
        ],
        'kiss' => [
            'label' => 'Kiss',
            'h1'    => 'Couple Kiss Prompts <em>for AI</em>',
            'title' => 'Couple Kiss Prompts for AI: Cheek, Forehead &amp; Soft Kiss',
            'desc'  => 'Tasteful couple kiss prompts for Gemini AI and ChatGPT, covering cheek kiss, forehead kiss and soft kiss poses with consistent faces.',
            'intro' => 'A kiss pose hides part of both faces, which is exactly where most AI tools start guessing and get the features wrong. These prompts spell out the angle and the overlap so the visible half of each face stays accurate &mdash; cheek kisses, forehead kisses and softer close moments.',
        ],
        'hug' => [
            'label' => 'Hug',
            'h1'    => 'Couple Hug Prompts <em>for AI</em>',
            'title' => 'Couple Hug &amp; Cuddle Prompts for Gemini AI | Arigato Devan',
            'desc'  => 'Warm couple hug and cuddle prompts for AI image generation. Back hugs, side cuddles and cosy poses that keep both faces intact.',
            'intro' => 'Hugs are deceptively tricky: arms cross, shoulders overlap, and AI tools love to merge two people into one shape. These prompts describe how the bodies sit together so you get a believable hug with two separate, recognisable faces.',
        ],
        'collage' => [
            'label' => 'Collage',
            'h1'    => 'Collage Couple Prompts <em>2 &amp; 3 Frame</em>',
            'title' => '2 &amp; 3 Frame Collage Couple Prompts for Gemini AI',
            'desc'  => 'Multi-frame collage couple prompts for Gemini AI. Generate 2-frame and 3-frame photo grids in one go, with matching faces across every panel.',
            'intro' => 'Collage prompts produce several photos in a single generation, laid out as a 2-frame or 3-frame grid. The hard part is keeping the same two people consistent across every panel, and that is what these prompts are written to control.',
        ],
        'night' => [
            'label' => 'Night',
            'h1'    => 'Night Couple Prompts <em>for AI</em>',
            'title' => 'Night Couple Prompts for AI: Neon, Flash &amp; Low Light',
            'desc'  => 'Night-time couple prompts for Gemini AI and ChatGPT. Neon streets, flash photography and low-light scenes that stay sharp instead of muddy.',
            'intro' => 'Low light is where AI photos usually fall apart into noise and smudged features. These night prompts name the light source directly &mdash; neon signs, street lamps, phone screens, camera flash &mdash; so the scene stays moody without losing either face.',
        ],
        'playful' => [
            'label' => 'Playful',
            'h1'    => 'Playful Couple Prompts <em>for AI</em>',
            'title' => 'Playful &amp; Fun Couple Prompts for Gemini AI',
            'desc'  => 'Playful couple prompts for AI photos: teasing, laughing and goofy poses that feel light and fun instead of overly posed.',
            'intro' => 'Not every couple photo needs to be serious and cinematic. These prompts go for the lighter side &mdash; teasing, laughing, pulling faces, messing around &mdash; while still keeping the two people recognisable.',
        ],
        'goldenhour' => [
            'label' => 'Golden hour',
            'h1'    => 'Golden Hour Couple Prompts <em>for AI</em>',
            'title' => 'Golden Hour Couple Prompts for AI Photos | Arigato Devan',
            'desc'  => 'Golden hour couple prompts for Gemini AI. Warm sunset light, backlit glow and soft shadows for dreamy couple portraits.',
            'intro' => 'Golden hour is the easiest way to make an AI photo look expensive: low sun, long shadows, warm rim light around both faces. These prompts pin down the sun position and the direction of the glow so the light lands the same way every time.',
        ],
        'traditional' => [
            'label' => 'Traditional',
            'h1'    => 'Traditional Indian Couple Prompts <em>for AI</em>',
            'title' => 'Traditional Indian Couple Prompts for Gemini AI',
            'desc'  => 'Traditional Indian couple prompts for AI photos: saree, dupatta, temple and festival looks made for Indian couples and wedding-style shoots.',
            'intro' => 'Most AI image tools are trained mainly on Western reference photos, so Indian outfits and jewellery come out approximate at best. These prompts describe the drape, the fabric and the detailing properly, covering saree and dupatta looks, temple settings and festival wear.',
        ],
        'car' => [
            'label' => 'Car',
            'h1'    => 'Car Couple Prompts <em>for AI</em>',
            'title' => 'Car Couple Prompts for AI Photos | Arigato Devan',
            'desc'  => 'Car couple prompts for Gemini AI and ChatGPT: road trip windows, night drives and sunroof poses with clean framing and consistent faces.',
            'intro' => 'Car shots give you a built-in frame &mdash; a window, a windscreen, an open sunroof &mdash; which is why they photograph so well. These prompts set the seat positions and the light coming through the glass so both faces stay lit instead of falling into shadow.',
        ],
        'cafe' => [
            'label' => 'Cafe date',
            'h1'    => 'Cafe Date Couple Prompts <em>for AI</em>',
            'title' => 'Cafe Date Couple Prompts for Gemini AI | Arigato Devan',
            'desc'  => 'Cafe and coffee date couple prompts for AI photos. Cosy table scenes, warm indoor light and natural date-day poses.',
            'intro' => 'The cafe date is a whole aesthetic: a small table, two cups, warm window light and a slightly blurred background. These prompts build that scene properly, including where the couple sits relative to the window.',
        ],
        'flash' => [
            'label' => 'Flash & retro',
            'h1'    => 'Flash &amp; Retro Couple Prompts <em>for AI</em>',
            'title' => 'Flash &amp; 90s Retro Couple Prompts for AI | Arigato Devan',
            'desc'  => 'Direct-flash and 90s retro couple prompts for Gemini AI. Harsh flash, film grain and vintage looks for a nostalgic couple photo aesthetic.',
            'intro' => 'Direct flash is deliberately unflattering, and that is the point &mdash; harsh light, dark background, a bit of grain, like a photo pulled off a disposable camera. These prompts recreate that 90s and 2000s film look without turning either face into mush.',
        ],
    ];

    return $collections;
}

/**
 * Returns the SEO block for an indexable collection, or null when the tag is
 * not curated (in which case gallery.php should serve noindex).
 */
function gallery_seo_meta(?string $tag): ?array
{
    $tag = trim(strtolower((string) $tag));
    if ($tag === '' || $tag === 'all') {
        return null;
    }

    return gallery_seo_collections()[$tag] ?? null;
}

/**
 * Absolute URLs for every indexable collection, for sitemap.php.
 */
function gallery_seo_collection_urls(string $base = 'https://arigatodevan.com'): array
{
    $urls = [];
    foreach (array_keys(gallery_seo_collections()) as $tag) {
        $urls[] = rtrim($base, '/') . '/gallery.php?tag=' . urlencode($tag);
    }

    return $urls;
}
