<?
    $init_processing_target = function(Module $module, Target $target) {
        doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/math.min.js"></script>');
    };
?>