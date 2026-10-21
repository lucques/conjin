<?
    // Mirror the request entrypoint's outer buffer around authentication/rendering.
    ob_start();
    require __DIR__ . '/auth-session.php';
    // Test-only deployment policy switch, independent of request scheme/headers.
    $GLOBALS['auth_test_https'] = ($_GET['https'] ?? '') === '1';
    $GLOBALS['auth_test_password_hash'] = password_hash('correct', PASSWORD_DEFAULT);
    auth_init();
    $action = $_GET['action'] ?? 'guest';
    if ($action === 'login') {
        try {
            auth_handle_login_and_exit(auth_test_login_profile());
        }
        catch (RuntimeException $error) {
            if ($error->getMessage() !== 'render-login') {
                throw $error;
            }
            echo json_encode(['active' => session_status() === PHP_SESSION_ACTIVE, 'view' => $GLOBALS['auth_test_login_view']]);
        }
    }
    elseif ($action === 'token') {
        echo "rendered before session startup\n";
        $token = auth_issue_submission_token('http:form');
        echo json_encode(['token' => $token]);
    }
    elseif ($action === 'consume') {
        $result = auth_consume_submission_token($_GET['token'] ?? null, 'http:form');
        echo json_encode(['result' => $result->name]);
    }
    else {
        echo json_encode(['active' => session_status() === PHP_SESSION_ACTIVE, 'user' => auth_get_user()->name]);
    }
