<?
namespace PHPMailer\PHPMailer {
    class PHPMailer {
        public static array $sent = [];
        public static string $mode = 'success';
        public string $CharSet = '';
        public string $Subject = '';
        public string $Body = '';
        public string $ErrorInfo = 'Simulated mail failure';
        private array $addresses = [];

        public function __construct(bool $exceptions = false) {}
        public function setFrom(string $address): void {}
        public function addAddress(string $address): void { $this->addresses[] = $address; }
        public function send(): bool {
            $db = new \PDO('sqlite:' . $GLOBALS['feedback_test_db']);
            \PHPUnit\Framework\Assert::assertTrue((int)$db->query('SELECT count(*) FROM submissions')->fetchColumn() > count(self::$sent), 'Submission must be committed before mail is attempted');
            self::$sent[] = ['addresses' => $this->addresses, 'subject' => $this->Subject, 'body' => $this->Body];
            if (self::$mode === 'throw') {
                throw new \RuntimeException('Simulated transport exception');
            }
            return self::$mode === 'success';
        }
    }
}

namespace Conjin\Modules\FeedbackCollector {
    function path_store(string $name): string { return $GLOBALS['feedback_test_db']; }
    function auth_is_logged_in(): bool { return $GLOBALS['feedback_test_logged_in']; }
    function auth_is_cur_user_privileged_for_custom_privilege(string $privilege): bool { return $GLOBALS['feedback_test_privileged']; }
    function auth_aux_serialize_user(array $identity): string { return json_encode($identity, JSON_THROW_ON_ERROR); }
    function auth_get_user_email(): ?string {
        $GLOBALS['feedback_test_email_reads']++;
        return $GLOBALS['feedback_test_author_email'];
    }
    function auth_get_user(): object {
        return new class {
            public function get_user_identity(): array { return ['tag' => 'Static', 'contents' => $GLOBALS['feedback_test_user']]; }
        };
    }
    function isset_global_config(string $key): bool { return $GLOBALS['feedback_test_mail_config']; }
    function get_global_config(string ...$keys): string { return 'sender@example.test'; }
    function log_error_silently(string $message): void { $GLOBALS['feedback_test_logs'][] = $message; }
}

namespace {
    require_once dirname(__DIR__, 3) . '/composer-projects/conjin/src/conjin/inc/auth_submission_tokens.php';
    function auth_get_user(): object { return \Conjin\Modules\FeedbackCollector\auth_get_user(); }
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    use Conjin\Modules\FeedbackCollector as F;
    use PHPMailer\PHPMailer\PHPMailer;

    require dirname(__DIR__, 3) . '/composer-projects/conjin/src/modules-shared/feedback-collector/processing.php';



    function config(string $title = 'IT feedback', F\Anonymity $anonymity = F\Anonymity::NonAnonymous): F\Config {
        return new F\Config('feedback.sqlite', 'recipient@example.test', new F\FormConfig($title, [
            new F\Question('WLAN?', new F\SingleChoice([
                new F\Option('Unproblematisch'),
                new F\Option('Problematisch', [
                    new F\Question('Welche Probleme?', new F\Text(max_length: 100), true),
                    new F\Question('Hotspot möglich?', new F\SingleChoice([
                        new F\Option('Ja', [new F\Question('Welches Netz?', new F\Text(multiline: false), true)]),
                        new F\Option('Nein')
                    ]), true),
                    new F\Question('Kommentar', new F\Text())
                ])
            ]), true),
            new F\Question('Geräte', new F\MultipleChoice([
                new F\Option('PC'),
                new F\Option('AppleTV'),
                new F\Option('Etwas anderes', [new F\Question('Was sonst?', new F\Text())])
            ]), true),
            new F\Question('Optional choice', new F\SingleChoice([new F\Option('Optional answer')])),
            new F\Question('Sonstiges <&>', new F\Text())
        ]), privilege_write: 'feedback_write', author_identity_to_label: function(string $id): string {
            $GLOBALS['feedback_test_label_reads']++;
            return 'Test User';
        }, anonymity: $anonymity);
    }

    function render(F\FeedbackCollector $collector, ?F\SubmissionResult $result = null): string {
        ob_start();
        $collector->print_form($result);
        return ob_get_clean();
    }

    function sectioned_config(F\Config $config): F\Config {
        $questions = $config->form->questions;
        return new F\Config($config->file_name, $config->email_address, new F\FormConfig($config->form->title, [
            new F\Section('WLAN <&>', [$questions[0]]),
            new F\Section('Equipment', [$questions[1], $questions[2]]),
            $questions[3]
        ]), privilege_write: $config->privilege_write, author_identity_to_label: $config->author_identity_to_label, anonymity: $config->anonymity);
    }

    function request_for(F\FeedbackCollector $collector, array $answers): array {
        $html = render($collector);
        preg_match('/name="feedback_form" value="([^"]+)"/', $html, $form);
        preg_match('/name="feedback_token" value="([^"]+)"/', $html, $token);
        return ['feedback_form' => $form[1], 'feedback_token' => $token[1], 'answers' => $answers];
    }

}
