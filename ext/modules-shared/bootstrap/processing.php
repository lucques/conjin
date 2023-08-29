<?
    $init_processing = function(Module $module, Target $target) {
        doc_extensions_add_css('<script src="' . $module->get_url() . '/res/bootstrap.bundle.min.js"></script>');
    };
?>