<?
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
        
        function get_or_default(array $path, $default) {
            if ($this->isset(...$path)) {
                return $this->get(...$path);
            }
            else {
                return $default;
            }
        }
    }
    

    //////////////////
    // Semantic IDs //
    //////////////////

    // Take a title and return a semantic id, i.e. in kebab case
    function kebabize($title): string {
        $slug = strtolower($title);

        // Umlauts and sharp s
        $slug = str_replace('ä', 'ae', $slug);
        $slug = str_replace('ö', 'oe', $slug);
        $slug = str_replace('ü', 'ue', $slug);
        $slug = str_replace('ß', 'ss', $slug);

        $slug = preg_replace('/[^a-z0-9]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }


    //////////
    // URLs //
    //////////

    // Get full anchor based on anchor ids, without # prefix
    function anchor_collect($anchor_ids) {
        return implode('_', $anchor_ids);
    }

    // This function removes all the internal query params from `$base_queries`
    // and adds `$queries`. The `$base_queries` may be e.g. a copy of `$_GET`.
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


    ////////////////
    // MIME types //
    ////////////////

    function get_mime($path) {
        $mime = mime_content_type($path);
        
        // If mime type is not known, try to guess it from the file extension
        // Comment: Unclear why `css` and `js` extension are not recognized,
        // but whatever... this fixes it for now.
        if ($mime == 'text/plain' || $mime == 'text/html') {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if     ($ext == 'css') { return 'text/css'; }
            elseif ($ext == 'js')  { return 'application/javascript'; }
        }

        return $mime;
    }


    ///////////////////
    // Date and time //
    ///////////////////

    // By "date" we mean a date without time
    function date_to_iso(DateTimeInterface $date): string {
        return $date->format('Y-m-d');
    }

    function assert_iso_date(string $date_iso) {
        assert(DateTimeImmutable::createFromFormat('Y-m-d', $date_iso) !== false, 'Invalid date format');
    }

    function iso_to_date(string $date_iso): ?DateTimeImmutable {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $date_iso);
        if ($date === false) {
            return null;
        }
        return $date;
    }


    //////////////////////
    // Helper functions //
    //////////////////////

    // An array set is the following: assoc array where each value is an array that represents a set.
    function aux_array_set_add(&$assocArray, $key, $value) {
        if (!isset($assocArray[$key])) {
            $assocArray[$key] = [];
        }
        // Add the value to the set if it's not already present
        if (!in_array($value, $assocArray[$key])) {
            $assocArray[$key][] = $value;
        }
    }

    function aux_array_set_remove(&$assocArray, $key, $value) {
        if (isset($assocArray[$key])) {
            $index = array_search($value, $assocArray[$key]);
            if ($index !== false) {
                unset($assocArray[$key][$index]);
            }
        }

        // Remove the key if the set is empty
        if (empty($assocArray[$key])) {
            unset($assocArray[$key]);
        }
    }

    // Recursively update config `$a` with config `$b`
    // Both are nested assoc arrays (nesting depth 0 corresponds to ground
    // types like `int` )
    function aux_nested_update($a, $b) {
        // Base case:  If any of `$a` or `$b` are non-assoc-arrays, return `$b`
        if (!is_array($a) ||
            !is_array($b) ||
            (array_is_list($a) && count($a) > 0) ||
            (array_is_list($b) && count($b) > 0)) {
            return $b;
        }

        // Recursive case
        foreach ($a as $key => $value) {
            if (isset($b[$key])) {
                $a[$key] = aux_nested_update($value, $b[$key]);
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