<?
    $init_processing_target = function(Module $module, Target $target) {
        doc_extensions_add_head_element('<script src="'. $module->get_url() . '/res/sql-wasm.min.js"></script>');
        doc_extensions_add_head_element('<script>const sqlJs = initSqlJs({ locateFile: filename => `' . $module->get_url() . '/res/${filename}` });</script>');
    };
?>