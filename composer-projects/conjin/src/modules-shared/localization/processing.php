<?
    const LOCALIZATION_LANGUAGE_COOKIE_NAME = 'conjin_language';
    const LOCALIZATION_EXPLICIT_CHOICE_QUERY_PARAM = 'localization-explicit-choice';


    ///////////////
    // Init defs //
    ///////////////

    $init_processing_target = function(Module $module, Target $target) {
        $languages = $module->config->get('languages');
        $remember_explicit_choice = $module->config->get('rememberExplicitChoice');
        $language_switch = $module->config->get('language_switch');

        assert(array_is_list($languages), 'Localization languages must be a list');
        assert(is_bool($remember_explicit_choice), 'Localization rememberExplicitChoice must be a boolean');
        assert($language_switch === null || array_is_list($language_switch), 'Localization language switch must be null or a list of target ids');

        foreach ($languages as $language) {
            assert(is_array($language), 'Each localization language must be an associative array');
            assert(isset($language['languageTag']) && is_string($language['languageTag']) && $language['languageTag'] !== '', 'Each localization language needs a non-empty languageTag');
            assert(isset($language['label']) && is_string($language['label']) && $language['label'] !== '', 'Each localization language needs a non-empty label');
        }
        $language_tags = array_column($languages, 'languageTag');
        assert(count(array_unique($language_tags)) === count($language_tags), 'Localization language tags must be unique');

        $current_language = $module->config->get('current_language');
        $alternatives_and_nontranslations = array_merge(
            $module->config->get('alternatives'),
            $module->config->get('nontranslations'),
            [$current_language => $target->get_ids()]
        );

        // Cache for later use
        $GLOBALS['localization'] = [
            'current_language' => $current_language,
            'languages' => $languages,
            'rememberExplicitChoice' => $remember_explicit_choice,
            'alternativesAndNontranslations' => $alternatives_and_nontranslations,
        ];

        foreach (array_keys($alternatives_and_nontranslations) as $language_tag) {
            assert(in_array($language_tag, $language_tags, true), "Localization language tag `$language_tag` is not configured");
        }

        // An explicit language choice is recorded by the destination target.
        // Redirect afterwards so the marker is not left in bookmarks or shared URLs.
        if ($remember_explicit_choice && ($_GET[LOCALIZATION_EXPLICIT_CHOICE_QUERY_PARAM] ?? null) === '1') {
            $cookie_path = rtrim(get_global_config('url_base'), '/') . '/';
            $cookie_was_set = setcookie(LOCALIZATION_LANGUAGE_COOKIE_NAME, $current_language, [
                'expires' => time() + 365 * 24 * 60 * 60,
                'path' => $cookie_path,
                'secure' => get_global_config('https'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            if (!$cookie_was_set) {
                log_error_silently('Could not remember the explicit language choice');
            }

            $clean_queries = $_GET;
            unset($clean_queries[LOCALIZATION_EXPLICIT_CHOICE_QUERY_PARAM]);
            $clean_url = url_collect($target->get_ids()) . make_query_string([], base_queries: $clean_queries);

            header('Cache-Control: no-store');
            redirect_see_other_and_exit($clean_url);
        }
        else {
            // Include current language as well
            $alternatives = $module->config->get('alternatives');
            if (!isset($alternatives[$current_language]) &&
                $module->config->get('is_alternative')) {
                $alternatives[$current_language] = $target->get_ids();
            }
            // Sort according to language keys
            ksort($alternatives);

            // Add to document head
            foreach ($alternatives as $lang => $target_ids) {
                doc_extensions_add_head_element('<link rel="alternate" hreflang="'. $lang .'" href="'. url_full_collect($target_ids) .'" />');
            }
            if ($language_switch !== null) {
                doc_extensions_add_head_element('<link rel="alternate" hreflang="x-default" href="'. url_full_collect($language_switch) .'" />');
            }
        }
    };

    
    ////////////
    // Macros //
    ////////////

    function get_language_tag(): ?string {
        return $GLOBALS['localization']['current_language'];
    }

    function localization_get_language_choice_url(array $target_ids): string {
        $url = url_collect($target_ids);
        if (!$GLOBALS['localization']['rememberExplicitChoice']) {
            return $url;
        }

        return $url . make_query_string([
            LOCALIZATION_EXPLICIT_CHOICE_QUERY_PARAM => '1',
        ]);
    }

    function localization_get_remembered_language_tag(): ?string {
        if (!$GLOBALS['localization']['rememberExplicitChoice']) {
            return null;
        }

        $language_tag = $_COOKIE[LOCALIZATION_LANGUAGE_COOKIE_NAME] ?? null;
        if (!is_string($language_tag) || language_tag_to_text($language_tag) === null) {
            return null;
        }

        return $language_tag;
    }

    function localization_get_accepted_language_tag(): ?string {
        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
        if (!is_string($accept_language) || trim($accept_language) === '') {
            return null;
        }

        $preferences = [];
        foreach (explode(',', $accept_language) as $index => $item) {
            $matches = [];
            $is_valid = preg_match(
                '/^\s*(\*|[a-z]{1,8}(?:-[a-z0-9]{1,8})*)\s*(?:;\s*q\s*=\s*(0(?:\.[0-9]{0,3})?|1(?:\.0{0,3})?))?\s*$/i',
                $item,
                $matches,
            );
            if ($is_valid !== 1) {
                continue;
            }

            $preferences[] = [
                'range' => strtolower($matches[1]),
                'quality' => isset($matches[2]) && $matches[2] !== '' ? (float) $matches[2] : 1.0,
                'index' => $index,
            ];
        }

        $best_match = null;
        foreach ($GLOBALS['localization']['languages'] as $language_index => $language) {
            $language_tag = $language['languageTag'];
            $normalized_language_tag = strtolower($language_tag);
            $language_tag_subtags = substr_count($normalized_language_tag, '-') + 1;
            $best_preference = null;

            foreach ($preferences as $preference) {
                $range = $preference['range'];
                if ($range === '*') {
                    $specificity = 0;
                }
                elseif ($range === $normalized_language_tag) {
                    $specificity = 2 * $language_tag_subtags + 1;
                }
                elseif (str_starts_with($normalized_language_tag, $range . '-')) {
                    $specificity = 2 * (substr_count($range, '-') + 1);
                }
                elseif (str_starts_with($range, $normalized_language_tag . '-')) {
                    // Fall back from a requested regional or other specific variant
                    // to a less specific language configured by the application.
                    $specificity = 2 * $language_tag_subtags - 1;
                }
                else {
                    continue;
                }

                if ($best_preference === null ||
                    $specificity > $best_preference['specificity'] ||
                    ($specificity === $best_preference['specificity'] &&
                        $preference['quality'] > $best_preference['quality']) ||
                    ($specificity === $best_preference['specificity'] &&
                        $preference['quality'] === $best_preference['quality'] &&
                        $preference['index'] < $best_preference['index'])) {
                    $best_preference = $preference + ['specificity' => $specificity];
                }
            }

            if ($best_preference === null || $best_preference['quality'] === 0.0) {
                continue;
            }

            $candidate = [
                'languageTag' => $language_tag,
                'quality' => $best_preference['quality'],
                'preferenceIndex' => $best_preference['index'],
                'languageIndex' => $language_index,
            ];
            if ($best_match === null ||
                $candidate['quality'] > $best_match['quality'] ||
                ($candidate['quality'] === $best_match['quality'] &&
                    $candidate['preferenceIndex'] < $best_match['preferenceIndex']) ||
                ($candidate['quality'] === $best_match['quality'] &&
                    $candidate['preferenceIndex'] === $best_match['preferenceIndex'] &&
                    $candidate['languageIndex'] < $best_match['languageIndex'])) {
                $best_match = $candidate;
            }
        }

        return $best_match['languageTag'] ?? null;
    }

    function localization_add_vary_header(bool $cookie, bool $accept_language): void {
        if (!$cookie && !$accept_language) {
            return;
        }

        $vary_fields = [];
        $normalized_vary_fields = [];
        foreach (headers_list() as $response_header) {
            if (strncasecmp($response_header, 'Vary:', 5) !== 0) {
                continue;
            }

            foreach (explode(',', substr($response_header, 5)) as $vary_field) {
                $vary_field = trim($vary_field);
                if ($vary_field === '*') {
                    return;
                }
                $normalized_vary_field = strtolower($vary_field);
                if ($vary_field !== '' && !isset($normalized_vary_fields[$normalized_vary_field])) {
                    $vary_fields[] = $vary_field;
                    $normalized_vary_fields[$normalized_vary_field] = true;
                }
            }
        }

        if ($cookie && !isset($normalized_vary_fields['cookie'])) {
            $vary_fields[] = 'Cookie';
        }
        if ($accept_language && !isset($normalized_vary_fields['accept-language'])) {
            $vary_fields[] = 'Accept-Language';
        }
        if ($vary_fields === []) {
            return;
        }

        header_remove('Vary');
        header('Vary: ' . implode(', ', $vary_fields));
    }

    function get_language_alternatives_and_nontranslations(?array $preferred_order = null): array {
        $map = $GLOBALS['localization']['alternativesAndNontranslations'];
        if ($preferred_order === null) {
            $preferred_order = array_map(function(array $language): string {
                return $language['languageTag'];
            }, $GLOBALS['localization']['languages']);
        }
        uksort($map, function($a, $b) use ($preferred_order) {
            $a_index = array_search($a, $preferred_order, true);
            $b_index = array_search($b, $preferred_order, true);
            if ($a_index === false) $a_index = PHP_INT_MAX;
            if ($b_index === false) $b_index = PHP_INT_MAX;
            return $a_index <=> $b_index;
        });
        return $map;
    }


    function language_tag_to_text(string $language_tag): ?string {
        foreach ($GLOBALS['localization']['languages'] as $language) {
            if ($language['languageTag'] === $language_tag) {
                return $language['label'];
            }
        }
        return null;
    }
?>
