<?
    $init_processing_target = function(Module $module, Target $target) {
        doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/mathjax-extensions.js"></script>');
    };


    //////////////////////////
    // Used by CONTENT file //
    //////////////////////////

    /**
     * Insert a unicode character in math mode
     * 
     * Currently only € is supported, feel free to add further ones
     */
    function mathjax_unic(string $symbol) {
        if ($symbol == '€') {
            return '\unicode{x20AC}';
        }
    }

    /**
     * Use geometric shapes as variables in math mode: ▢, ○
     */
    function mathjax_term_var(int $index) {
        if ($index == 0) {
            return '\unicode{x25A2}';
        } else {
            return '\unicode{x25CB}';
        }
    }
?>