<?
    $init_processing = function(Module $module, Target $target) {
        doc_extensions_add_css('<script src="' . $module->get_url() . '/res/bootstrap.bundle.min.js"></script>');

        if ($module->config->get_or_default(['import_standalone_css'], false)) {
            doc_extensions_add_css('<link rel="stylesheet" href="' . $module->get_url() . '/res/bootstrap.min.css">');
        }
    };
?>