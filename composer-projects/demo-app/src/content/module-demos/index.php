<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'Modules');

        $c->activate_module('subpages-all');
    };
?>

<? $process = function (Target $target) { ?>

<p>
    Demos fo various modules
</p>

<? }; ?>