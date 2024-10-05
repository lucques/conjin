<?
    $init_processing_target = function(Module $module, Target $target) {
        doc_extensions_add_head_element('<link rel="stylesheet" href="'. $module->get_url() . '/res/jsxgraph.css" />');
        doc_extensions_add_head_element('<script src="'. $module->get_url() . '/res/jsxgraphcore.js"></script>');
    };
?>