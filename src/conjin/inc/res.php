<?
    function check_and_handle_if_resource() {
        if (isset($_GET['target']) && isset($_GET['res'])) {
            $path = path('content') . '/' . $_GET['target'] . 'res/' . $_GET['res'];
            $real_path = realpath($path);

            // Does resource exist?
            if ($real_path !== false) {
                // Make sure that file is part of `content`.
                if (str_starts_with($real_path, realpath(path('content')))) {
                    // File still cached by browser?
                    check_whether_unmodified_and_handle($path);            

                    header('Content-Type: ' . get_mime($path));
                    readfile($real_path);
                    
                    exit();
                }
                // Otherwise, deny permission.
                else
                {
                    send_response_and_exit(403); // Unauthorized
                }
            }
            // Otherwise, not found.
            else {
                send_not_found_response_and_exit(); // Not Found
            }
        }
    }
?>