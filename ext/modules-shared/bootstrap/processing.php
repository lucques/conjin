<?
    $init_processing_target = function(Module $module, Target $target) {
        if ($module->config->get('import_from_url') !== null) {
            doc_extensions_add_head_element('<script src="' . $module->config->get('import_from_url') . '/js/bootstrap.min.js "></script>');

            if ($module->config->get('import_standalone_css')) {
                doc_extensions_add_head_element('<link href="' . $module->config->get('import_from_url') . '/css/bootstrap.min.css " rel="stylesheet">');
            }
        }
        else {
            doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/bootstrap.bundle.min.js"></script>');

            if ($module->config->get('import_standalone_css')) {
                doc_extensions_add_head_element('<link rel="stylesheet" href="' . $module->get_url() . '/res/bootstrap.min.css">');
            }
        }
    };
?>