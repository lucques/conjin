<?
use PHPUnit\Framework\TestCase;

final class LoginRedirectTest extends TestCase {
    public function testDestinationsAfterOuterQueryDecoding(): void {
        require_once __DIR__ . '/../fixtures/auth-session.php';
        foreach (['/', '/app/'] as $base) {
            $GLOBALS['auth_test_url_base'] = $base;
            foreach (['', null, [], 42, 'relative/', 'https://attacker.example/', 'https://site.example/app/', '//attacker.example/', '/\\attacker.example/', '/%5cattacker.example/', '/%255cattacker.example/', '/app/../other/', '/app/%2e%2e/other/', '/app/%252e%252e/other/', '/app/%2f../other/', '/app/%252fother/', '/app/%', '/app/%zz', "/app/\r\nLocation:evil", '/app/%0d%0aevil', '/app/%250aevil', '/app/?q=%5c', '/app/#%00'] as $input) {
                self::assertNull(auth_validate_login_redirect($input), var_export([$base, $input], true));
            }
            foreach ([$base, $base . 'courses/task/?solution=true#answer', $base . '?next=https%3A%2F%2Fexample.com', $base . 'file%20name/?q=hello%20world'] as $input) {
                // Model the encoding of a redirect inside the original login URL.
                parse_str(http_build_query(['redirect' => $input]), $query);
                self::assertSame($input, auth_validate_login_redirect($query['redirect']));
            }
        }
        $GLOBALS['auth_test_url_base'] = '/app/';
        self::assertSame('/app', auth_validate_login_redirect('/app'));
        foreach (['/other/', '/application/', '/app-other/'] as $input) {
            self::assertNull(auth_validate_login_redirect($input));
        }
        unset($GLOBALS['auth_test_url_base']);
    }
}
