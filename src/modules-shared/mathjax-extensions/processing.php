<?
    $init_processing_target = function(Module $module, Target $target) {
        // State
        $GLOBALS['mathjax_eqset_number_of_lines'] = -1;  // -1 means no eqset is active

        // Use `prepend` instead of `add` because the config must be loaded before the mathjax library itself
        doc_extensions_prepend_head_element('<script src="' . $module->get_url() . '/res/mathjax-extensions.js"></script>');
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


    ///////////////////
    // Equation sets //
    ///////////////////

    // Example:
    //
    //     2x + 2 = 4   | -2
    // <=> 2x     = 2   | :2
    // <=>  x     = 1

    function mjax_eqset_start($align_lhs = 'l', $align_rhs = 'l') {
        // There must not be an active eqset
        assert($GLOBALS['mathjax_eqset_number_of_lines'] == -1, 'An equation set is already active');

        // Set number of lines to 0
        $GLOBALS['mathjax_eqset_number_of_lines'] = 0;
?>
        \begin{tightarray}{l<?= $align_lhs ?>l<?= $align_rhs ?>l}
<?
    }

    function mjax_eqset_end() {
        // There must be an active eqset
        assert($GLOBALS['mathjax_eqset_number_of_lines'] != -1, 'No equation set is active');

        // Reset number of lines
        $GLOBALS['mathjax_eqset_number_of_lines'] = -1;
?>
        \end{tightarray}
<?
    }

    function mjax_eqset_line(?string $left, string $right, ?string $transformation = null, $show_bar = true, $sign = '=', $margin_bottom_cm = null) {
        // There must be an active eqset
        assert($GLOBALS['mathjax_eqset_number_of_lines'] != -1, 'No equation set is active');

        $line = '';

        // <=>
        if ($GLOBALS['mathjax_eqset_number_of_lines'] == 0 || $left === null) {
            $line = '&';
        }
        else {
            $line = '\\Leftrightarrow~&';
        }

        // Main part
        $line .= ' ' . $left . ' &\,\,' . $sign . '~ ' . $right;

        // Transformation
        if ($transformation !== null && $show_bar) {
            $line .= ' &\qquad |\,' . $transformation;
        }
        else if ($transformation !== null && !$show_bar) {
            $line .= ' &\qquad ' . $transformation;
        }
        else {
            $line .= ' &';
        }

        $line .= '\\\\';
        if ($margin_bottom_cm !== null) {
            $line .= '[' . $margin_bottom_cm . 'cm]';
        }
        
        echo $line . "\n";

        // Increment number of lines
        $GLOBALS['mathjax_eqset_number_of_lines']++;
    }


    /////////////
    // Vectors //
    /////////////

    function mjax_vec_2d($x, $y): string {
        return '\begin{pmatrix}' . $x . '\\\\' . $y . '\end{pmatrix}';
    }

    function mjax_vec_3d($x, $y, $z): string {
        return '\begin{pmatrix}' . $x . '\\\\' . $y . '\\\\' . $z . '\end{pmatrix}';
    }
?>
