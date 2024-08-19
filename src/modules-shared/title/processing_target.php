<?
    require_once(__DIR__ . '/processing_common.php');

    $init_processing_target = function(Module $module, Target $target) {
        // Cache target for later use
        $GLOBALS['title_target'] = &$target;
    };
?>