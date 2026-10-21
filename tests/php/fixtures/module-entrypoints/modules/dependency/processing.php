<?
    $process_target = $process_syslet = function(Module $m, Processable $p) {
        $GLOBALS['events'][] = 'process dependency';
    };
