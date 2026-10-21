<?
// Explicit API only: activating this module never records an action.
final class MatomoHttpApi {
    private static array $queue = [];
    private static bool $registered = false;
    private static int $spent_ns = 0;

    private static array $seen = [];

    public static function for_context(Processable $context): Module {
        return $context->get_activated_modules()['matomo-http-api'];
    }

    public static function is_localhost(string $url): bool {
        $host = strtolower(rtrim(parse_url($url, PHP_URL_HOST) ?? '', '.'));
        return $host === 'localhost' || str_ends_with($host, '.localhost') || $host === '[::1]'
            || (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false && str_starts_with($host, '127.'));
    }

    public static function enabled(Module $module): bool {
        if (!$module->config->get('enabled') || !$module->config->get('endpoint')
            || !$module->config->get('site_id') || !extension_loaded('curl')) {
            return false;
        }
        return !$module->config->get('exclude_localhost') || !self::is_localhost(url_full());
    }

    public static function is_get_request(Module $module): bool {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && self::enabled($module);
    }

    // The caller chooses the identity of an action; state is isolated by module.
    public static function once(Module $module, string $key): bool {
        $key = $module->name . ':' . $key;
        if (isset(self::$seen[$key])) {
            return false;
        }
        self::$seen[$key] = true;
        return true;
    }

    public static function request_path(): string {
        return '/' . ltrim(explode('?', $_SERVER['REQUEST_URI'] ?? '/', 2)[0], '/');
    }

    // Explicit opt-in convenience: enrich a Conjin page view with request context.
    // The lower-level page_view/event/track methods never add visitor context.
    public static function track_target(Target $target): void {
        try {
            $api = self::for_context($target);
            if (!self::is_get_request($api) || !self::once($api, 'page')) {
                return;
            }
            $parameters = [
                'urlref' => $_SERVER['HTTP_REFERER'] ?? '',
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'lang' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            ];
            // A hosting proxy may still be the connection peer; ignore forwarded headers.
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $token = $api->config->get('token_auth');
            if (is_string($token) && $token !== '' && is_string($ip)
                && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) !== false) {
                $parameters['cip'] = $ip;
            }
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['matomo_http_api_visitor_id'] ??= bin2hex(random_bytes(8));
                $parameters['_id'] = $_SESSION['matomo_http_api_visitor_id'];
            }
            $title = $target->has_activated_module('title')
                ? ($target->get_activated_modules()['title']->config->get('plain') ?? implode('/', $target->get_ids()))
                : implode('/', $target->get_ids());
            self::page_view($api, url_full_collect($target->get_ids()), $title, $parameters, range(200, 299));
        } catch (Throwable) {
            // Analytics must not interrupt the page response.
        }
    }

    public static function page_view(Module $module, string $url, string $title, array $parameters = [], ?array $statuses = null): void {
        self::track($module, ['url' => $url, 'action_name' => $title] + $parameters, $statuses);
    }

    public static function event(Module $module, string $category, string $action, string $name, array $parameters = [], ?array $statuses = null): void {
        self::track($module, ['e_c' => $category, 'e_a' => $action, 'e_n' => $name, 'ca' => 1] + $parameters, $statuses);
    }

    // null statuses means immediate delivery; an array schedules shutdown delivery.
    public static function track(Module $module, array $parameters, ?array $statuses = null): void {
        try {
            $endpoint = $module->config->get('endpoint');
            $site = $module->config->get('site_id');
            if (!self::enabled($module)) {
                return;
            }
            // Authentication and destination belong to configuration, never extra parameters.
            unset($parameters['token_auth'], $parameters['endpoint']);
            $token = $module->config->get('token_auth');
            if (is_string($token) && $token !== '') {
                $parameters['token_auth'] = $token;
            }
            $entry = [$endpoint, ['idsite' => $site, 'rec' => 1, 'apiv' => 1, 'send_image' => 0] + $parameters, $statuses];
            if ($statuses === null) {
                self::send($entry);
                return;
            }
            self::$queue[] = $entry;
            if (!self::$registered) {
                self::$registered = true;
                register_shutdown_function([self::class, 'flush']);
            }
        } catch (Throwable) {
            // Tracking must never interrupt the caller.
        }
    }

    public static function flush(): void {
        $entries = self::$queue;
        self::$queue = [];
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
            return;
        }
        $status = http_response_code() ?: 200;
        foreach ($entries as $entry) {
            if (in_array($status, $entry[2], true)) {
                self::send($entry);
            }
        }
    }

    private static function send(array $entry): void {
        $remaining = intdiv(500000000 - self::$spent_ns, 1000000);
        if ($remaining < 1) {
            return;
        }
        $start = hrtime(true);
        $handle = null;
        try {
            $handle = curl_init($entry[0]);
            if ($handle === false) {
                return;
            }
            curl_setopt_array($handle, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($entry[1], '', '&', PHP_QUERY_RFC3986),
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT_MS => min(200, $remaining),
                CURLOPT_TIMEOUT_MS => $remaining,
                CURLOPT_WRITEFUNCTION => static fn($handle, string $data): int => strlen($data),
            ]);
            curl_exec($handle);
        } catch (Throwable) {
            // Best effort, without retries.
        } finally {
            if ($handle !== null && $handle !== false) {
                curl_close($handle);
            }
            self::$spent_ns += hrtime(true) - $start;
        }
    }
}
