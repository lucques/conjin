<?
    //////////////////////////////
    // Session management: Init //
    //////////////////////////////

    // Gets called during app initialization.
    // After return, `$GLOBALS['user']` is set, based on session etc.
    function auth_init() {
        // Auth by cookie
        if (isset($_COOKIE['password'])) {

            // Assume the user or become "guest"
            $user = auth_aux_try_login($_COOKIE['password']);

            if ($user !== null) {
                $GLOBALS['user'] = $user;
            }
            else {
                $GLOBALS['user'] = get_global_config('authorization', 'guestUser');
            }
        }
        // Auth by login session
        else {
            session_start();

            // Resume user or become "guest"
            if (isset($_SESSION['user'])) {
                $GLOBALS['user'] = $_SESSION['user'];
            }
            else {
                $_SESSION['user'] = get_global_config('authorization', 'guestUser'); // Save in session
                $GLOBALS['user']  = get_global_config('authorization', 'guestUser');
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

            process_login(core_load_obj('syslet_login'), $logout_successful, $password_incorrect);
            
            exit();
        }
    }

    // Precondition: User is eligible to logout.
    function auth_handle_logout_and_exit() {
        $_SESSION['logout_successful'] = true;
        $_SESSION['user']              = get_global_config('authorization', 'guestUser');
        $GLOBALS['user']               = get_global_config('authorization', 'guestUser');
        
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

    function auth_is_logged_in() {
        return $GLOBALS['user'] != get_global_config('authorization', 'guestUser');
    }

    function auth_get_user() {
        return $GLOBALS['user'];
    }

    // Return the user or `null`
    function auth_aux_try_login($password): ?string {
        // Try all the passwords and assign user
        foreach (get_global_config('authentication', 'users2passwordHashes') as $user => $hash) {
            if (password_verify($password, $hash)) {
                return $user;
            }
        }

        return null;
    }


    /////////////////////////////////////////
    // Authorization: BEFORE preprocessing // 
    /////////////////////////////////////////

    const AUTH_VIEW_ACTION = ['tag' => 'View', 'contents' => []];

    function auth_is_user_privileged_to_preprocess(): bool {
        return auth_aux_is_user_privileged_without_rules($GLOBALS['user'], ['tag' => 'Preprocess', 'contents' => []]);
    }

    function auth_is_user_privileged_to_debug(): bool {
        return auth_aux_is_user_privileged_without_rules($GLOBALS['user'], ['tag' => 'Debug', 'contents' => []]);
    }

    // This function checks whether `$user` has a `$privilege`.
    // It only relies on the `auth` config and not on any data structures
    // created during preprocessing.
    //
    // It is therefore inefficient and should only be used *before*
    // preprocessing has taken place.
    //
    // Furthermore, only `actors2privileges` is considered, not `actors2targetRules`.
    //
    // $user:      string
    // $privilege: array in the `config.json` format,
    //             e.g. ['tag' => 'Preprocess', 'contents' => []]
    function auth_aux_is_user_privileged_without_rules(string $user, array $privilege): bool {

        // Root user has any privilege
        if ($user == get_global_config('authorization', 'rootUser')) {
            return true;
        }

        // Go through all actors that have the `$privilege`
        foreach (get_global_config('authorization', 'actors2privileges') as $a2p) {
            if ($a2p['privilege'] != $privilege) { continue; } // Join condition

            $cur_actor = $a2p['actor'];

            // Is current actor the user
            // 1. `$user` or
            // 2. "guest" (anything guest may do, anyone may do)?
            if ($cur_actor['tag'] == 'User' &&
                in_array($cur_actor['contents'], [$user, get_global_config('authorization', 'guestUser')])) {
                return true;
            }

            // Is current actor a group that contains `$user`?
            if ($cur_actor['tag'] == 'Group') {
                $cur_group = $cur_actor['contents'];
                foreach (get_global_config('authorization', 'users2groups') as $u2g) {
                    if ($u2g['group'] != $cur_group) { continue; } // Join condition

                    // Check whether `$user` is member of `$cur_group`
                    if ($u2g['user'] == $user) {
                        return true;
                    }

                    // Anything that "guest" can do, any user can do
                    // Check whether "guest" is member of `$cur_group`
                    if ($u2g['user'] == get_global_config('authorization', 'guestUser')) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    // This function builds up an assoc array with the groups as keys that point
    // to the list of users assigned to each group.
    //
    // The result of this function should be cached during preprocessing.
    //
    // Return: dict<string, list<string>>
    function auth_generate_groups_2_userlist(): array {
        $groups_2_userlist = [];

        foreach (get_global_config('authorization', 'users2groups') as $u2g) {       
            aux_array_set_add($groups_2_userlist, $u2g['group'], $u2g['user']);
        }

        return $groups_2_userlist;
    }

    // This function builds up an assoc array with the serialized actions as
    // keys that point to the list of actors that are granted access to these
    // actions.
    //
    // Two sources are used to build up the privileges; 1. gets overriden by 2.
    //
    // 1. Hierarchical rules ("allow" and "deny" is inherited down the target tree)
    // 2. Single privileges
    //
    // The result of this function should be cached during preprocessing.
    //
    // Return: dict<action_ser, list<actor_ser>>
    function auth_generate_actions_ser_2_actorlist_ser_for_target(array $target_ids): array {
        $actions_ser_2_actorlist_ser = [];

        // 1. Rule-based privileges: Go through all parent targets
        for ($i = 0; $i < count($target_ids)+1; $i++) {
            $cur_target_ids = array_slice($target_ids, 0, $i);

            foreach (get_global_config('authorization', 'actors2targetRules') as $a2r) {
                $cur_rule = $a2r['rule'];

                if ($cur_rule['contents']['targetIds'] == $cur_target_ids) {
                    $cur_action_ser = auth_aux_serialize_action($cur_rule['contents']['action']);
                    $cur_actor_ser  = auth_aux_serialize_actor($a2r['actor']);

                    if ($cur_rule['tag'] == 'Allow') {
                        // Add
                        aux_array_set_add($actions_ser_2_actorlist_ser, $cur_action_ser, $cur_actor_ser);
                    }
                    else {
                        // Remove
                        aux_array_set_remove($actions_ser_2_actorlist_ser, $cur_action_ser, $cur_actor_ser);
                    }
                }
            }
        }

        // 2. Single privileges
        foreach (get_global_config('authorization', 'actors2privileges') as $a2p) {
            $cur_privilege = $a2p['privilege'];

            if ($cur_privilege['tag'] == 'Target' &&
                $cur_privilege['contents']['targetIds'] == $target_ids) {

                $cur_action_ser = auth_aux_serialize_action($cur_privilege['contents']['action']);
                $cur_actor_ser  = auth_aux_serialize_actor($a2p['actor']);

                // Add
                aux_array_set_add($actions_ser_2_actorlist_ser, $cur_action_ser, $cur_actor_ser);
            }
        }

        return $actions_ser_2_actorlist_ser;
    }


    ////////////////////////////////////////
    // Authorization: AFTER preprocessing // 
    ////////////////////////////////////////

    function auth_is_cur_user_privileged_for_view(Target $target): bool {
        $privileged_actors = $target->actions_ser_2_actorlist_ser[auth_aux_serialize_action(AUTH_VIEW_ACTION)];
        return auth_is_cur_user_among_authorized_actors($privileged_actors);
    }

    function auth_is_cur_user_privileged_for_custom_action(Target $target, string $custom_action): bool {
        $action = ['tag' => 'Custom', 'contents' => $custom_action];
        $privileged_actors = $target->actions_ser_2_actorlist_ser[auth_aux_serialize_action($action)];
        return auth_is_cur_user_among_authorized_actors($privileged_actors);
    }

    // $authorized_groups: list<string>
    // $authorized_users:  list<string>
    function auth_is_cur_user_among_authorized_groups_users(array $groups, array $users): bool {
        $authorized_actors_ser = array_merge(
            array_map(function ($group) {
                return auth_aux_serialize_actor([
                    'tag'      => 'Group',
                    'contents' => $group
                ]);
            }, $groups),
            array_map(function ($user) {
                return auth_aux_serialize_actor([
                    'tag'      => 'User',
                    'contents' => $user
                ]);
            }, $users)
        );

        return auth_is_cur_user_among_authorized_actors($authorized_actors_ser);
    }

    // $authorized_actors_ser: list<actor_ser>
    function auth_is_cur_user_among_authorized_actors(array $authorized_actors_ser): bool {
        return auth_aux_is_user_authorized(
            user:                  auth_get_user(),
            guestUser:             get_global_config('authorization', 'guestUser'),
            rootUser:              get_global_config('authorization', 'rootUser'),
            groups_2_userlist:     core_load_obj('groups_2_userlist'),
            authorized_actors_ser: $authorized_actors_ser
        );
    }

    // This function checks whether `$user` is part of `$authorized_actorlist`.
    // It is also authorized if:
    // - `$guestUser` is authorized
    // - `$user` is `$rootUser`
    // It relies on data structures created during preprocessing.
    //
    // It can therefore only be used *after* preprocessing has taken place.
    //
    // $user:                  string
    // $guestUser:             string
    // $rootUser:              string
    // $groups_2_userlist:     dict<string, list<string>>
    // $authorized_actors_ser: list<actor_ser>
    function auth_aux_is_user_authorized(string $user, string $guestUser, string $rootUser, array $groups_2_userlist, array $authorized_actors_ser): bool {
        // Root user is always authorized
        if ($user == $rootUser) {
            return true;
        }

        // Go through all authorized actors
        foreach ($authorized_actors_ser as $cur_actor_ser) {
            // If current actor is a user...
            if (auth_aux_is_ser_actor_user($cur_actor_ser)) {
                $cur_user = auth_aux_ser_actor_to_user($cur_actor_ser);

                // Check whether current actor is `$user`
                if ($cur_user == $user) {
                    return true;
                }

                // Anything that "guest" can do, any user can do
                // Check whether current actor is `$guestUser`
                if ($cur_user == $guestUser) {
                    return true;
                }
            }
            // If current actor is a group...
            else {
                $cur_group = auth_aux_ser_actor_to_group($cur_actor_ser);

                // Check whether `$user` is member of `$cur_group`
                if (in_array($user, $groups_2_userlist[$cur_group] ?? [])) {
                    return true;
                }
                    
                // Anything that "guest" can do, any user can do
                // Check whether "guest" is member of `$cur_group`
                if (in_array($guestUser, $groups_2_userlist[$cur_group] ?? [])) {
                    return true;
                }
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

    function auth_aux_serialize_actor($group) {
        return $group['tag'] . ' ' . $group['contents'];
    }

    function auth_aux_is_ser_actor_user($actor_ser) {
        return substr($actor_ser, 0, 4) == 'User';
    }

    function auth_aux_is_ser_actor_group($actor_ser) {
        return substr($actor_ser, 0, 5) == 'Group';
    }

    function auth_aux_ser_actor_to_user($actor_ser) {
        return substr($actor_ser, 5);
    }

    function auth_aux_ser_actor_to_group($actor_ser) {
        return substr($actor_ser, 6);
    }

    function auth_aux_serialize_action($action) {
        if ($action['tag'] == 'View') {
            return $action['tag'];
        }
        else {
            return $action['tag'] . ' ' . $action['contents'];
        }
    }

    function auth_aux_merge_actions_ser_2_actorlist_sers(array $a, array $b): array {
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