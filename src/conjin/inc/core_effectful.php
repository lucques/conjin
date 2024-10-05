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

    function core_load_store_file($filename) {
        $path = path_store($filename);
        if (!file_exists($path)) {
            // fail('Error: Store file does not exist: ' . $path);  // TODO
        }

        return file_get_contents($path);
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

    function load_file($path) {
        if (!file_exists($path)) {
            // fail('Error: File does not exist.');  // TODO
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

    function path_store($suffix = '') {
        return $GLOBALS['core_path_store'] . '/' . $suffix;
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