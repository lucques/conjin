<?
    $init_processing = function(Module $module, Target $target) {
        doc_extensions_add_head_import('<script src="'. $module->get_url() . '/res/sql-wasm.js"></script>');
        doc_extensions_add_head_import('<script>const sqlJs = initSqlJs({ locateFile: filename => `' . $module->get_url() . '/res/${filename}` });</script>');
    };
?>