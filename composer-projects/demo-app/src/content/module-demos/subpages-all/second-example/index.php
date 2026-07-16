<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'Second example');
    };
?>

<? $process = function (Target $target) { ?>

<p>This is the second automatically discovered subpage.</p>

<? }; ?>
