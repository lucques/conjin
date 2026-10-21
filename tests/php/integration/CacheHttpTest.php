<?
use PHPUnit\Framework\TestCase;

final class CacheHttpTest extends TestCase {
    public function testResourcePoliciesValidatorsAndRedirectsOverHttp(): void {
        $listener = stream_socket_server('tcp://127.0.0.1:0');
        self::assertIsResource($listener);
        $address = stream_socket_get_name($listener, false);
        fclose($listener);
        $process = proc_open([PHP_BINARY, '-d', 'short_open_tag=1', '-S', $address, __DIR__ . '/../fixtures/cache-http.php'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
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
            self::assertTrue($ready);
            $request = static function(string $query = '', array $headers = [], string $method = 'GET') use ($address): array {
                $context = stream_context_create(['http' => [
                    'header' => implode("\r\n", $headers), 'method' => $method,
                    'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 5,
                ]]);
                $body = file_get_contents('http://' . $address . '/?' . $query, false, $context);
                return [$body, $http_response_header];
            };
            foreach (['' => 'private', 'public=1' => 'public'] as $query => $policy) {
                [$body, $headers] = $request($query);
                self::assertSame('resource', $body);
                self::assertContains('Cache-Control: ' . $policy . ', no-cache', $headers);
                $modified = array_values(preg_grep('/^Last-Modified:/i', $headers));
                self::assertCount(1, $modified);
                $condition = 'If-Modified-Since:' . substr($modified[0], strlen('Last-Modified:'));
                foreach (['GET', 'HEAD'] as $method) {
                    [$body, $headers] = $request($query, [$condition], $method);
                    self::assertStringContainsString('304', $headers[0]);
                    self::assertSame('', $body);
                    self::assertContains($modified[0], $headers);
                    self::assertContains('Cache-Control: ' . $policy . ', no-cache', $headers);
                }
                foreach (['If-Modified-Since: invalid', 'If-Modified-Since: Wed, 01 Jan 2020 00:00:00 GMT'] as $condition) {
                    [$body, $headers] = $request($query, [$condition]);
                    self::assertStringContainsString('200', $headers[0]);
                    self::assertSame('resource', $body);
                }
                [$body, $headers] = $request($query, ['If-Modified-Since: Wed, 01 Jan 2031 00:00:00 GMT', 'If-None-Match: "unknown"']);
                self::assertStringContainsString('200', $headers[0]);
                [$body, $headers] = $request($query, [], 'HEAD');
                self::assertSame('', $body);
                self::assertStringContainsString('200', $headers[0]);
                [, $headers] = $request($query, [], 'POST');
                self::assertStringContainsString('405', $headers[0]);
                self::assertContains('Cache-Control: no-store', $headers);
            }
            foreach (['deny=1' => '403', 'sensitive=1' => '200'] as $query => $status) {
                [, $headers] = $request($query, ['If-Modified-Since: Wed, 01 Jan 2031 00:00:00 GMT']);
                self::assertStringContainsString($status, $headers[0]);
                self::assertContains('Cache-Control: no-store', $headers);
                self::assertSame([], preg_grep('/^Last-Modified:/i', $headers));
            }
            foreach ([301, 302, 303] as $status) {
                [, $headers] = $request('redirect=' . $status);
                self::assertStringContainsString((string) $status, $headers[0]);
                self::assertContains('Cache-Control: no-store', $headers);
                self::assertContains('Location: /destination', $headers);
                self::assertSame([], preg_grep('/^Last-Modified:/i', $headers));
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
