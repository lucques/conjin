<?
    /*
        This module offers to include exercises and solutions.
        It is independent of the "solution_mode" module.
    */

    $init_processing = function(Module $module, Target $target) {       
        $GLOBALS['ex_next_number']      = 1;
        $GLOBALS['ex_sol_cur_admitted'] = false;
    };


    function ex_set_next_number(int $number) {
        $GLOBALS['ex_next_number'] = $number;
    }

    // Exercise accordion stack
    function ex_start() {
        acc_start(variant: 'exercise', only_one_open: false);
    }
    
    function ex_end() {
        acc_end();

        // Increment counter
        $GLOBALS['ex_next_number']++;
    }

    // Description
    function ex_desc_start($title = '', bool $numbered = true, $open = false) {
        if ($title == '' && $numbered) {
            acc_item_start('<strong>Aufgabe ' . $GLOBALS['ex_next_number'] . '</strong>', variant: 'exercise', open: $open);
        }
        elseif ($title == '' && !$numbered) {
            acc_item_start('<strong>Aufgabe</strong>', variant: 'exercise', open: $open);
        }
        elseif ($title != '' && $numbered) {
            acc_item_start('<strong>Aufgabe ' . $GLOBALS['ex_next_number'] . '</strong>: ' . $title, variant: 'exercise', open: $open);
        }
        else {
            acc_item_start($title, variant: 'exercise', open: $open);
        }
    }

    function ex_desc_end() {
        acc_item_end();
    }

    // Tip
    function ex_tip_start($title = '', $open = false) {
        if ($title == '') {
            acc_item_start('<strong>Tipp</strong>', variant: 'tip', open: $open);
        }
        else {
            acc_item_start('<strong>' . $title . '</strong>', variant: 'tip', open: $open);
        }
    }

    function ex_tip_end() {
        acc_item_end();
    }

    // Solution
    function ex_sol_start($admitted_roles = [], $title = 'Lösungsvorschlag', $open = false) {
        // Show solution if role is `admin` or among the admitted ones
        $GLOBALS['ex_sol_cur_admitted'] = true; // TODO
                    
        if ($GLOBALS['ex_sol_cur_admitted']) {
            acc_item_start($title, variant: 'solution', open: $open);
        }
        else {
            ob_start();  // Prepare to throw away
        }
    }

    function ex_sol_end() {
        if ($GLOBALS['ex_sol_cur_admitted']) {
            acc_item_end();
        }
        else {
            ob_end_clean();  // Throw away
        }
    }
?>