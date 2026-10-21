<?
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class SubmissionTokensTest extends TestCase {
    protected function setUp(): void {
        require dirname(__DIR__, 3) . '/composer-projects/conjin/src/conjin/inc/auth.php';
        session_start();
        $_SESSION = [];
        $GLOBALS['user'] = new StaticResolvedUser('alice');
    }

    public function testIssuanceScopeIdentityAndConsumption(): void {
        $token = auth_issue_submission_token('test:edit:1');
        self::assertSame(SubmissionTokenResult::Invalid, auth_consume_submission_token(str_repeat('a', 64), 'test:edit:1'));
        foreach ([null, [], 42, 'bad', $token . 'x'] as $malformed) {
            self::assertSame(SubmissionTokenResult::Invalid, auth_consume_submission_token($malformed, 'test:edit:1'));
        }
        self::assertSame(SubmissionTokenResult::Invalid, auth_consume_submission_token($token, 'test:edit:2'));
        self::assertSame(SubmissionTokenResult::Invalid, auth_consume_submission_token($token, 'other:edit:1'));
        $GLOBALS['user'] = new StaticResolvedUser('bob');
        self::assertSame(SubmissionTokenResult::Invalid, auth_consume_submission_token($token, 'test:edit:1'));
        $GLOBALS['user'] = new StaticResolvedUser('alice');
        self::assertSame(SubmissionTokenResult::Valid, auth_consume_submission_token($token, 'test:edit:1'));
        self::assertSame(SubmissionTokenResult::Duplicate, auth_consume_submission_token($token, 'test:edit:1'));
        self::assertSame(SubmissionTokenResult::Invalid, auth_consume_submission_token($token, 'test:edit:2'));
    }

    public function testOtherSessionAndExpiry(): void {
        $token = auth_issue_submission_token('test:form');
        $original = $_SESSION;
        $_SESSION = [];
        self::assertSame(SubmissionTokenResult::Invalid, auth_consume_submission_token($token, 'test:form'));
        $_SESSION = $original;
        $_SESSION['conjin_submission_tokens']['issued'][$token]['expires'] = time() - 1;
        self::assertSame(SubmissionTokenResult::Invalid, auth_consume_submission_token($token, 'test:form'));
    }

    public function testTabsAndSharedLimits(): void {
        $first = auth_issue_submission_token('test:form');
        $second = auth_issue_submission_token('test:form');
        self::assertNotSame($first, $second);
        self::assertSame(SubmissionTokenResult::Valid, auth_consume_submission_token($first, 'test:form'));
        self::assertSame(SubmissionTokenResult::Valid, auth_consume_submission_token($second, 'test:form'));
        $oldest = auth_issue_submission_token('test:form');
        for ($i = 0; $i < 256; $i++) {
            auth_issue_submission_token('other:form');
        }
        self::assertCount(256, $_SESSION['conjin_submission_tokens']['issued']);
        self::assertSame(SubmissionTokenResult::Invalid, auth_consume_submission_token($oldest, 'test:form'));
        for ($i = 0; $i < 130; $i++) {
            $token = auth_issue_submission_token('test:form');
            self::assertSame(SubmissionTokenResult::Valid, auth_consume_submission_token($token, 'test:form'));
        }
        self::assertCount(128, $_SESSION['conjin_submission_tokens']['consumed']);
        self::assertSame(SubmissionTokenResult::Invalid, auth_consume_submission_token($first, 'test:form'));
    }

}
