<?
    $init_processing_target = function(Module $module, Target $target) {
        doc_extensions_add_head_element('<link href="'  . $module->get_url() . '/res/prism.css" rel="stylesheet">');
        doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/prism.js"></script>');
    };
?>