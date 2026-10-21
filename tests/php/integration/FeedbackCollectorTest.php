<?
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Conjin\Modules\FeedbackCollector as F;
use PHPMailer\PHPMailer\PHPMailer;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
#[\PHPUnit\Framework\Attributes\Group('module:feedback-collector')]
final class FeedbackCollectorTest extends TestCase {
    private array $fixture;

    protected function setUp(): void {
        require __DIR__ . '/../fixtures/feedback.php';
        require __DIR__ . '/../fixtures/feedback-state.php';
        $collector = new F\FeedbackCollector(config());
        $db = new PDO('sqlite:' . $GLOBALS['feedback_test_db']);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $base = ['q0' => 'o0', 'q1' => ['o0']];
        $this->fixture = [$collector, $db, $base];
    }

    protected function tearDown(): void {
        unlink($GLOBALS['feedback_test_db']);
    }

    public function testUnrelatedRequests(): void {
        [$collector, $db, $base] = $this->fixture;
        $_POST = [];
        self::assertTrue($collector->handle_submission() === null, 'Unrelated requests must be ignored');
    }

    public function testRequiredFollowUps(): void {
        [$collector, $db, $base] = $this->fixture;
        $_POST = request_for($collector, []);
        $result = $collector->handle_submission();
        self::assertTrue($result->kind === F\SubmissionResultKind::Invalid && isset($result->errors['q0'], $result->errors['q1']), 'Required top-level questions must be validated');
        $_POST = request_for($collector, ['q0' => 'o1', 'q1' => ['o0']]);
        $result = $collector->handle_submission();
        self::assertTrue(isset($result->errors['q0-o1-q0'], $result->errors['q0-o1-q1']), 'Selected branches must require their follow-ups');
        $_POST = request_for($collector, $_POST['answers'] + ['q0-o1-q0' => 'Slow', 'q0-o1-q1' => 'o0']);
        $result = $collector->handle_submission();
        self::assertTrue(isset($result->errors['q0-o1-q1-o0-q0']), 'Nested follow-ups must be validated recursively');
    }

    public function testInvalidAttemptIsConsumedAndFreshFormCanSubmit(): void {
        [$collector, $db, $base] = $this->fixture;
        $_POST = request_for($collector, []);
        self::assertSame(F\SubmissionResultKind::Invalid, $collector->handle_submission()->kind);
        $_POST['answers'] = $base;
        self::assertSame(F\SubmissionResultKind::Duplicate, $collector->handle_submission()->kind);
        self::assertSame(0, (int)$db->query('SELECT count(*) FROM submissions')->fetchColumn());
        $_POST = request_for($collector, $base);
        self::assertSame(F\SubmissionResultKind::Saved, $collector->handle_submission()->kind);
    }

    public function testMalformedAnswers(): void {
        [$collector, $db, $base] = $this->fixture;
        foreach ([
            ['q0' => 'forged', 'q1' => ['o0']],
            ['q0' => ['o0'], 'q1' => ['o0']],
            ['q0' => 'o0', 'q1' => 'o0'],
            ['q0' => 'o0', 'q1' => [['o0']]],
            ['q0' => 'o0', 'q1' => ['unknown']],
            $base + ['q3' => ['nested']],
            $base + ['q3' => "\xFF"],
            ['q0' => 'o1', 'q0-o1-q0' => str_repeat('ü', 101), 'q0-o1-q1' => 'o1', 'q1' => ['o0']]
        ] as $invalid) {
            $_POST = request_for($collector, $invalid);
            self::assertTrue($collector->handle_submission()->kind === F\SubmissionResultKind::Invalid, 'Malformed input must be rejected');
        }
        self::assertTrue((int)$db->query('SELECT count(*) FROM submissions')->fetchColumn() === 0 && PHPMailer::$sent === [], 'Invalid requests must neither persist nor notify');
    }

    public function testTokensBelongToIdentity(): void {
        [$collector, $db, $base] = $this->fixture;
        $_POST = request_for($collector, $base);
        $_POST['feedback_token'] = 'forged';
        self::assertTrue($collector->handle_submission()->kind === F\SubmissionResultKind::Expired, 'Unissued tokens must be rejected');
        $_POST = request_for($collector, $base);
        $GLOBALS['feedback_test_user'] = 'another-teacher';
        self::assertTrue($collector->handle_submission()->kind === F\SubmissionResultKind::Expired, 'Tokens must be bound to the identity');
        $GLOBALS['feedback_test_user'] = 'teacher';
    }

    public function testAuthorization(): void {
        [$collector, $db, $base] = $this->fixture;
        foreach (['feedback_test_logged_in', 'feedback_test_privileged'] as $flag) {
            $_POST = request_for($collector, $base);
            $GLOBALS[$flag] = false;
            self::assertTrue($collector->handle_submission()->kind === F\SubmissionResultKind::Forbidden, 'Unauthorized submission must be denied');
            self::assertTrue(!str_contains(render($collector), '<form'), 'Unauthorized users must not receive a form');
            $GLOBALS[$flag] = true;
        }
    }

    public function testEscapesRestoredText(): void {
        [$collector, $db, $base] = $this->fixture;
        $_POST = request_for($collector, ['q0' => 'o1', 'q0-o1-q0' => '<script>alert("x")</script>', 'q1' => ['o0']]);
        $result = $collector->handle_submission();
        $html = render($collector, $result);
        self::assertTrue(str_contains($html, '&lt;script&gt;') && !str_contains($html, '<script>alert'), 'Validation rerender must retain and escape submitted text');
    }

    public function testPersistsSnapshotAndRejectsReplay(): void {
        [$collector, $db, $base] = $this->fixture;
        $_POST = request_for($collector, $base + ['q0-o1-q0' => ['malicious'], 'q0-o1-q1-o0-q0' => 'hidden-secret', 'q3' => 'Danke!']);
        $result = $collector->handle_submission();
        self::assertTrue($result->kind === F\SubmissionResultKind::Saved && $result->submission_id === 1, 'Valid submission must save and send');
        $first = $db->query('SELECT * FROM submissions')->fetch();
        $snapshot = json_decode($first['questions_answers'], true, flags: JSON_THROW_ON_ERROR);
        self::assertTrue($snapshot['author_label'] === 'Test User' && $snapshot['title'] === 'IT feedback', 'Snapshot must preserve title and author label');
        self::assertTrue($snapshot['anonymous'] === false && $snapshot['author_email'] === 'teacher-private@example.test', 'Identified submissions must snapshot the authenticated email address');
        self::assertTrue($first['author_identity'] === '{"tag":"Static","contents":"teacher"}' && str_ends_with($first['created_on'], 'Z'), 'Authenticated identity and UTC timestamp must be saved');
        $children = $snapshot['questions'][0]['options'][1]['follow_up_questions'];
        self::assertTrue(!$children[0]['active'] && $children[0]['answer'] === null, 'Inactive branch input must be discarded, while its wording remains snapshotted');
        $mail = PHPMailer::$sent[0];
        self::assertTrue($mail['addresses'] === ['recipient@example.test'], 'Send only to the configured recipient');
        self::assertTrue(str_contains($mail['body'], 'E-Mail-Adresse: teacher-private@example.test'), 'Identified notifications must include the email address');
        foreach (['WLAN?', 'Welche Probleme?', 'Hotspot möglich?', 'Welches Netz?', 'Unproblematisch', 'Danke!', 'teacher'] as $text) {
            self::assertTrue(str_contains($mail['body'], $text), 'Email must include every question, all options, answers, and identity');
        }
        self::assertTrue(!str_contains($mail['body'], 'hidden-secret'), 'Unselected branch answers must not leak into email');
        self::assertTrue(str_contains($mail['body'], F\FeedbackCollector::questions_answers_to_text($snapshot)), 'Email must render the stored snapshot');
        self::assertTrue($collector->handle_submission()->kind === F\SubmissionResultKind::Duplicate && count(PHPMailer::$sent) === 1, 'Replaying a POST must not create another submission or email');
    }

    public function testFreshTokensAndNestedAnswers(): void {
        [$collector, $db, $base] = $this->fixture;
        $_POST = request_for($collector, $base);
        $collector->handle_submission();
        $first = $db->query('SELECT * FROM submissions')->fetch();
        $snapshot = json_decode($first['questions_answers'], true);
        $_POST = request_for($collector, ['q0' => 'o1', 'q0-o1-q0' => 'Slow in room 1', 'q0-o1-q1' => 'o0', 'q0-o1-q1-o0-q0' => 'LTE', 'q0-o1-q2' => 'Optional', 'q1' => ['o1', 'o0', 'o0']]);
        self::assertTrue($collector->handle_submission()->kind === F\SubmissionResultKind::Saved, 'Same identity may submit again with a fresh token');
        $second = json_decode($db->query('SELECT questions_answers FROM submissions WHERE submission_id = 2')->fetchColumn(), true);
        self::assertTrue(count(array_filter($second['questions'][1]['options'], fn(array $option): bool => $option['selected'])) === 2, 'Multiple choices must retain every selection without duplicates');
        self::assertTrue(str_contains(PHPMailer::$sent[1]['body'], 'LTE'), 'Nested answers must appear in the notification');
    }

    public function testNotificationFailuresPreserveSubmissions(): void {
        [$collector, $db, $base] = $this->fixture;
        foreach (['failure', 'throw'] as $mode) {
            PHPMailer::$mode = $mode;
            $_POST = request_for($collector, $base);
            $result = $collector->handle_submission();
            self::assertTrue($result->kind === F\SubmissionResultKind::SavedMailFailed, 'Mail failure must remain a saved submission');
            ob_start();
            $collector->print_submission_result($result);
            $html = ob_get_clean();
            self::assertTrue(str_contains($html, 'gespeichert') && str_contains($html, 'nicht erneut'), 'Mail failure must clearly tell the submitter the entry was saved');
        }
        $GLOBALS['feedback_test_mail_config'] = false;
        $_POST = request_for($collector, $base);
        self::assertTrue($collector->handle_submission()->kind === F\SubmissionResultKind::SavedMailFailed, 'Missing mail configuration must still preserve submissions');
        self::assertTrue(count($GLOBALS['feedback_test_logs']) === 3, 'Notification failures must be logged');
        self::assertTrue((int)$db->query('SELECT count(*) FROM submissions')->fetchColumn() === 3, 'All successful saves, including mail failures, must persist');
    }

    public function testChangedConfigurationPreservesSnapshots(): void {
        [$collector, $db, $base] = $this->fixture;
        $_POST = request_for($collector, $base);
        $collector->handle_submission();
        $first = $db->query('SELECT * FROM submissions')->fetch();
        $snapshot = json_decode($first['questions_answers'], true);
        $_POST = request_for($collector, $base);
        $changed = new F\FeedbackCollector(config('Changed form'));
        self::assertTrue($changed->handle_submission()->kind === F\SubmissionResultKind::Expired, 'An old displayed form must not silently use changed configuration');
        self::assertTrue($db->query('SELECT * FROM submissions WHERE submission_id = 1')->fetch() === $first, 'Reopening with changed configuration must leave old submissions untouched');
        self::assertTrue(str_starts_with(F\FeedbackCollector::questions_answers_to_text($snapshot), 'IT feedback'), 'Old submissions must remain readable without their original config');
        self::assertTrue($db->query("SELECT name FROM sqlite_master WHERE type = 'table'")->fetchAll(PDO::FETCH_COLUMN) === ['submissions'], 'Database must contain exactly one table');
        self::assertTrue(array_column($db->query('PRAGMA table_info(submissions)')->fetchAll(), 'name') === ['submission_id', 'author_identity', 'created_on', 'questions_answers'], 'Schema must omit email notification state');
    }

    public function testDatabaseFailureAllowsRetry(): void {
        [$collector, $db, $base] = $this->fixture;
        $_POST = request_for($collector, $base);
        $db->exec("CREATE TRIGGER reject_submission BEFORE INSERT ON submissions BEGIN SELECT RAISE(FAIL, 'Simulated disk error'); END");
        self::assertTrue($collector->handle_submission()->kind === F\SubmissionResultKind::ErrorDb, 'Database failure must return a retryable error');
        self::assertSame(F\SubmissionResultKind::Duplicate, $collector->handle_submission()->kind);
        $db->exec('DROP TRIGGER reject_submission');
        $_POST = request_for($collector, $base);
        $GLOBALS['feedback_test_mail_config'] = true;
        PHPMailer::$mode = 'success';
        self::assertTrue($collector->handle_submission()->kind === F\SubmissionResultKind::Saved, 'A fresh form must permit retry after a database failure');
    }

    public function testRepeatedFieldsAndReorderedForms(): void {
        [$collector, $db, $base] = $this->fixture;
        // Reused objects and duplicate labels still represent independent fields at each position.
        $repeated_question = new F\Question('Repeated prompt', new F\Text());
        $repeated_option = new F\Option('Repeated choice', [$repeated_question]);
        $repeated = new F\FeedbackCollector(new F\Config('feedback.sqlite', 'recipient@example.test', new F\FormConfig('Repeated labels', [
            $repeated_question,
            new F\Question('Repeated prompt', new F\MultipleChoice([$repeated_option, $repeated_option])),
            $repeated_question
        ])));
        $html = render($repeated);
        preg_match_all('/\bid="([^"]+)"/', $html, $ids);
        self::assertTrue(count(array_unique($ids[1])) === count($ids[1]), 'Repeated labels must not produce duplicate HTML IDs');
        $answers = ['q0' => 'First', 'q1' => ['o0', 'o1'], 'q1-o0-q0' => 'Left branch', 'q1-o1-q0' => 'Right branch', 'q2' => 'Last'];
        $_POST = request_for($repeated, $answers);
        $result = $repeated->handle_submission();
        self::assertTrue($result->kind === F\SubmissionResultKind::Saved, 'Forms without configured IDs must save independently addressed repeated questions');
        $stored = json_decode($db->query('SELECT questions_answers FROM submissions ORDER BY submission_id DESC LIMIT 1')->fetchColumn(), true);
        self::assertTrue($stored['questions'][0]['answer'] === 'First' && $stored['questions'][2]['answer'] === 'Last', 'Repeated question instances must preserve separate top-level answers');
        self::assertTrue($stored['questions'][1]['options'][0]['follow_up_questions'][0]['answer'] === 'Left branch' &&
            $stored['questions'][1]['options'][1]['follow_up_questions'][0]['answer'] === 'Right branch', 'Repeated options must preserve separate nested answers');

        $_POST = request_for($repeated, $answers);
        $reordered = new F\FeedbackCollector(new F\Config('feedback.sqlite', 'recipient@example.test', new F\FormConfig('Repeated labels', [
            $repeated->config->form->questions[1], $repeated_question, $repeated_question
        ])));
        $result = $reordered->handle_submission();
        self::assertTrue($result->kind === F\SubmissionResultKind::Expired && $result->values === [], 'Reordered questions must reject old forms without restoring answers to new positions');
        self::assertTrue(!str_contains(render($reordered, $result), 'Left branch'), 'Expired answers must not appear under new question labels');
    }

    public function testAnonymityModes(): void {
        [$collector, $db, $base] = $this->fixture;
        $optional = new F\FeedbackCollector(config(anonymity: F\Anonymity::Optional));
        $anonymous_only = new F\FeedbackCollector(config(anonymity: F\Anonymity::Anonymous));
        $optional_html = render($optional);
        self::assertTrue(str_contains($optional_html, 'data-feedback-anonymous checked'), 'Optional anonymity must be selected by default');
        self::assertTrue(preg_match_all('/type="radio"[^>]*name="feedback_anonymous"/', $optional_html) === 2, 'Optional anonymity must offer two radio buttons in one group');
        self::assertTrue(str_contains($optional_html, 'Mit Name (teacher-private@example.test)'), 'The identified option must display the authenticated email address');
        self::assertTrue(!str_contains(render($collector), 'data-feedback-anonymous') && !str_contains(render($anonymous_only), 'data-feedback-anonymous'), 'Fixed anonymity modes must not render an anonymity choice');

        foreach ([
            [$optional, null, true],
            [$optional, '1', true],
            [$optional, '0', false],
            [$anonymous_only, '0', true],
            [$collector, '1', false]
        ] as [$subject, $posted_choice, $expected_anonymous]) {
            $_POST = request_for($subject, $base);
            if ($posted_choice !== null) {
                $_POST['feedback_anonymous'] = $posted_choice;
            }
            $email_reads = $GLOBALS['feedback_test_email_reads'];
            $label_reads = $GLOBALS['feedback_test_label_reads'];
            $result = $subject->handle_submission();
            self::assertTrue($result->kind === F\SubmissionResultKind::Saved, 'Each configured anonymity mode must accept a valid submission');
            $row = $db->query('SELECT * FROM submissions ORDER BY submission_id DESC LIMIT 1')->fetch();
            $saved = json_decode($row['questions_answers'], true);
            $delivery = PHPMailer::$sent[array_key_last(PHPMailer::$sent)];
            self::assertTrue($saved['anonymous'] === $expected_anonymous, 'The server must enforce fixed anonymity modes and honor the optional choice');
            ob_start();
            $subject->print_submission_result($result);
            $confirmation = ob_get_clean();
            self::assertTrue(str_contains($confirmation, '<strong>anonym</strong>') === $saved['anonymous'], 'The confirmation must emphasize anonymity only when the saved submission is anonymous');
            self::assertTrue(str_contains($confirmation, 'per E-Mail an ' . $delivery['addresses'][0] . ' versendet.'), 'The confirmation must name the notification recipient');
            self::assertTrue(!str_contains($confirmation, 'Nr. '), 'The confirmation must omit the submission number');
            if ($expected_anonymous) {
                self::assertTrue($row['author_identity'] === '' && $saved['author_label'] === 'Anonym' && $saved['author_email'] === null, 'Anonymous rows must omit identifying metadata');
                self::assertTrue($GLOBALS['feedback_test_email_reads'] === $email_reads && $GLOBALS['feedback_test_label_reads'] === $label_reads, 'Anonymous submissions must not retrieve the email or call the identity-label callback');
                foreach (['teacher', 'Test User', 'teacher-private@example.test', 'Identität:', 'E-Mail-Adresse:'] as $private_text) {
                    self::assertTrue(!str_contains($row['questions_answers'], $private_text) && !str_contains($delivery['body'], $private_text), 'Anonymous snapshots and notifications must not contain identifying metadata');
                }
                self::assertTrue(str_contains($delivery['body'], 'Absender: Anonym'), 'Anonymous notifications must identify their anonymity');
            }
            else {
                self::assertTrue($row['author_identity'] === '{"tag":"Static","contents":"teacher"}' && $saved['author_email'] === 'teacher-private@example.test', 'Non-anonymous submissions must retain their identity and email');
                self::assertTrue(str_contains($delivery['body'], 'teacher-private@example.test'), 'Non-anonymous notifications must include the authenticated email');
            }
            self::assertTrue(str_contains(render($optional, $result), 'data-feedback-anonymous checked'), 'The next fresh form must default to anonymous again');
        }
    }

    public function testAnonymityValidation(): void {
        [$collector, $db, $base] = $this->fixture;
        $optional = new F\FeedbackCollector(config(anonymity: F\Anonymity::Optional));
        foreach (['0', '1'] as $choice) {
            $_POST = request_for($optional, []);
            $_POST['feedback_anonymous'] = $choice;
            $result = $optional->handle_submission();
            self::assertTrue($result->kind === F\SubmissionResultKind::Invalid && $result->anonymous === ($choice === '1'), 'Validation errors must retain the anonymity choice');
            $restored_html = render($optional, $result);
            self::assertTrue(str_contains($restored_html, 'data-feedback-anonymous checked') === ($choice === '1') &&
                str_contains($restored_html, 'data-feedback-identified checked') === ($choice === '0'), 'The radios must restore exactly the submitted choice after validation errors');
        }
        foreach ([[], ['0'], 'unexpected'] as $invalid_choice) {
            $_POST = request_for($optional, $base);
            $_POST['feedback_anonymous'] = $invalid_choice;
            $result = $optional->handle_submission();
            self::assertTrue($result->kind === F\SubmissionResultKind::Invalid && $result->anonymous === true, 'Malformed anonymity choices must fail safely');
        }
    }

    public function testChangedPrivacyInvalidatesTokens(): void {
        [$collector, $db, $base] = $this->fixture;
        $optional = new F\FeedbackCollector(config(anonymity: F\Anonymity::Optional));
        $_POST = request_for($optional, $base);
        $_POST['feedback_anonymous'] = '1';
        $mail_count = count(PHPMailer::$sent);
        $row_count = (int)$db->query('SELECT count(*) FROM submissions')->fetchColumn();
        self::assertTrue($collector->handle_submission()->kind === F\SubmissionResultKind::Expired, 'Changing from optional to non-anonymous must invalidate previously displayed forms');
        self::assertTrue(count(PHPMailer::$sent) === $mail_count && (int)$db->query('SELECT count(*) FROM submissions')->fetchColumn() === $row_count, 'Expired privacy choices must not persist or send anything');
    }

    public function testMissingEmailAndEscapedIdentity(): void {
        [$collector, $db, $base] = $this->fixture;
        $optional = new F\FeedbackCollector(config(anonymity: F\Anonymity::Optional));
        $GLOBALS['feedback_test_author_email'] = null;
        $_POST = request_for($collector, $base);
        self::assertTrue($collector->handle_submission()->kind === F\SubmissionResultKind::Saved, 'Accounts without an email address must still be able to submit');
        self::assertTrue(str_contains(PHPMailer::$sent[array_key_last(PHPMailer::$sent)]['body'], 'E-Mail-Adresse: (Nicht verfügbar.)'), 'A missing authenticated email must never be invented');
        self::assertTrue(str_contains(render($optional), 'Mit Name (Test User)'), 'Without an email, the identified option must show the configured identity label');
        $escaped_identity = new F\FeedbackCollector(new F\Config('feedback.sqlite', 'recipient@example.test', config()->form,
            author_identity_to_label: fn(string $id): string => 'Teacher <&>', anonymity: F\Anonymity::Optional));
        self::assertTrue(str_contains(render($escaped_identity), 'Mit Name (Teacher &lt;&amp;&gt;)'), 'The identity in the radio label must be HTML-escaped');
    }

    public function testSectionMetadataAndFieldNames(): void {
        [$collector, $db, $base] = $this->fixture;
        $sectioned = new F\FeedbackCollector(sectioned_config(config()));
        $html = render($sectioned);
        self::assertTrue(substr_count($html, 'data-feedback-section') === 2 && str_contains($html, 'WLAN &lt;&amp;&gt;'), 'Sections must render titled cards with escaped titles');
        preg_match_all('/name="(answers\[[^"]+)"/', render($collector), $plain_names);
        preg_match_all('/name="(answers\[[^"]+)"/', $html, $section_names);
        self::assertTrue($plain_names[1] === $section_names[1], 'Adding visual section wrappers must not change any question field names');
        self::assertTrue(str_contains($html, 'Mit * markierte Fragen sind Pflichtfelder.'), 'Forms with required questions in sections must show the required notice');
        self::assertTrue(str_contains($html, 'Problematisch <em>(enthält Folgefragen)</em>') && str_contains($html, 'Etwas anderes <em>(enthält Folgefrage)</em>'), 'Options must show the correctly inflected follow-up hint in their labels');
        $_POST = request_for($sectioned, $base);
        self::assertTrue($sectioned->handle_submission()->kind === F\SubmissionResultKind::Saved, 'Wrapped questions must accept the same answers as unwrapped questions');
        $snapshot = json_decode($db->query('SELECT questions_answers FROM submissions ORDER BY submission_id DESC LIMIT 1')->fetchColumn(), true);
        self::assertTrue($snapshot['questions'][0]['type'] === 'section' && $snapshot['questions'][0]['title'] === 'WLAN <&>', 'Snapshots must retain section titles as layout metadata');
        self::assertTrue($snapshot['questions'][0]['questions'][0]['id'] === 'q0' && $snapshot['questions'][1]['questions'][0]['id'] === 'q1', 'Section wrappers must not change stored question IDs');
        self::assertTrue(str_contains(PHPMailer::$sent[array_key_last(PHPMailer::$sent)]['body'], 'WLAN <&>'), 'Mail must retain section titles from the snapshot');
    }

    public function testNestedSections(): void {
        [$collector, $db, $base] = $this->fixture;
        $layout = new F\FeedbackCollector(new F\Config('feedback.sqlite', 'recipient@example.test', new F\FormConfig('Optional sections', [
            new F\Section('Outer', [
                new F\Question('Introduction', new F\Text()),
                new F\Section('Inner', [
                    new F\Question('Choice', new F\MultipleChoice([
                        new F\Option('Zero'),
                        new F\Option('One', [new F\Section('One detail', [new F\Question('Detail', new F\Text())])]),
                        new F\Option('Many', [new F\Section('Two details', [new F\Question('First detail', new F\Text()), new F\Question('Second detail', new F\Text())])])
                    ]))
                ])
            ]),
            new F\Question('After the sections', new F\Text())
        ])));
        $html = render($layout);
        self::assertTrue(!str_contains($html, 'Mit * markierte Fragen sind Pflichtfelder.'), 'Fully optional forms must omit the required notice');
        self::assertTrue(str_contains($html, 'One <em>(enthält Folgefrage)</em>') && str_contains($html, 'Many <em>(enthält Folgefragen)</em>'), 'Follow-up counts must count questions inside sections, not the section wrappers');
        self::assertTrue(!str_contains($html, 'Zero <em>'), 'Options without follow-up questions must not show a hint');
        $_POST = request_for($layout, ['q0' => 'Before', 'q1' => ['o1', 'o2'], 'q1-o1-q0' => 'One answer', 'q1-o2-q0' => 'First answer', 'q1-o2-q1' => 'Second answer', 'q2' => 'After']);
        self::assertTrue($layout->handle_submission()->kind === F\SubmissionResultKind::Saved, 'Nested sections and conditional sections must validate and save');
        $snapshot = json_decode($db->query('SELECT questions_answers FROM submissions ORDER BY submission_id DESC LIMIT 1')->fetchColumn(), true);
        $choice = $snapshot['questions'][0]['questions'][1]['questions'][0];
        self::assertTrue($choice['id'] === 'q1' && $choice['options'][2]['follow_up_questions'][0]['questions'][1]['answer'] === 'Second answer' && $snapshot['questions'][1]['id'] === 'q2', 'Nested layout groups must preserve the answer hierarchy and field numbering');
        self::assertTrue(!isset($snapshot['questions'][0]['id']) && !isset($snapshot['questions'][0]['answer']), 'Sections must not introduce answer fields');
    }

    public function testConditionalRequiredSections(): void {
        [$collector, $db, $base] = $this->fixture;
        $conditional_required = new F\FeedbackCollector(new F\Config('feedback.sqlite', 'recipient@example.test', new F\FormConfig('Conditional requirement', [
            new F\Question('Optional choice', new F\SingleChoice([
                new F\Option('More', [new F\Section('Details', [new F\Question('Required detail', new F\Text(), required: true)])])
            ]))
        ])));
        self::assertTrue(str_contains(render($conditional_required), 'Mit * markierte Fragen sind Pflichtfelder.'), 'Required questions in inactive conditional sections must still trigger the notice');
        $_POST = request_for($conditional_required, ['q0' => 'o0']);
        $result = $conditional_required->handle_submission();
        self::assertTrue($result->kind === F\SubmissionResultKind::Invalid && isset($result->errors['q0-o0-q0']), 'Required questions inside selected conditional sections must still validate');
        $_POST = request_for($conditional_required, []);
        self::assertTrue($conditional_required->handle_submission()->kind === F\SubmissionResultKind::Saved, 'An inactive section must not activate its required questions');
    }

}
