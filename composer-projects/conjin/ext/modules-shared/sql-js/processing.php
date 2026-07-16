<?
    $init_processing_target = function(Module $module, Target $target) {
        if ($module->config->get('import_from_url') !== null) {
            doc_extensions_add_head_element('<script src="' . $module->config->get('import_from_url') . '/sql-wasm.js"></script>');
            doc_extensions_add_head_element('<script>const sqlJs = initSqlJs({ locateFile: filename => `' . $module->config->get('import_from_url') . '/${filename}` });</script>');
        }
        else {
            doc_extensions_add_head_element('<script src="'. $module->get_url() . '/res/sql-wasm.min.js"></script>');
            doc_extensions_add_head_element('<script>const sqlJs = initSqlJs({ locateFile: filename => `' . $module->get_url() . '/res/${filename}` });</script>');
        }
    };
?>