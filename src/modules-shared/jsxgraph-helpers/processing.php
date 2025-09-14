<?
    $init_processing_target = function(Module $module, Target $target) {
        doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/jsxgraph-helpers.js"></script>');
    };


    include_once __DIR__ . '/processing_common.php';
    include_once __DIR__ . '/processing_sequence.php';
    include_once __DIR__ . '/processing_plot_2d.php';
    include_once __DIR__ . '/processing_plot_3d_dynamic.php';
    include_once __DIR__ . '/processing_plot_3d_cavalier.php';
?>
