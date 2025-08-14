<?
    use Jumbojett\OpenIDConnectClient;

    ///////////////////////////////////////////////
    // Authentication & Session management: Init //
    ///////////////////////////////////////////////

    // Gets called during app initialization.
    // After return...
    // - Session is started
    // - `$GLOBALS['user']` is set, based on session etc.
    function auth_init() {
        // Start session
        session_start();

        // 1. Auth by cookie
        if (isset($_COOKIE['password'])) {
            // Assume the user or become "guest"
            $user = auth_aux_try_login($_COOKIE['password']);
            if ($user !== null) {
                $GLOBALS['user'] = $user;
            }
            else {
                $GLOBALS['user'] = auth_aux_make_guest_user();
            }
        }
        // 2. Auth by login session
        else {
            // Become "guest" if session is new or resume otherwise
            if (!isset($_SESSION['user'])) {
                $_SESSION['user'] = auth_aux_make_guest_user();
            }
            $GLOBALS['user'] = $_SESSION['user'];
        }
    }


    //////////////////////////////
    // Authentication: Handlers //
    //////////////////////////////

    // Preconditions:
    // - User is not currently logged in
    function auth_handle_login_and_exit() {
        $password_incorrect = false;
        $openid_fail        = null;  // Either `null` or of type [providerName => '...', message => 'the-message]

        // 1. If login for static user was tried
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
        // 2. If login using an OpenID provider was tried
        else if (isset($_GET['openid'])) {
            $providers = get_global_config('authentication', 'openIdProviders');
            
            // Find provider
            $providerName = $_GET['openid'];
            $server       = null;
            $clientID     = null;
            $clientSecret = null;
            foreach ($providers as $provider) {
                if ($provider['name'] == $providerName) {
                    $server       = $provider['server'];
                    $clientID     = $provider['clientId'];
                    $clientSecret = $provider['clientSecret'];
                    break;
                }
            }

            if ($server === null) {
                fail('OpenID provider not found: ' . $providerName);
            }

            // Save potential redirect URL in session var, because the OpenID
            // server will not return it via query string.
            if (isset($_GET['redirect'])) {
                $_SESSION['redirect'] = $_GET['redirect'];
            }

            // Use OpenID Connect client to authenticate
            $oidc = new OpenIDConnectClient($server, $clientID, $clientSecret);
            $oidc->addScope($provider['scopes']);

            // Any error from the OpenID server is thrown as an exception.
            try {
                // Run authentication
                $oidc->authenticate();

                // Pick up after re-connect
                $idAttribute = $oidc->requestUserInfo($provider['idAttribute']);

                $emailAttribute = isset($provider['emailAttribute']) ? 
                    $oidc->requestUserInfo($provider['emailAttribute']) : 
                    null;

                // Collect markers
                $markers = [];
                foreach ($provider['markerAttributes'] as $markerAttribute) {
                    $value = $oidc->requestUserInfo($markerAttribute['key']);

                    if ($markerAttribute['isList']) {
                        foreach ($value as $marker) {
                            if (isset($markerAttribute['subkey'])) {
                                $subkey = $markerAttribute['subkey'];
                                $markers[] = $marker->$subkey;
                            }
                            else {
                                $markers[] = $marker;
                            }
                        }
                    }
                    else {
                        if (isset($markerAttribute['subkey'])) {
                            $markers = [$value[$markerAttribute['subkey']]];
                        }
                        else {
                            $markers = [$value];
                        }
                    }
                }

                $user = auth_aux_make_openid_marked_user($providerName, $idAttribute, $emailAttribute, $markers);

                // Establish login session
                $_SESSION['user'] = $user;
                $GLOBALS['user']  = $user;
            }
            catch (Exception $e) {
                $openid_fail = [
                    'providerName' => $providerName,
                    'message'      => $e->getMessage(),
                ];
            }
        }

        // By now, potential login attempts were handled. Proceed by redirect
        // or by showing the login page.    
        if (auth_is_logged_in()) {
            auth_redirect_after_successful_login_and_exit();
        }
        else {
            // Show login page
            $logout_successful = isset($_SESSION['logout_successful']) && $_SESSION['logout_successful'];

            // Only show message once: Remove flag for future if set now.
            if ($logout_successful) {
                $_SESSION['logout_successful'] = false;
            }

            // Get OpenID provider names
            $openid_provider_names = [];
            foreach (get_global_config('authentication', 'openIdProviders') as $provider) {
                $openid_provider_names[] = $provider['name'];
            }

            process_login(core_load_obj('syslet_login'), $logout_successful, $password_incorrect, $openid_fail, $openid_provider_names);
            
            exit();
        }
    }

    // Precondition: User is eligible to logout.
    function auth_handle_logout_and_exit() {
        $_SESSION['logout_successful'] = true;
        $_SESSION['user']              = auth_aux_make_guest_user();
        $GLOBALS['user']               = $_SESSION['user'];
        
        redirect_and_exit(url() . 'login/');
    }


    // Redirect from login page to the page the user wanted to access.
    function auth_redirect_after_successful_login_and_exit() {
        // There are three options:
        // 1. Redirect is in the query string
        // 2. Redirect is in the session variable
        // 3. No redirect; go to home page
        if (isset($_GET['redirect'])) {
            redirect_and_exit($_GET['redirect']);
        }
        elseif (isset($_SESSION['redirect'])) {
            // Retrieve and remove from session var
            $redirect = $_SESSION['redirect'];
            unset($_SESSION['redirect']);

            redirect_and_exit($redirect);
        }
        else {
            redirect_and_exit(url());
        }
    }


    ////////////////////////////////////////////////////
    // Authentication: Functions available after init //
    ////////////////////////////////////////////////////

    function auth_is_logged_in(): bool {
        return $GLOBALS['user'] != auth_aux_make_guest_user();
    }

    function auth_get_user(): array {
        return $GLOBALS['user'];
    }

    function auth_is_openid_user(): bool {
        return $GLOBALS['user']['tag'] == 'OpenId';
    }

    function auth_is_static_user(): bool {
        return $GLOBALS['user']['tag'] == 'Static';
    }

    function auth_get_user_name(): string {
        if (auth_is_static_user()) {
            return $GLOBALS['user']['contents'];
        }
        else {
            return $GLOBALS['user']['contents']['id'];
        }
    }

    // Return the user or `null`
    function auth_aux_try_login($password): ?array {
        // Try all the passwords and assign user
        foreach (get_global_config('authentication', 'users2passwordHashes') as $user => $hash) {
            if (password_verify($password, $hash)) {
                return auth_aux_make_static_user($user);
            }
        }

        return null;
    }


    ////////////////////////////////////////////////////////
    // Session management: Functions available after init //
    ////////////////////////////////////////////////////////

    // The following three functions are used to manage the deduplication of
    // multiple POST requests. Any form that uses it must include a hidden
    // UUID that identifies the current request, obtained by
    // `auth_get_cur_postdedup_uuid()`. Then on receiving a POST request,
    // first check that the UUID is set at all, using
    // `auth_has_postdedup_uuid()`. If so, check whether under that UUID a POST
    // request has already been registered, via
    // `auth_register_check_postdedup_uuid()`.
    // If yes: Duplicate POST request detected.
    // If no: Register the UUID for future requests.

    $GLOBALS['request_uuid'] = null;

    function auth_get_cur_postdedup_uuid(): string {
        if ($GLOBALS['request_uuid'] === null) {
            $GLOBALS['request_uuid'] = bin2hex(random_bytes(16));
        }
        return $GLOBALS['request_uuid'];
    }

    function auth_has_postdedup_uuid(): bool {
        return isset($_POST['request_uuid']);
    }

    function auth_register_check_postdedup_uuid(): bool {
        if (!isset($_SESSION['postdedup_burned_uuids'])) {
            $_SESSION['postdedup_burned_uuids'] = [];
        }
        
        if (in_array($_POST['request_uuid'], $_SESSION['postdedup_burned_uuids'])) {
            // Duplicate request detected
            return false;
        }
        else {
            // Register UUID for future requests
            $_SESSION['postdedup_burned_uuids'][] = $_POST['request_uuid'];
            return true;
        }
    }


    /////////////////////////////////////////
    // Authorization: BEFORE preprocessing // 
    /////////////////////////////////////////

    function auth_is_user_privileged_to_preprocess(): bool {
        return auth_aux_is_static_user_privileged_without_rules($GLOBALS['user'], ['tag' => 'Preprocess', 'contents' => []]);
    }

    function auth_is_user_privileged_to_debug(): bool {
        return auth_aux_is_static_user_privileged_without_rules($GLOBALS['user'], ['tag' => 'Debug', 'contents' => []]);
    }

    // This function checks whether static `$user` has a `$privilege`.
    // It only relies on the `auth` config and not on any data structures
    // created during preprocessing.
    //
    // It is therefore inefficient and should only be used *before*
    // preprocessing has taken place.
    //
    // Furthermore, only `actorsXprivileges` is considered, not `actorsXtargetRules`.
    //
    // $user:      array in the `config.json` format (must be static)
    // $privilege: array in the `config.json` format,
    //             e.g. ['tag' => 'Preprocess', 'contents' => []]
    function auth_aux_is_static_user_privileged_without_rules(array $user, array $privilege): bool {

        // Root user has any privilege
        if ($user == auth_aux_make_root_user()) {
            return true;
        }

        // Go through all actors that have the `$privilege`
        foreach (get_global_config('authorization', 'actorsXprivileges') as $a_x_p) {
            if ($a_x_p['privilege'] != $privilege) { continue; } // Filter

            $cur_actor = $a_x_p['actor'];

            // Is current actor the user
            // 1. `$user` or
            // 2. "guest" (anything guest may do, anyone may do)?
            if ($cur_actor['tag'] == 'User' &&
                in_array($cur_actor['contents'], [$user, auth_aux_make_guest_user()])) {
                return true;
            }

            // Is current actor a group that contains `$user`?
            if ($cur_actor['tag'] == 'Group') {
                $cur_group = $cur_actor['contents'];
                foreach (get_global_config('authorization', 'usersXgroups') as $u_x_g) {
                    if ($u_x_g['group'] != $cur_group) { continue; } // Join condition

                    // Check whether `$user` is member of `$cur_group`
                    if ($u_x_g['user'] == $user) {
                        return true;
                    }

                    // Anything that "guest" can do, any user can do
                    // Check whether "guest" is member of `$cur_group`
                    if ($u_x_g['user'] == auth_aux_make_guest_user()) {
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
    // Return: dict<string, list<user_ser>>
    function auth_generate_groups_2_userlist_ser(): array {
        $groups_2_userlist_ser = [];

        foreach (get_global_config('authorization', 'usersXgroups') as $u_x_g) {       
            aux_array_set_add($groups_2_userlist_ser, $u_x_g['group'], auth_aux_serialize_user($u_x_g['user']));
        }

        return $groups_2_userlist_ser;
    }

    // This function builds up an assoc array with the OpenID provider names as
    // keys and the corresponding group names as values.
    //
    // The result of this function should be cached during preprocessing.
    //
    // Return: dict<string, list<openid_marker>>
    function auth_generate_groups_2_openidmarkerlist(): array {
        $groups_2_openidmarkerlist = [];

        foreach (get_global_config('authorization', 'openIdMarkersXgroups') as $m_x_g) {
            aux_array_set_add($groups_2_openidmarkerlist, $m_x_g['group'], $m_x_g['marker']);
        }

        return $groups_2_openidmarkerlist;
    }

    // This function builds up an assoc array with the serialized actions as
    // keys that point to the list of actors that are granted access to these
    // actions.
    //
    // Two sources are used to build up the privileges; 1. gets overridden by 2.
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

            foreach (get_global_config('authorization', 'actorsXtargetRules') as $a_x_r) {
                $cur_rule = $a_x_r['rule'];

                if ($cur_rule['contents']['targetIds'] == $cur_target_ids) {
                    $cur_action_ser = auth_aux_serialize_action($cur_rule['contents']['action']);
                    $cur_actor_ser  = auth_aux_serialize_actor($a_x_r['actor']);

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
        foreach (get_global_config('authorization', 'actorsXprivileges') as $a_x_p) {
            $cur_privilege = $a_x_p['privilege'];

            if ($cur_privilege['tag'] == 'Target' &&
                $cur_privilege['contents']['targetIds'] == $target_ids) {

                $cur_action_ser = auth_aux_serialize_action($cur_privilege['contents']['action']);
                $cur_actor_ser  = auth_aux_serialize_actor($a_x_p['actor']);

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
        $view_action_ser = auth_aux_serialize_action(['tag' => 'View', 'contents' => []]);
        $privileged_actors = $target->actions_ser_2_actorlist_ser[$view_action_ser] ?? [];
        return auth_is_cur_user_among_authorized_actors($privileged_actors);
    }

    function auth_is_cur_user_privileged_for_custom_action(Target $target, string $custom_action): bool {
        $action_ser = auth_aux_serialize_action(['tag' => 'Custom', 'contents' => $custom_action]);
        $privileged_actors = $target->actions_ser_2_actorlist_ser[$action_ser] ?? [];
        return auth_is_cur_user_among_authorized_actors($privileged_actors);
    }

    // $authorized_groups: list<string>
    // $authorized_users:  list<user>
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
            user:                            auth_get_user(),
            guestUser:                       auth_aux_make_guest_user(),
            rootUser:                        auth_aux_make_root_user(),
            groups_2_userlist_ser:           core_load_obj('groups_2_userlist_ser'),
            groups_2_openidmarkerlist:       core_load_obj('groups_2_openidmarkerlist'),
            authorized_actors_ser:           $authorized_actors_ser
        );
    }

    // This function checks whether the marked user `$user` is part of
    // `$authorized_actorlist`.
    //
    // It is also authorized if:
    // - `$staticGuestUser` is authorized
    // - `$user` is `$staticRootUser`
    // It relies on data structures created during preprocessing.
    //
    // It can therefore only be used *after* preprocessing has taken place.
    //
    // To make things easier, the static users are users instead of user ids.
    //
    // $user:                            marked_user
    // $staticGuestUser:                 user
    // $staticRootUser:                  user
    // $groups_2_userlist_ser:           dict<string, list<user_ser>>
    // $groups_2_openidmarkerlist:       dict<string, list<openid_marker>>
    // $authorized_actors_ser:           list<actor_ser>
    function auth_aux_is_user_authorized(
        array $user,
        array $guestUser,
        array $rootUser,
        array $groups_2_userlist_ser,
        array $groups_2_openidmarkerlist,
        array $authorized_actors_ser
    ): bool {
        // Root user is always authorized
        if ($user == $rootUser) {
            return true;
        }

        // Go through all authorized actors
        foreach ($authorized_actors_ser as $cur_actor_ser) {
            $cur_actor = auth_aux_deserialize_actor($cur_actor_ser);

            // If current actor is a user...
            if ($cur_actor['tag'] == 'User') {
                $cur_user = $cur_actor['contents'];

                // Check whether current actor is unmarked `$user`
                if ($cur_user == auth_aux_unmark_user($user)) {
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
                $cur_group = $cur_actor['contents'];

                // Check whether unmarked `$user` is member of `$cur_group`
                $user_ser = auth_aux_serialize_user(auth_aux_unmark_user($user));
                if (in_array($user_ser, $groups_2_userlist_ser[$cur_group] ?? [])) {
                    return true;
                }

                // If `$user` is provided by OpenID, check whether there is a
                // marker that is attached to `$cur_group`
                if ($user['tag'] == 'OpenId' &&
                    isset($groups_2_openidmarkerlist[$cur_group]))
                {
                    foreach ($groups_2_openidmarkerlist[$cur_group] as $marker) {
                        if ($marker['providerName'] == $user['contents']['providerName'])
                        {
                            // If only the OpenID is used as marker
                            if (!isset($marker['marker'])) {
                                return true;
                            }
                            // If the marker is used, check on it.
                            else if (in_array($marker['marker'], $user['contents']['markers'])) {
                                return true;
                            }
                        }
                    }
                }

                // Anything that "guest" can do, any user can do
                // Check whether "guest" is member of `$cur_group`
                $guestUser_ser = auth_aux_serialize_user($guestUser);
                if (in_array($guestUser_ser, $groups_2_userlist_ser[$cur_group] ?? [])) {
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

    function auth_get_login_url_for_openid(string $provider_name): string {
        return url() . 'login/openid/' . $provider_name;
    }

    function auth_get_logout_url(): string {
        return url() . 'logout/';
    }


    ///////////////////////
    // Types and helpers //
    ///////////////////////

    // Generally, users, actors, actions etc. are represented as defined in the
    // Dhall types, but then as assoc arrays.
    //
    // To give an example, a user is represented in the following form:
    //
    // [
    //     'tag'      => 'Static',
    //     'contents' => 'username'
    // ]
    //
    // or
    //
    // [
    //     'tag'      => 'OpenId',
    //     'contents' => [
    //         'providerName' => 'the-provider',
    //         'id'           => 'the-userid',
    //     ]
    // ]
    //
    // Serialization is defined as functions mapping to unique strings.
    //
    // A marked user is a user together with markers.
    // This only applies to OpenID users (typically groups).
    //
    // [
    //     'tag'      => 'Static',
    //     'contents' => 'username'
    // ]
    //
    // or
    //
    // [
    //     'tag'      => 'OpenId',
    //     'contents' => [
    //         'providerName' => 'the-provider',
    //         'id'           => 'the-userid',
    //         'markers'      => ['marker1', 'marker2'] // OpenID markers
    //     ]
    // ]


    // Remove the markers
    function auth_aux_unmark_user(array $marked_user): array {
        if ($marked_user['tag'] == 'Static') {
            return $marked_user;
        }
        else {
            return [
                'tag'      => 'OpenId',
                'contents' => [
                    'providerName' => $marked_user['contents']['providerName'],
                    'id'           => $marked_user['contents']['id']
                ]
            ];
        }
    }

    function auth_aux_serialize_user(array $user) {
        if ($user['tag'] == 'Static') {
            return 'Static ' . $user['contents'];
        }
        else {
            return 'OpenId ' . $user['contents']['providerName'] . ' ' . $user['contents']['id'];
        }
    }

    function auth_aux_deserialize_user(string $user_ser) {
        if (substr($user_ser, 0, 6) == 'Static') {
            return [
                'tag'      => 'Static',
                'contents' => substr($user_ser, 7)
            ];
        }
        else {
            $parts = explode(' ', $user_ser, 3);
            return [
                'tag'      => 'OpenId',
                'contents' => [
                    'providerName' => $parts[1],
                    'id'           => $parts[2]
                ]
            ];
        }
    }

    function auth_aux_serialize_action(array $action) {
        if ($action['tag'] == 'View') {
            return 'View';
        }
        else {
            return 'Custom ' . $action['contents'];
        }
    }

    function auth_aux_serialize_actor(array $actor) {
        if ($actor['tag'] == 'User') {
            return 'User ' . auth_aux_serialize_user($actor['contents']);
        }
        else {
            return 'Group ' . $actor['contents'];
        }
    }

    function auth_aux_deserialize_actor(string $actor_ser) {
        if (substr($actor_ser, 0, 4) == 'User') {
            return [
                'tag'      => 'User',
                'contents' => auth_aux_deserialize_user(substr($actor_ser, 5))
            ];
        }
        else {
            return [
                'tag'      => 'Group',
                'contents' => substr($actor_ser, 6)
            ];
        }
    }

    function auth_aux_is_ser_actor_user($actor_ser) {
        return substr($actor_ser, 0, 4) == 'User';
    }

    function auth_aux_is_ser_actor_group($actor_ser) {
        return substr($actor_ser, 0, 5) == 'Group';
    }


    /////////////////////
    // Further helpers //
    /////////////////////

    function auth_aux_make_static_user($name){
        return [
            'tag'      => 'Static',
            'contents' => $name
        ];
    }

    function auth_aux_make_guest_user() {
        return auth_aux_make_static_user(get_global_config('authorization', 'staticGuestUser'));
    }

    function auth_aux_make_root_user() {
        return auth_aux_make_static_user(get_global_config('authorization', 'staticRootUser'));
    }

    function auth_aux_make_openid_marked_user(string $providerName, string $id, ?string $email, array $markers): array {
        if ($email === null) {
            return [
                'tag'      => 'OpenId',
                'contents' => [
                    'providerName' => $providerName,
                    'id'           => $id,
                    'markers'      => $markers
                ]
            ];
        }
        else {
            return [
                'tag'      => 'OpenId',
                'contents' => [
                    'providerName' => $providerName,
                    'id'           => $id,
                    'email'        => $email,
                    'markers'      => $markers
                ]
            ];
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