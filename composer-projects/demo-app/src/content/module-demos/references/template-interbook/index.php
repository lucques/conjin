<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('references');
    };

    $process = function (Target $target) {
        require(__DIR__ . '/../demo.php');
    };
?>
