<?php
/**
 * Tag Descriptions Helper
 * Provides dictionary, storage, synchronization, and retrieval of short descriptions (< 40 words) for tags.
 */

if (!function_exists('count_tag_words')) {
    function count_tag_words(?string $text): int
    {
        $text = trim((string)$text);
        if ($text === '') {
            return 0;
        }
        return count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY));
    }
}

if (!function_exists('get_curated_tag_descriptions_dictionary')) {
    /**
     * Complete dictionary of creative short descriptions (< 40 words)
     * covering all known popular tags and styles.
     */
    function get_curated_tag_descriptions_dictionary(): array
    {
        return [
            '1980s' => 'Vintage 80s aesthetics with analog grain, warm nostalgic tones, and retro couple fashion that stays true to both likenesses.',
            '1980s portrait' => 'Classic 1980s studio portrait framing with soft diffuser glow, vintage hairdos, and timeless nostalgic warmth.',
            '2 frame collage' => 'Two-panel split collage capturing matching outfits and seamless facial likeness across both romantic scenes.',
            '2000s' => 'Early 2000s nostalgic aesthetic with pop tones, digital camera flare, and Y2K couple styling.',
            '3 frame collage' => 'Three-panel vertical or horizontal story grid maintaining continuous couple likeness and complementary colour palettes.',
            '3 peoples' => 'Balanced trio and group compositions with sharp facial clarity, avoiding distorted hands or merged silhouettes.',
            '90s flash' => 'High-contrast 90s direct flash photography with dark backgrounds, candid expressions, and raw film texture.',
            'backpack look' => 'Casual travel looks with stylish backpacks, outdoor city vibes, and natural candid couple wandering.',
            'badge' => 'Chic couple portrait styling featuring custom identity badges, varsity logos, and playful detail framing.',
            'balcony' => 'Romantic balcony scenes with breezy golden sunsets, city skylines, and intimate railings framing.',
            'beach moment' => 'Sun-kissed oceanside memories with crashing waves, gentle breezes, and golden reflections with sharp facial consistency.',
            'beautiful poster' => 'Cinematic movie poster framing with dramatic typography space, bold color grading, and blockbuster romantic presence.',
            'black and white' => 'Timeless monochrome contrast with deep shadows, sculptured rim lighting, and elegant couple intimacy.',
            'black saree' => 'Bold and elegant black saree aesthetic with sheer pallu draping and cinematic contrast, keeping couple likeness crisp.',
            'blue dress' => 'Serene blue dress styling with cool ambient tones, flowing fabric texture, and romantic daylight harmony.',
            'blue top' => 'Relaxed everyday style in matching blue tops, effortless daylight lighting, and genuine smiles.',
            'blurry' => 'Artistic motion blur and dreamlike light trails while keeping couple faces sharp, emotive, and recognisable.',
            'butterfly' => 'Whimsical fairytale details with fluttering butterflies, pastel daylight, and soft dreamy focus.',
            'cafe' => 'Cosy coffee date moments with steaming mugs, warm cafe interior tones, and soft depth of field for date-day vibes.',
            'cafe date' => 'Intimate coffee shop corners with warm ambient glow, table chatter, and natural relaxed expressions.',
            'campaign' => 'Editorial fashion campaign framing with high-end magazine styling, crisp lighting, and polished couple posing.',
            'candid' => 'Unposed, genuine couple moments full of laughter and gentle connection, avoiding stiff or artificial postures.',
            'car' => 'Cinematic car framing through windows, windscreens, and sunroofs with clean interior ambient illumination.',
            'cheek hold' => 'Tender touch moments with gentle palm on cheek, affectionate gazes, and soft emotional warmth.',
            'cheek kiss' => 'Playful and sweet cheek kisses with natural facial cheek contouring and affectionate happy expressions.',
            'chinup' => 'Confident, loving poses with gentle chin lifts, direct eye contact, and sharp jawline lighting.',
            'cjp' => 'Signature curated couple prompt aesthetics with cinematic color tones and impeccable facial consistency.',
            'close' => 'Intimate near-distance couple portraiture highlighting micro-expressions, soft breath, and emotional connection.',
            'close bond' => 'Heartfelt moments capturing true emotional bonding, gentle head leans, and quiet togetherness.',
            'close hug' => 'Tight, protective embraces with natural arm wrapping, keeping both individual faces clearly delineated.',
            'close sitting' => 'Cozy side-by-side sitting arrangements on steps, couches, or curbs with relaxed body language.',
            'closeup' => 'Tight portraits focusing on eye contact, skin texture, and lifelike expressions with maximum facial sharpness.',
            'cloudy beach' => 'Moody coastal shores under soft overcast skies, wind-blown hair, and atmospheric sea foam.',
            'cold drink' => 'Refreshing summer date vibes sharing chilled sodas, iced coffees, and playful café moments.',
            'cosplay' => 'Vibrant anime and pop-culture couple cosplay with detailed costumes, themed props, and dynamic poses.',
            'couple' => 'Classic couple portraits celebrating shared love, matched styling, and balanced cinematic light.',
            'couple bond' => 'Deep emotional unity captured through gentle touches, synchronized laughter, and steady loving gazes.',
            'couple cosplay' => 'Dual character cosplay with matching thematic lore, fantasy accessories, and stylized lighting.',
            'couple cuddle' => 'Snug couch and bedroom cuddles wrapped in cozy blankets with peaceful, affectionate faces.',
            'couple hug' => 'Warm, protective embraces that blend natural posture and two distinct, lifelike facial profiles.',
            'couple vibe' => 'Effortless cool couple aesthetic with matching streetwear, attitude, and vibrant modern tones.',
            'cowboyhat' => 'Rustic western romance with stylish cowboy hats, warm dust lighting, and open ranch backdrops.',
            'cozy home' => 'Intimate at-home dates with warm lamps, soft knit sweaters, and relaxed, happy indoor comfort.',
            'cozy rest' => 'Peaceful moments resting together, quiet Sunday mornings, and tender sleepy smiles.',
            'cozy room' => 'Warm fairy-lit bedroom sanctuaries, soft bedsheets, and intimate, quiet couple conversations.',
            'cricket fan' => 'Passionate cricket stadium excitement, team jerseys, tricolor cheek paint, and celebratory hugs.',
            'cricket vibes' => 'Energetic cricket fan romance with stadium floodlights, shared jerseys, and spirited cheer.',
            'dark hero look' => 'Moody vigilante aesthetic with sleek dark leather, shadows, and cinematic neo-noir romance.',
            'dark suit' => 'Sharp tailored dark suit and cocktail dress sophistication, perfect for red-carpet couple glam.',
            'daylight' => 'Pure natural sun illumination with soft shadows, clean colors, and genuine outdoor vitality.',
            'distance' => 'Poetic perspective shots with meaningful eye contact across distance, framing longing and love.',
            'dreamy' => 'Ethereal soft-focus visuals with floating dust motes, pastel gradients, and romantic warmth.',
            'drink' => 'Casual cafe sipping and toasting drinks with authentic hand holding and candid table banter.',
            'duckface pose' => 'Cute, playful pout poses with fun energy, expressive eyes, and casual smartphone snapshot vibes.',
            'dupatta' => 'Dreamy dupatta poses with flying sheer borders and golden backlight for authentic Indian couple elegance.',
            'earring' => 'Intimate jewelry-focused framing showing traditional jhumkas, sparkling studs, and gentle head turns.',
            'elevator' => 'Sleek elevator mirror reflections with stainless-steel sheen, ambient ceiling panels, and modern couple allure.',
            'elevator moment' => 'Spontaneous private elevator glances, mirrored depth, and sharp urban couple chic.',
            'emotional' => 'Deeply moving, tearful or tender expressions that convey pure vulnerability and heartfelt devotion.',
            'everyday style' => 'Effortless casual fashion in sneakers, tees, and denim for relatable real-life couple romance.',
            'eyecontact' => 'Electric soul-gazing eye contact that anchors the entire composition with raw emotional power.',
            'face press' => 'Intimate cheek-to-cheek and temple touches that radiate warmth while keeping both faces clearly recognizable.',
            'fairytale look' => 'Enchanted forest aesthetics with magical glow, floral arches, and regal fairytale romance.',
            'festival' => 'Vibrant festive celebrations with twinkling lights, colorful ethnic attire, and joyful energy.',
            'festival night' => 'Luminous festive nights filled with sparklers, lanterns, glowing diyas, and celebratory smiles.',
            'film grain' => 'Authentic 35mm film texture, subtle chromatic grain, and timeless analog photograph character.',
            'fingerheart' => 'Trendy Korean finger-heart gestures with cute smiles, playful charm, and K-drama romantic vibes.',
            'fishpout' => 'Silly, affectionate fish-pout expressions bringing humorous candid fun to couple selfie moments.',
            'flash' => 'Direct flash aesthetic with crisp foreground pop, 90s party energy, and punchy contrast.',
            'floral' => 'Lush flower bouquets, blooming gardens, and botanical backdrops for fresh, romantic vibrancy.',
            'forehead kiss' => 'Deeply affectionate forehead kiss moments symbolising love and devotion, with soft rim light.',
            'forehead lean' => 'Quiet forehead-to-forehead leans with closed eyes, mutual trust, and intimate stillness.',
            'funny expression' => 'Quirky facial expressions, spontaneous laughing bursts, and uninhibited couple joy.',
            'garden date' => 'Sun-dappled botanical garden strolls among green foliage, park benches, and gentle hand-holding.',
            'gentle kiss' => 'Soft, tender lip-to-cheek kisses with peaceful expressions and warm rim illumination.',
            'getting ready' => 'Behind-the-scenes vanity mirror shots, fixing ties, adjusting earrings, and pre-date excitement.',
            'glam' => 'High-fashion red carpet glam with shimmering fabrics, flawless grooming, and dramatic luxury lighting.',
            'glam dress' => 'Show-stopping evening gowns and cocktail dresses with rich textures and elegant couple poses.',
            'glam vibes' => 'Sophisticated champagne nights, opulent interior lounges, and polished couple radiance.',
            'goldenhour' => 'Warm sunset light, low sun, and golden rim lighting for glowing couple portraits with natural depth.',
            'grass' => 'Lying together on fresh green lawns under open skies, relaxed park picnics, and sunlit daydreams.',
            'group photo' => 'Multi-person friend and couple group gatherings with balanced exposure and sharp faces all around.',
            'hair mustache' => 'Playful, goofy moments using hair strands as fake mustaches with contagious laughter.',
            'hallway' => 'Dramatic architectural perspective down hotel or apartment corridors with moody ambient sconces.',
            'handkiss' => 'Chivalrous royal hand kisses with gentle bowing, tender eye contact, and romantic grace.',
            'heart' => 'Love-filled gestures, heart-shaped shadow silhouettes, and sweet romantic symbolism.',
            'heartfelt moment' => 'Tender, emotional gestures that capture sincere devotion and unfiltered human warmth.',
            'hidden moment' => 'Intimate stolen glances tucked behind curtains, pillars, or umbrellas away from the crowd.',
            'hoodie' => 'Snug oversized streetwear hoodies, casual chill vibes, and cozy winter evening companionship.',
            'hug' => 'Warm, protective hugs and cuddles with natural body overlap that keep both facial contours clear.',
            'korean heart' => 'Sweet K-pop inspired heart poses, cute aegyo charm, and bright, pastel aesthetics.',
            'lipstick' => 'Playful lipstick kiss marks on cheeks, touch-up mirror moments, and bold romantic red tones.',
            'lounge' => 'Plush velvet sofa lounging, low ambient lighting, and relaxed evening date sophistication.',
            'lovely' => 'Pure, sweet romance bathed in gentle pastel daylight with soft smiles and tender gestures.',
            'luxury dress' => 'Haute couture gowns with dramatic embroidery, silk satins, and opulent gala atmosphere.',
            'luxury vibes' => 'Upscale penthouses, sports cars, and private terraces with five-star couple elegance.',
            'macro shot' => 'Ultra-detailed macro framing on intertwined fingers, wedding rings, and expressive eye details.',
            'matching outfits' => 'Coordinated couple wardrobe in matching tones, streetwear silhouettes, and harmonized colors.',
            'metro' => 'Cinematic subway station platforms and train transit car frames with moody urban fluorescent light.',
            'mirror selfie' => 'Trendy mirror selfie angles with realistic phone handling and reflective ambient glow.',
            'morning vibes' => 'Soft early morning sunlight filtering through sheer curtains, steaming mugs, and peaceful wake-ups.',
            'moustache' => 'Distinguished gentleman styling with well-groomed mustaches, retro charm, and classic poise.',
            'movie poster style' => 'Epic cinematic compositions with title header space, atmospheric haze, and blockbuster color grades.',
            'navratri' => 'Traditional Navratri garba nights with colorful chaniya cholis, oxidized jewelry, and festive twirls.',
            'neon' => 'Vibrant electric pink, cyan, and purple neon reflections cutting through dark urban nights.',
            'night' => 'Moody night scenes with street lamps, city glows, and flash effects keeping faces clear and sharp.',
            'night couple' => 'Romantic nocturnal strolls under starry skies and illuminated city avenues with cinematic warmth.',
            'night selfie' => 'Crisp smartphone selfie shots in nighttime cityscape backdrops with bright screen flash glow.',
            'night vibes' => 'Ambient evening aesthetics, midnight drives, and quiet terrace talks under distant city lights.',
            'nose touch' => 'Adorably intimate Eskimo kisses and gentle nose bumps full of giggles and tender affection.',
            'only for boy' => 'Masculine solo prompts highlighting confident posture, sharp jawlines, and clean editorial lighting.',
            'outdoors' => 'Vibrant open-air landscapes, mountain trails, and natural scenic vistas with expansive depth.',
            'party' => 'High-energy party celebrations with confetti, dance floor lights, and celebratory champagne toasts.',
            'party night' => 'Electrifying club and rooftop party atmospheres with strobe accents and fashionable nightwear.',
            'phone selfie' => 'Casual arm-extended phone selfies with authentic wide angles, natural hand grips, and bright smiles.',
            'playful' => 'Lively, teasing couple banter, laughing mid-sentence, and goofy poses that feel delightfully real.',
            'playful cheek' => 'Affectionate cheek squishes, gentle pinches, and happy giggles full of youthful romance.',
            'playful nuzzle' => 'Tender nose and cheek nuzzling into hair and neck with sweet, cozy closeness.',
            'playful pose' => 'Dynamic candid poses like piggybacks, spins, and silly faces bursting with happy energy.',
            'playful trace' => 'Gentle finger tracing along jawlines or noses with loving smiles and delicate lighting.',
            'polaroid' => 'Classic white-bordered instant film photos with vintage color shifts and tangible memory appeal.',
            'polka dot dress' => 'Charming retro polka dot dress styling with vintage 50s flair and sunny outdoor picnic charm.',
            'portrait' => 'Clean, balanced portraits with flattering bokeh backgrounds and true-to-life facial accuracy.',
            'poster' => 'High-impact promotional poster aesthetic with dramatic contrast and cinematic scale.',
            'pout' => 'Cute, endearing pouty expressions that bring humor and playful romance to couple portraits.',
            'pout pose' => 'Sassy, fun couple pout poses with smartphone selfie angles and stylish youthful charm.',
            'red dress' => 'Stunning crimson and ruby dresses that command attention with cinematic passion and elegance.',
            'restaurant' => 'Candlelit dinner tables with gourmet settings, soft wine glass reflections, and romantic whispers.',
            'retro' => 'Vintage 80s and 90s film aesthetic with warm grain, nostalgic hues, and timeless couple style.',
            'romantic' => 'Soft, dreamy couple prompts designed for gentle light, tender expressions, and authentic likeness.',
            'romantic moment' => 'Unscripted sparks of deep affection, gentle hand caresses, and quiet heartfelt devotion.',
            'romantic mood' => 'Atmospheric moodiness with dim candlelight, soft rain outside windows, and peaceful closeness.',
            'romantic pose' => 'Graceful, balanced couple embraces that photograph effortlessly while preserving face identity.',
            'rooftop date' => 'Panoramic rooftop views overlooking city skylines during twilight sunset with fairy lights.',
            'rose' => 'Classic red roses, surprise flower handoffs, and romantic floral accents with vibrant petals.',
            'royal enfield' => 'Classic biker romance on open highways and vintage Royal Enfield rides with rugged outdoor light.',
            'ruby dress' => 'Deep jewel-toned ruby dress glamour with luxurious sheen, golden accents, and regal presence.',
            'saree' => 'Timeless saree looks capturing graceful pallu drapes, vibrant silk textures, and classic Indian elegance.',
            'saree look' => 'Traditional ethnic aesthetics featuring ornate sarees, delicate bangles, and authentic drape detailing.',
            'selfie' => 'Ready-to-use couple selfie prompts handling camera angles, mirror selfies, and natural close-ups.',
            'shoulder lean' => 'Peaceful head rests on shoulders, signifying trust, comfort, and quiet evening companionship.',
            'sky' => 'Dramatic wide-angle compositions against expansive blue skies, fluffy clouds, or twilight hues.',
            'soda can' => 'Retro roadside date aesthetic sharing vintage soda cans, classic straws, and summer romance.',
            'solo' => 'Striking single-subject portraits crafted for dramatic lighting, character depth, and sharp features.',
            'solo boy' => 'Dedicated male solo prompts showcasing sharp tailoring, rugged street style, and cinematic gaze.',
            'solo girl' => 'Captivating female solo portraits with elegant hair movement, radiant skin tones, and rich styling.',
            'stadium' => 'Electrifying sports arena atmospheres with roaring crowds, giant floodlights, and stadium seats.',
            'stairs' => 'Architectural stairwell compositions with leading lines, casual sitting poses, and city chic.',
            'straw kiss' => 'Playful diner date moments sharing a single milkshake or cold beverage through two straws.',
            'sunlight' => 'Brilliant golden sun rays and sun flares illuminating hair highlights with radiant natural warmth.',
            'superhero duo' => 'Dynamic heroic couple pairing in high-tech suits with cinematic smoke, embers, and epic stances.',
            'superhero look' => 'Comic-inspired vigilante styling with sleek suits, emblems, and dramatic heroic backlights.',
            'superhero vibes' => 'Action-movie romance with flying sparks, futuristic cityscapes, and powerful couple teamwork.',
            'sweet closeup' => 'Gentle micro-portraits capturing delicate eyelashes, soft smiles, and intimate couple whispers.',
            'sweet moment' => 'Tender everyday gestures of love like sharing jackets, fixing hair, or wiping away a tear.',
            'temple' => 'Sacred Indian temple stone carvings, brass bells, traditional marigold garlands, and spiritual peace.',
            'tender moment' => 'Vulnerable and profoundly loving moments celebrating quiet devotion and emotional connection.',
            'traditional' => 'Traditional Indian couple outfits with rich embroidery, temple settings, and authentic festival wear.',
            'train' => 'Atmospheric train passenger window views, historic rail journeys, and romantic travel wanderlust.',
            'vintage street' => 'Cobblestone alleyways, vintage lampposts, and old European or heritage Indian streetscapes.',
            'vintage vibe' => 'Timeless sepia and aged film tones with classic 1970s wardrobe and nostalgic romance.',
            'wall lean' => 'Relaxed street portraits leaning against textured brick or concrete walls with casual confidence.',
            'warm hug' => 'Deep, comforting embrace filled with safe warmth, closed eyes, and genuine heart-to-heart closeness.',
            'webcam' => 'Early 2000s low-fi webcam aesthetic with nostalgic pixel charm, cool monitor glow, and raw candor.',
            'yellow dress' => 'Vibrant sunflower yellow dresses radiating sunshine, summer energy, and joyful romantic optimism.',
        ];
    }
}

if (!function_exists('generate_creative_tag_description')) {
    /**
     * Fallback generator for any custom or newly added tag (< 25 words).
     */
    function generate_creative_tag_description(string $tag): string
    {
        $clean = ucwords(trim(str_replace(['-', '_'], ' ', $tag)));
        return "Creative {$clean} couple prompts with cinematic framing, balanced lighting, and lifelike expressions that keep facial features sharp and consistent.";
    }
}

if (!function_exists('sync_discovered_tags')) {
    /**
     * Discovers all unique tags from prompts table, ensures every tag exists
     * in tag_descriptions table, and seeds empty descriptions with creative copy.
     */
    function sync_discovered_tags(?PDO $pdo = null): array
    {
        if ($pdo === null) {
            global $pdo;
        }
        if (!$pdo) {
            return [];
        }

        // Ensure table exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tag_descriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tag_name VARCHAR(100) NOT NULL UNIQUE,
                description TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_tag_name (tag_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $dict = get_curated_tag_descriptions_dictionary();

        // 1. Collect all distinct tags from prompts
        $tag_counts = [];
        try {
            $stmt = $pdo->query("SELECT tag FROM prompts WHERE tag IS NOT NULL AND tag != '' AND (is_trial = 0 OR is_trial IS NULL)");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $parts = explode(',', strtolower($row['tag']));
                foreach ($parts as $p) {
                    $p = trim($p);
                    if ($p !== '' && $p !== 'all') {
                        $tag_counts[$p] = ($tag_counts[$p] ?? 0) + 1;
                    }
                }
            }
        } catch (Exception $e) {}

        // Also check curated_prompts table if available
        try {
            $chk = $pdo->query("SHOW TABLES LIKE 'curated_prompts'")->fetchColumn();
            if ($chk) {
                $cstmt = $pdo->query("SELECT tags FROM curated_prompts WHERE tags IS NOT NULL AND tags != ''");
                while ($crow = $cstmt->fetch(PDO::FETCH_ASSOC)) {
                    $parts = explode(',', strtolower($crow['tags']));
                    foreach ($parts as $p) {
                        $p = trim($p);
                        if ($p !== '' && $p !== 'all') {
                            $tag_counts[$p] = ($tag_counts[$p] ?? 0) + 1;
                        }
                    }
                }
            }
        } catch (Exception $e) {}

        // Also include all dictionary tags so they are present in admin
        foreach (array_keys($dict) as $dk) {
            if (!isset($tag_counts[$dk])) {
                $tag_counts[$dk] = 0;
            }
        }

        // 2. Fetch existing tag_descriptions rows
        $existing = [];
        try {
            $estmt = $pdo->query("SELECT tag_name, description FROM tag_descriptions");
            while ($erow = $estmt->fetch(PDO::FETCH_ASSOC)) {
                $existing[$erow['tag_name']] = trim((string)$erow['description']);
            }
        } catch (Exception $e) {}

        // 3. Insert or seed missing tags
        $insertStmt = $pdo->prepare("INSERT INTO tag_descriptions (tag_name, description) VALUES (?, ?) ON DUPLICATE KEY UPDATE description = CASE WHEN description IS NULL OR description = '' THEN VALUES(description) ELSE description END");

        foreach ($tag_counts as $tagName => $count) {
            $currDesc = $existing[$tagName] ?? '';
            if ($currDesc === '') {
                // Pick dictionary description or smart fallback
                $seedDesc = $dict[$tagName] ?? generate_creative_tag_description($tagName);
                try {
                    $insertStmt->execute([$tagName, $seedDesc]);
                    $existing[$tagName] = $seedDesc;
                } catch (Exception $e) {}
            }
        }

        return $tag_counts;
    }
}

if (!function_exists('save_tag_description')) {
    function save_tag_description(PDO $pdo, string $tag, string $description): array
    {
        $tag = trim(strtolower($tag));
        if ($tag === '') {
            return ['success' => false, 'message' => 'Tag name cannot be empty.'];
        }

        $cleanDesc = trim($description);
        $wordCount = count_tag_words($cleanDesc);
        if ($wordCount > 40) {
            return [
                'success' => false,
                'message' => "Description is {$wordCount} words. Maximum allowed is 40 words."
            ];
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO tag_descriptions (tag_name, description) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE description = VALUES(description), updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$tag, $cleanDesc]);
            return [
                'success' => true,
                'tag' => $tag,
                'description' => $cleanDesc,
                'words' => $wordCount
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

if (!function_exists('get_tag_description')) {
    function get_tag_description(PDO $pdo, string $tag): ?string
    {
        $tag = trim(strtolower($tag));
        if ($tag === '' || $tag === 'all') {
            return null;
        }

        try {
            $stmt = $pdo->prepare("SELECT description FROM tag_descriptions WHERE tag_name = ? LIMIT 1");
            $stmt->execute([$tag]);
            $desc = $stmt->fetchColumn();
            if ($desc !== false && trim((string)$desc) !== '') {
                return trim((string)$desc);
            }
        } catch (Exception $e) {}

        // Fallback to dictionary
        $dict = get_curated_tag_descriptions_dictionary();
        return $dict[$tag] ?? null;
    }
}
