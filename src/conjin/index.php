<?
    ///////////////////
    // Setup, part 1 //
    ///////////////////

    require('inc/core_pure.php');
    require('inc/core_effectful.php');

    enum Req: string {
        case Debug      = 'debug';
        case Preprocess = 'preprocess';
        case Login      = 'login';
        case Logout     = 'logout';
        case Process    = 'process';
        case Res        = 'res';
    }

    $req = Req::tryFrom($_GET['req'] ?? '');

    if ($req == null) {
        send_response_and_exit(400); // Bad request
    }


    ///////////////////
    // Setup, part 2 //
    ///////////////////

    global_config_init();

    require('inc/load_mech.php');
    require('inc/module_mech.php');
    require('inc/auth.php');
    auth_init();
    require('inc/target.php');


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
            send_response_and_exit(403); // Unauthorized
        }
    }
    elseif ($req == Req::Preprocess) {

        ////////////////
        // Preprocess //
        ////////////////

        // Check privilege
        if (!auth_is_user_privileged_to_preprocess()) {
            send_response_and_exit(403); // Unauthorized
        }

        // Libraries
        require('inc/preprocess_mech.php');

        // Preprocessing preprares two objects:
        // 1. For auth:    `groups_2_userlist`
        // 2. Target tree: `root_target`
        core_save_obj('groups_2_userlist', auth_generate_groups_2_userlist());
        core_save_obj('root_target',       preprocess_all());

        // Render response
        send_response_and_exit(message: 'Preprocessing done.');
    }
    else {

        // Make sure preprocessing has taken place
        if (!core_obj_exists('root_target')) {
            send_response_and_exit(status_code: 500, message: "Preprocessing not done.");
        }


        if ($req == Req::Login) {

            ///////////
            // Login //
            ///////////
    
            // Already logged in? Then redirect.
            if (auth_is_logged_in()) {
                auth_redirect_after_successful_login_and_exit();
            }
            else {
                auth_handle_login_and_exit();
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
                send_response_and_exit(403); // Unauthorized
            }
            
        }
        elseif ($req == $req::Process) {
    
            /////////////
            // Process //
            /////////////
    
            // Libraries
            require('inc/cache.php'); // Deals with browser cache (304 Not Modified)
            require('inc/process_mech.php');
    
            // Interpret request
            $GET_target = $_GET['target'] ?? ''; // Coalesce to empty string
            $requested_target_ids = target_query_to_target_ids($GET_target);
            
            if ($requested_target_ids === null) {
                send_response_and_exit(404); // Not Found
            }
    
            $target = core_load_obj('root_target')->find_child($requested_target_ids);
    
            // If target does not exist, send "not found"
            if ($target == null) {
                send_response_and_exit(404); // Not Found
            }
    
            // If user is not privileged to view target, either redirect to
            // login page or send "unauthorized"
            if (!auth_is_cur_user_privileged_for_view($target)) {
                if (!auth_is_logged_in()) {
                    redirect_and_exit(auth_get_login_url_with_redirect());
                }
                else {
                    send_response_and_exit(403); // Unauthorized
                }
            }
    
            // Respond "not modified", i.e. file cached by browser?
            $requested_path = path_collect($requested_target_ids) . '/index.php';
            check_whether_unmodified_and_handle($requested_path);
                
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
                send_response_and_exit(404); // Not Found
            }
    
            // 1. Interpret `target` part of the request
            $GET_target = $_GET['target'] ?? ''; // Coalesce to empty string
            $requested_target_ids = target_query_to_target_ids($GET_target);
    
            if ($requested_target_ids === null) {
                send_response_and_exit(404); // Not Found
            }
    
            $target = core_load_obj('root_target')->find_child($requested_target_ids);
                
            // If target does not exist, send "not found"
            if ($target == null) {
                send_response_and_exit(404); // Not Found
            }
    
            // 2. Interpret `res` part of the request
            $path = path('content') . '/' . $GET_target . 'res/' . $_GET['res'];
            $real_path = realpath($path);
    
            // If resource does not exist, send "not found"
            if ($real_path === false) {
                send_response_and_exit(404); // Not Found
            }
    
            // If user is not privileged to view target, either redirect to
            // login page or send "unauthorized"
            if (!auth_is_cur_user_privileged_for_view($target)) {
                if (!auth_is_logged_in()) {
                    redirect_and_exit(auth_get_login_url_with_redirect());
                }
                else {
                    send_response_and_exit(403); // Unauthorized
                }
            }
            
            // If resource outside of `content` dir, deny permission.
            if (!str_starts_with($real_path, realpath(path('content')))) {
                send_response_and_exit(403); // Unauthorized
            }
    
            // Respond "not modified", i.e. file cached by browser?
            check_whether_unmodified_and_handle($path);
    
            // Send file
            header('Content-Type: ' . get_mime($path));
            readfile($real_path);
        }
    }
?>