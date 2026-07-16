<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');

        $c->activate_module('localization');
        $c->run_macro('localization', 'set_language', 'en', true);
        $c->run_macro('localization', 'add_alternative', 'de', ['module-tests-expect-success', 'localization', 'de']);
        $c->run_macro('localization', 'add_nontranslation', 'fr', ['module-tests-expect-success', 'localization', 'fr-unavailable']);
    };
?>

<? $process = function (Target $target) { ?>

<p id="localized-content">English content</p>

<? }; ?>
