<?
    $preprocess_common = function(Module $m, PreprocessContext $c) {
        $c->activate_module('dependency');
    };
    $preprocess_target = function(Module $m, TargetPreprocessContext $c) use ($preprocess_common) {
        $preprocess_common($m, $c);
        $c->activate_module('target-only');
        $GLOBALS['events'][] = 'preprocess target';
    };
    $preprocess_syslet = function(Module $m, SysletPreprocessContext $c) use ($preprocess_common) {
        $GLOBALS['events'][] = 'preprocess syslet';
        $preprocess_common($m, $c);
    };
