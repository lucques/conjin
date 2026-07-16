<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');
        $c->activate_module('footnotes');
    };
?>

<? $process = function (Target $target) { ?>

<p id="content-without-footnotes">No footnotes were collected.</p>

<? }; ?>
