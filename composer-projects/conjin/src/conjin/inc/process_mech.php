<?
    function process_login(LoginProfile $login_profile, bool $logout_successful, bool $password_incorrect, mixed $openid_fail, array $openid_provider_names) {

        // Init modules
        foreach ($login_profile->activated_modules as $name => $module) {
            ob_start();
            $module->init_processing_syslet($login_profile, core_load_obj('target_root'));
            ob_end_clean();
        }

        // Render
        $login_profile->get_template()->render_login($login_profile, $logout_successful, $password_incorrect, $openid_fail, $openid_provider_names);
    }

    function process_not_found_and_exit() {
        http_response_code(404);

        $GET_target = $_GET['target'] ?? ''; // Coalesce to empty string
        $requested_target_ids = target_query_to_target_ids($GET_target);
        
        $syslet = core_load_obj('syslet_not_found');
    
        // Init modules
        foreach ($syslet->activated_modules as $name => $module) {
            ob_start();
            $module->init_processing_syslet($syslet, core_load_obj('target_root'));
            ob_end_clean();
        }

        // Render
        $syslet->get_template()->render_not_found($syslet, $requested_target_ids);

        exit();
    }

    function process_unauthorized_and_exit() {
        http_response_code(401);

        $GET_target = $_GET['target'] ?? ''; // Coalesce to empty string
        $requested_target_ids = target_query_to_target_ids($GET_target);
        
        $syslet = core_load_obj('syslet_unauthorized');
    
        // Init modules
        foreach ($syslet->activated_modules as $name => $module) {
            ob_start();
            $module->init_processing_syslet($syslet, core_load_obj('target_root'));
            ob_end_clean();
        }

        // Render
        $syslet->get_template()->render_unauthorized($syslet, $requested_target_ids);

        exit();
    }

    function process(Target $target): void {
        assert($target->content_location == ContentLocation::INLINE || $target->content_location == ContentLocation::EXTRA, 'Can only process targets with inline or extra content location');

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

        if ($target->content_location == ContentLocation::INLINE) {
            $script_path = $target->path('index.php');
            $defs = load_defs_from_script($script_path);
            assert($defs['process'] !== null, 'Missing `$process` function');

            // Process
            ob_start();
            $defs['process']($target);
            $content = ob_get_clean();
        }
        else // ContentLocation::EXTRA
        {
            $script_path = $target->path('content.php');

            // Process
            ob_start();
            require($script_path);
            $content = ob_get_clean();
        }

        // Render
        $target->get_template()->render_target($target, $content);
    }
?>
