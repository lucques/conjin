<?
    $init_processing_target = function(Module $module, Target $target) {
        // State
        $GLOBALS['mathjax_eqset_number_of_lines'] = -1;  // -1 means no eqset is active

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


    ///////////////////
    // Equation sets //
    ///////////////////

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

    function mjax_eqset_line(?string $left, string $right, ?string $transformation = null) {
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
        $line .= ' ' . $left . ' &~= ' . $right;

        // Transformation
        if ($transformation !== null) {
            $line .= ' &\qquad |\,' . $transformation;
        }
        else {
            $line .= ' &';
        }

        $line .= '\\\\';
        
        echo $line . "\n";

        // Increment number of lines
        $GLOBALS['mathjax_eqset_number_of_lines']++;
    }


    // & 300 &~= t(x) &\\
    // \Leftrightarrow~& 300 &~= 55x &\qquad |:55\\
    // \Leftrightarrow~& 5{,}5 &~= x &


?>