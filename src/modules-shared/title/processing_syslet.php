<?
    require_once(__DIR__ . '/processing_common.php');

    $init_processing_syslet = function(Module $module, Syslet $syslet, Target $root_target) {
        // Cache root target for later use
        $GLOBALS['title_target'] = &$root_target;
    };
?>