<?    
    $init_processing = function(Module $module, Target $target) {
        doc_extensions_add_head_import('<script src="' . $module->get_url() . '/res/es5/tex-chtml.js" id="MathJax-script" async></script>');
    };
?>