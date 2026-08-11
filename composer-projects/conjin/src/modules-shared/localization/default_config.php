<?
    $default_config = function(): array {
        return [
            'languages' => [
                [
                    'languageTag' => 'en',
                    'label' => 'English',
                ],
                [
                    'languageTag' => 'de',
                    'label' => 'Deutsch',
                ],
            ],
            'rememberExplicitChoice' => true,
            'current_language'       => 'en', // The language of the content, e.g. `en`, `de`.
            'is_alternative'         => true, // If false, target is considered a nontranslation
            'alternatives'           => [],   // Assoc array of language code => (array of target ids)
            'nontranslations'        => [],   // Assoc array of language code => (array of target ids)
            'language_switch'        => null, // Target ids of the language-neutral entry page, or null
        ];
    }
?>
