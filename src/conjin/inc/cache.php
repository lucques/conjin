<?
    function check_whether_unmodified_and_handle($path) {
        // Is response 304 even possible?
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $client_timestamp = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            $server_timestamp = filemtime($path);

            header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', $server_timestamp));
            
            if ($client_timestamp >= $server_timestamp) {
                send_response_and_exit(304); // Not Modified
            }
        }
    }
?>