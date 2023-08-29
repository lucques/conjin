<?
    $init_processing = function(Module $module, Target $target) {
        doc_extensions_add_head_import('<script src="' . $module->get_url() . '/res/mathjax-extensions.js"></script>');
    };
?>