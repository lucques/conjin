<?
    /*
        This module offers the printing of arithmetic expressions.
    */

    $get_processing_dependencies = function() {
        return [
            new ModuleLocation('mathjax', is_shared: true, is_external: true),
        ];
    };

    $init_processing = function(Module $module, Target $target) {
        $GLOBALS['math_tex_colors'] = ['green', 'blue', 'red', 'violet'];
    };


    ///////////
    // Terms //
    ///////////

    // $cur_depth: Used to keep track of the colors
    // $max_depth: Maximum depth of the term tree. For unbounded depth, set `=== null`

    function math_get_boxed_term_rec($t, $cur_depth, $max_depth) {
        if (is_array($t)) {
            // Composite
            if (isset($t['op_multi'])) {
                // Multi op
                $operands = [];
                foreach ($t['operands'] as $operand) {
                    $operand = math_get_boxed_term_rec($operand, $cur_depth + 1, math_aux_depth_next($max_depth));
                    $operands[] = math_aux_deep_enough($max_depth) ? math_make_box($operand, $cur_depth) : $operand;
                }

                return math_op_multi_to_tex($t['op_multi'], $operands);
            }
            elseif (isset($t['op_bin'])) {
                // Binary op
                $left  = math_get_boxed_term_rec($t['left'],  $cur_depth + 1, math_aux_depth_next($max_depth));
                $right = math_get_boxed_term_rec($t['right'], $cur_depth + 1, math_aux_depth_next($max_depth));

                $left_res  = math_aux_deep_enough($max_depth) ? math_make_box($left, $cur_depth)  : $left;
                $right_res = math_aux_deep_enough($max_depth) ? math_make_box($right, $cur_depth) : $right;

                return math_op_bin_to_tex($t['op_bin'], $left_res, $right_res);
            }
            else {
                // Unary op
                $operand  = math_get_boxed_term_rec($t['operand'], $cur_depth + 1, math_aux_depth_next($max_depth));
                $operand_res = math_aux_deep_enough($max_depth) ? math_make_box($operand, $cur_depth) : $operand;

                return math_op_un_to_tex($t['op_un'], $operand_res);
            }
        }   
        else {
            // Atomic
            return $t;
        }
    }

    function math_get_boxed_term($t, $level = 0, $depth = null) {
        return math_get_boxed_term_rec($t, $level, $depth);
    }

    function math_get_term($t) {
        return math_get_boxed_term_rec($t, 0, null);
    }

    function math_make_box($content, $level = 0) {
        return '\fcolorbox{' . $GLOBALS['math_tex_colors'][$level] . '}{white}{$' . $content . '$}';
    }

    function math_make_empty_box($level) {
        return math_make_box('\phantom{T}', $level);
    }

    function math_aux_depth_next($depth) {
        if ($depth === null) {
            return null;
        }
        else {
            return $depth - 1;
        }
    }

    function math_aux_deep_enough($depth) {
        return $depth === null || $depth > 0;
    }

    function math_init_mathjax() {
        // empty
    }


    //////////////////////////
    // Flat pretty printers // 
    //////////////////////////

    // $operator:                          unary or binary operator
    // $operand, $left, $right, $operands: string

    function math_op_un_to_tex($operator, $operand) {
        if     ($operator == '-')    return '-~' . $operand;
        elseif ($operator == 'sq')   return $operand . '^2';
        elseif ($operator == 'sqrt') return '\sqrt{' . $operand . '}';
        else   fail('operator not found: ' . $operator);
    }

    function math_op_bin_to_tex($operator, $left, $right) {
        if     ($operator == '+')    return $left . ' + ' . $right;
        elseif ($operator == '-')    return $left . ' - ' . $right;
        elseif ($operator == '*')    return $left . ' \cdot ' . $right;
        elseif ($operator == ':')    return $left . ' : ' . $right;
        elseif ($operator == 'frac') return '\frac{' . $left . '}{' . $right . '}';
        elseif ($operator == '^')    return $left . '^{' . $right . '}';
        else   fail('operator not found: ' . $operator);
    }

    function math_op_multi_to_tex($operator, $operands) {
        if     ($operator == '+')    return implode(' + ', $operands);
        elseif ($operator == '*')    return implode(' \cdot ', $operands);
        else   fail('operator not found: ' . $operator);
    }


    //////////////////////
    // Print table rows // 
    //////////////////////

    // TODO rename functions

    // $operator:                          unary or binary operator
    // $operand, $left, $right, $operands: string

    function aux_print_row($term, $level = 0, $depth = null ) {
        assert(is_array($term));
?>
    <tr>
        <td>
            $<?= $term['raw'] ?>$
        </td>
        <td>
            $<?= math_get_boxed_term($term, $level, $depth) ?>$
        </td>
        <td>
            <?= $term['type'] ?>
        </td>
        <td>
            <div class="d-flex column-gap-base justify-content-left align-items-center">
<?
        if (isset($term['op_multi'])) {
            // Multi op
            $operands = [];
            foreach ($term['operands'] as $operand) {
                $operands[] = ' $' . math_get_boxed_term_rec($operand, $level + 1, math_aux_depth_next($depth)) . '$ ';
            }
?>
            <?= implode(' und ', $operands) ?>
<?
        }
        else if (isset($term['op_bin'])) {
            // Bin op
            $left  = math_get_boxed_term($term['left'],  level: $level + 1, depth: math_aux_depth_next($depth));
            $right = math_get_boxed_term($term['right'], level: $level + 1, depth: math_aux_depth_next($depth));

?>
            $ <?= math_make_box($left,  level: $level) ?> $ und
            $ <?= math_make_box($right, level: $level) ?> $
<?
        }
        else {
            // Unary op
            $operand = math_get_boxed_term($term['operand'], level: $level + 1, depth: math_aux_depth_next($depth));
?>
            $ <?= math_make_box($operand, level: $level) ?> $
<?
        }
?>
            </div>
        </td>
    </tr>
<?
    }

    function aux_print_rows($term, $level = 0) {
        if (is_array($term)) {
            aux_print_row($term, level: $level, depth: 100);
            if (isset($term['op_multi'])) {
                foreach ($term['operands'] as $operand) {
                    aux_print_rows($operand, $level + 1);
                }
            }
            else if (isset($term['op_bin'])) {
                aux_print_rows($term['left'], $level + 1);
                aux_print_rows($term['right'], $level + 1);
            }
            else {
                aux_print_rows($term['operand'], $level + 1);
            }
        }
    }
?>