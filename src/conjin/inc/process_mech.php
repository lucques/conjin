<?
    function process_login(Syslet $syslet, bool $logout_successful, bool $password_incorrect) {

        // Init modules
        foreach ($syslet->activated_modules as $name => $module) {
            ob_start();
            $module->init_processing_syslet($syslet, core_load_obj('target_root'));
            ob_end_clean();
        }

        // Render
        $syslet->get_template()->render_login($syslet, $logout_successful, $password_incorrect);
    }

    function process_not_found(Syslet $syslet, ?array $target_ids) {
        // Init modules
        foreach ($syslet->activated_modules as $name => $module) {
            ob_start();
            $module->init_processing_syslet($syslet, core_load_obj('target_root'));
            ob_end_clean();
        }

        // Render
        $syslet->get_template()->render_not_found($syslet, $target_ids);
    }

    function process(Target $target): void {

        //////////////////
        // Init modules //
        //////////////////

        foreach ($target->activated_modules as $name => $module) {
            ob_start();
            $module->init_processing_target($target);
            ob_end_clean();
        }


        //////////////////
        // Pass-through //
        //////////////////

        $script_path = $target->path('/index.php');
        $defs = load_defs_from_script($script_path);
        assert($defs['process'] !== null, 'Missing `$process` function');

        // First process only `$process` function: If this one fails, no need to further process the header component.
        ob_start();
        $defs['process']($target);
        $content = ob_get_clean();

        // Render
        $target->get_template()->render_target($target, $content);
    }
?>