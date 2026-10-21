<?
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class AuthSessionTest extends TestCase {
    protected function setUp(): void {
        require __DIR__ . '/../fixtures/auth-session.php';
        $_COOKIE = $_GET = $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SESSION);
        putenv('CONJIN_ENABLE_INTERNAL_AUTH=0');
        $GLOBALS['auth_test_password_hash'] = password_hash('correct', PASSWORD_DEFAULT);
    }

    protected function tearDown(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function testAnonymousVisitAndInvalidSubmissionStaySessionless(): void {
        auth_init();
        self::assertSame('guest', auth_get_user()->name);
        self::assertSame(PHP_SESSION_NONE, session_status());
        self::assertSame('', session_id());
        self::assertArrayNotHasKey('_SESSION', $GLOBALS);
        self::assertSame(SubmissionTokenResult::Invalid, auth_consume_submission_token(str_repeat('a', 64), 'form'));
        self::assertSame(PHP_SESSION_NONE, session_status());
    }

    public function testReturningUserResumesBeforeAuthorization(): void {
        auth_ensure_session_started();
        $_SESSION['user'] = new StaticResolvedUser('alice');
        $id = session_id();
        session_write_close();
        unset($_SESSION);
        $_COOKIE[session_name()] = $id;
        auth_init();
        self::assertSame(PHP_SESSION_ACTIVE, session_status());
        self::assertSame('alice', auth_get_user()->name);
        self::assertTrue(auth_is_logged_in());
    }

    public function testSessionPolicyRejectsUnknownIdsAndOverridesUnsafeDefaults(): void {
        session_cache_limiter('nocache');
        ini_set('session.use_strict_mode', '0');
        ini_set('session.use_only_cookies', '0');
        session_set_cookie_params(['lifetime' => 3600, 'domain' => 'example.test', 'httponly' => false, 'samesite' => 'None']);
        $unknown = bin2hex(random_bytes(24));
        session_id($unknown);
        auth_ensure_session_started();
        self::assertNotSame($unknown, session_id());
        self::assertSame('', session_cache_limiter());
        self::assertSame('1', ini_get('session.use_strict_mode'));
        self::assertSame('1', ini_get('session.use_only_cookies'));
        self::assertSame([
            'lifetime' => 0, 'path' => '/', 'domain' => '',
            'secure' => false, 'httponly' => true, 'samesite' => 'Lax',
        ], session_get_cookie_params());
    }

    public function testHttpsPolicySurvivesOidcStyleSessionReopening(): void {
        $GLOBALS['auth_test_https'] = true;
        auth_ensure_session_started();
        $id = session_id();
        session_write_close();
        session_set_cookie_params(['secure' => false, 'httponly' => false, 'samesite' => 'None']);
        auth_ensure_session_started();
        self::assertSame($id, session_id());
        self::assertTrue(session_get_cookie_params()['secure']);
        self::assertTrue(session_get_cookie_params()['httponly']);
        self::assertSame('Lax', session_get_cookie_params()['samesite']);
    }

    public function testBufferedFormStartsSessionOnceAndReplaysAreRejected(): void {
        auth_init();
        ob_start();
        try {
            echo '<html><body>Before the form';
            self::assertFalse(headers_sent());
            $token = auth_issue_submission_token('form');
            $id = session_id();
            auth_ensure_session_started();
            self::assertSame($id, session_id());
            self::assertSame(PHP_SESSION_ACTIVE, session_status());
            self::assertArrayNotHasKey('user', $_SESSION);
            self::assertSame(SubmissionTokenResult::Valid, auth_consume_submission_token($token, 'form'));
            self::assertSame(SubmissionTokenResult::Duplicate, auth_consume_submission_token($token, 'form'));
        }
        finally {
            ob_end_clean();
        }
    }

    public function testPasswordCookieDoesNotRequireSession(): void {
        $_COOKIE['password'] = 'correct';
        auth_init();
        self::assertSame('alice', auth_get_user()->name);
        self::assertSame(PHP_SESSION_NONE, session_status());
    }

    public function testInitialLoginPageStaysSessionless(): void {
        auth_init();
        try {
            auth_handle_login_and_exit(auth_test_login_profile());
            self::fail('Expected login form');
        }
        catch (RuntimeException $error) {
            self::assertSame('render-login', $error->getMessage());
        }
        self::assertSame([false, false, null], $GLOBALS['auth_test_login_view']);
        self::assertSame(PHP_SESSION_NONE, session_status());
        self::assertSame('', session_id());
    }

    public function testSuccessfulLoginCreatesPersistentSession(): void {
        auth_init();
        $_POST['password'] = 'correct';
        try {
            auth_handle_login_and_exit(auth_test_login_profile());
            self::fail('Expected redirect');
        }
        catch (RuntimeException $error) {
            self::assertSame('redirect:/', $error->getMessage());
        }
        self::assertSame(PHP_SESSION_ACTIVE, session_status());
        self::assertSame('alice', $_SESSION['user']->name);
        self::assertSame('alice', auth_get_user()->name);
    }

    public function testPasswordLoginRotatesExistingSessionAndInvalidatesOldId(): void {
        auth_init();
        auth_ensure_session_started();
        $_SESSION['redirect'] = '/task/';
        $_SESSION['preserved'] = 'existing state';
        $old_id = session_id();
        $_POST['password'] = 'correct';
        try {
            auth_handle_login_and_exit(auth_test_login_profile());
            self::fail('Expected redirect');
        }
        catch (RuntimeException $error) {
            self::assertSame('redirect:/task/', $error->getMessage());
        }
        $this->assertRotatedSession($old_id, 'alice');
    }

    public function testFailedPasswordLoginKeepsExistingSessionId(): void {
        auth_init();
        auth_ensure_session_started();
        $id = session_id();
        $_POST['password'] = 'wrong';
        try {
            auth_handle_login_and_exit(auth_test_login_profile());
            self::fail('Expected login form');
        }
        catch (RuntimeException $error) {
            self::assertSame('render-login', $error->getMessage());
        }
        self::assertSame($id, session_id());
        self::assertFalse(auth_is_logged_in());
        self::assertArrayNotHasKey('user', $_SESSION);
    }

    public function testOidcIdentityPersistenceReopensAndRotatesClosedSession(): void {
        auth_init();
        auth_ensure_session_started();
        $_SESSION['redirect'] = '/task/';
        $_SESSION['preserved'] = 'existing state';
        $old_id = session_id();
        session_write_close();
        // The OIDC client may close the session before callback persistence.
        auth_persist_authenticated_user(new OpenIdResolvedUser('provider', 'oidc-user', null, []));
        self::assertSame('/task/', $_SESSION['redirect']);
        $this->assertRotatedSession($old_id, 'oidc-user');
    }

    private function assertRotatedSession(string $old_id, string $identity): void {
        $new_id = session_id();
        self::assertNotSame($old_id, $new_id);
        self::assertSame($identity, auth_get_user_name());
        self::assertSame('existing state', $_SESSION['preserved']);
        session_write_close();
        unset($_SESSION);
        session_id($old_id);
        auth_ensure_session_started();
        self::assertArrayNotHasKey('user', $_SESSION);
        session_destroy();
        unset($_SESSION);
        session_id($new_id);
        $_COOKIE[session_name()] = $new_id;
        auth_init();
        self::assertSame($identity, auth_get_user_name());
        self::assertSame('existing state', $_SESSION['preserved']);
    }

    public function testLoginErrorsAndLogoutKeepExistingBehavior(): void {
        auth_init();
        $_POST['password'] = 'wrong';
        try {
            auth_handle_login_and_exit(auth_test_login_profile());
        }
        catch (RuntimeException $error) {
            self::assertSame('render-login', $error->getMessage());
        }
        self::assertSame([false, true, null], $GLOBALS['auth_test_login_view']);
        self::assertSame(PHP_SESSION_NONE, session_status());
        auth_ensure_session_started();
        $_SESSION['user'] = new StaticResolvedUser('alice');
        $GLOBALS['user'] = $_SESSION['user'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['logout_token'] = auth_issue_submission_token('auth:logout');
        try {
            auth_handle_logout_and_exit();
        }
        catch (RuntimeException $error) {
            self::assertSame('redirect:/login/', $error->getMessage());
        }
        self::assertSame('guest', $_SESSION['user']->name);
        self::assertTrue($_SESSION['logout_successful']);
    }

    public function testLogoutRejectsGetAndInvalidTokensWithoutChangingIdentity(): void {
        auth_ensure_session_started();
        $_SESSION['user'] = $GLOBALS['user'] = new StaticResolvedUser('alice');
        $valid = auth_issue_submission_token('auth:logout');
        $wrong_scope = auth_issue_submission_token('other');
        $expired = auth_issue_submission_token('auth:logout');
        $_SESSION['conjin_submission_tokens']['issued'][$expired]['expires'] = time() - 1;
        $consumed = auth_issue_submission_token('auth:logout');
        auth_consume_submission_token($consumed, 'auth:logout');
        $wrong_identity = auth_issue_submission_token('auth:logout');
        $_SESSION['conjin_submission_tokens']['issued'][$wrong_identity]['context'] = 'different identity';
        foreach ([['GET', $valid, 405], ['HEAD', $valid, 405], ['POST', null, 403], ['POST', [], 403], ['POST', str_repeat('a', 64), 403], ['POST', $wrong_scope, 403], ['POST', $expired, 403], ['POST', $consumed, 403], ['POST', $wrong_identity, 403]] as [$method, $token, $status]) {
            $_SERVER['REQUEST_METHOD'] = $method;
            $_POST = ['logout_token' => $token];
            try {
                auth_handle_logout_and_exit();
                self::fail('Expected rejection');
            }
            catch (RuntimeException $error) {
                self::assertSame('response:' . $status, $error->getMessage());
            }
            self::assertSame('alice', auth_get_user_name());
            self::assertSame('alice', $_SESSION['user']->name);
            self::assertArrayNotHasKey('logout_successful', $_SESSION);
        }
        self::assertSame(SubmissionTokenResult::Valid, auth_consume_submission_token($valid, 'auth:logout'));
    }

    public function testLogoutLeavesPasswordCookieAuthenticationUntouched(): void {
        $_COOKIE['password'] = 'correct';
        auth_init();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['logout_token'] = auth_issue_submission_token('auth:logout');
        try {
            auth_handle_logout_and_exit();
            self::fail('Expected redirect');
        }
        catch (RuntimeException $error) {
            self::assertSame('redirect:/login/', $error->getMessage());
        }
        self::assertSame('guest', $_SESSION['user']->name);
        self::assertSame('correct', $_COOKIE['password']);
        self::assertSame([], preg_grep('/^Set-Cookie: password=/i', headers_list()));
        auth_init();
        self::assertSame('alice', auth_get_user_name());
    }

    public function testClosedSessionCanBeReopenedBeforeAuthWrites(): void {
        auth_init();
        auth_ensure_session_started();
        $_SESSION['oidc_provider_name'] = 'provider';
        $id = session_id();
        session_write_close();
        auth_ensure_session_started();
        self::assertSame($id, session_id());
        self::assertSame('provider', $_SESSION['oidc_provider_name']);
    }

    public function testPasswordRedirectPrecedenceFallbackAndCleanup(): void {
        $GLOBALS['auth_test_url_base'] = '/app/';
        foreach ([['/app/task/?q=1#answer', '/app/task/?q=1#answer'], ['https://evil.example/', '/app/'], [[], '/app/'], [null, '/app/']] as [$input, $expected]) {
            auth_init();
            auth_ensure_session_started();
            $_SESSION['redirect'] = '/app/stale/';
            $_GET = ['redirect' => $input];
            $_POST = ['password' => 'correct'];
            try {
                auth_handle_login_and_exit(auth_test_login_profile());
                self::fail('Expected redirect');
            }
            catch (RuntimeException $error) {
                self::assertSame('redirect:' . $expected, $error->getMessage());
            }
            self::assertSame('alice', $_SESSION['user']->name);
            self::assertArrayNotHasKey('redirect', $_SESSION);
        }
    }

    public function testOidcDestinationCaptureAndFinalRevalidation(): void {
        auth_ensure_session_started();
        $_SESSION['redirect'] = '/stale/';
        $_GET = ['redirect' => '//evil.example/'];
        auth_capture_login_redirect();
        self::assertArrayNotHasKey('redirect', $_SESSION);
        $_GET = ['redirect' => '/task/?q=1#answer'];
        auth_capture_login_redirect();
        $_GET = [];
        auth_capture_login_redirect();
        self::assertSame('/task/?q=1#answer', $_SESSION['redirect']);
        foreach (['/task/?q=1#answer' => '/task/?q=1#answer', 'https://evil.example/' => '/'] as $stored => $expected) {
            $_SESSION['redirect'] = $stored;
            $_SESSION['oidc_provider_name'] = 'provider';
            $_SESSION['oidc_login_profile_id'] = 'test';
            try {
                auth_redirect_after_successful_login_and_exit();
                self::fail('Expected redirect');
            }
            catch (RuntimeException $error) {
                self::assertSame('redirect:' . $expected, $error->getMessage());
            }
            foreach (['redirect', 'oidc_provider_name', 'oidc_login_profile_id'] as $key) {
                self::assertArrayNotHasKey($key, $_SESSION);
            }
        }
    }

    public function testHttpCookieIsIssuedOnlyWhenNeededAndWorksOnNextRequest(): void {
        $listener = stream_socket_server('tcp://127.0.0.1:0');
        self::assertIsResource($listener);
        $address = stream_socket_get_name($listener, false);
        fclose($listener);
        $router = __DIR__ . '/../fixtures/auth-session-http.php';
        $process = proc_open([PHP_BINARY, '-d', 'short_open_tag=1', '-S', $address, $router], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        try {
            $ready = false;
            for ($i = 0; $i < 100; $i++) {
                $connection = @stream_socket_client('tcp://' . $address, $errno, $error, 0.05);
                if ($connection !== false) {
                    fclose($connection);
                    $ready = true;
                    break;
                }
                usleep(20000);
            }
            self::assertTrue($ready, 'PHP HTTP fixture did not start');
            $request = static function(string $query, ?string $cookie = null) use ($address): array {
                $context = stream_context_create(['http' => ['header' => $cookie === null ? '' : 'Cookie: ' . $cookie, 'timeout' => 5]]);
                $body = file_get_contents('http://' . $address . '/?' . $query, false, $context);
                return [$body, $http_response_header];
            };
            [$body, $headers] = $request('action=guest');
            self::assertSame(['active' => false, 'user' => 'guest'], json_decode($body, true));
            self::assertSame([], preg_grep('/^Set-Cookie:/i', $headers));
            [$body, $headers] = $request('action=login');
            self::assertSame(['active' => false, 'view' => [false, false, null]], json_decode($body, true));
            self::assertSame([], preg_grep('/^Set-Cookie:/i', $headers));
            [$body, $headers] = $request('action=consume&token=invented');
            self::assertSame('Invalid', json_decode($body, true)['result']);
            self::assertSame([], preg_grep('/^Set-Cookie:/i', $headers));
            [$body, $headers] = $request('action=token');
            self::assertStringStartsWith("rendered before session startup\n", $body);
            $token = json_decode(explode("\n", $body, 2)[1], true)['token'];
            $cookies = array_values(preg_grep('/^Set-Cookie:/i', $headers));
            self::assertCount(1, $cookies);
            self::assertStringContainsString('; HttpOnly', $cookies[0]);
            self::assertStringContainsString('; SameSite=Lax', $cookies[0]);
            self::assertStringContainsString('; path=/', $cookies[0]);
            self::assertStringNotContainsString('; secure', strtolower($cookies[0]));
            self::assertStringNotContainsString('domain=', strtolower($cookies[0]));
            self::assertStringNotContainsString('expires=', strtolower($cookies[0]));
            preg_match('/^Set-Cookie: ([^;]+)/i', $cookies[0], $match);
            $cookie = $match[1];
            [$body] = $request('action=guest', $cookie);
            self::assertTrue(json_decode($body, true)['active']);
            [$body] = $request('action=consume&token=' . $token, $cookie);
            self::assertSame('Valid', json_decode($body, true)['result']);
            [$body] = $request('action=consume&token=' . $token, $cookie);
            self::assertSame('Duplicate', json_decode($body, true)['result']);
            // Inspect HTTPS policy over the fixture transport without pretending
            // that an HTTP client will send a Secure cookie back automatically.
            [, $headers] = $request('action=token&https=1');
            $cookies = array_values(preg_grep('/^Set-Cookie:/i', $headers));
            self::assertCount(1, $cookies);
            self::assertStringContainsString('; secure', strtolower($cookies[0]));
            self::assertStringContainsString('; httponly', strtolower($cookies[0]));
            self::assertStringContainsString('; samesite=lax', strtolower($cookies[0]));
        }
        finally {
            proc_terminate($process);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        }
    }

    public function testHeadersAlreadySentProducesClearFailure(): void {
        $helper = dirname(__DIR__, 3) . '/composer-projects/conjin/src/conjin/inc/auth_session.php';
        $script = 'require ' . var_export($helper, true) . '; echo "sent:"; try { auth_ensure_session_started(); } catch (RuntimeException $e) { echo $e->getMessage(); exit(0); } exit(1);';
        $process = proc_open([PHP_BINARY, '-d', 'short_open_tag=1', '-d', 'output_buffering=0', '-r', $script], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $errors);
        self::assertStringContainsString('Cannot start session: headers already sent', $output);
    }

    public function testLoginLocationHeaderAndAuthenticatedSessionOverHttp(): void {
        $listener = stream_socket_server('tcp://127.0.0.1:0');
        $address = stream_socket_get_name($listener, false);
        fclose($listener);
        $process = proc_open([PHP_BINARY, '-d', 'short_open_tag=1', '-S', $address, __DIR__ . '/../fixtures/login-redirect-http.php'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        try {
            $ready = false;
            for ($i = 0; $i < 100; $i++) {
                $connection = @stream_socket_client('tcp://' . $address, $errno, $error, 0.05);
                if ($connection !== false) {
                    fclose($connection);
                    $ready = true;
                    break;
                }
                usleep(20000);
            }
            self::assertTrue($ready);
            foreach (['/app/task/?q=1#answer' => '/app/task/?q=1#answer', 'https://evil.example/' => '/app/', '/app/%252e%252e/other/' => '/app/'] as $input => $expected) {
                $context = stream_context_create(['http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => 'password=correct', 'follow_location' => 0, 'timeout' => 5]]);
                file_get_contents('http://' . $address . '/?redirect=' . rawurlencode($input), false, $context);
                self::assertContains('Cache-Control: no-store', $http_response_header);
                self::assertContains('Location: ' . $expected, $http_response_header);
                self::assertStringContainsString('303', $http_response_header[0]);
                $cookies = array_values(preg_grep('/^Set-Cookie:/i', $http_response_header));
                preg_match('/^Set-Cookie: ([^;]+)/i', $cookies[0], $match);
                $context = stream_context_create(['http' => ['header' => 'Cookie: ' . $match[1], 'timeout' => 5]]);
                $body = file_get_contents('http://' . $address . '/?check=1', false, $context);
                self::assertSame(['user' => 'alice', 'redirect_stored' => false], json_decode($body, true));
            }
            // Use the authenticated session from the final password-login request.
            $cookie = $match[1];
            $http = static function(string $query, string $method = 'GET', array $data = [], ?string $session_cookie = null) use ($address, $cookie): array {
                $context = stream_context_create(['http' => [
                    'method' => $method,
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\nCookie: " . ($session_cookie ?? $cookie),
                    'content' => http_build_query($data), 'follow_location' => 0, 'ignore_errors' => true, 'timeout' => 5,
                ]]);
                $body = file_get_contents('http://' . $address . '/?' . $query, false, $context);
                return [$body, $http_response_header];
            };
            [$body] = $http('logout_token=1');
            $token = json_decode($body, true)['token'];
            [, $headers] = $http('logout=1');
            self::assertStringContainsString('405', $headers[0]);
            self::assertContains('Allow: POST', $headers);
            foreach ([[], ['logout_token' => 'invented']] as $data) {
                [, $headers] = $http('logout=1', 'POST', $data);
                self::assertStringContainsString('403', $headers[0]);
            }
            // A separately issued session token cannot be used in this session.
            [$body] = $http('logout_token=1', 'GET', [], 'unrelated=1');
            $foreign = json_decode($body, true)['token'];
            [, $headers] = $http('logout=1', 'POST', ['logout_token' => $foreign]);
            self::assertStringContainsString('403', $headers[0]);
            [$body] = $http('check=1');
            self::assertSame('alice', json_decode($body, true)['user']);
            [, $headers] = $http('logout=1', 'POST', ['logout_token' => $token]);
            self::assertStringContainsString('303', $headers[0]);
            self::assertContains('Location: /app/login/', $headers);
            self::assertSame([], preg_grep('/^Set-Cookie: password=/i', $headers));
            [$body] = $http('check=1');
            self::assertSame('guest', json_decode($body, true)['user']);
            [, $headers] = $http('logout=1', 'POST', ['logout_token' => $token]);
            self::assertStringContainsString('403', $headers[0]);
            foreach (['/app/task/?q=1' => '/app/login/test/?redirect=%2Fapp%2Ftask%2F%3Fq%3D1', '//evil.example/' => '/app/login/test/'] as $stored => $expected) {
                $context = stream_context_create(['http' => ['follow_location' => 0, 'timeout' => 5]]);
                file_get_contents('http://' . $address . '/?retry=1&stored=' . rawurlencode($stored), false, $context);
                self::assertContains('Cache-Control: no-store', $http_response_header);
                self::assertContains('Location: ' . $expected, $http_response_header);
            }
        }
        finally {
            proc_terminate($process);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        }
    }
}
