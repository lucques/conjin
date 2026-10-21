<?
    ob_start();
    $framework = dirname(__DIR__, 3) . '/composer-projects/conjin/src/conjin';
    require $framework . '/inc/cache.php';
    require $framework . '/inc/core_effectful.php';
    header('Cache-Control: no-store');
    if (isset($_GET['redirect'])) {
        header('Cache-Control: public, no-cache');
        header('Last-Modified: Wed, 01 Jan 2020 00:00:00 GMT');
        match ($_GET['redirect']) {
            '301' => redirect_permanently_and_exit('/destination'),
            '302' => redirect_temporarily_and_exit('/destination'),
            '303' => redirect_see_other_and_exit('/destination'),
        };
    }
    if (!isset($_GET['deny'])) {
        header_remove('Cache-Control');
        if (isset($_GET['sensitive'])) {
            header('Cache-Control: no-store');
        }
        check_whether_unmodified_and_handle_and_exit(__FILE__, isset($_GET['public']));
        header('Content-Type: text/plain');
        if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
            echo 'resource';
        }
    }
    else {
        http_response_code(403);
    }
