<?
    require_once(__DIR__ . '/processing_common.php');

    $init_processing_target = function(Module $module, Target $target) {
        $GLOBALS['nav_view_target_ids'] = $target->get_ids();
    };
?>