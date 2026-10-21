<?
// Standalone regression test: no site configuration or external network required.
require_once dirname(__DIR__) . '/processing.php';

// The API only needs a configured Module; use minimal doubles to keep this test portable.
class Module {
    public object $config;
    public function __construct(array $values) {
        $this->config = new class($values) {
            public function __construct(private array $values) {}
            public function get(string $key): mixed { return $this->values[$key] ?? null; }
        };
    }
}

if (($argv[1] ?? '') === 'child') {
    [$script, $child, $endpoint, $mode] = $argv;
    $api = new Module(['enabled' => $mode !== 'disabled', 'exclude_localhost' => false, 'endpoint' => $mode === 'empty' ? null : $endpoint, 'site_id' => 7, 'token_auth' => in_array($mode, ['authenticated', 'authenticated-immediate'], true) ? 'fixture-token' : null]);
    $_SERVER['HTTP_USER_AGENT'] = 'Do not forward';
    $_SERVER['HTTP_REFERER'] = 'https://private.example/';
    $_SERVER['REMOTE_ADDR'] = '192.0.2.123';
    http_response_code(200);
    if ($mode === 'activation') {
        // Loading the API above is the only action.
    } elseif ($mode === 'budget') {
        // An unresponsive collector consumes the shared budget on the first request.
        MatomoHttpApi::event($api, 'Test', 'Immediate', 'first');
        MatomoHttpApi::event($api, 'Test', 'Deferred', 'second', [], [200]);
        MatomoHttpApi::event($api, 'Test', 'Deferred', 'third', [], [200]);
    } elseif (in_array($mode, ['immediate', 'authenticated-immediate'], true)) {
        MatomoHttpApi::page_view($api, 'https://example.test/', 'Grüße & SQL', ['idsite' => 99, 'rec' => 0, 'token_auth' => 'forbidden', 'endpoint' => 'https://forbidden.example/']);
        // The collector must receive the immediate event despite the later 500.
        http_response_code(500);
    } elseif ($mode === 'fatal') {
        MatomoHttpApi::event($api, 'Test', 'Deferred', 'fatal', [], [200]);
        trigger_error('Expected fixture fatal', E_USER_ERROR);
    } elseif ($mode === 'mismatch') {
        MatomoHttpApi::event($api, 'Test', 'Deferred', 'mismatch', [], [304]);
    } else {
        MatomoHttpApi::event($api, 'Test', 'Deferred', 'a & b', ['token_auth' => 'forbidden'], [200]);
    }
    if (session_status() !== PHP_SESSION_NONE) {
        throw new RuntimeException('API started a session');
    }
    echo 'done';
    exit;
}

function verify(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}
verify(extension_loaded('curl'), 'Run this test with PHP cURL enabled');
$listener = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
verify($listener !== false, $error);
$endpoint = 'http://' . stream_socket_get_name($listener, false) . '/matomo.php';
try {
    foreach (['activation', 'disabled', 'empty', 'mismatch', 'fatal', 'deferred', 'immediate', 'budget', 'authenticated', 'authenticated-immediate'] as $mode) {
        $start = microtime(true);
        $process = proc_open([PHP_BINARY, '-d', 'short_open_tag=1', '-d', 'display_errors=stderr', __FILE__, 'child', $endpoint, $mode], [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $expected = in_array($mode, ['deferred', 'immediate', 'budget', 'authenticated', 'authenticated-immediate'], true);
        $connection = @stream_socket_accept($listener, $expected ? 2 : 0.15);
        $parameters = null;
        if ($connection !== false) {
            stream_set_timeout($connection, 2);
            verify(trim(fgets($connection)) === 'POST /matomo.php HTTP/1.1', 'Wrong HTTP request');
            $length = 0;
            while (($header = fgets($connection)) !== false && trim($header) !== '') {
                if (stripos($header, 'Content-Length:') === 0) { $length = (int)substr($header, 15); }
            }
            $body = '';
            while (strlen($body) < $length) {
                $chunk = fread($connection, $length - strlen($body));
                verify($chunk !== false && $chunk !== '', 'Incomplete body');
                $body .= $chunk;
            }
            parse_str($body, $parameters);
            if ($mode !== 'budget') {
                fwrite($connection, "HTTP/1.1 204 No Content\r\nContent-Length: 0\r\nConnection: close\r\n\r\n");
                fclose($connection);
            }
        }
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $exit = proc_close($process);
        if (is_resource($connection)) { fclose($connection); }
        verify(($parameters !== null) === $expected, 'Unexpected tracking: ' . $mode);
        verify($mode === 'fatal' ? $exit !== 0 : ($exit === 0 && $errors === '' && $output === 'done'), 'Caller failed: ' . $mode . $errors);
        if ($parameters !== null) {
            verify($parameters['idsite'] === '7' && $parameters['rec'] === '1', 'Protected fields overridden');
            verify(($parameters['token_auth'] ?? null) === (in_array($mode, ['authenticated', 'authenticated-immediate'], true) ? 'fixture-token' : null), 'Configuration token missing or overridden');
            foreach (['_id', 'uid', 'ua', 'urlref', 'cip', 'lang', 'endpoint'] as $field) {
                verify(!isset($parameters[$field]), 'Implicit or protected field: ' . $field);
            }
        }
        if ($mode === 'deferred') { verify($parameters['e_n'] === 'a & b', 'Encoding failed'); }
        if ($mode === 'immediate') { verify($parameters['action_name'] === 'Grüße & SQL', 'Page title encoding failed'); }
        if ($mode === 'budget') { verify(microtime(true) - $start < 0.9, 'Immediate/deferred budget multiplied'); }
        $extra = @stream_socket_accept($listener, 0.02);
        verify($extra === false, 'Unexpected extra request: ' . $mode);
    }
    echo "Explicit API, protected fields, immediate/deferred delivery, fatal errors, session isolation, and shared budget passed.\n";
} finally {
    fclose($listener);
}
