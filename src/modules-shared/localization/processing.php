<?
    ///////////////
    // Init defs //
    ///////////////

    $init_processing_target = function(Module $module, Target $target) {
        // Cache for later use
        $GLOBALS['localization_language'] = $module->config->get('language');
        $GLOBALS['localization_alternatives_and_nontranslations'] =
            array_merge(
                $module->config->get('alternatives'),
                $module->config->get('nontranslations'),
                [$GLOBALS['localization_language'] => $target->get_ids()]
            );
        
        // Include current language as well
        $alternatives = $module->config->get('alternatives');
        if (!isset($alternatives[$GLOBALS['localization_language']]) &&
            $module->config->get('is_alternative')) {
            $alternatives[$GLOBALS['localization_language']] = $target->get_ids();
        }
        // Sort according to language keys
        ksort($alternatives);
        
        // Add to document head
        foreach ($alternatives as $lang => $target_ids) {
            doc_extensions_add_head_element('<link rel="alternate" hreflang="'. $lang .'" href="'. url_collect($target_ids) .'" />');
        }
    };

    
    ////////////
    // Macros //
    ////////////

    function get_language(): ?string {
        return $GLOBALS['localization_language'];
    }

    function get_language_alternatives_and_nontranslations(array $preferred_order): array {
        $map = $GLOBALS['localization_alternatives_and_nontranslations'];
        uksort($map, function($a, $b) use ($preferred_order) {
            $a_index = array_search($a, $preferred_order);
            $b_index = array_search($b, $preferred_order);
            if ($a_index === false) $a_index = PHP_INT_MAX;
            if ($b_index === false) $b_index = PHP_INT_MAX;
            return $a_index <=> $b_index;
        });
        return $map;
    }

    function language_to_flag(string $lang): ?string {
        static $map = [
            'en' => '🇬🇧',
            'de' => '🇩🇪',
            'fr' => '🇫🇷',
        ];
        return $map[$lang] ?? null;
    }

    function language_to_text(string $lang): ?string {
        static $map = [
            'en' => 'English',
            'de' => 'Deutsch',
            'fr' => 'Français',
        ];
        return $map[$lang] ?? null;
    }
?>