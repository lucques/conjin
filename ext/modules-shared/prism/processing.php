<?
    $init_processing = function(Module $module, Target $target) {
        doc_extensions_add_head_import('<link href="'  . $module->get_url() . '/res/prism.css" rel="stylesheet">');
        doc_extensions_add_head_import('<script src="' . $module->get_url() . '/res/prism.js"></script>');
    };
?>