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
        $c->run_macro('localization', 'set_language', 'en', true);
        $c->run_macro('localization', 'set_language_switch', []);
        $c->run_macro('localization', 'add_alternative', 'de', ['module-tests-expect-success', 'localization', 'de']);
        $c->run_macro('localization', 'add_nontranslation', 'fr', ['module-tests-expect-success', 'localization', 'fr-unavailable']);
    };
?>

<? $process = function (Target $target) {
    localization_add_vary_header(cookie: true, accept_language: true);
?>

<p id="localized-content">English content</p>
<p id="remembered-language"><?= localization_get_remembered_language_tag() ?? '' ?></p>
<p id="accepted-language"><?= localization_get_accepted_language_tag() ?? '' ?></p>

<? }; ?>
