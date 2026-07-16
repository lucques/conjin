<?
    $init_preprocessing = function(Module $m, PreprocessContext $c) {
        $c->activate_module('bootstrap');
        $c->activate_module('source');
    };
?>