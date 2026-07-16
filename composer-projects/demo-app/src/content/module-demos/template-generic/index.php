<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->add_subpage('custom-title');
        $c->add_subpage('bootstrap-extended');
    };
?>
