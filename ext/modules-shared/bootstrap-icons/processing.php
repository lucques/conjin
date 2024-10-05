<?
    $init_processing_target = function(Module $module, Target $target) {
        doc_extensions_add_head_element('<link href="' . $module->get_url() . '/res/bootstrap-icons.min.css" rel="stylesheet">');
    };
?>