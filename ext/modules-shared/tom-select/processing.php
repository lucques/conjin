<?
    $init_processing_target = function(Module $module, Target $target) {
        doc_extensions_add_head_element('<link rel="stylesheet" href="'. $module->get_url() . '/res/tom-select.css" />');
        doc_extensions_add_head_element('<script src="'. $module->get_url() . '/res/tom-select.complete.min.js"></script>');

        doc_extensions_add_js_after_dom_setup(<<<JS
            document.querySelectorAll('.tom-select').forEach((el) => {
                new TomSelect(el, {
                    maxItems: 3,
                    closeAfterSelect: true
                });
            });
        JS);
    };
?>