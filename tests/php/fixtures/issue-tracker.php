<?
namespace PHPMailer\PHPMailer {
    // Capture deliveries without contacting a mail server.
    class PHPMailer {
        public static array $sent = [];
        public static bool $sendSuccessful = true;
        public string $ErrorInfo = 'Fake delivery failure';
        public string $CharSet = '';
        public string $Subject = '';
        public string $Body = '';
        private array $addresses = [];

        function setFrom(string $address): bool { return true; }
        function addAddress(string $address): bool { $this->addresses[] = $address; return true; }
        function send(): bool {
            self::$sent[] = ['addresses' => $this->addresses, 'subject' => $this->Subject, 'body' => $this->Body];
            return self::$sendSuccessful;
        }
    }
}

namespace Conjin\Modules\IssueTracker {
    function send_response_and_exit(int $status, string $message): never {
        throw new \RuntimeException($message, $status);
    }
    function path_store(string $file_name): string { return ':memory:'; }
    function auth_is_cur_user_privileged_for_custom_privilege(string $privilege): bool { return $privilege !== 'admin' || ($GLOBALS['test_admin'] ?? true); }
    function auth_aux_serialize_user(array $identity): string { return json_encode($identity); }
    function auth_get_user(): object {
        return new class {
            function get_user_identity(): array { return ['test-user']; }
        };
    }
    function auth_get_user_email(): string { return $GLOBALS['test_author_email']; }
    function isset_global_config(string $key): bool { return $key === 'mail'; }
    function test_get_global_config(string ...$keys): mixed {
        return $keys[0] === 'authentication' ? [['name' => 'test-provider']] : 'sender@example.test';
    }
    function log_error_silently(string $message): void { $GLOBALS['test_mail_errors'][] = $message; }
    function next_unique_id(): int { static $id = 0; return ++$id; }
    function url_full_collect(array $ids): string { return 'https://example.test/issues/'; }

    // Bind the stub only at runtime so editors resolve the real configuration function.
    eval('namespace ' . __NAMESPACE__ . '; function get_global_config(...$keys): mixed { return test_get_global_config(...$keys); }');
}

namespace {
    require_once dirname(__DIR__, 3) . '/composer-projects/conjin/src/conjin/inc/auth_submission_tokens.php';
    function auth_get_user(): object { return \Conjin\Modules\IssueTracker\auth_get_user(); }
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    use Conjin\Modules\IssueTracker as I;
    use PHPMailer\PHPMailer\PHPMailer;

    require dirname(__DIR__, 3) . '/composer-projects/conjin/src/modules-shared/issue-tracker/processing.php';



    function check_notification(I\IssueTracker $tracker, object $result, array $expected, array $block_recipients): void {
        \PHPUnit\Framework\Assert::assertTrue($result instanceof I\SuccessWithMailsNIR || $result instanceof I\SuccessWithMailsNPR, 'Submission must succeed with mail');
        \PHPUnit\Framework\Assert::assertTrue($result->recipients === $expected, 'Recipients must combine all sources and distinct reasons');
        \PHPUnit\Framework\Assert::assertTrue($block_recipients === $expected, 'Mail callback must receive the same recipients as the result');
        $delivery = PHPMailer::$sent[array_key_last(PHPMailer::$sent)];
        \PHPUnit\Framework\Assert::assertTrue($delivery['addresses'] === array_column($expected, 'email_address'), 'Each recipient must receive mail exactly once');
        $footer = explode("\n", explode("\n\n", $delivery['body'], 2)[1]);
        \PHPUnit\Framework\Assert::assertTrue(count($footer) === count($expected), 'Mail footer must show one entry per recipient');

        ob_start();
        if ($result instanceof I\SuccessWithMailsNIR) {
            $tracker->print_handle_new_issue_res($result);
        }
        else {
            $tracker->print_handle_new_post_res($result);
        }
        $html = ob_get_clean();
        \PHPUnit\Framework\Assert::assertTrue(str_contains($html, 'alert-success'), 'Submission must render a success alert');
        \PHPUnit\Framework\Assert::assertTrue(!str_contains($html, '<Room & A>'), 'Tag names must be escaped in the alert');
        preg_match_all('~<li>(.*?)</li>~s', $html, $matches);
        \PHPUnit\Framework\Assert::assertTrue(count($matches[1]) === count($expected), 'Alert must show one entry per recipient');
        foreach ($expected as $index => $recipient) {
            $alert_text = html_entity_decode(strip_tags($matches[1][$index]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            foreach ([$recipient['email_address'], ...$recipient['reasons']] as $text) {
                \PHPUnit\Framework\Assert::assertTrue(str_contains($footer[$index], $text), 'Mail footer must retain every email and reason');
                \PHPUnit\Framework\Assert::assertTrue(str_contains($alert_text, $text), 'Alert must retain every email and reason');
            }
        }
    }

    function check_template_tags(array $tag_names, string $subject_prefix, string $content): void {
        $delivery = PHPMailer::$sent[array_key_last(PHPMailer::$sent)];
        \PHPUnit\Framework\Assert::assertTrue($delivery['subject'] === $subject_prefix . ' / ' . json_encode($tag_names), 'Subject template must receive the issue ID, summary when applicable, and current tag names');
        $body = explode("\n\n", $delivery['body'], 2)[0];
        \PHPUnit\Framework\Assert::assertTrue($body === $content . "\nTags: " . json_encode($tag_names), 'Body template must receive the content and current tag names');
    }
}
