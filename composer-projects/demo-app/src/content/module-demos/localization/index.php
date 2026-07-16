<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');

        $c->activate_module('subpages-all');
        $c->activate_module('nav-build');
    };
?>
