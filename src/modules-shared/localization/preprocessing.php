<?
    $init_preprocessing = function(Module $m, PreprocessContext $c) {
        $c->activate_module('doc-extensions');
    };


    $set_language = function(
        PreprocessContext $c,
        string $lang,
        bool $is_alternative = true,
    ) {
        $c->update_module_config('localization', [
            'language' => $lang,
            'is_alternative' => $is_alternative,
        ]);
    };

    $add_alternative = function(
        PreprocessContext $c,
        string $lang,
        array $target_ids,
    ) {
        $c->update_module_config('localization', [
            'alternatives' => [
                $lang => $target_ids,
            ],
        ]);
    };

    $add_nontranslation = function(
        PreprocessContext $c,
        string $lang,
        array $target_ids,
    ) {
        $c->update_module_config('localization', [
            'nontranslations' => [
                $lang => $target_ids,
            ],
        ]);
    };
?>