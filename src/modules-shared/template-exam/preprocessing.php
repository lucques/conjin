<?
    $init_preprocessing = function(Module $m, PreprocessContext $c) {
        $c->activate_module('template-navigable');
        $c->activate_module('template-bootstrapped');
    };
?>