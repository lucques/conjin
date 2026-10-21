<?
    $preprocess_target = function(Module $m, TargetPreprocessContext $c) {
        $GLOBALS['events'][] = 'preprocess target-only';
    };
