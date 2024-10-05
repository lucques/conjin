<?
    $init_processing_target = function(Module $module, Target $target) {
        $is_always_on  = $module->config->get('always_on');
        $is_always_off = $module->config->get('always_off');
        assert(!$is_always_on && !$is_always_off, 'Incompatible options');

        $is_requested  = isset($_GET['solution']) && $_GET['solution'] === '1';

        // Read allowed groups from config
        $restrict_to_groups = $module->config->get('restrict_to_groups');
        $restrict_to_users  = $module->config->get('restrict_to_users');

        // Allowed if...
        $is_allowed =
            // 1. either no restriction is given or...
            (count($restrict_to_groups) == 0 && count($restrict_to_users) == 0) ||
            // 2. current user is among the allowed actors 
            auth_is_cur_user_among_authorized_groups_users(
                groups: $restrict_to_groups,
                users: $restrict_to_users
            );


        //////////////////
        // Global state //
        //////////////////

        // For template
        $GLOBALS['sol_mode_allowed'] = $is_allowed;
        $GLOBALS['sol_mode_on']      = (!$is_always_off && $is_requested && $is_allowed) || $is_always_on;

        // For content
        $GLOBALS['sol_mode_last_macro'] = null; // Possible values: null, 'start', 'gap'
    };


    ///////////////////////////////////
    // Functions offered to TEMPLATE //
    ///////////////////////////////////

    function is_sol_mode_allowed(): bool {
        return $GLOBALS['sol_mode_allowed'];
    }

    function is_sol_mode_on(): bool {
        return $GLOBALS['sol_mode_on'];
    }

    function sol_mode_get_queries_to_request_on(): array {
        return ['solution' => true];
    }

    function sol_mode_get_queries_to_request_off(): array {
        return ['solution' => null];
    }


    //////////////////////////////////
    // Functions offered to CONTENT //
    //////////////////////////////////

    // Basic: Insert solution

    function sol_start() {
        assert($GLOBALS['sol_mode_last_macro'] == null);

        if (!is_sol_mode_on()) {
            ob_start();
        }

        $GLOBALS['sol_mode_last_macro'] = 'start';
    }

    function sol_gap() {
        assert($GLOBALS['sol_mode_last_macro'] == 'start');

        if (!is_sol_mode_on()) {
            ob_end_clean();
        }
        else {
            ob_start();
        }

        $GLOBALS['sol_mode_last_macro'] = 'gap';
    }

    function sol_end() {
        assert(in_array($GLOBALS['sol_mode_last_macro'], ['start', 'gap']));

        if ($GLOBALS['sol_mode_last_macro'] == 'start') {
            if (!is_sol_mode_on()) {
                ob_end_clean();
            }
        }
        else {
            if (is_sol_mode_on()) {
                ob_end_clean();
            }    
        }

        $GLOBALS['sol_mode_last_macro'] = null;   
    }
?>