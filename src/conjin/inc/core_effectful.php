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
        echo '<pre>';
        print_r($var);
        echo '</pre>';
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
        assert($status_code != 404, 'Use different function for 404');

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

    function send_not_found_response_and_exit() {
        // Throw away any buffered output
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code(404);

        $GET_target = $_GET['target'] ?? ''; // Coalesce to empty string
        $requested_target_ids = target_query_to_target_ids($GET_target);
        
        process_not_found(core_load_obj('syslet_not_found'), $requested_target_ids);

        exit();
    }

    function redirect_and_exit($url) {
        http_response_code(303);
        header('Location: ' . $url);
        exit();
    }


    ///////////////////
    // Global Config //
    ///////////////////

    function global_config_init(): void {
        // Merge `users2passwordHashes` into global config
        $config               = json_decode(load_file('../config.json'), associative: true);
        $users2passwordHashes = json_decode(load_file('../users.json'),  associative: true);
        $config['authentication']['users2passwordHashes'] = $users2passwordHashes;

        $GLOBALS['config'] = new ConfigTree($config);

        $GLOBALS['core_path_base']       = realpath(get_global_config('path_base'));
        $GLOBALS['core_path_preprocess'] = realpath(get_global_config('path_preprocess'));

        assert($GLOBALS['core_path_base'] !== false, 'Path base does not exist.');
        assert($GLOBALS['core_path_preprocess'] !== false, 'Path to preprocess dir does not exist.');
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

        // Populate cache
        $s = file_get_contents(path_preprocess('obj/' . $name));
        $GLOBALS['loaded_objs'][$name] = unserialize($s);

        return $GLOBALS['loaded_objs'][$name];
    }

    function core_obj_exists($name) {
        return isset($GLOBALS['loaded_objs'][$name]) || file_exists(path_preprocess('obj/' . $name));
    }

    function load_file($path) {
        if (!file_exists($path)) {
            fail('Error: File does not exist.');
        }
        
        return file_get_contents($path);
    }


    ///////////
    // Paths //
    ///////////

    // Paths are always understood as absolute paths.
    function path($suffix = '') {
        return $GLOBALS['core_path_base'] . '/' . $suffix;
    }

    function path_preprocess($suffix = '') {
        return $GLOBALS['core_path_preprocess'] . '/' . $suffix;
    }

    // Get path based on target ids
    function path_collect($target_ids = []) {
        return path('content') . '/' . implode('/', $target_ids);
    }

    function to_url($path_absolute): string {
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
        return get_global_config('url_base') . $suffix;
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


    ////////////////
    // Unique ids //
    ////////////////

    $GLOBALS['unique_id_counter'] = 0;

    function next_unique_id(): int {
        return $GLOBALS['unique_id_counter']++;
    }
?>