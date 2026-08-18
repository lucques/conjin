<?
    ///////////////////
    // Setup, part 1 //
    ///////////////////

    require('inc/core_init.php');
    require('inc/core_pure.php');
    require('inc/core_effectful.php');

    enum Req: string {
        case Debug        = 'debug';
        case Preprocess   = 'preprocess';
        case Login        = 'login';
        case OidcStart    = 'oidc-start';
        case OidcCallback = 'oidc-callback';
        case Logout       = 'logout';
        case Show         = 'show';
        case Res          = 'res';
        case Other        = 'other';
    }

    $req = Req::tryFrom($_GET['req'] ?? '');

    if ($req == null) {
        send_response_and_exit(400); // Bad request
    }


    ///////////////////
    // Setup, part 2 //
    ///////////////////

    require('inc/load_mech.php');
    require('inc/module_mech.php');
    require('inc/auth.php');
    auth_init();
    require('inc/target.php');
    // Require always since also needed for "not found" and "unauthorized"
    require('inc/process_mech.php');


    ///////////////////////
    // Determine request //
    ///////////////////////

    if ($req == Req::Debug) {

        ///////////
        // Debug //
        ///////////

        if (auth_is_user_privileged_to_debug()) {
            require('inc/debug.php');
        }
        else {
            auth_handle_unauthorized_and_exit(); // Unauthorized
        }
    }
    elseif ($req == Req::Preprocess) {

        ////////////////
        // Preprocess //
        ////////////////

        // Check privilege
        if (!auth_is_user_privileged_to_preprocess()) {
            auth_handle_unauthorized_and_exit();
        }

        // Libraries
        require('inc/preprocess_mech.php');

        // Preprocessing prepares objects:
        // 1. For auth:    `groups_2_userlist_ser`
        // 2. Targets:     `target_root`
        // 3. Syslets (including login profiles)
        core_save_obj('groups_2_userlist_ser',       auth_generate_groups_2_userlist_ser());
        core_save_obj('groups_2_openidmarkerlist',   auth_generate_groups_2_openidmarkerlist());

        $login_profiles = preprocess_login_profiles();
        $default_login_profile_id = preprocess_default_login_profile_id($login_profiles);
        $target_root = preprocess_target_root($default_login_profile_id);

        preprocess_validate_target_login_profiles($target_root, $login_profiles);

        core_save_obj('target_root',                 $target_root);
        core_save_obj('login_profiles',              $login_profiles);
        core_save_obj('default_login_profile_id',    $default_login_profile_id);
        core_save_obj('syslet_not_found',            preprocess_syslet('not_found'));
        core_save_obj('syslet_unauthorized',         preprocess_syslet('unauthorized'));

        // Render response
        send_response_and_exit(message: 'Preprocessing done.');
    }
    else {

        // Make sure preprocessing has taken place
        if (!core_obj_exists('target_root')) {
            send_response_and_exit(status_code: 500, message: "Preprocessing not done.");
        }

        if ($req == Req::Login) {

            ///////////
            // Login //
            ///////////
    
            $login_profile_id = $_GET['login_profile'] ?? auth_get_default_login_profile_id();
            $login_profiles = core_load_obj('login_profiles');

            if (!is_string($login_profile_id) || !isset($login_profiles[$login_profile_id])) {
                process_not_found_and_exit();
            }

            // Already logged in? Then redirect.
            if (auth_is_logged_in()) {
                auth_redirect_after_successful_login_and_exit();
            }
            else {
                auth_handle_login_and_exit($login_profiles[$login_profile_id]);
            }

        }
        elseif ($req == Req::OidcStart) {

            /////////////////////////////
            // OpenID Connect: Start   //
            /////////////////////////////

            $provider_name = $_GET['oidc_provider'] ?? null;
            $login_profile_id = $_GET['login_profile'] ?? auth_get_default_login_profile_id();
            $login_profiles = core_load_obj('login_profiles');

            if (
                !is_string($provider_name)
                || !is_string($login_profile_id)
                || !isset($login_profiles[$login_profile_id])
            ) {
                process_not_found_and_exit();
            }

            if (auth_is_logged_in()) {
                auth_redirect_after_successful_login_and_exit();
            }
            else {
                auth_handle_oidc_start_and_exit($provider_name, $login_profiles[$login_profile_id]);
            }

        }
        elseif ($req == Req::OidcCallback) {

            ////////////////////////////////
            // OpenID Connect: Callback   //
            ////////////////////////////////

            $provider_name = $_GET['oidc_provider'] ?? null;
            $default_login_profile_id = auth_get_default_login_profile_id();
            $login_profile_id = $_SESSION['oidc_login_profile_id'] ?? $default_login_profile_id;
            $login_profiles = core_load_obj('login_profiles');

            if (!is_string($provider_name)) {
                process_not_found_and_exit();
            }
            if (!is_string($login_profile_id) || !isset($login_profiles[$login_profile_id])) {
                $login_profile_id = $default_login_profile_id;
            }

            if (auth_is_logged_in()) {
                auth_redirect_after_successful_login_and_exit();
            }
            else {
                auth_handle_oidc_callback_and_exit($provider_name, $login_profiles[$login_profile_id]);
            }

        }
        elseif ($req == Req::Logout) {
    
            ////////////
            // Logout //
            ////////////
    
            // Logged in? Then logout.
            if (auth_is_logged_in()) {
                auth_handle_logout_and_exit();
            }
            else {
                auth_handle_unauthorized_and_exit();
            }
            
        }
        elseif ($req == Req::Show) {
    
            //////////
            // Show //
            //////////
    
            // Libraries
            require('inc/cache.php'); // Deals with browser cache (304 Not Modified)
    
            // Interpret request
            $GET_target = $_GET['target'] ?? ''; // Coalesce to empty string
            $requested_target_ids = target_query_to_target_ids($GET_target);
            
            if ($requested_target_ids === null) {
                process_not_found_and_exit(); // Not Found
            }
    
            $target = core_load_obj('target_root')->find_child($requested_target_ids);
    
            // If target does not exist, send "not found"
            if ($target == null) {
                process_not_found_and_exit(); // Not Found
            }

            // If target's content consists of a redirect, send "moved permanently"
            if ($target->content_location == ContentLocation::REDIRECT) {
                $redirect_target_ids = load_def_from_script_and_get(path_collect($requested_target_ids, 'index.php'), 'redirect');
                redirect_permanently_and_exit(url_collect($redirect_target_ids)); // Moved permanently
            }

            // If target has no content, send "not found"
            if ($target->content_location == ContentLocation::NONE) {
                process_not_found_and_exit(); // Not Found
            }
    
            // If user is not privileged to view target, either redirect to
            // login page or send "unauthorized"
            if (!auth_is_cur_user_privileged_for_view($target)) {
                auth_handle_unauthorized_and_exit($target->get_login_profile());
            }
    
            // Respond "not modified", i.e. file cached by browser?
            $requested_path = path_collect($requested_target_ids, 'index.php');
            check_whether_unmodified_and_handle_and_exit($requested_path);
                
            // Render response
            process($target);
    
            // Statistics
            echo '<!-- Rendered in ' . timer_get_elapsed_ms() . ' ms -->';
        }
        elseif ($req == Req::Res) {
    
            /////////
            // Res //
            /////////
                
            // Libraries            
            require('inc/cache.php'); // Deals with browser cache (304 Not Modified)
    
            // Check up front that `res` query is not empty
            if (!isset($_GET['res'])) {
                process_not_found_and_exit(); // Not Found
            }

            // 1. Interpret `target` part of the request
            $GET_target = $_GET['target'] ?? ''; // Coalesce to empty string
            $requested_target_ids = target_query_to_target_ids($GET_target);
    
            if ($requested_target_ids === null) {
                process_not_found_and_exit(); // Not Found
            }
    
            $target = core_load_obj('target_root')->find_child($requested_target_ids);
                
            // If target does not exist, send "not found"
            if ($target == null) {
                process_not_found_and_exit(); // Not Found
            }
    
            // 2. Interpret `inc` and `res` part of the request
            $path = null;

            if (isset($_GET['inc'])) {
                // `inc` part present
                $path = path('content') . '/' . $GET_target . 'inc/' . $_GET['inc'] . 'res/' . $_GET['res'];
            }
            else {
                // `inc` part absent
                $path = path('content') . '/' . $GET_target . 'res/' . $_GET['res'];    
            }
            $real_path = realpath($path);
    
            // If resource does not exist, send "not found"
            if ($real_path === false) {
                process_not_found_and_exit(); // Not Found
            }
    
            // If user is not privileged to view target, either redirect to
            // login page or send "unauthorized"
            if (!auth_is_cur_user_privileged_for_view($target)) {
                auth_handle_unauthorized_and_exit($target->get_login_profile());
            }
            
            // If resource outside of `content` dir, treat it as non-existing.
            if (!str_starts_with($real_path, realpath(path('content')))) {
                process_not_found_and_exit(); // Not Found
            }
    
            // Respond "not modified", i.e. file cached by browser?
            check_whether_unmodified_and_handle_and_exit($path);
    
            // If not handled already away by now: Send file
            header('Content-Type: ' . get_mime($path));
            readfile($real_path);
        }
        elseif ($req == Req::Other) {
            process_not_found_and_exit(); // Not Found            
        }
    }
?>
