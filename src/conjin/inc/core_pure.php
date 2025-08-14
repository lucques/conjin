<?
    //////////////////
    // Semantic IDs //
    //////////////////

    // Take a title and return a semantic id, i.e. in kebab case
    function kebabize($title): string {
        // Lowercase
        $slug = strtolower($title);

        // Remove HTML tags
        $slug = strip_tags($slug);

        // Remove special characters like &shy;
        $slug = preg_replace('/&[a-z]+;/', '', $slug);

        // Replace umlauts and sharp s
        $slug = str_replace('ä', 'ae', $slug);
        $slug = str_replace('ö', 'oe', $slug);
        $slug = str_replace('ü', 'ue', $slug);
        $slug = str_replace('ß', 'ss', $slug);

        // Replace any other characters by `-`
        $slug = preg_replace('/[^a-z0-9]/', '-', $slug);
        // Replace multiple `-` by single `-`
        $slug = preg_replace('/-+/', '-', $slug);
        // Remove leading and trailing `-`
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

    // Definitions:
    // - `iso_date`:  E.g. `2024-12-21`: A date in the format `YYYY-MM-DD`, following the ISO 8601 standard
    // - `iso_month`: E.g. `2024-12`: Possibly negative year, followed by a month, e.g. `-123-12` which stands for December of the year 123 BC
    // - `iso_year`:  E.g. `2024`: Possibly negative year, with an unbounded number of digits, e.g. `-123` which stands for the year 123 BC

    function is_iso_year(string $string): bool {
        return preg_match('/^-?\d{1,}$/', $string) === 1;
    }

    function is_iso_month(string $string): bool {
        return preg_match('/^-?\d{1,}-\d{1,2}$/', $string) === 1;
    }

    function is_iso_date(string $string): bool {
        return preg_match('/^-?\d{1,}-\d{1,2}-\d{1,2}$/', $string) === 1;
    }

    function date_to_iso(DateTimeInterface $date): string {
        return $date->format('Y-m-d');
    }

    function assert_iso_date(string $string) {
        assert(is_iso_date($string), 'Invalid date format');
    }

    function iso_to_date(string $date_iso): ?DateTimeImmutable {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $date_iso);
        if ($date === false) {
            return null;
        }
        return $date;
    }

    // $date must be either an iso date, iso month, or iso year
    function floor_to_iso_date(string $date): string {
        if (is_iso_year($date)) {
            return $date . '-01-01';
        }
        elseif (is_iso_month($date)) {
            return $date . '-01';
        }
        elseif (is_iso_date($date)) {
            return $date;
        }
        else {
            throw new Exception('Invalid date format');
        }
    }

    // $date must be either an iso date, iso month, or iso year
    function ceil_to_iso_date(string $date): string {
        if (is_iso_year($date)) {
            return $date . '-12-31';
        }
        elseif (is_iso_month($date)) {
            return $date . '-' . date('t', strtotime($date . '-01'));
        }
        elseif (is_iso_date($date)) {
            return $date;
        }
        else {
            throw new Exception('Invalid date format');
        }
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
            if (array_key_exists($key, $b)) {
                $a[$key] = aux_nested_update($value, $b[$key]);
            }
        }
        foreach ($b as $key => $value) {
            if (!array_key_exists($key, $a)) {
                $a[$key] = $value;
            }
        }

        return $a;
    }
?>