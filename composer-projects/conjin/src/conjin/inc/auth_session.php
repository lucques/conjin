<?
    // All framework session startup goes through this auth layer. Third-party
    // OIDC code may close/reopen sessions, so always check PHP's current status.
    function auth_ensure_session_started(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        if (headers_sent($file, $line)) {
            throw new RuntimeException("Cannot start session: headers already sent at $file:$line.");
        }
        // Use deployment policy rather than request/proxy headers to select HTTPS.
        // Apply again when reopening a session closed by the OIDC client.
        foreach (['session.use_strict_mode', 'session.use_only_cookies'] as $setting) {
            if (ini_set($setting, '1') === false) {
                throw new RuntimeException("Could not configure $setting.");
            }
        }
        if (!session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => get_global_config('https'),
            'httponly' => true,
            'samesite' => 'Lax',
        ])) {
            throw new RuntimeException('Could not configure the authentication session cookie.');
        }
        // Conjin controls response caching independently of session startup.
        if (session_cache_limiter('') === false) {
            throw new RuntimeException('Could not disable the automatic session cache limiter.');
        }
        if (!session_start()) {
            throw new RuntimeException('Could not start the authentication session.');
        }
    }

    function auth_resume_session_if_present(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $cookie = $_COOKIE[session_name()] ?? null;
        if (is_string($cookie) && $cookie !== '') {
            auth_ensure_session_started();
        }
    }
