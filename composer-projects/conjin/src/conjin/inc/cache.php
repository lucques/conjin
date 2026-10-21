<?
    // Content resources remain private: authorization and module hooks run first.
    // Public module resources explicitly opt into shared storage.
    function check_whether_unmodified_and_handle_and_exit($path, bool $public = false): void {
        if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
            header('Cache-Control: no-store');
            header('Allow: GET, HEAD');
            http_response_code(405);
            exit;
        }
        // A resource observer can prohibit storage (for example for session state).
        foreach (headers_list() as $header) {
            if (preg_match('/^Cache-Control:.*\bno-store\b/i', $header)) {
                return;
            }
        }
        header('Cache-Control: ' . ($public ? 'public' : 'private') . ', no-cache');
        $modified = filemtime($path);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $modified) . ' GMT');
        // If-None-Match takes precedence; this handler does not issue ETags.
        if (!isset($_SERVER['HTTP_IF_NONE_MATCH']) && isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $since = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            if ($since !== false && $since >= $modified) {
                while (ob_get_level() > 0) { ob_end_clean(); }
                http_response_code(304);
                exit;
            }
        }
    }
