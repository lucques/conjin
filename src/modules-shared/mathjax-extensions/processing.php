<?
    $init_processing = function(Module $module, Target $target) {
        doc_extensions_add_head_import('<script src="' . $module->get_url() . '/res/mathjax-extensions.js"></script>');
    };

    function mathjax_unic(string $symbol) {
        if ($symbol == '€') {
            return '\unicode{x20AC}';
        }
    }

    function mathjax_term_var(int $index) {
        if ($index == 0) {
            return '\unicode{x25A2}';
        } else {
            return '\unicode{x25CB}';
        }
    }
?>