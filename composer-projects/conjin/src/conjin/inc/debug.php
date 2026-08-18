<?
    use Jumbojett\OpenIDConnectClient;

    ///////////////////////////////
    // Artifacts, objects, files //
    ///////////////////////////////

    $build_artifacts = [
        'htaccess'    => '.htaccess',
        'config-json' => 'config.json',
        'users-json'  => 'users.json',
    ];

    $openid_provider_names = [];
    foreach (get_global_config('authentication', 'openIdProviders') as $provider) {
        $openid_provider_names[] = $provider['name'];
    }

    $preprocess_objs = [
        'target_root',
        'login_profiles',
        'default_login_profile_id',
        'syslet_not_found',
        'syslet_unauthorized',
        'groups_2_userlist_ser',
        'groups_2_openidmarkerlist',
        'nav',
    ];

    $store_files = null;
    if (isset($GLOBALS['core_path_store'])) {
        $store_files = [];
        foreach (glob(path_store('*')) as $file) {
            $store_files[] = basename($file);
        }
    }


    //////////
    // Main //
    //////////

    if (!isset($_GET['what'])) {
        echo '<html><head><title>Debug</title></head><body><h1>What to debug?</h1><h2>Web server info</h2><ul>';
        echo '<li><a href="?what=phpinfo">?what=phpinfo</a></li></ul>';

        
        echo '<h2>Session variables</h2><ul>';
        foreach ($_SESSION as $key => $value) {
            echo '<li>' . htmlspecialchars($key) . ': <pre>' . htmlspecialchars(var_export($value, true)) . '</pre></li>';
        }
        echo '</ul>';


        echo '<h2>OpenID mechanisms</h2>';

        if (count($openid_provider_names) > 0) {
            echo '<p><em>(Each link will trigger a single-sign-on flow with the respective OpenID provider.)</em></p>';
            echo '<ul>';

            foreach ($openid_provider_names as $name) {
                echo '<li><a href="openid/' . $name . '">openid/' . $name . '</a></li>';
            }

            echo '</ul>';
        }
        else {
            echo '<em>No OpenID providers configured.</em>';
        }
        
        echo '<h2>Build artifacts</h2><table><tr><td>File</td><td>Size</td></tr>';

        foreach ($build_artifacts as $what => $file) {
            // Determine file size
            $file_path = '../' . $file;
            $file_size = file_exists($file_path) ? round(filesize($file_path) / 1024, 2) . ' KB' : 'N/A';

            echo '<tr><td><a href="?what=' . $what . '">' . $file . '</a></td><td>' . $file_size . '</td></tr>';
        }

        echo '</table><h2>Preprocess objects</h2><em>(Rendered as JSON)</em><table><tr><td>Name</td><td>Size</td></tr>';

        foreach ($preprocess_objs as $name) {
            // Determine file size
            $file_path = path_preprocess('obj/' . $name);
            $file_size = file_exists($file_path) ? round(filesize($file_path) / 1024, 2) . ' KB' : 'N/A';

            echo '<tr><td><a href="?what=preprocess-obj&name=' . $name . '">' . $name . '</a></td><td>' . $file_size . '</td></tr>';
        }
        
        echo '</table>';

        echo '<h2>Store files</h2>';
        if ($store_files !== null) {
            echo '<table><tr><td>Name</td><td>Size</td><td>Edit</td></tr>';

            foreach ($store_files as $file_name) {
                $file_size = round(filesize(path_store($file_name)) / 1024, 2) . ' KB';
                $edit_link = debug_aux_is_sqlite_file(path_store($file_name))
                    ? '<a href="?what=store-file-sqlite&amp;name=' . urlencode($file_name) . '">SQLite editor</a>'
                    : '';

                echo '<tr><td><a href="?what=store-file-download&amp;name=' . urlencode($file_name) . '">' . htmlspecialchars($file_name) . '</a></td><td>' . $file_size . '</td><td>' . $edit_link . '</td></tr>';
            }

            echo '</table>';
        }
        else {
            echo '<em>Store files are not configured.</em>';
        }

        echo '</body></html>';

        exit();
    }


    $what = $_GET['what'];

    if ($what == 'phpinfo') {
        phpinfo();
    }
    // TODO remove the following?
    elseif ($what == 'log') {
        header('Content-Type: text/plain');
        readfile('/var/log/mail.log');
    }
    elseif ($what == 'htaccess') {
        header('Content-Type: text/plain');
        readfile('../.htaccess');
    }
    elseif ($what == 'config-json') {
        header('Content-Type: application/json');
        readfile('../config.json');
    }
    elseif ($what == 'users-json') {
        header('Content-Type: application/json');
        readfile('../users.json');
    }
    elseif ($what == 'openid') {
        if (!isset($_GET['openid'])) {
            echo "Please specify openid via ?openid=...\n";
        }
        elseif (in_array($_GET['openid'], $openid_provider_names)) {
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
                echo 'OpenID provider "' . htmlspecialchars($providerName) . '" not found in config.json.';
            }
            else {
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

            
                    // Start outputting debug info
                    debug_aux_print_openid_result_begin($providerName, $server, $clientID, $provider);
                    echo '<h2>OpenID authentication result</h2>';
                    echo '<ul>';
                    echo '<li>ID Attribute: ' . htmlspecialchars($idAttribute) . '</li>';
                    echo '<li>E-Mail attribute: ';
                    if ($emailAttribute !== null) {
                        echo htmlspecialchars($emailAttribute);
                    }
                    else {
                        echo '<em>Not configured</em>';
                    }
                    echo '</li>';

                    // Inspect raw attributes / markers
                    echo '<li>Raw attributes and markers:';
                    if (count($provider['markerAttributes']) == 0) {
                        echo ' <em>None configured</em>';
                    }
                    else {
                        echo '<ul>';
                        foreach ($provider['markerAttributes'] as $markerAttribute) {
                            echo '<li>Attribute: <strong>' . htmlspecialchars($markerAttribute['attributeName']) . '</strong>';
                            echo '<ul>';
                            $value = $oidc->requestUserInfo($markerAttribute['attributeName']);
                            echo '<li>Raw value: <pre>' . htmlspecialchars(var_export($value, true)) . '</pre></li>';

                            // Try to unpack the value into markers
                            if ($markerAttribute['isList'] && !is_object($value) && !is_array($value)) {
                                echo '<li><em>Warning: Expected a list, but got a non-list value.</em></li>';
                            }
                            elseif ($markerAttribute['isList'] && (is_object($value) || is_array($value))) {
                                echo '<li>Unpacking list into markers:<ul>';
                                foreach ($value as $marker) {
                                    if (isset($markerAttribute['subkey']) && !is_object($marker)) {
                                        echo '<li><em>Warning: Expected a list of objects, but got a non-object value.</em></li>';
                                    }
                                    elseif (isset($markerAttribute['subkey']) && is_object($marker) && !property_exists($marker, $markerAttribute['subkey'])) {
                                        echo '<li><em>Warning: Expected subkey "' . htmlspecialchars($markerAttribute['subkey']) . '" in marker, but it does not exist.</em></li>';
                                    }
                                    elseif (isset($markerAttribute['subkey'])) {
                                        $subkey = $markerAttribute['subkey'];
                                        echo '<li>Subkey "' . htmlspecialchars($subkey) . '" results in marker: <strong>' . htmlspecialchars($marker->{$subkey}) . '</strong></li>';
                                    }
                                    else {
                                        echo '<li>Marker: <strong>' . htmlspecialchars($marker) . '</strong></li>';
                                    }
                                }
                                echo '</ul></li>';
                            }
                            else {
                                assert(!$markerAttribute['isList']);
                                                        
                                if (isset($markerAttribute['subkey']) && !is_object($value) && !is_array($value)) {
                                    echo '<li><em>Warning: Expected an object, but got a non-object value.</em></li>';
                                }
                                elseif (isset($markerAttribute['subkey']) && is_object($value) && !property_exists($value, $markerAttribute['subkey'])) {
                                    echo '<li><em>Warning: Expected subkey "' . htmlspecialchars($markerAttribute['subkey']) . '" in value, but it does not exist.</em></li>';
                                }
                                elseif (isset($markerAttribute['subkey']) && is_object($value) && property_exists($value, $markerAttribute['subkey'])) {
                                    echo '<li>Subkey "' . htmlspecialchars($markerAttribute['subkey']) . '" results in marker: <strong>' . htmlspecialchars($value->{$markerAttribute['subkey']}) . '</strong></li>';
                                }
                                else {
                                    echo '<li>Marker: <strong>' . htmlspecialchars($value) . '</strong></li>';
                                }
                            }
                            echo '</ul></li>';
                        }
                        
                        echo '</ul>';
                    }
                    echo '</li>';
                    debug_aux_print_openid_result_end();
                }
                catch (Exception $e) {
                    debug_aux_print_openid_result_begin($providerName, $server, $clientID, $provider);
                    echo '<h2>OpenID authentication failed</h2>';
                    echo '<ul>';
                    echo '<li>Provider: ' . htmlspecialchars($providerName) . '</li>';
                    echo '<li>Error message: ' . htmlspecialchars($e->getMessage()) . '</li>';
                    echo '<li>Trace: <pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre></li>';
                    echo '</ul>';
                    debug_aux_print_openid_result_end();
                }
            }
        }
        else {
            echo "OpenID provider '" . htmlspecialchars($_GET['openid']) . "' not found.\n";
        }
    }
    elseif ($what == 'preprocess-obj') {
        if (!isset($_GET['name'])) {
            echo "Please specify name via ?name=...\n";   
        }
        elseif (in_array($_GET['name'], $preprocess_objs)) {
            // Special treatment for 'nav'
            if ($_GET['name'] == 'nav') {

                // Load classes -- hacky, but ok for debugging purposes
                include('../modules-shared/nav-common/datatypes.php');
                $obj = core_load_obj('nav');
                header('Content-Type: application/json');
                debug_aux_print_json($obj);
            }
            else {
                header('Content-Type: application/json');
                debug_aux_print_json(core_load_obj($_GET['name']));
            }
        }
        else {
            echo "Preprocess object '" . htmlspecialchars($_GET['name']) . "' not found.\n";
        }
    }
    elseif ($what == 'store-file-download') {
        if (!isset($_GET['name'])) {
            echo "Please specify name via ?name=...\n";   
        }    
        elseif (in_array($_GET['name'], $store_files)) {
            $file_name = $_GET['name'];
            $file_path = path_store($file_name);

            // Use a printable ASCII fallback plus RFC 5987 for UTF-8 file names.
            $fallback_file_name = preg_replace('/[^\x20-\x7E]/', '_', $file_name);
            $fallback_file_name = str_replace(['\\', '"'], ['\\\\', '\\"'], $fallback_file_name);

            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $fallback_file_name . '"; filename*=UTF-8\'\'' . rawurlencode($file_name));
            header('Content-Length: ' . filesize($file_path));
            header('X-Content-Type-Options: nosniff');
            readfile($file_path);
        }
        else {
            echo "Store file '" . htmlspecialchars($_GET['name']) . "' not found.\n";
        }
    }
    elseif ($what == 'store-file-sqlite') {
        if (!isset($_GET['name'])) {
            echo "Please specify name via ?name=...\n";   
        }    
        elseif (in_array($_GET['name'], $store_files) && debug_aux_is_sqlite_file(path_store($_GET['name']))) {
            require(__DIR__ . '/debug_sqlite.php');
        }
        else {
            echo "Store file '" . htmlspecialchars($_GET['name']) . "' not found or is not a valid SQLite file.\n";
        }
    }
    else {
        echo 'Not found';
    }


    /////////////
    // Helpers //
    /////////////

    function debug_aux_print_json($obj) {
        $json = json_encode($obj);
        if ($json === false) {
            echo 'Error during `json_encode`: ' . json_last_error_msg();
        }
        else {
            echo $json;
        }
    }

    function debug_aux_is_sqlite_file($path) {
        return is_file($path) && file_get_contents($path, false, null, 0, 16) === "SQLite format 3\0";
    }

    function debug_aux_print_openid_result_begin($providerName, $server, $clientID, $provider) {
        echo '<html><head><title>Debug OpenID: ' . htmlspecialchars($providerName) . '</title></head><body><h1>Debug OpenID: ' . htmlspecialchars($providerName) . '</h1>';
        echo '<h2>Configuration in config.json</h2>';
        echo '<ul>';
        echo '<li>Provider: ' . htmlspecialchars($providerName) . '</li>';
        echo '<li>Server: ' . htmlspecialchars($server) . '</li>';
        echo '<li>Client ID: ' . htmlspecialchars($clientID) . '</li>';
        echo '<li>ID attribute: ' . htmlspecialchars($provider['idAttribute']) . '</li>';
        echo '<li>E-Mail attribute: ' . (isset($provider['emailAttribute']) ? htmlspecialchars($provider['emailAttribute']) : '<em>Not configured</em>') . '</li>';
        echo '<li>Marker attributes: <ul>';
        foreach ($provider['markerAttributes'] as $markerAttribute) {
            echo '<li>Attribute name: ' . htmlspecialchars($markerAttribute['attributeName']) . ', isList: ' . ($markerAttribute['isList'] ? 'true' : 'false') . ', subkey: ' . (isset($markerAttribute['subkey']) ? htmlspecialchars($markerAttribute['subkey']) : '<em>Not configured</em>') . '</li>';
        }
        echo '</ul></li>';
        echo '<li>Scopes: <ul>';
        foreach ($provider['scopes'] as $scope) {
            echo '<li>' . htmlspecialchars($scope) . '</li>';
        }
        echo '</ul></li>';
        echo '</ul>';
    }

    function debug_aux_print_openid_result_end() {
        echo '</body></html>';
    }
?>
