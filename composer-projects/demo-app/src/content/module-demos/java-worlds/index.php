<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('java-worlds');
    };
?>

<? $process = function (Target $target) { ?>

<h1>java-worlds</h1>

<p>Nothing to see here. This module only provides helper functions for including code examples from a java-worlds project.</p>

<? }; ?>
