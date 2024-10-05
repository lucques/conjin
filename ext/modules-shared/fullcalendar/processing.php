<?
    $init_processing_target = function(Module $module, Target $target) {
        doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/dist/index.global.min.js"></script>');
        doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/packages/bootstrap5/index.global.min.js"></script>');
        doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/packages/daygrid/index.global.min.js"></script>');
    };
?>