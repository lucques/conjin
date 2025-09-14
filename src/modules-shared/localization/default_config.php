<?
    $default_config = function(): array {
        return [
            'language'        => 'en', // The language of the content, e.g. `en`, `de`.
            'is_alternative'  => true, // If false, target is considered a nontranslation
            'alternatives'    => [],   // Assoc array of language code => (array of target ids)
            'nontranslations' => [],   // Assoc array of language code => (array of target ids)
        ];
    }
?>