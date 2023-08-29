<?
    $init_processing = function(Module $module, Target $target) {
        doc_extensions_add_head_import('<link href="'  . $module->get_url() . '/res/jquery.mCustomScrollbar.min.css" rel="stylesheet">');
        doc_extensions_add_head_import('<script src="' . $module->get_url() . '/res/jquery.mCustomScrollbar.concat.min.js"></script>');
    };
?>