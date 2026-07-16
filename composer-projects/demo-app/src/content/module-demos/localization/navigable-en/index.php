<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');

        $c->activate_module('localization');
        $c->run_macro('localization', 'set_language', 'en', true);
        $c->run_macro('localization', 'add_alternative', 'de', ['module-demos', 'localization', 'navigable-de']);
    };
?>

<? $process = function (Target $target) { ?>

<p>
    This page is in English. The corresponding alternative page in German is <a href="../navigable-de/">here</a>.
</p>

<? }; ?>
