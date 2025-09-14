<?
    ////////////////////////////
    // Special HTTP responses //
    ////////////////////////////

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

    function redirect_temporally_and_exit($url) {
        http_response_code(303);
        header('Location: ' . $url);
        exit();
    }

    function redirect_permanently_and_exit($url) {
        http_response_code(301);
        header('Location: ' . $url);
        exit();
    }


    //////////////////////////////////////////////////////
    // Save and load: Objs and Res during preprocessing //
    //////////////////////////////////////////////////////

    function core_save_obj($name, $obj) {
        $s = serialize($obj);
        core_save_file(path_preprocess('obj/' . $name), $s);
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

    function core_save_res($name, $content) {
        core_save_file(path_preprocess('res/' . $name), $content);
    }


    //////////////////////////////////
    //// Save and load: Store files //
    //////////////////////////////////

    function save_store_file($filename, $content) {
        $path = path_store($filename);
        core_save_file($path, $content);
    }


    ////////////////////////////
    // Save and load: General //
    ////////////////////////////

    function core_save_file($path, $content) {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, recursive: true);
        }

        file_put_contents($path, $content);
    }


    ///////////
    // Paths //
    ///////////

    // Paths are always understood as absolute paths.
    // `$suffix` given: Must not have a leading slash.
    // `$suffix` not given: Returns path with trailing slash.

    function path($suffix = '') {
        return $GLOBALS['core_path_base'] . '/' . $suffix;
    }

    function path_preprocess($suffix = '') {
        return $GLOBALS['core_path_preprocess'] . '/' . $suffix;
    }

    function path_store($suffix = '') {
        return $GLOBALS['core_path_store'] . '/' . $suffix;
    }

    // Get path based on target ids
    function path_collect($target_ids = [], $suffix = '') {
        return path('content') . implode('', array_map(fn($id) => '/' . $id, $target_ids)) . '/' . $suffix;
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

    function to_url($path_absolute): string {
        // Call `realpath` to resolve things like `/content/..res/...` etc.
        return url(substr($path_absolute, strlen(path_collect())));
    }

    // This function really returns a root-relative URL, e.g. `/intro/res/img.jpg`
    // `$suffix` must not start with a slash.
    function url($suffix = '') {
        $url_base = get_global_config('url_base');
        // Deal with root '/' being the only dir that has a trailing slash
        if ($url_base === '/') {
            return '/' . $suffix;
        }
        else {
            return $url_base . '/' . $suffix;
        }
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

    // This function returns the full URL, including protocol and host, e.g. `https://conjin.org/intro/res/img.jpg`
    // `$suffix` must not start with a slash.
    function url_full($suffix = ''): string {
        $protocol = get_global_config('https') ? 'https' : 'http';
        $url_base = get_global_config('url_base');

        if ($url_base === '/') {
            return $protocol . '://' . get_global_config('host') . '/' . $suffix;
        }
        else {
            return $protocol . '://' . get_global_config('host') . $url_base . '/' . $suffix;
        }
    }

    function url_full_collect($target_ids = [], $anchor_ids = []): string {
        $suffix = '';
        foreach ($target_ids as $id) {
            $suffix .= $id . '/';
        }

        $anchor = anchor_collect($anchor_ids);
        if ($anchor != '') {
            return url_full($suffix . '#' . $anchor);
        }
        else {
            return url_full($suffix);
        }
    }

    ////////////////////////
    // Unique ids: Global //
    ////////////////////////

    // Produce globally unique id's for any purpose, e.g. CSS class names, JS var names etc.
    $GLOBALS['unique_id_counter'] = 0;
    function next_unique_id(): int {
        return $GLOBALS['unique_id_counter']++;
    }


    //////////////////////////////////////////
    // Unique ids: Namespaces (stack-based) //
    //////////////////////////////////////////

    // Push and pop a namespace to create unique ids within that namespace.
    $GLOBALS['namespace_stack'] = [];
    
    function ns_push() {
        $GLOBALS['namespace_stack'][] = next_unique_id();
    }
    
    function ns_pop() {
        assert(count($GLOBALS['namespace_stack']) > 0, 'Namespace stack underflow');
        array_pop($GLOBALS['namespace_stack']);
    }
    
    // Get a unique id within the current namespace. The id is prefixed with 'ns_' and includes the namespace stack to ensure uniqueness.
    function ns_id(string $id): string {
        $stack = $GLOBALS['namespace_stack'];
        if (count($stack) == 0) {
            return $id;
        }

        return 'ns_' . implode('_', $stack) . '_' . $id;
    }
?>