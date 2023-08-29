<?
    $GLOBALS['load_mech_temp_script_path'] = null;
    $GLOBALS['load_mech_script_path_def_names_2_defs'] = [];

    function load_defs_from_target_ids(array $target_ids): array {
        $script_path = path_collect($target_ids) . '/index.php';
        return load_defs_from_script($script_path);
    }

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
?>