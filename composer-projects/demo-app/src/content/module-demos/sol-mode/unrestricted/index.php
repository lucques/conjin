<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');

        $c->activate_module('sol-mode');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'unrestricted');
    };
?>

<? $process = function (Target $target) { ?>

<p>
    Authorization: Das sol-mode-Modul ist nicht eingeschränkt. Die Lösung darf von allen gesehen werden.
</p>
<p>
    Hier eine Frage: Was ist passiert?
</p>
<? sol_start(); ?>
<p>
    Antwort: Nichts ist passiert.
</p>
<? sol_end(); ?>

<? html_h(2, 'Lückentext'); ?>
<p>
    Es geht mir <? sol_start(); ?>gut<? sol_gap(); ?>____<? sol_end(); ?>.
</p>

<? }; ?>
