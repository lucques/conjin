<?
    $init_preprocessing = function(Module $m, PreprocessContext $c) {
        $c->activate_module('template-navigable');
        $c->activate_module('template-bootstrapped');
        
        $c->activate_module('print-mode', config: ['always_on' => true]);
        $c->activate_module('grading');
    };
?>