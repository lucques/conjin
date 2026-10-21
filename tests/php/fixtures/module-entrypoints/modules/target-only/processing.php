<?
    $process_target = function(Module $m, Target $t) {
        $GLOBALS['events'][] = 'process target-only';
    };
