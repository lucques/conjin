<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('js-standard-lib');
    };
?>

<? $process = function (Target $target) { ?>

<p>js-standard-lib integration fixture</p>

<? }; ?>
