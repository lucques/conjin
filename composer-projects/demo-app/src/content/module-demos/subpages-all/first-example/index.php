<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'First example');
    };
?>

<? $process = function (Target $target) { ?>

<p>This is the first automatically discovered subpage.</p>

<? }; ?>
