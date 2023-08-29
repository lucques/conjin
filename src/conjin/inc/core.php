<?
    ///////////
    // Timer //
    ///////////

    $GLOBALS['timer_start'] = hrtime(true);

    function timer_get_elapsed_ms() {
        return (hrtime(true) - $GLOBALS['timer_start'])/1e+6;
    }


    ///////////////////////////////
    // Error Logging & Debugging //
    ///////////////////////////////

    error_reporting(0);

    function the_shutdown_function()
    {
        $last_error = error_get_last();
        
        if ($last_error != null) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            fail("Error code: " . $last_error['type'] . "\n\nError message:\n\n" . $last_error['message'] . "\n\nError file: " . $last_error['file'] . "\n\nError line: " . $last_error['line']);

        }
    }

    function the_error_handler($error_code, $error_msg, $error_file, $error_line)
    {
        fail("Error code: $error_code\n\nError message:\n\n$error_msg\n\nError file: $error_file\n\nError line: $error_line");
    }

    // Register error handlers
    set_error_handler("the_error_handler");
    register_shutdown_function("the_shutdown_function");

    function debug_var($var) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        print_r($var);
    }


    ////////////////////////////
    // Special HTTP responses //
    ////////////////////////////

    function fail($message) {
        ob_start();
        echo "\n\nStack trace:\n\n";
        debug_print_backtrace();
        $backtrace = ob_get_clean();

        send_response_and_exit(status_code: 500, message: $message . $backtrace);
    }

    function send_response_and_exit($status_code = 200, $message = null) {
        // Throw away any buffered output
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($status_code);
        
        if ($message === null) {
            if ($status_code == 200) {
                echo "OK.\n";
            }
            elseif ($status_code == 400) {
                echo "Fehlerhafte Anfrage\n";
            }
            elseif ($status_code == 404) {
                echo "Seite nicht gefunden.\n";
            }
            elseif ($status_code == 403) {
                echo "Zugriff verweigert.\n";
            }
            elseif ($status_code == 500) {
                echo "Interner Serverfehler.\n";
            }
        }
        else {
            echo $message . "\n";
        }

        exit();
    }

    function redirect_and_exit($url) {
        http_response_code(303);
        header('Location: ' . $url);
        exit();
    }

    

    ////////////
    // Config //
    ////////////

    // Stateless wrapper for a nested assoc array
    class ConfigTree {
        function __construct(public readonly array $root) {}

        function get(...$path) {
            $config = $this->root;
            foreach ($path as $key) {
                $config = $config[$key];
            }
            return $config;
        }
    
        function get_or_default(array $path, $default) {
            $config = $this->root;
            foreach ($path as $key) {
                if (isset($config[$key])) {
                    $config = $config[$key];
                }
                else {
                    return $default;
                }
            }
            return $config;
        }
    
        function isset(...$path) {
            $config = $this->root;
            foreach ($path as $key) {
                if (!isset($config[$key])) {
                    return false;
                }
                $config = $config[$key];
            }
            return true;
        }
    }

    function global_config_init(): void {
        $GLOBALS['config'] = new ConfigTree(json_decode(load_file('../config.json'), associative: true));

        $GLOBALS['core_path_root']       = realpath(get_global_config('path_root'));
        $GLOBALS['core_path_preprocess'] = realpath(get_global_config('path_preprocess'));
    }

    function get_global_config(...$path): mixed {
        return $GLOBALS['config']->get(...$path);
    }

    function get_global_config_or_default(array $path, mixed $default): mixed {
        return $GLOBALS['config']->get_or_default($path, $default);
    }

    function isset_global_config(...$path): bool {
        return $GLOBALS['config']->isset(...$path);
    }


    ///////////////////
    // Save and load //
    ///////////////////

    function core_save_obj($name, $obj) {
        $s = serialize($obj);
        core_save_file(path_preprocess('obj/' . $name), $s);
    }

    function core_save_res($name, $content) {
        core_save_file(path_preprocess('res/' . $name), $content);
    }

    function core_save_file($path, $content) {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, recursive: true);
        }

        file_put_contents($path, $content);
    }

    function core_load_obj($name) {
        // Get from cache
        if (isset($GLOBALS['loaded_objs'][$name])) {
            return $GLOBALS['loaded_objs'][$name];
        }

        $s = file_get_contents(path_preprocess('obj/' . $name));

        // Populate cache
        $GLOBALS['loaded_objs'][$name] = unserialize($s);

        return $GLOBALS['loaded_objs'][$name];
    }

    function core_obj_exists($name) {
        return isset($GLOBALS['loaded_objs'][$name]) || file_exists(path_preprocess('obj/' . $name));
    }

    function load_file($path) {
        if (!file_exists($path)) {
            fail('Error: Preprocessing not finished.');
        }
        
        return file_get_contents($path);
    }


    ///////////
    // Paths //
    ///////////

    // Paths are always understood as absolute paths.
    function path($suffix = '') {
        return $GLOBALS['core_path_root'] . '/' . $suffix;
    }

    function path_preprocess($suffix = '') {
        return $GLOBALS['core_path_preprocess'] . '/' . $suffix;
    }

    // Get path based on target ids
    function path_collect($target_ids = []) {
        return path('content') . '/' . implode('/', $target_ids);
    }

    function path_to_url($path_absolute): string {
        return url(substr($path_absolute, strlen(path_collect())));
    }

    function path_to_target_ids($path_absolute): array {
        $path_relative = substr($path_absolute, strlen(path_collect()));

        if ($path_relative == '') {
            return [];
        }
        else {
            return explode('/', $path_relative);
        }
    }


    //////////
    // URLs //
    //////////

    function url($suffix = '') {
        return get_global_config('url_root') . $suffix;
    }

    function url_collect($target_ids = [], $anchor_ids = []) {
        $suffix = '';
        foreach ($target_ids as $id) {
            $suffix .= $id . '/';
        }
        
        $anchor = anchor_collect($anchor_ids);
        if ($anchor != '') {
            return url($suffix . '#' . $anchor);
        }
        else {
            return url($suffix);
        }
    }

    // Get full anchor based on anchor ids, without # prefix
    function anchor_collect($anchor_ids) {
        return implode('-', $anchor_ids);
    }

    // This function removes all the internal query params from `$base_queries`
    // and adds `$queries`. The `$base_queries` is e.g. `$_GET`.
    //
    // $queries:      dict<string, string>
    // $base_queries: dict<string, string>
    function make_query_string(array $queries, array $base_queries = []): string {
        // Remove internal query elements
        unset($base_queries['req']);
        unset($base_queries['target']);
        unset($base_queries['res']);
        unset($base_queries['redirect']);
        unset($base_queries['user']);

        $params = array_merge($base_queries, $queries);
        $query  = http_build_query($params);

        if ($query == '') {
            return '';
        }
        else {
            return '?' . $query;
        }
    }


    ////////////////////////////
    // Interpret query string //
    ////////////////////////////

    function target_query_to_target_ids(string $target_query): ?array {           
        // Extract target ids
        $requested_target_ids = explode('/', $target_query);
        
        // The target string must end with `/`, otherwise send "not found"
        if ($requested_target_ids[count($requested_target_ids) - 1] != '') {
            return null;
        }
        array_pop($requested_target_ids);

        return $requested_target_ids;
    }


    function aux_update_config($a, $b) {
        if (!is_array($a)
             || !is_array($b)
             || array_is_list($a)
             || array_is_list($b)) {
            return $b;
        }

        foreach ($a as $key => $value) {
            if (isset($b[$key])) {
                $a[$key] = aux_update_config($value, $b[$key]);
            }
        }

        foreach ($b as $key => $value) {
            if (!isset($a[$key])) {
                $a[$key] = $value;
            }
        }

        return $a;
    }
?>