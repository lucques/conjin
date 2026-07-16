<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'Tau Prolog');

        $c->activate_module('klipse-with-tau-prolog');
    };
?>

<? $process = function (Target $target) { ?>

<div id="tau-prolog-interpreter"></div>

<script>
const interpreter = new tauPrologInterface.InterpreterComponent('tau-prolog-interpreter');
interpreter.init();
</script>

<? }; ?>
