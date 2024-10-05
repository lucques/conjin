<?
    /*
        This module offers to include the Leafletjs library on the page.
    */

    $init_processing_target = function(Module $module, Target $target) {
        doc_extensions_add_head_element('<script src="'. $module->get_url() . '/res/spin-wheel-iife.js"></script>');
    };
?>