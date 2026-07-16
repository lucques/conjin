<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');

        $c->activate_module('localization');
        $c->run_macro('localization', 'set_language', 'fr', false);
        $c->run_macro('localization', 'add_alternative', 'de', ['module-tests-expect-success', 'localization', 'de']);
        $c->run_macro('localization', 'add_alternative', 'en', ['module-tests-expect-success', 'localization', 'en']);
    };
?>

<? $process = function (Target $target) { ?>

<p id="localized-content">Cette page n'est pas disponible en français.</p>

<? }; ?>
