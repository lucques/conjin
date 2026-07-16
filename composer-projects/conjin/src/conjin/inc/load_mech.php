<?
    ///////////
    // State //
    ///////////

    $GLOBALS['load_mech_temp_script_path'] = null;
    $GLOBALS['load_mech_script_path_def_names_2_defs'] = [];


    /////////////////////////
    // Effectful functions //
    /////////////////////////

    function load_defs_from_script(string $script_path): array {
        if (!file_exists($script_path)) {
            return [];
        }
        
        // Canonicalize
        $GLOBALS['load_mech_temp_script_path'] = realpath($script_path);

        if (!isset($GLOBALS['load_mech_script_path_def_names_2_defs'][$GLOBALS['load_mech_temp_script_path']])) {
            // Clear scope
            foreach (array_keys(get_defined_vars()) as $key) {
                unset($$key);
            }
            // For some reason, $key remains in scope. Fine:
            unset($key);

            // Fill scope by including script
            ob_start();
            require_once($GLOBALS['load_mech_temp_script_path']);
            ob_end_clean();

            // Retrieve new scope
            $GLOBALS['load_mech_script_path_def_names_2_defs'][$GLOBALS['load_mech_temp_script_path']] = get_defined_vars();
        }

        return $GLOBALS['load_mech_script_path_def_names_2_defs'][$GLOBALS['load_mech_temp_script_path']];
    }


    ///////////////
    // Shortcuts //
    ///////////////

    // Assert on the way that the definitions exist

    function load_def_from_script_and_call(string $script_path, string $def_name, ...$args): mixed {
        assert(file_exists($script_path), "Missing file `$script_path`");

        $defs = load_defs_from_script($script_path);

        assert(isset($defs[$def_name]), "Missing definition of `\$$def_name` in file `$script_path`");

        return $defs[$def_name](...$args);
    }

    function load_def_from_script_and_get(string $script_path, string $def_name): mixed {
        assert(file_exists($script_path), "Missing file `$script_path`");

        $defs = load_defs_from_script($script_path);

        assert(isset($defs[$def_name]), "Missing definition of `\$$def_name` in file `$script_path`");

        return $defs[$def_name];
    }
?>