<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');

        $c->activate_module('localization');
        $c->run_macro('localization', 'set_language', 'de', true);
        $c->run_macro('localization', 'add_alternative', 'en', ['module-demos', 'localization', 'interbook-en']);
    };
?>

<? $process = function (Target $target) { ?>
   
<p>
    Diese Seite ist auf Deutsch. Die entsprechende alternative Seite in Englisch ist <a href="../interbook-en/">hier</a>.
</p>

<? }; ?>
