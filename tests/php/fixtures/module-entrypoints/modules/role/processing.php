<?
    $process_common = function() {
        $GLOBALS['events'][] = 'local processing helper';
    };
    $process_target = function(Module $m, Target $t) use ($process_common) {
        $GLOBALS['events'][] = 'process target';
        $process_common();
    };
    $process_syslet = function(Module $m, Syslet $s, Target $root) use ($process_common) {
        $process_common();
        $GLOBALS['events'][] = 'process syslet';
        $GLOBALS['received_syslet'] = $s;
        $GLOBALS['received_root'] = $root;
    };
