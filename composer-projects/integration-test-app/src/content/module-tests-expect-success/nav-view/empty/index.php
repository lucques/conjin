<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('nav-view');
        $c->activate_module('subpages-all');
    };
?>

<? $process = function (Target $target) { ?>

<output id="nav-tree-empty"><?= nav_is_tree_empty() ? 'true' : 'false' ?></output>

<div id="authorization-filtered-nav">
    <? nav_print(0, with_decoration: true); ?>
</div>

<? }; ?>
