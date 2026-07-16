<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('nav-view');
        $c->activate_module('subpages-all');
    };
?>

<? $process = function (Target $target) { ?>

<div id="authorization-filtered-nav">
    <? nav_print(0, with_decoration: true); ?>
</div>

<? }; ?>
