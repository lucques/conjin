<?
    require_once __DIR__ . '/auth_session.php';

    // Session-backed CSRF protection and duplicate detection for state-changing forms.
    enum SubmissionTokenResult {
        case Valid;
        case Duplicate;
        case Invalid;
    }

    function auth_submission_token_context(string $scope): string {
        return hash('sha256', serialize([auth_get_user()->get_user_identity(), $scope]));
    }

    // Limits are shared across all scopes in the session. Keep the session lock held
    // until the action finishes; releasing it early permits concurrent submissions.
    function auth_prune_submission_tokens(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new LogicException('Submission tokens require an active session.');
        }
        // Discard the previous namespace-based format; old forms must be reloaded.
        if (!isset($_SESSION['conjin_submission_tokens']['issued'], $_SESSION['conjin_submission_tokens']['consumed'])) {
            $_SESSION['conjin_submission_tokens'] = ['issued' => [], 'consumed' => []];
        }
        $bucket = &$_SESSION['conjin_submission_tokens'];
        $now = time();
        foreach (['issued' => 256, 'consumed' => 128] as $kind => $limit) {
            $bucket[$kind] = array_filter($bucket[$kind], static fn(array $record): bool => $record['expires'] > $now);
            $bucket[$kind] = array_slice($bucket[$kind], -$limit, null, true);
        }
    }

    function auth_issue_submission_token(string $scope): string {
        auth_ensure_session_started();
        auth_prune_submission_tokens();
        $token = bin2hex(random_bytes(32));
        $_SESSION['conjin_submission_tokens']['issued'][$token] = [
            'context' => auth_submission_token_context($scope),
            'expires' => time() + 7200
        ];
        auth_prune_submission_tokens();
        return $token;
    }

    // Consume an issued, matching token before validation or side effects. Every
    // rendered form receives a new token, including after an unsuccessful attempt.
    function auth_consume_submission_token(mixed $token, string $scope): SubmissionTokenResult {
        auth_resume_session_if_present();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return SubmissionTokenResult::Invalid;
        }
        auth_prune_submission_tokens();
        if (!is_string($token) || preg_match('/\A[0-9a-f]{64}\z/', $token) !== 1) {
            return SubmissionTokenResult::Invalid;
        }
        $bucket = &$_SESSION['conjin_submission_tokens'];
        $context = auth_submission_token_context($scope);
        if (isset($bucket['consumed'][$token]) && hash_equals($bucket['consumed'][$token]['context'], $context)) {
            return SubmissionTokenResult::Duplicate;
        }
        if (!isset($bucket['issued'][$token]) || !hash_equals($bucket['issued'][$token]['context'], $context)) {
            return SubmissionTokenResult::Invalid;
        }
        $bucket['consumed'][$token] = $bucket['issued'][$token];
        unset($bucket['issued'][$token]);
        auth_prune_submission_tokens();
        return SubmissionTokenResult::Valid;
    }
