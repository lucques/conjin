<?
    require dirname(__DIR__, 3) . '/composer-projects/conjin/src/conjin/inc/auth.php';

    require dirname(__DIR__, 3) . '/composer-projects/conjin/src/conjin/inc/target.php';

    function auth_test_login_profile(): LoginProfile {
        return new LoginProfile('test', [], 'test');
    }

    function get_global_config(string ...$keys): mixed {
        return match (implode('.', $keys)) {
            'https' => $GLOBALS['auth_test_https'] ?? false,
            'authorization.staticGuestUser' => 'guest',
            'authentication.users2passwordHashes' => ['alice' => $GLOBALS['auth_test_password_hash']],
            'authentication.openIdProviders' => [],
            default => throw new LogicException('Unexpected configuration key')
        };
    }

    function url(): string { return $GLOBALS['auth_test_url_base'] ?? '/'; }
    function redirect_see_other_and_exit(string $url): never {
        throw new RuntimeException('redirect:' . $url);
    }
    function process_login(LoginProfile $profile, bool $logout, bool $incorrect, mixed $failure, array $providers): never {
        $GLOBALS['auth_test_login_view'] = [$logout, $incorrect, $failure];
        throw new RuntimeException('render-login');
    }

    function send_response_and_exit(int $status): never {
        throw new RuntimeException('response:' . $status);
    }
