<?
    $GLOBALS['dependency_loads'] = ($GLOBALS['dependency_loads'] ?? 0) + 1;
    $preprocess_target = $preprocess_syslet = function(Module $m, PreprocessContext $c) {
        $GLOBALS['events'][] = 'preprocess dependency';
    };
