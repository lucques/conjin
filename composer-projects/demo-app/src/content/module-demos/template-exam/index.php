<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-exam');
        $c->activate_module('nav-build');

        $c->add_subpage('klassenarbeit');
    };
?>
