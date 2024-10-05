<?
    $init_processing_target = function(Module $module, Target $target) {       
        
        //////////////////
        // Global state //
        //////////////////
        
        // For content
        $GLOBALS['exercise_next_number'] = 1;
        $GLOBALS['exercise_last_macro']  = null; // Possible values: null, 'start', 'sol_print', 'sol_omit'
    };


    /////////////////////
    // Used by CONTENT //
    /////////////////////
    
    function ex_start($title = null, bool $numbered = true, $variant = 'exercise', $open = false, $only_one_open = false, $class = '', $style = '') {
        // Track state
        assert($GLOBALS['exercise_last_macro'] == null, 'Exercise not finished yet');

        // Start new acc set
        acc_start(only_one_open: $only_one_open, class: $class, style: $style);

        // Start new item
        if ($numbered) {
            if ($title === null) {
                $title = '<strong>Aufgabe ' . $GLOBALS['exercise_next_number'] . '</strong>';
            }
            else {
                $title = '<strong>Aufgabe ' . $GLOBALS['exercise_next_number'] . '</strong>: ' . $title;
            }
            $GLOBALS['exercise_next_number']++;
        }
        else {
            if ($title === null) {
                $title = '<strong>Aufgabe</strong>';
            }
        }
        acc_item_start($title, variant: $variant, open: $open);

        // Track state
        $GLOBALS['exercise_last_macro'] = 'start';
    }
    
    function ex_item($title, $variant = null, $open = false) {
        // Track state
        assert(in_array($GLOBALS['exercise_last_macro'], ['start', 'sol_print', 'sol_omit']));

        // End last item
        ex_aux_end_last_item();

        // Start new item
        acc_item_start($title, variant: $variant, open: $open);

        // Track state
        $GLOBALS['exercise_last_macro'] = 'start';
    }

    function ex_hint($title = '<strong>Tipp</strong>', $variant = 'hint', $open = false) {
        ex_item($title, variant: $variant, open: $open);
    }

    function ex_sol($restrict_to_groups = [], $restrict_to_users = [], $title = 'Lösungsvorschlag', $variant = 'solution', $open = false, $hidden = false) {
        // Track state
        assert(in_array($GLOBALS['exercise_last_macro'], ['start', 'sol_print', 'sol_omit']));

        // End last item
        ex_aux_end_last_item();

        // Start new item and track state
        // Allowed if...
        $is_allowed =
            // 1. either no restriction is given or...
            (count($restrict_to_groups) == 0 && count($restrict_to_users) == 0) ||
            // 2. current user is among the allowed actors 
            auth_is_cur_user_among_authorized_groups_users(
                groups: $restrict_to_groups,
                users: $restrict_to_users
            );
        
        $actually_hidden = $hidden || !$is_allowed;

        acc_item_start($title, variant: $variant, open: $open, hidden: $actually_hidden);

        // Track state
        if ($actually_hidden) {
            $GLOBALS['exercise_last_macro'] = 'sol_omit';
        }
        else {
            $GLOBALS['exercise_last_macro'] = 'sol_print';
        }
    }

    function ex_end() {
        // Track state
        assert(in_array($GLOBALS['exercise_last_macro'], ['start', 'sol_print', 'sol_omit']));

        // End last item
        ex_aux_end_last_item();

        // End last acc set
        acc_end();

        // Track state
        $GLOBALS['exercise_last_macro'] = null;
    }

    function ex_set_next_number(int $number) {
        $GLOBALS['exercise_next_number'] = $number;
    }


    /////////////
    // Helpers //
    /////////////

    function ex_aux_end_last_item() {
        acc_item_end();
    }
?>