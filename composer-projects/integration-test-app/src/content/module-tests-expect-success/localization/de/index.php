<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');

        $c->activate_module('localization', [
            'languages' => [
                ['languageTag' => 'en', 'label' => 'English'],
                ['languageTag' => 'de', 'label' => 'Deutsch'],
                ['languageTag' => 'fr', 'label' => 'Français'],
            ],
            'rememberExplicitChoice' => true,
        ]);
        $c->run_macro('localization', 'set_language', 'de', true);
        $c->run_macro('localization', 'set_language_switch', []);
        $c->run_macro('localization', 'add_alternative', 'en', ['module-tests-expect-success', 'localization', 'en']);
        $c->run_macro('localization', 'add_nontranslation', 'fr', ['module-tests-expect-success', 'localization', 'fr-unavailable']);
    };
?>

<? $process = function (Target $target) { ?>

<p id="localized-content">Deutscher Inhalt</p>

<? }; ?>
