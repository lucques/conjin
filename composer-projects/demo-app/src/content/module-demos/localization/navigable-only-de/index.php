<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');

        $c->activate_module('localization');
        $c->run_macro('localization', 'set_language', 'de', true);
    };
?>

<? $process = function (Target $target) { ?>
   
<p>
    Diese Seite gibt es nur auf Deutsch.
</p>
<p>
    Es soll <strong>keine</strong> Sprachumschaltung angezeigt werden.
</p>

<? }; ?>
