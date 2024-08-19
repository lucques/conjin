<?
    ////////////
    // Syntax //
    ////////////

    // A truth value ("val") is a `bool`.

    // An exp has the following structure:
    // [
    //    'op_bin'       => '&&' | '||' | '->' | '<->' | 'xor',
    //    'op_un'        => '!',
    //    'left'         => exp,
    //    'right'        => exp,
    //    'operand'      => exp,
    //    'operands'     => list(exp),
    //    'explicit_tex' => string           // optional
    // ]
    // | 'true'
    // | 'false'
    // | 'p' | 'q' | ...

    function logic_init_mathjax() {
        // empty
    }

    function logic_is_op_equiv($exp) {
        return is_array($exp) && isset($exp['op_bin']) && $exp['op_bin'] == '<->';
    }

    function logic_is_op_imp($exp) {
        return is_array($exp) && isset($exp['op_bin']) && $exp['op_bin'] == '->';
    }

    function logic_is_op_bin_or($exp) {
        return is_array($exp) && isset($exp['op_bin']) && $exp['op_bin'] == '||';
    }

    function logic_is_op_multi_or($exp) {
        return is_array($exp) && isset($exp['op_multi']) && $exp['op_multi'] == '||';
    }

    function logic_is_op_bin_and($exp) {
        return is_array($exp) && isset($exp['op_bin']) && $exp['op_bin'] == '&&';
    }

    function logic_is_op_multi_and($exp) {
        return is_array($exp) && isset($exp['op_multi']) && $exp['op_multi'] == '&&';
    }

    function logic_is_op_bin_xor($exp) {
        return is_array($exp) && isset($exp['op_bin']) && $exp['op_bin'] == 'xor';
    }

    function logic_is_op_multi_xor($exp) {
        return is_array($exp) && isset($exp['op_multi']) && $exp['op_multi'] == 'xor';
    }

    function logic_is_op_not($exp) {
        return is_array($exp) && isset($exp['op_un']) && $exp['op_un'] == '!';
    }

    function logic_is_const($exp) {
        return is_string($exp) && ($exp == 'true' || $exp == 'false');
    }

    function logic_is_var($exp) {
        return is_string($exp) && preg_match('/^[a-z]$/', $exp);
    }

    // Check whether `$exp` is well-formed (ADTs would have made this unnecessary)
    // returns: bool
    function logic_is_well_formed($exp) {
        return  logic_is_op_equiv($exp) ||
                logic_is_op_imp($exp) ||
                logic_is_op_bin_or($exp) ||
                logic_is_op_multi_or($exp) ||
                logic_is_op_bin_and($exp) ||
                logic_is_op_multi_and($exp) ||
                logic_is_op_bin_xor($exp) ||
                logic_is_op_multi_xor($exp) ||
                logic_is_op_not($exp) ||
                logic_is_const($exp) || 
                logic_is_var($exp);
    }

    function logic_has_explicit_tex($exp) {
        return is_array($exp) && isset($exp['explicit_tex']);
    }
    

    ////////////////
    // Evaluation //
    ////////////////

    // Evaluate a formula of propositional logic
    // $exp:    exp
    // $assign: array(variable -> boolean)
    // returns: boolean
    function logic_eval_prop($exp, $assign) {
        if (is_array($exp)) {
            // Composite
            if (isset($exp['op_bin'])) {
                // Binary op
                $left  = logic_eval_prop($exp['left'], $assign);
                $right = logic_eval_prop($exp['right'], $assign);

                return logic_eval_prop_op_bin($exp['op_bin'], $left, $right);
            }
            elseif (isset($exp['op_un'])) {
                // Unary op
                $operand = logic_eval_prop($exp['operand'], $assign);

                return logic_eval_prop_op_un($exp['op_un'], $operand);
            }
            elseif (isset($exp['operands'])) {
                // Multi op
                $operands = array_map('logic_eval_prop', $exp['operands']);

                return logic_eval_prop_op_multi($exp['op_multi'], $operands);
            }
            else {
                fail('unknown exp: ' . print_r($exp, true));
            }
        }
        else {
            // Atomic
            return logic_eval_prop_atomic($exp, $assign);
        }
    }

    // $operator: un or bin operator
    // $left:     boolean
    // $right:    boolean
    // $operand:  boolean
    // $operands: list(boolean)
    // $assign:   array(variable -> boolean)
    // returns:   boolean
    function logic_eval_prop_op_bin($operator, $left, $right) {
        if     ($operator == '<->') { return $left == $right;  }
        elseif ($operator == '->')  { return !$left || $right; }
        elseif ($operator == '||')  { return $left || $right;  }
        elseif ($operator == '&&')  { return $left && $right;  }
        elseif ($operator == 'xor') { return $left xor $right; }
        else   { fail('operator not found: ' . $operator); } 
    }

    function logic_eval_prop_op_un($operator, $operand) {
        if     ($operator == '!') { return !$operand; }
        else   { fail('operator not found: ' . $operator); } 
    }

    function logic_eval_prop_op_multi($operator, $operands) {
        if     ($operator == '||') { return array_reduce($operands, function($a, $b) { return $a || $b; }, false); }
        elseif ($operator == '&&') { return array_reduce($operands, function($a, $b) { return $a && $b; }, true); }
        elseif ($operator == 'xor') { return array_reduce($operands, function($a, $b) { return $a xor $b; }, false); }
        else   { fail('operator not found: ' . $operator); } 
    }

    function logic_eval_prop_atomic($atomic_exp, $assign) {
        if     ($atomic_exp == 'false') { return false; }
        elseif ($atomic_exp == 'true')  { return true;  }
        elseif (isset($assign[$atomic_exp])) { return $assign[$atomic_exp]; }
        else   { fail('unknown atomic exp: ' . $atomic_exp); }
    }


    ////////////////////////
    // TeX printers: Exps //
    ////////////////////////

    // Assertion `logic_is_well_formed($exp)` is executed only once in `logic_texify_exp`
    // to eliminate "typing" errors

    // Abbreviations:
    // "seq" stands for "sequence of"

    // $exp:     exp
    // returns:  string (for HTML)
    function logic_texify_exp_printer($exp) {
        return '$' . logic_texify_exp($exp) . '$';
    }

    // $exp:     exp
    // returns:  string (TeX)
    function logic_texify_exp($exp) {
        assert(logic_is_well_formed($exp));
        return logic_texify_op_equiv($exp);
    }

    function logic_texify_op_equiv($exp) {
        if (logic_has_explicit_tex($exp)) { return $exp['explicit_tex']; }

        if (logic_is_op_equiv($exp)) {
            return logic_texify_op_equiv($exp['left']) . ' \leftrightarrow ' . logic_texify_op_equiv($exp['right']);
        }
        else {
            return logic_texify_op_imp($exp);
        }
    }

    function logic_texify_op_imp($exp) {
        if (logic_has_explicit_tex($exp)) { return $exp['explicit_tex']; }

        if (logic_is_op_imp($exp)) {
            return logic_texify_op_or($exp['left']) . ' \rightarrow ' . logic_texify_op_imp($exp['right']);
        }
        else {
            return logic_texify_op_or($exp);
        }
    }

    function logic_texify_op_or($exp) {
        if (logic_has_explicit_tex($exp)) { return $exp['explicit_tex']; }

        if     (logic_is_op_bin_or($exp)) {
            return logic_texify_op_or($exp['left']) . ' \lor ' . logic_texify_op_or($exp['right']);
        }
        elseif (logic_is_op_multi_or($exp)) {
            return implode(' \lor ', array_map('logic_texify_op_or', $exp['operands']));
        }
        else {
            return logic_texify_op_and($exp);
        }
    }

    function logic_texify_op_and($exp) {
        if (logic_has_explicit_tex($exp)) { return $exp['explicit_tex']; }

        if     (logic_is_op_bin_and($exp)) {
            return logic_texify_op_and($exp['left']) . ' \land ' . logic_texify_op_and($exp['right']);
        }
        elseif (logic_is_op_multi_and($exp)) {
            return implode(' \land ', array_map('logic_texify_op_and', $exp['operands']));
        }
        else {
            return logic_texify_op_xor($exp);
        }
    }

    function logic_texify_op_xor($exp) {
        if (logic_has_explicit_tex($exp)) { return $exp['explicit_tex']; }

        if     (logic_is_op_bin_xor($exp)) {
            return logic_texify_op_xor($exp['left']) . ' \oplus ' . logic_texify_op_xor($exp['right']);
        }
        elseif (logic_is_op_multi_xor($exp)) {
            return implode(' \oplus ', array_map('logic_texify_op_xor', $exp['operands']));
        }
        else {
            return logic_texify_op_neg($exp);
        }
    }

    function logic_texify_op_neg($exp) {
        if     (logic_has_explicit_tex($exp)) {
            return $exp['explicit_tex'];
        }
        elseif (logic_is_op_not($exp)) {
            return '\neg ' . logic_texify_op_neg($exp['operand']);
        }
        else {
            return logic_texify_atomic($exp);
        }
    }

    function logic_texify_atomic($exp) {
        if     (logic_has_explicit_tex($exp)) {
            return $exp['explicit_tex'];
        }

        elseif (is_array($exp)) {
            return '\left(' . logic_texify_exp($exp) . '\right)';
        }
        elseif ($exp == 'true')  {
            return '\top';
        }
        elseif ($exp == 'false') {
            return '\bot';
        }
        else   {
            return '\mathit{' . $exp . '}';
        }
    }


    /////////////////////////////////////////////
    // TeX printers: Exps with explicit parens //
    /////////////////////////////////////////////

    // Print
    // - `p <-> (q -> r)` instead of `p <-> q -> r`
    // - `p -> (p || r)` instead of `p -> p || r`
    // - `p -> (q -> r)` instead of `p -> q -> r`
    // - `p || (q && r)` instead of `p || q && r`
    // - etc.
    // On the other hand, print
    // - `p -> !q`, i.e. negation is considered bound "tight" enough 
    
    // Abbreviations:
    // "ep"     stands for "explicit parens".
    // "parens" stands for "add parentheses if necessary"

    // All printers carry the "ep" prefix as this is the mode of printing.
    // For each assoc binary op, there are two printers:
    // - `logic_texify_ep_op_<op>`     prints the exp with explicit parens
    // - `logic_texify_ep_seq_op_<op>` prints the exp with explicit parens, but first prints a sequence of `<op>`

    // $exp:     exp
    // returns:  string (for HTML)
    function logic_texify_ep_exp_printer($exp) {
        return '$' . logic_texify_ep_exp($exp) . '$';
    }

    // $exp:     exp
    // returns:  string (TeX)
    function logic_texify_ep_exp($exp) {
        assert(logic_is_well_formed($exp));
        return logic_texify_ep_op_equiv($exp);
    }

    function logic_texify_ep_op_equiv($exp) {
        if (logic_has_explicit_tex($exp)) { return $exp['explicit_tex']; }

        if (logic_is_op_equiv($exp)) {
            return logic_texify_ep_op_equiv($exp['left']) . ' \leftrightarrow ' . logic_texify_ep_op_equiv($exp['right']);
        }
        else {
            // Charge "parens" obligation
            return logic_texify_ep_op_imp_parens($exp);
        }
    }

    function logic_texify_ep_op_imp_parens($exp) {
        if (logic_has_explicit_tex($exp)) { return $exp['explicit_tex']; }

        if (logic_is_op_imp($exp)) {
            // Discharge "parens" obligation
            return '(' . logic_texify_ep_op_imp($exp) . ')';
        }
        else {
            // Forward "parens" obligation
            return logic_texify_ep_op_or_parens($exp);
        }
    }

    function logic_texify_ep_op_imp($exp) {
        if (logic_has_explicit_tex($exp)) { return $exp['explicit_tex']; }

        if (logic_is_op_imp($exp)) {
            return logic_texify_ep_op_or($exp['left']) . ' \rightarrow ' . logic_texify_ep_op_or($exp['right']);
        }
        else {
            // Charge "parens" obligation
            return logic_texify_ep_op_or($exp);
        }
    }

    function logic_texify_ep_op_or_parens($exp) {
        if (logic_has_explicit_tex($exp)) { return $exp['explicit_tex']; }

        if (logic_is_op_bin_or($exp)) {
            // Discharge "parens" obligation
            return '(' . logic_texify_ep_op_or($exp) . ')';
        }
        else {
            // Forward "parens" obligation
            return logic_texify_ep_op_and_parens($exp);
        }
    }

    function logic_texify_ep_op_or($exp) {
        if (logic_has_explicit_tex($exp)) { return $exp['explicit_tex']; }

        if (logic_is_op_bin_or($exp)) {
            return logic_texify_ep_op_or($exp['left']) . ' \lor ' . logic_texify_ep_op_or($exp['right']);
        }
        elseif (logic_is_op_multi_or($exp)) {
            return implode(' \lor ', array_map('logic_texify_ep_op_or', $exp['operands']));
        }
        else {
            // Charge "parens" obligation
            return logic_texify_ep_op_and_parens($exp);
        }
    }

    function logic_texify_ep_op_and_parens($exp) {
        if (logic_has_explicit_tex($exp)) { return $exp['explicit_tex']; }

        if (logic_is_op_bin_and($exp)) {
            // Charge "parens" obligation
            return '(' . logic_texify_ep_op_and($exp) . ')';
        }
        else {
            // Drop the "parens" obligation (stop forward)
            return logic_texify_ep_op_neg($exp);
        }
    }

    function logic_texify_ep_op_and($exp) {
        if (logic_has_explicit_tex($exp)) { return $exp['explicit_tex']; }

        if (logic_is_op_bin_and($exp)) {
            return logic_texify_ep_op_and($exp['left']) . ' \land ' . logic_texify_ep_op_and($exp['right']);
        }
        elseif (logic_is_op_multi_and($exp)) {
            return implode(' \land ', array_map('logic_texify_ep_op_and', $exp['operands']));
        }
        else {
            // Don't charge the "parens" obligation (stop forward)
            return logic_texify_ep_op_neg($exp);
        }
    }

    function logic_texify_ep_op_neg($exp) {
        if (logic_has_explicit_tex($exp)) { return $exp['explicit_tex']; }

        if (logic_is_op_not($exp)) {
            return '\neg ' . logic_texify_ep_op_neg($exp['operand']);
        }
        else {
            return logic_texify_atomic($exp);
        }
    }

    function logic_texify_ep_atomic($exp) {
        if (logic_has_explicit_tex($exp)) { return $exp['explicit_tex']; }
           
        if     ($exp == 'true')  {
            return '\top';
        }
        elseif ($exp == 'false') {
            return '\bot';
        }
        elseif (logic_is_var($exp))  {
            return '\mathit{' . $exp . '}';
        }
        else {
            return '\left(' . logic_texify_ep_exp($exp) . '\right)';
        }
    }


    ///////////////////////////////
    // TeX printer: Truth values //
    ///////////////////////////////

    function logic_texify_val(bool $val) {
        if   ($val) { return '1'; }
        else        { return '0'; }
    }


    ////////////////////////////////////////////
    // HTML+MathJax printers for truth tables //
    ////////////////////////////////////////////

    // $vars: list(var)
    // $exps: list(exp)
    function logic_print_tt($exps, $printer = 'logic_texify_exp_printer') {
        $vars = array_values(array_unique(array_merge(...array_map('logic_aux_get_vars', $exps))));
        sort($vars);

        logic_print_tt_start($vars, $exps, $printer);
    
        foreach (logic_aux_generate_assigns($vars) as $assign) {
            logic_print_tt_row_eval($vars, $assign, $exps);
        }
        
        logic_print_tt_end();
    }

    // $vars:    list(var)
    // $exps:    list(exp)
    // $printer: printer function
    function logic_print_tt_start($vars, $exps, $printer) {
?>
        <table class="table table-bordered border-secondary table-striped text-center">
            <thead>
<?
        $vars_str = array_map(function($var) use ($printer) {
            return call_user_func($printer, $var);
        }, $vars);

        $exps_str = array_map(function($exp) use ($printer) {
            return call_user_func($printer, $exp);
        }, $exps);

        logic_print_tt_row($vars_str, $exps_str, td_th: 'th');
?>
            </thead>
            <tbody>
<?
    }

    function logic_print_tt_row_eval($vars, $assign, $exps) {
        $vars_eval = array_map(function($var) use ($assign) {
            return logic_aux_dollarize(logic_texify_val(logic_eval_prop_atomic($var, $assign)));
        }, $vars);
        $exps_eval = array_map(function($exp) use ($assign) {
            return logic_aux_dollarize(logic_texify_val(logic_eval_prop($exp, $assign)));
        }, $exps);

        logic_print_tt_row($vars_eval, $exps_eval, td_th: 'td');
    }

    function logic_print_tt_row($cells_left, $cells_right, $td_th = 'td') {
?>
                <tr>
<?
        foreach ($cells_left as $i => $cell_left) {
            if ($i == 0 && count($cells_left) == 1) {
?>
                    <<?= $td_th ?> style="border-right-width: 4px;"><?= $cell_left ?></<?= $td_th ?>>
<?
            }
            elseif ($i == 0 && count($cells_left) > 1) {
?>
                    <<?= $td_th ?>><?= $cell_left ?></<?= $td_th ?>>
<?
            }
            elseif ($i < count($cells_left) - 1) {
?>
                    <<?= $td_th ?> class="border-start-0"><?= $cell_left ?></<?= $td_th ?>>
<?
            }
            else {
?>
                    <<?= $td_th ?> class="border-start-0" style="border-right-width: 4px;"><?= $cell_left ?></<?= $td_th ?>>
<?
            }
        }
        foreach ($cells_right as $i => $cell_right) {
            if ($i < count($cells_right) - 1) {
?>
                    <<?= $td_th ?> class="border-start-0"><?= $cell_right ?></<?= $td_th ?>>
<?
            }
            else {
?>
                    <<?= $td_th ?> class="border-start-0"><?= $cell_right; ?></<?= $td_th ?>>
<?
            }
        }
?>
                </tr>
<?
    }

    function logic_print_tt_end() {
?>
            </tbody>
        </table>
<?
    }

    /////////
    // Aux //
    /////////

    function logic_aux_dollarize($str) {
        return '$' . $str . '$';
    }

    // $vars: list(var)
    // return: list(array(var -> bool))
    function logic_aux_generate_assigns($vars) {
        $assigns = [];

        $n = count($vars);
        $m = 2 ** $n;

        for ($i = 0; $i < $m; $i++) {
            $assign = [];

            for ($j = 0; $j < $n; $j++) {
                $assign[$vars[count($vars)-1 - $j]] = ($i >> $j) & 1;
            }

            $assigns[] = $assign;
        }

        return $assigns;
    }

    // $exp:    exp
    // returns: list(var)
    function logic_aux_get_vars($exp) {
        if (is_array($exp)) {
            // Composite
            if (isset($exp['op_bin'])) {
                // Binary op
                return array_merge(
                    logic_aux_get_vars($exp['left']),
                    logic_aux_get_vars($exp['right']));
            }
            elseif (isset($exp['op_un'])) {
                // Unary op
                return logic_aux_get_vars($exp['operand']);
            }
            elseif (isset($exp['op_multi'])) {
                // Multi op
                $vars = [];

                foreach ($exp['operands'] as $operand) {
                    $vars = array_merge($vars, logic_aux_get_vars($operand));
                }

                return $vars;
            }
            else {
                fail('unknown exp: ' . print_r($exp, true));
            }
        }
        else {
            // Atomic
            if ($exp == 'true' || $exp == 'false') {
                return [];
            }
            else {
                return [$exp];
            }
        }
    }
?>