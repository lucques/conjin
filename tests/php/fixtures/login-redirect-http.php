<?
    require dirname(__DIR__, 3) . '/composer-projects/conjin/src/conjin/inc/auth.php';
    require dirname(__DIR__, 3) . '/composer-projects/conjin/src/conjin/inc/core_effectful.php';
    require dirname(__DIR__, 3) . '/composer-projects/conjin/src/conjin/inc/core_pure.php';
    require dirname(__DIR__, 3) . '/composer-projects/conjin/src/conjin/inc/target.php';

    function get_global_config(string ...$keys): mixed {
        return match (implode('.', $keys)) {
            'https' => false,
            'url_base' => '/app',
            'authorization.staticGuestUser' => 'guest',
            'authentication.users2passwordHashes' => ['alice' => password_hash('correct', PASSWORD_DEFAULT)],
            default => throw new LogicException('Unexpected configuration key')
        };
    }

    $GLOBALS['loaded_objs']['default_login_profile_id'] = 'default';
    ob_start();
    auth_init();
    if (isset($_GET['logout'])) {
        auth_handle_logout_and_exit();
    }
    if (isset($_GET['logout_token'])) {
        header('Content-Type: application/json');
        echo json_encode(['token' => auth_issue_submission_token('auth:logout')]);
        exit();
    }
    if (isset($_GET['retry'])) {
        auth_ensure_session_started();
        $_SESSION['redirect'] = $_GET['stored'] ?? null;
        auth_aux_handle_oidc_failure_and_exit('provider', new LoginProfile('test', [], 'test'), new Exception('fixture failure'));
    }
    if (isset($_GET['check'])) {
        header('Content-Type: application/json');
        echo json_encode(['user' => auth_get_user_name(), 'redirect_stored' => isset($_SESSION['redirect'])]);
    }
    else {
        auth_ensure_session_started();
        $_SESSION['redirect'] = '/app/stale/';
        auth_handle_login_and_exit(new LoginProfile('test', [], 'test'));
    }
