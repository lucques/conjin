<?
    use Jumbojett\OpenIDConnectClient;

    interface ResolvedUser {
        public function get_user_identity(): array;
    }

    final readonly class OpenIdMarker {
        public function __construct(
            public string $attributeName,
            public string $markerName
        ) {}
    }

    final readonly class StaticResolvedUser implements ResolvedUser {
        public function __construct(public string $name) {}

        public function get_user_identity(): array {
            return [
                'tag'      => 'Static',
                'contents' => $this->name
            ];
        }
    }

    final readonly class OpenIdResolvedUser implements ResolvedUser {
        /** @param list<OpenIdMarker> $markers */
        public function __construct(
            public string $providerName,
            public string $id,
            public ?string $email,
            public array $markers
        ) {}

        public function get_user_identity(): array {
            return [
                'tag'      => 'OpenId',
                'contents' => [
                    'providerName' => $this->providerName,
                    'id'           => $this->id
                ]
            ];
        }
    }

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

        // Temporary migration from array-based session users to ResolvedUser
        // objects. Clear the complete old session and issue a fresh session ID
        // instead of trying to translate potentially stale session state.
        // TODO: Remove after 2026-08-15.
        if (isset($_SESSION['user']) && !($_SESSION['user'] instanceof ResolvedUser)) {
            session_unset();
            session_regenerate_id(delete_old_session: true);
        }

        // 1. Auth as trusted local deployment user
        // Local deployment jobs reach Apache through a dedicated, unexposed
        // port. Trust their user cookie only when both Apache and the deployment
        // explicitly enable internal authentication.
        if (auth_aux_is_trusted_internal_request()) {
            $user_name = $_COOKIE['user'] ?? null;
            $static_users = get_global_config('authentication', 'users2passwordHashes');

            if (is_string($user_name) && array_key_exists($user_name, $static_users)) {
                $GLOBALS['user'] = new StaticResolvedUser($user_name);
            }
            else {
                $GLOBALS['user'] = auth_aux_make_guest_resolved_user();
            }
        }
        // 2. Auth by cookie
        elseif (isset($_COOKIE['password'])) {
            // Assume the user or become "guest"
            $user = auth_aux_try_login($_COOKIE['password']);
            if ($user !== null) {
                $GLOBALS['user'] = $user;
            }
            else {
                $GLOBALS['user'] = auth_aux_make_guest_resolved_user();
            }
        }
        // 3. Auth by login session
        else {
            // Become "guest" if session is new or resume otherwise
            if (!isset($_SESSION['user'])) {
                $_SESSION['user'] = auth_aux_make_guest_resolved_user();
            }
            $GLOBALS['user'] = $_SESSION['user'];
        }
    }


    //////////////////////////////
    // Authentication: Handlers //
    //////////////////////////////

    // Preconditions:
    // - User is not currently logged in
    function auth_handle_login_and_exit(LoginProfile $login_profile) {
        $password_incorrect = false;
        $openid_fail        = $_SESSION['oidc_fail'] ?? null;
        unset($_SESSION['oidc_fail']);

        // If login for static user was tried
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

        // By now, a potential static login attempt was handled. Proceed by
        // redirecting or showing the login page.
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

            process_login($login_profile, $logout_successful, $password_incorrect, $openid_fail, $openid_provider_names);
            
            exit();
        }
    }

    // Preconditions:
    // - User is not currently logged in
    // - Login profile exists
    function auth_handle_oidc_start_and_exit(string $provider_name, LoginProfile $login_profile): never {
        $provider = auth_aux_get_openid_provider($provider_name);
        if ($provider === null) {
            process_not_found_and_exit();
        }

        if (isset($_GET['redirect'])) {
            if (!is_string($_GET['redirect'])) {
                send_response_and_exit(400);
            }
            $_SESSION['redirect'] = $_GET['redirect'];
        }

        $_SESSION['oidc_provider_name'] = $provider_name;
        $_SESSION['oidc_login_profile_id'] = $login_profile->id;

        try {
            auth_aux_make_oidc_client($provider)->authenticate();
        }
        catch (Exception $e) {
            auth_aux_handle_oidc_failure_and_exit($provider_name, $login_profile, $e);
        }

        fail('OpenID Connect authorization did not redirect');
    }

    // Preconditions:
    // - User is not currently logged in
    function auth_handle_oidc_callback_and_exit(string $provider_name, LoginProfile $login_profile): never {
        if (!isset($_GET['code']) && !isset($_GET['error'])) {
            send_response_and_exit(400);
        }

        $provider = auth_aux_get_openid_provider($provider_name);
        if ($provider === null) {
            process_not_found_and_exit();
        }

        try {
            if (($_SESSION['oidc_provider_name'] ?? null) !== $provider_name) {
                throw new Exception('No matching OpenID Connect login attempt was found.');
            }

            $oidc = auth_aux_make_oidc_client($provider);
            $oidc->authenticate();
            $user = auth_aux_resolve_openid_user($provider_name, $provider, $oidc);

            $_SESSION['user'] = $user;
            $GLOBALS['user']  = $user;
        }
        catch (Exception $e) {
            auth_aux_handle_oidc_failure_and_exit($provider_name, $login_profile, $e);
        }

        auth_redirect_after_successful_login_and_exit();
    }

    function auth_aux_get_openid_provider(string $provider_name): ?array {
        foreach (get_global_config('authentication', 'openIdProviders') as $provider) {
            if ($provider['name'] === $provider_name) {
                return $provider;
            }
        }

        return null;
    }

    function auth_aux_make_oidc_client(array $provider): OpenIDConnectClient {
        $oidc = new OpenIDConnectClient(
            $provider['server'],
            $provider['clientId'],
            $provider['clientSecret']
        );
        $oidc->setRedirectURL(auth_get_oidc_callback_url($provider['name'], full: true));
        $oidc->addScope($provider['scopes']);

        return $oidc;
    }

    function auth_aux_resolve_openid_user(
        string $provider_name,
        array $provider,
        OpenIDConnectClient $oidc
    ): OpenIdResolvedUser {
        $id_attribute = $oidc->requestUserInfo($provider['idAttribute']);
        $email_attribute = isset($provider['emailAttribute'])
            ? $oidc->requestUserInfo($provider['emailAttribute'])
            : null;

        $markers = [];
        foreach ($provider['markerAttributes'] as $marker_attribute) {
            $value = $oidc->requestUserInfo($marker_attribute['attributeName']);

            if ($marker_attribute['isList']) {
                foreach ($value as $marker) {
                    if (isset($marker_attribute['subkey'])) {
                        $subkey = $marker_attribute['subkey'];
                        $markers[] = new OpenIdMarker(
                            $marker_attribute['attributeName'],
                            $marker->$subkey
                        );
                    }
                    else {
                        $markers[] = new OpenIdMarker(
                            $marker_attribute['attributeName'],
                            $marker
                        );
                    }
                }
            }
            elseif (isset($marker_attribute['subkey'])) {
                $markers[] = new OpenIdMarker(
                    $marker_attribute['attributeName'],
                    $value[$marker_attribute['subkey']]
                );
            }
            else {
                $markers[] = new OpenIdMarker(
                    $marker_attribute['attributeName'],
                    $value
                );
            }
        }

        return new OpenIdResolvedUser($provider_name, $id_attribute, $email_attribute, $markers);
    }

    function auth_aux_handle_oidc_failure_and_exit(
        string $provider_name,
        LoginProfile $login_profile,
        Exception $exception
    ): never {
        $_SESSION['oidc_fail'] = [
            'providerName' => $provider_name,
            'message'      => $exception->getMessage(),
        ];
        unset($_SESSION['oidc_provider_name']);
        unset($_SESSION['oidc_login_profile_id']);

        $queries = isset($_SESSION['redirect']) && is_string($_SESSION['redirect'])
            ? ['redirect' => $_SESSION['redirect']]
            : [];
        redirect_see_other_and_exit(auth_get_login_url($login_profile->id) . make_query_string($queries));
    }

    // Precondition: User is eligible to logout.
    function auth_handle_logout_and_exit() {
        $_SESSION['logout_successful'] = true;
        $_SESSION['user']              = auth_aux_make_guest_resolved_user();
        $GLOBALS['user']               = $_SESSION['user'];
        
        redirect_see_other_and_exit(url() . 'login/');
    }


    // Redirect from login page to the page the user wanted to access.
    function auth_redirect_after_successful_login_and_exit() {
        unset($_SESSION['oidc_provider_name']);
        unset($_SESSION['oidc_login_profile_id']);

        // There are three options:
        // 1. Redirect is in the query string
        // 2. Redirect is in the session variable
        // 3. No redirect; go to home page
        if (isset($_GET['redirect'])) {
            redirect_see_other_and_exit($_GET['redirect']);
        }
        elseif (isset($_SESSION['redirect'])) {
            // Retrieve and remove from session var
            $redirect = $_SESSION['redirect'];
            unset($_SESSION['redirect']);

            redirect_see_other_and_exit($redirect);
        }
        else {
            redirect_see_other_and_exit(url());
        }
    }

    function auth_handle_unauthorized_and_exit(?string $login_profile_id = null) {
        if (!auth_is_logged_in()) {
            redirect_temporarily_and_exit(auth_get_login_url_with_redirect($login_profile_id));
        }
        else {
            process_unauthorized_and_exit(); // Unauthorized
        }
    }


    ////////////////////////////////////////////////////
    // Authentication: Functions available after init //
    ////////////////////////////////////////////////////

    function auth_is_logged_in(): bool {
        return auth_get_user()->get_user_identity() != auth_aux_make_guest_user_identity();
    }

    function auth_get_user(): ResolvedUser {
        return $GLOBALS['user'];
    }

    function auth_is_openid_user(): bool {
        return auth_get_user() instanceof OpenIdResolvedUser;
    }

    function auth_is_static_user(): bool {
        return auth_get_user() instanceof StaticResolvedUser;
    }

    function auth_get_user_name(): string {
        $user = auth_get_user();
        if ($user instanceof StaticResolvedUser) {
            return $user->name;
        }
        else if ($user instanceof OpenIdResolvedUser) {
            return $user->id;
        }

        throw new LogicException('Unknown resolved user type: ' . get_debug_type($user));
    }

    function auth_get_user_email(): ?string {
        $user = auth_get_user();
        return $user instanceof OpenIdResolvedUser ? $user->email : null;
    }

    // Return the user or `null`
    function auth_aux_try_login($password): ?ResolvedUser {
        // Try all the passwords and assign user
        foreach (get_global_config('authentication', 'users2passwordHashes') as $user => $hash) {
            if (password_verify($password, $hash)) {
                return new StaticResolvedUser($user);
            }
        }

        return null;
    }

    function auth_aux_is_trusted_internal_request(): bool {
        return getenv('CONJIN_ENABLE_INTERNAL_AUTH') === '1'
            && getenv('CONJIN_INTERNAL_REQUEST') === '1';
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
            // Limit the size of the burned UUIDs list to avoid memory issues
            if (count($_SESSION['postdedup_burned_uuids']) > 20) {
                $_SESSION['postdedup_burned_uuids'] = array_slice($_SESSION['postdedup_burned_uuids'], -20);
            }
            return true;
        }
    }


    /////////////////////////////////////////
    // Authorization: BEFORE preprocessing // 
    /////////////////////////////////////////

    function auth_is_user_privileged_to_preprocess(): bool {
        return auth_aux_is_static_user_privileged_without_rules($GLOBALS['user']->get_user_identity(), ['tag' => 'Preprocess', 'contents' => []]);
    }

    function auth_is_user_privileged_to_debug(): bool {
        return auth_aux_is_static_user_privileged_without_rules($GLOBALS['user']->get_user_identity(), ['tag' => 'Debug', 'contents' => []]);
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
        if ($user == auth_aux_make_root_user_identity()) {
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
                in_array($cur_actor['contents'], [$user, auth_aux_make_guest_user_identity()])) {
                return true;
            }

            // Is current actor a group that contains `$user`?
            if ($cur_actor['tag'] == 'Group') {
                $cur_group = $cur_actor['contents'];
                foreach (get_global_config('authentication', 'usersXgroups') as $u_x_g) {
                    if ($u_x_g['group'] != $cur_group) { continue; } // Join condition

                    // Check whether `$user` is member of `$cur_group`
                    if ($u_x_g['user'] == $user) {
                        return true;
                    }

                    // Anything that "guest" can do, any user can do
                    // Check whether "guest" is member of `$cur_group`
                    if ($u_x_g['user'] == auth_aux_make_guest_user_identity()) {
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

        foreach (get_global_config('authentication', 'usersXgroups') as $u_x_g) {
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

        foreach (get_global_config('authentication', 'openIdProviders') as $provider) {
            foreach ($provider['markersXgroups'] as $m_x_g) {
                $marker_enriched = [
                    'providerName'  => $provider['name'],
                    'attributeName'  => $m_x_g['marker']['attributeName'],
                    'markerName'    => $m_x_g['marker']['markerName']
                ];
                aux_array_set_add($groups_2_openidmarkerlist, $m_x_g['group'], $marker_enriched);
            }
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

    function auth_is_cur_user_privileged_for_custom_privilege(string $custom_privilege): bool {
        $privileged_actors_ser = [];

        foreach (get_global_config('authorization', 'actorsXprivileges') as $a_x_p) {
            $privilege = $a_x_p['privilege'];

            if ($privilege['tag'] !== 'Custom' ||
                $privilege['contents'] !== $custom_privilege) {
                continue;
            }

            $actor_ser = auth_aux_serialize_actor($a_x_p['actor']);
            if (!in_array($actor_ser, $privileged_actors_ser, true)) {
                $privileged_actors_ser[] = $actor_ser;
            }
        }

        return auth_is_cur_user_among_authorized_actors($privileged_actors_ser);
    }

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

    // Checks actual group membership from explicit usersXgroups assignments,
    // matching OpenID markersXgroups assignments, and groups containing the
    // guest user. Unlike authorization checks, this does not treat the root
    // user as a member of every group merely because root has every privilege.
    function auth_is_cur_user_in_group(string $group_name): bool {
        $user = auth_get_user();
        $groups_2_userlist_ser = core_load_obj('groups_2_userlist_ser');
        $groups_2_openidmarkerlist = core_load_obj('groups_2_openidmarkerlist');

        $group_users_ser = $groups_2_userlist_ser[$group_name] ?? [];
        $user_ser = auth_aux_serialize_user($user->get_user_identity());
        $guest_user_ser = auth_aux_serialize_user(auth_aux_make_guest_user_identity());

        if (in_array($user_ser, $group_users_ser, true) ||
            in_array($guest_user_ser, $group_users_ser, true))
        {
            return true;
        }

        if ($user instanceof OpenIdResolvedUser) {
            foreach ($groups_2_openidmarkerlist[$group_name] ?? [] as $marker) {
                if ($marker['providerName'] !== $user->providerName) {
                    continue;
                }

                foreach ($user->markers as $user_marker) {
                    if ($marker['attributeName'] === $user_marker->attributeName &&
                        $marker['markerName'] === $user_marker->markerName)
                    {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    // $authorized_groups: list<string>
    // $authorized_users:  list<string>
    function auth_is_cur_user_among_authorized_groups_static_users(array $groups, array $static_users): bool {
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
                    'contents' => [
                        'tag'      => 'Static',
                        'contents' => $user
                    ]
                ]);
            }, $static_users)
        );

        return auth_is_cur_user_among_authorized_actors($authorized_actors_ser);
    }

    // $authorized_actors_ser: list<actor_ser>
    function auth_is_cur_user_among_authorized_actors(array $authorized_actors_ser): bool {
        return auth_aux_is_user_authorized(
            user:                            auth_get_user(),
            guestUser:                       auth_aux_make_guest_user_identity(),
            rootUser:                        auth_aux_make_root_user_identity(),
            groups_2_userlist_ser:           core_load_obj('groups_2_userlist_ser'),
            groups_2_openidmarkerlist:       core_load_obj('groups_2_openidmarkerlist'),
            authorized_actors_ser:           $authorized_actors_ser
        );
    }

    // This function checks whether the resolved user `$user` is part of
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
    // $user:                            ResolvedUser
    // $staticGuestUser:                 user
    // $staticRootUser:                  user
    // $groups_2_userlist_ser:           dict<string, list<user_ser>>
    // $groups_2_openidmarkerlist:       dict<string, list<openid_marker>>
    // $authorized_actors_ser:           list<actor_ser>
    function auth_aux_is_user_authorized(
        ResolvedUser $user,
        array $guestUser,
        array $rootUser,
        array $groups_2_userlist_ser,
        array $groups_2_openidmarkerlist,
        array $authorized_actors_ser
    ): bool {
        // Root user is always authorized
        if ($user->get_user_identity() == $rootUser) {
            return true;
        }

        // Go through all authorized actors
        foreach ($authorized_actors_ser as $cur_actor_ser) {
            $cur_actor = auth_aux_deserialize_actor($cur_actor_ser);

            // If current actor is a user...
            if ($cur_actor['tag'] == 'User') {
                $cur_user = $cur_actor['contents'];

                // Check whether current actor is `$user`
                if ($cur_user == $user->get_user_identity()) {
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

                // Check whether `$user` is member of `$cur_group`
                $user_ser = auth_aux_serialize_user($user->get_user_identity());
                if (in_array($user_ser, $groups_2_userlist_ser[$cur_group] ?? [])) {
                    return true;
                }

                // If `$user` is provided by OpenID, check whether there is a
                // marker that is attached to `$cur_group`
                if ($user instanceof OpenIdResolvedUser &&
                    isset($groups_2_openidmarkerlist[$cur_group]))
                {
                    foreach ($groups_2_openidmarkerlist[$cur_group] as $marker) {
                        if ($marker['providerName'] == $user->providerName) {
                            foreach ($user->markers as $user_marker) {
                                if ($marker['attributeName'] == $user_marker->attributeName &&
                                    $marker['markerName'] == $user_marker->markerName) {
                                    return true;
                                }
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

    function auth_get_default_login_profile_id(): string {
        return core_load_obj('default_login_profile_id');
    }

    function auth_get_login_url(?string $profile_id = null): string {
        if ($profile_id === null || $profile_id === auth_get_default_login_profile_id()) {
            return url() . 'login/';
        }

        assert(
            preg_match('/^[a-z0-9_-]+$/', $profile_id) === 1,
            "Invalid login profile name `$profile_id`"
        );

        return url() . 'login/' . $profile_id . '/';
    }

    // This function returns the url to a login page, with a redirection param
    // pointing to the current url.
    // Any query strings e.g. `?solution=true` are preserved.
    function auth_get_login_url_with_redirect(?string $login_profile_id = null): string {
        $login_url = auth_get_login_url($login_profile_id);

        if ($_SERVER['REQUEST_URI'] != '/') {
            return $login_url . make_query_string(['redirect' => $_SERVER['REQUEST_URI']]);
        }
        else {
            return $login_url;
        }
    }

    function auth_get_oidc_start_url(string $provider_name): string {
        return url() . 'auth/oidc/' . rawurlencode($provider_name) . '/start';
    }

    function auth_get_oidc_callback_url(string $provider_name, bool $full = false): string {
        $suffix = 'auth/oidc/' . rawurlencode($provider_name) . '/callback';

        return $full ? url_full($suffix) : url() . $suffix;
    }

    function auth_get_logout_url(): string {
        return url() . 'logout/';
    }


    ///////////////////////
    // Types and helpers //
    ///////////////////////

    // User identities, actors, actions etc. are represented as defined in the
    // Dhall types, but then as assoc arrays.
    //
    // To give an example, a user identity is represented in the following form:
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
    // The current authenticated user is represented by a ResolvedUser object.
    // It can be converted back to the Dhall-compatible identity via
    // ResolvedUser::get_user_identity().

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

    function auth_aux_make_static_user_identity(string $name): array {
        return [
            'tag'      => 'Static',
            'contents' => $name
        ];
    }

    function auth_aux_make_guest_user_identity(): array {
        return auth_aux_make_static_user_identity(get_global_config('authorization', 'staticGuestUser'));
    }

    function auth_aux_make_root_user_identity(): array {
        return auth_aux_make_static_user_identity(get_global_config('authorization', 'staticRootUser'));
    }

    function auth_aux_make_guest_resolved_user(): StaticResolvedUser {
        return new StaticResolvedUser(get_global_config('authorization', 'staticGuestUser'));
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
