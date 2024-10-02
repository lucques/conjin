<?
    $init_processing_target = function(Module $module, Target $target) {
        doc_extensions_add_head_element('<script defer="defer" src="'. $module->get_url() . '/res/776.js"></script>');
        doc_extensions_add_head_element('<script defer="defer" src="'. $module->get_url() . '/res/eskuel-suite.js"></script>');
    };


    ////////////
    // Macros //
    ////////////

    /**
     * Initializes a browser widget with given databases
     */
    // TODO
    function sql_js_browser_add_widget() {
        
    }
?>