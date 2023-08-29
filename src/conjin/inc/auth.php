<?
    //////////////////////////////
    // Session management: Init //
    //////////////////////////////

    // Gets called during app initialization.
    // After return, `$_GLOBALS['user']` is set, based on session etc.
    function auth_init() {
        if (!isset_global_config('auth')) {
            // There is no security enabled, so users can be assumed arbitrarily,
            // otherwise become "root"
            if (isset($_GET['user'])) {
                $GLOBALS['user'] = $_GET['user'];
            }
            else {
                $GLOBALS['user'] = 'root';
            }
        }
        else {
            // If auth is enabled...

            if (isset($_COOKIE['password'])) {
                // Auth by cookie

                // Assume the user or become "guest"
                $user = auth_aux_try_login($_COOKIE['password']);

                if ($user !== null) {
                    $GLOBALS['user'] = $user;
                }
                else {
                    $GLOBALS['user'] = get_global_config('auth', 'guestUser');
                }
            }
            else {
                // Auth by login session
                session_start();

                // Resume user or become "guest"
                if (isset($_SESSION['user'])) {
                    $GLOBALS['user'] = $_SESSION['user'];
                }
                else {
                    $_SESSION['user'] = get_global_config('auth', 'guestUser'); // Save in session
                    $GLOBALS['user']  = get_global_config('auth', 'guestUser');
                }
            }
        }
    }


    //////////////////////////////////
    // Session Management: Handlers //
    //////////////////////////////////

    // Preconditions:
    // - User is eligible to login.
    function auth_handle_login_and_exit() {
        $password_incorrect = false;

        // If login data was sent
        if (isset($_POST['password'])) {
            // Get the user or `null`
            $user = auth_aux_try_login($_POST['password']);

            if ($user !== null) {
                // Password correct
                $_SESSION['user'] = $user;
                $GLOBALS['user']  = $user;
            }
            else {
                // Password incorrect
                $password_incorrect = true;
            }
        }

        if (auth_is_logged_in()) {
            auth_redirect_after_successful_login_and_exit();
        }
        else {
            // Show login page
            $logout_successful = isset($_SESSION['logout_successful']) && $_SESSION['logout_successful'];

            if ($logout_successful) {
                // Only show message once
                $_SESSION['logout_successful'] = false;
            }

            $template_name = get_global_config('auth', 'loginTemplate', 'id', 'name');

            $template = new Module(
                $template_name,
                get_global_config('auth', 'loginTemplate', 'id', 'isShared'),
                get_global_config('auth', 'loginTemplate', 'id', 'isExternal'),
                get_global_config('auth', 'loginTemplate', 'defaultConfig'));

            $template->render_component_generic(
                'login',
                core_load_obj('root_target'),
                [
                    'password_incorrect' => $password_incorrect,
                    'logout_successful'  => $logout_successful
                ]);

            exit();
        }
    }

    // Precondition: User is eligible to logout.
    function auth_handle_logout_and_exit() {
        $_SESSION['logout_successful'] = true;
        $_SESSION['user']              = get_global_config('auth', 'guestUser');
        $GLOBALS['user']               = get_global_config('auth', 'guestUser');
        
        redirect_and_exit(url() . 'login/');
    }


    // Redirect from login page to the page the user wanted to access.
    function auth_redirect_after_successful_login_and_exit() {
        if (isset($_GET['redirect'])) {
            redirect_and_exit($_GET['redirect']);
        }
        else {
            redirect_and_exit(url());
        }
    }


    ///////////////////////////////////
    // Session Management: Functions //
    ///////////////////////////////////
    
    function auth_is_enabled() {
        return isset_global_config('auth');
    }

    function auth_is_logged_in() {
        return $GLOBALS['user'] != get_global_config('auth', 'guestUser');
    }

    function auth_get_user() {
        return $GLOBALS['user'];
    }

    // Return the user or `null`
    function auth_aux_try_login($password): ?string {
        // Try all the passwords and assign user
        foreach (get_global_config('auth', 'users2passwordHashes') as $user => $hash) {
            if (password_verify($password, $hash)) {
                return $user;
            }
        }

        return null;
    }


    //////////////////////////////////////
    // Privileges: BEFORE preprocessing // 
    //////////////////////////////////////

    function auth_is_user_privileged_to_preprocess(): bool {
        return auth_aux_is_user_privileged($GLOBALS['user'], ['tag' => 'Preprocess', 'contents' => []]);
    }

    // This function checks whether `$user` has a `$privilege`.
    // It only relies on the `auth` config and not on any data structures
    // created during preprocessing.
    // It is therefore inefficient and should only be used *before*
    // preprocessing has taken place.
    //
    // $user:      string
    // $privilege: array in the `config.json` format,
    //             e.g. ['tag' => 'View', 'contents' => []]
    function auth_aux_is_user_privileged(string $user, array $privilege): bool {
        // If auth is disabled, anyone is privileged
        if (!isset_global_config('auth')) {
            return true;
        }

        // Root user has any privilege
        if ($user == get_global_config('auth', 'rootUser')) {
            return true;
        }

        // Go through all groups that have the `$privilege`
        foreach (get_global_config('auth', 'groups2privileges') as $g2p) {
            if ($g2p['privilege'] != $privilege) { continue; } // Join condition

            $cur_group = $g2p['group'];

            // Check whether current group is user-based on `$user`
            if ($cur_group['tag']      == 'UserBased' &&
                $cur_group['contents'] == $user) {
                return true;
            }

            // Anything that "guest" can do, any user can do
            // Check whether current group is user-based on "guest"
            if ($cur_group['tag']      == 'UserBased' &&
                $cur_group['contents'] == get_global_config('auth', 'guestUser')) {
                return true;
            }

            // Go through users that were explicitly added to the group
            foreach (get_global_config('auth', 'users2groups') as $u2g) {
                if ($u2g['group'] != $cur_group) { continue; } // Join condition

                $cur_user = $u2g['user'];

                // Check whether `$user` is member of `$cur_group`
                if ($cur_user == $user) {
                    return true;
                }

                // Anything that "guest" can do, any user can do
                // Check whether "guest" is member of `$cur_group`
                if ($cur_user == $user) {
                    return true;
                }
            }
        }

        return false;
    }

    // This function builds up an assoc array with the serialized groups as keys
    // that point to the list of users assigned to each group.
    //
    // The result of this function should be cached during preprocessing.
    //
    // Return: dict<group_ser, list<string>>
    function auth_generate_groups_2_userlist(): array {
        $groups_2_userlist = [];

        foreach (get_global_config('auth', 'users2groups') as $u2g) {
            $cur_group_ser = auth_aux_serialize_group($u2g['group']);
            $cur_user      = $u2g['user'];

            if (!isset($groups_2_userlist[$cur_group_ser])) {
                $groups_2_userlist[$cur_group_ser] = [];
            }

            array_push($groups_2_userlist[$cur_group_ser], $cur_user);
        }

        return $groups_2_userlist;
    }

    // This function builds up an assoc array with the serialized actions as
    // keys that point to the list of groups that are granted access to these
    // actions.
    //
    // The result of this function should be cached during preprocessing.
    //
    // Return:
    //   dict<action_ser, list<group_ser>> x
    //   dict<action_ser, list<group_ser>>
    function auth_generate_actions_2_grouplist_for_target(array $target_ids): array {
        $actions_2_grouplist_all              = [];
        $actions_2_grouplist_only_inheritable = [];

        foreach (get_global_config('auth', 'groups2privileges') as $g2p) {
            $cur_privilege = $g2p['privilege'];

            if ($cur_privilege['tag'] == 'Target' &&
                $cur_privilege['contents']['targetIds'] == $target_ids) {

                $cur_action_ser = auth_aux_ser_action($cur_privilege['contents']['action']);
                $cur_group_ser  = auth_aux_serialize_group($g2p['group']);

                // Collect
                if (!isset($actions_2_grouplist_all[$cur_action_ser])) {
                    $actions_2_grouplist_all[$cur_action_ser] = [];
                }    
                $actions_2_grouplist_all[$cur_action_ser][] = $cur_group_ser;

                if ($cur_privilege['contents']['inherit']) {
                    // Collect
                    if (!isset($actions_2_grouplist_only_inheritable[$cur_action_ser])) {
                        $actions_2_grouplist_only_inheritable[$cur_action_ser] = [];
                    }    
                    $actions_2_grouplist_only_inheritable[$cur_action_ser][] = $cur_group_ser;
                }
            }
        }

        return [
            'all'              => $actions_2_grouplist_all,
            'only_inheritable' => $actions_2_grouplist_only_inheritable
        ];
    }


    /////////////////////////////////////
    // Privileges: AFTER preprocessing // 
    /////////////////////////////////////

    function auth_is_user_privileged_for_view(Target $target): bool {
        $action = ['tag' => 'View', 'contents' => []];
        return auth_aux_is_user_privileged_for_target_action(auth_get_user(), $target, $action);
    }

    function auth_is_user_privileged_for_custom_action(Target $target, string $custom_action): bool {
        $action = ['tag' => 'Custom', 'contents' => $custom_action];
        return auth_aux_is_user_privileged_for_target_action(auth_get_user(), $target, $action);
    }

    // This function checks whether `$user` can perform `$action` on `$target`.
    // It relies on data structures created during preprocessing.
    //
    // It can therefore only be used *after* preprocessing has taken place.
    //
    // $user:      string
    // $target:    Target
    // $action:    array in the `config.json` format,
    //             e.g. ['tag' => 'Custom', 'contents' => 'viewSolution']
    function auth_aux_is_user_privileged_for_target_action(string $user, Target $target, array $action): bool {
        // If auth is disabled, anyone is privileged
        if (!isset_global_config('auth')) {
            return true;
        }

        // Root user has any privilege
        if ($user == get_global_config('auth', 'rootUser')) {
            return true;
        }

        $groups_2_userlist = core_load_obj('groups_2_userlist');
        $action_ser = auth_aux_ser_action($action);

        $guest = get_global_config('auth', 'guestUser');

        $group_user_ser  = auth_aux_serialize_group(['tag' => 'UserBased', 'contents' => $user]);
        $group_guest_ser = auth_aux_serialize_group(['tag' => 'UserBased', 'contents' => $guest]);

        // Go through all groups that have the privilege for `$action`
        foreach (($target->actions_2_grouplist[$action_ser]) ?? [] as $cur_group_ser) {
            // Check whether current group is user-based on `$user`
            if ($cur_group_ser == $group_user_ser) {
                return true;
            }

            // Anything that "guest" can do, any user can do
            // Check whether current group is user-based on "guest"
            if ($cur_group_ser == $group_guest_ser) {
                return true;
            }
        
            // Check whether `$user` is member of `$cur_group`
            if (in_array($user, $groups_2_userlist[$cur_group_ser] ?? [])) {
                return true;
            }
                
            // Anything that "guest" can do, any user can do
            // Check whether "guest" is member of `$cur_group`
            if (in_array($guest, $groups_2_userlist[$cur_group_ser] ?? [])) {
                return true;
            }
        }

        return false;
    }


    ///////////////////
    // For templates //
    ///////////////////

    // This function returns the url to a login page, with a redirection param
    // pointing to the current target / resource.
    // Any query strings e.g. `?solution=true` are preserved.
    function auth_get_login_url_with_redirect(): string {
        if ($_SERVER['REQUEST_URI'] != '/') {
            return url() . 'login/' . make_query_string(['redirect' => $_SERVER['REQUEST_URI']]);
        }
        else {
            return url() . 'login/';
        }
    }

    function auth_get_logout_url(): string {
        return url() . 'logout/';
    }


    /////////////
    // Helpers //
    /////////////

    function auth_aux_serialize_group($group) {
        return $group['tag'] . ' ' . $group['contents'];
    }

    function auth_aux_ser_action($action) {
        if ($action['tag'] == 'View') {
            return $action['tag'];
        }
        else {
            return $action['tag'] . ' ' . $action['contents'];
        }
    }

    function auth_aux_merge_actions_2_grouplists(array $a, array $b): array {
        $merged = $a;
    
        foreach ($b as $key => $values) {
            if (isset($merged[$key])) {
                // Merge and remove duplicates
                $merged[$key] = array_unique(array_merge($merged[$key], $values));
            } else {
                // Simply assign the values if the key is not already present in $merged
                $merged[$key] = $values;
            }
        }
    
        return $merged;
    }
?>