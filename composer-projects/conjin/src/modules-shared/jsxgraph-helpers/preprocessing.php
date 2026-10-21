<?
    $preprocess_target = $preprocess_syslet = function(Module $m, PreprocessContext $c) {
        $c->activate_module('graphing-types');
        $c->activate_module('mathjax');
        $c->activate_module('jsxgraph');
    };
?>
