<?php
/**
 * Best Works In (BWI) Models Helper
 * Manages selectable AI model pills (Nano Banana, ChatGPT, Gemini, etc.)
 */

if (!function_exists('get_bwi_models_file')) {
    function get_bwi_models_file(): string
    {
        return __DIR__ . '/bwi_models.json';
    }
}

if (!function_exists('get_default_bwi_models')) {
    function get_default_bwi_models(): array
    {
        return [
            [
                'id'       => 'nano_banana',
                'name'     => 'Nano Banana AI',
                'type'     => 'nano_banana',
                'icon'     => 'fa-solid fa-banana',
                'is_fixed' => true,
            ],
            [
                'id'       => 'chatgpt',
                'name'     => 'ChatGPT',
                'type'     => 'chatgpt',
                'icon'     => 'fa-solid fa-robot',
                'is_fixed' => true,
            ],
        ];
    }
}

if (!function_exists('get_bwi_models')) {
    function get_bwi_models(): array
    {
        $file = get_bwi_models_file();
        if (file_exists($file)) {
            $raw = @file_get_contents($file);
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded;
            }
        }
        $defaults = get_default_bwi_models();
        save_all_bwi_models($defaults);
        return $defaults;
    }
}

if (!function_exists('save_all_bwi_models')) {
    function save_all_bwi_models(array $models): bool
    {
        $file = get_bwi_models_file();
        $json = json_encode(array_values($models), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return (bool)@file_put_contents($file, $json);
    }
}

if (!function_exists('add_custom_bwi_model')) {
    function add_custom_bwi_model(string $name, string $type): array
    {
        $name = trim(strip_tags($name));
        if ($name === '') {
            return ['success' => false, 'error' => 'Model name cannot be empty.'];
        }
        if (mb_strlen($name) > 40) {
            $name = mb_substr($name, 0, 40);
        }

        $type = strtolower(trim($type));
        if (!in_array($type, ['gemini', 'chatgpt', 'nano_banana'], true)) {
            $type = 'gemini';
        }

        $icon = match($type) {
            'gemini'  => 'fa-solid fa-wand-magic-sparkles',
            'chatgpt' => 'fa-solid fa-robot',
            default   => 'fa-solid fa-banana',
        };

        $id = $type . ':' . $name;
        $models = get_bwi_models();

        // Check if ID or name already exists
        foreach ($models as $m) {
            if (strcasecmp($m['id'] ?? '', $id) === 0 || strcasecmp($m['name'] ?? '', $name) === 0) {
                return [
                    'success' => true,
                    'model'   => $m,
                    'exists'  => true,
                ];
            }
        }

        $new_model = [
            'id'       => $id,
            'name'     => $name,
            'type'     => $type,
            'icon'     => $icon,
            'is_fixed' => false,
        ];

        $models[] = $new_model;
        save_all_bwi_models($models);

        return [
            'success' => true,
            'model'   => $new_model,
            'exists'  => false,
        ];
    }
}

if (!function_exists('delete_custom_bwi_model')) {
    function delete_custom_bwi_model(string $id): bool
    {
        $models = get_bwi_models();
        $filtered = [];
        $found = false;
        foreach ($models as $m) {
            if (($m['id'] ?? '') === $id) {
                if (!empty($m['is_fixed'])) {
                    return false; // Cannot delete fixed default models
                }
                $found = true;
                continue;
            }
            $filtered[] = $m;
        }
        if ($found) {
            return save_all_bwi_models($filtered);
        }
        return false;
    }
}

if (!function_exists('parse_bwi_display')) {
    /**
     * Parses stored best_works_in value for display on prompt page
     */
    function parse_bwi_display(?string $bwi_val): ?array
    {
        if (empty($bwi_val)) {
            return null;
        }

        $val = trim($bwi_val);

        if ($val === 'nano_banana') {
            return [
                'type'        => 'nano_banana',
                'name'        => 'Nano Banana AI',
                'label'       => 'Best in Nano Banana',
                'badge_class' => 'pp-bwi-nano',
                'icon'        => 'fa-solid fa-banana',
            ];
        }

        if ($val === 'chatgpt') {
            return [
                'type'        => 'chatgpt',
                'name'        => 'ChatGPT',
                'label'       => 'Best in ChatGPT',
                'badge_class' => 'pp-bwi-chatgpt',
                'icon'        => 'fa-solid fa-robot',
            ];
        }

        if (str_starts_with($val, 'gemini:')) {
            $name = trim(substr($val, 7));
            return [
                'type'        => 'gemini',
                'name'        => $name,
                'label'       => 'Best in ' . $name,
                'badge_class' => 'pp-bwi-gemini',
                'icon'        => 'fa-solid fa-wand-magic-sparkles',
            ];
        }

        if (str_starts_with($val, 'chatgpt:')) {
            $name = trim(substr($val, 8));
            return [
                'type'        => 'chatgpt',
                'name'        => $name,
                'label'       => 'Best in ' . $name,
                'badge_class' => 'pp-bwi-chatgpt',
                'icon'        => 'fa-solid fa-robot',
            ];
        }

        if (str_starts_with($val, 'nano_banana:') || str_starts_with($val, 'banana:')) {
            $colon = strpos($val, ':');
            $name = trim(substr($val, $colon + 1));
            return [
                'type'        => 'nano_banana',
                'name'        => $name,
                'label'       => 'Best in ' . $name,
                'badge_class' => 'pp-bwi-nano',
                'icon'        => 'fa-solid fa-banana',
            ];
        }

        // Generic fallback
        if (stripos($val, 'gemini') !== false) {
            return [
                'type'        => 'gemini',
                'name'        => $val,
                'label'       => 'Best in ' . $val,
                'badge_class' => 'pp-bwi-gemini',
                'icon'        => 'fa-solid fa-wand-magic-sparkles',
            ];
        }

        return [
            'type'        => 'chatgpt',
            'name'        => $val,
            'label'       => 'Best in ' . $val,
            'badge_class' => 'pp-bwi-chatgpt',
            'icon'        => 'fa-solid fa-robot',
        ];
    }
}
