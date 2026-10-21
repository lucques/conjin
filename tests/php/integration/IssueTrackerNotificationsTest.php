<?
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Conjin\Modules\IssueTracker as I;
use PHPMailer\PHPMailer\PHPMailer;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
#[\PHPUnit\Framework\Attributes\Group('module:issue-tracker')]
final class IssueTrackerNotificationsTest extends TestCase {
    private array $blockRecipients = [];

    private function fixture(): array {
        require __DIR__ . '/../fixtures/issue-tracker.php';
        $this->blockRecipients = [];
        $mail = new I\MailConfig(
            always_notify_addresses: [' Shared@Example.test ', 'shared@example.test', 'always@example.test', '', null],
            new_issue_template_subject: fn(int $issue_id, string $issue_summary, array $tag_names): string =>
                'New issue #' . $issue_id . ': ' . $issue_summary . ' / ' . json_encode($tag_names),
            new_issue_template_body: fn(int $issue_id, string $view_issue_url, string $date, string $author, string $content, array $tag_names): string =>
                $content . "\nTags: " . json_encode($tag_names),
            new_post_template_subject: fn(int $issue_id, array $tag_names): string =>
                'New post #' . $issue_id . ' / ' . json_encode($tag_names),
            new_post_template_body: fn(int $issue_id, string $view_issue_url, string $date, string $author, string $content, string $new_status, array $previous_posts, array $tag_names): string =>
                $content . "\nTags: " . json_encode($tag_names),
            notification_block: function(array $recipients): string {
                $this->blockRecipients = $recipients;
                return implode("\n", array_map(
                    fn($recipient) => $recipient['email_address'] . ' (' . implode('; ', $recipient['reasons']) . ')',
                    $recipients
                ));
            },
            reason_tag_additional_email: '{email_address} is responsible for {tag_name}',
            reason_always_notify: 'Always notified',
            reason_participates_in_issue: 'Participates in issue'
        );
        $tags = [
            ['id' => 'a', 'name' => '<Room & A>', 'additional_email_addresses' => ['shared@example.test', 'tag@example.test']],
            ['id' => 'b', 'name' => 'Room B', 'additional_email_addresses' => ['SHARED@example.test', 'TAG@example.test']],
            ['id' => 'no-mail', 'name' => 'Room without notifications'],
            ['id' => 'other', 'name' => 'Other', 'additional_email_addresses' => ['unrelated@example.test']]
        ];
        $tracker = new I\IssueTracker(new I\Config(
            file_name: 'unused.db',
            view_target_ids: ['issues'],
            tags: $tags,
            privilege_admin: 'admin',
            privilege_write: 'write',
            may_edit_own_content_within_n_minutes: 0,
            view: new I\ViewConfig('', '', '', '', '', 'Issue', 'issue', 'Post', 'post', 'Tags', 'Tag', 'Solved', 'Open', fn($s) => $s, fn($s) => $s, fn($s) => $s),
            mail: $mail
        ));
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $GLOBALS['test_author_email'] = 'SHARED@example.test';
        $_POST = ['req' => 'new-issue', 'content' => 'Example issue', 'tags' => ['b', 'a', 'b', 'no-mail', 'unknown']];
        $_POST['request_uuid'] = auth_issue_submission_token(serialize(['issue-tracker', $tracker->config->file_name, $_POST['req'], in_array($_POST['req'], ['new-post', 'new-additional-email'], true) ? (int)$_POST['issue_id'] : null]));
        $result = $tracker->handle_new_issue($tags);
        $expected = [
            ['email_address' => 'Shared@Example.test', 'reasons' => [
                'Always notified', 'Participates in issue',
                'Shared@Example.test is responsible for <Room & A>',
                'Shared@Example.test is responsible for Room B'
            ]],
            ['email_address' => 'always@example.test', 'reasons' => ['Always notified']],
            ['email_address' => 'tag@example.test', 'reasons' => [
                'tag@example.test is responsible for <Room & A>',
                'tag@example.test is responsible for Room B'
            ]]
        ];
        $expected_tag_names = ['<Room & A>', 'Room B', 'Room without notifications'];
        $db = (new ReflectionProperty(I\IssueTracker::class, 'db'))->getValue($tracker);
        return [$tracker, $tags, $expected, $expected_tag_names, $db, $result];
    }

    public function testAllActionsRejectUnissuedTokensWithoutSideEffects(): void {
        [$tracker, $tags, , , $db] = $this->fixture();
        $before = $db->query('SELECT * FROM issue')->fetchAll();
        $posts = $db->query('SELECT * FROM post')->fetchAll();
        $mail_count = count(PHPMailer::$sent);
        foreach (['new-issue', 'new-post', 'new-additional-email', 'new-tag', 'edit-tag', 'mail-tags', 'edit-issue', 'edit-post', 'delete-issue', 'delete-post', 'delete-additional-email'] as $action) {
            $_POST = ['req' => $action, 'request_uuid' => str_repeat('a', 64),
                'issue_id' => '1', 'post_id' => '1', 'additional_email_id' => '1',
                'content' => 'Forged', 'summary' => 'Forged', 'subject' => 'Forged',
                'new_status' => 'closed', 'email_address' => 'forged@example.test',
                'tag_id' => 'a', 'name' => 'Forged'];
            $handler = 'handle_' . str_replace('-', '_', $action);
            try {
                if (in_array($action, ['new-issue', 'edit-issue'], true)) {
                    $tracker->$handler($tags);
                }
                else {
                    $tracker->$handler();
                }
                self::fail($action . ' did not reject the unissued token');
            }
            catch (RuntimeException $error) {
                self::assertSame(403, $error->getCode(), $action);
            }
        }
        self::assertSame($before, $db->query('SELECT * FROM issue')->fetchAll());
        self::assertSame($posts, $db->query('SELECT * FROM post')->fetchAll());
        self::assertSame($mail_count, count(PHPMailer::$sent));
    }

    public function testScopedTokensRejectOtherActionsAndSuppressReplay(): void {
        [$tracker, $tags, , , $db] = $this->fixture();
        $issue_token = new ReflectionMethod($tracker, 'issue_submission_token');
        $token = $issue_token->invoke($tracker, 'new-issue');
        $_POST = ['req' => 'new-post', 'issue_id' => '1', 'content' => 'Wrong scope', 'new_status' => 'open', 'request_uuid' => $token];
        try {
            $tracker->handle_new_post();
            self::fail('A different action accepted the token');
        }
        catch (RuntimeException $error) {
            self::assertSame(403, $error->getCode());
        }
        $_POST = ['req' => 'new-issue', 'content' => 'Valid issue', 'request_uuid' => $token];
        self::assertInstanceOf(I\SuccessWithMailsNIR::class, $tracker->handle_new_issue($tags));
        $count = (int)$db->query('SELECT count(*) FROM issue')->fetchColumn();
        $mail_count = count(PHPMailer::$sent);
        self::assertNull($tracker->handle_new_issue($tags));
        self::assertSame($count, (int)$db->query('SELECT count(*) FROM issue')->fetchColumn());
        self::assertSame($mail_count, count(PHPMailer::$sent));
    }

    public function testCreationEmailBatchAttributionAndFutureNotifications(): void {
        [$tracker, $tags, , , $db] = $this->fixture();
        PHPMailer::$sent = [];
        $GLOBALS['test_author_email'] = 'admin@example.test';
        $_POST = [
            'req' => 'new-issue', 'content' => 'Batch issue', 'tags' => ['a'],
            'additional_email_addresses' => "First@example.test; second@example.test\nFIRST@example.test shared@example.test",
            'set_other_openid_author' => '1', 'author_openid_provider' => 'test-provider',
            'author_openid_id' => 'requester', 'author_openid_email' => 'requester@example.test'
        ];
        $_POST['request_uuid'] = auth_issue_submission_token(serialize(['issue-tracker', $tracker->config->file_name, $_POST['req'], in_array($_POST['req'], ['new-post', 'new-additional-email'], true) ? (int)$_POST['issue_id'] : null]));
        $result = $tracker->handle_new_issue($tags);
        self::assertInstanceOf(I\SuccessWithMailsNIR::class, $result);
        $events = $db->query('SELECT * FROM issue_x_additional_email WHERE issue_id = 2 ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        self::assertSame(['First@example.test', 'second@example.test', 'shared@example.test'], array_column($events, 'email_address'));
        self::assertSame(['admin@example.test'], array_values(array_unique(array_column($events, 'author_email'))));
        self::assertSame(['["test-user"]'], array_values(array_unique(array_column($events, 'author_identity'))));
        $addresses = array_column($result->recipients, 'email_address');
        foreach (['First@example.test', 'second@example.test', 'admin@example.test', 'requester@example.test'] as $address) {
            self::assertContains($address, $addresses);
        }
        self::assertCount(1, PHPMailer::$sent);
        self::assertSame($addresses, PHPMailer::$sent[0]['addresses']);
        self::assertSame($result->recipients, $this->blockRecipients);
        $shared = array_values(array_filter($result->recipients, fn($r) => strtolower($r['email_address']) === 'shared@example.test'));
        self::assertCount(1, $shared);
        self::assertContains('Participates in issue', $shared[0]['reasons']);
        self::assertContains('Always notified', $shared[0]['reasons']);

        $_POST = ['req' => 'new-post', 'issue_id' => '2', 'content' => 'Reply', 'new_status' => 'open'];
        $_POST['request_uuid'] = auth_issue_submission_token(serialize(['issue-tracker', $tracker->config->file_name, $_POST['req'], in_array($_POST['req'], ['new-post', 'new-additional-email'], true) ? (int)$_POST['issue_id'] : null]));
        $reply = $tracker->handle_new_post();
        self::assertSame($addresses, array_column($reply->recipients, 'email_address'));
        $issues = $tracker->fetch_issues_with_posts_open();
        $issue = array_values(array_filter($issues, fn($i) => $i['id'] == 2))[0];
        self::assertCount(3, $issue['additional_emails']);
        self::assertSame('admin@example.test', $issue['additional_emails'][0]['author_email']);
    }

    public function testInvalidCreationEmailsPreserveFormAndCreateNothing(): void {
        [$tracker, $tags, , , $db] = $this->fixture();
        PHPMailer::$sent = [];
        $_POST = [
            'req' => 'new-issue', 'content' => '<Keep this>', 'tags' => ['b'],
            'additional_email_addresses' => 'good@example.test; bad<address; GOOD@example.test'
        ];
        $_POST['request_uuid'] = auth_issue_submission_token(serialize(['issue-tracker', $tracker->config->file_name, $_POST['req'], in_array($_POST['req'], ['new-post', 'new-additional-email'], true) ? (int)$_POST['issue_id'] : null]));
        $result = $tracker->handle_new_issue($tags);
        self::assertInstanceOf(I\ErrorIncompleteNIR::class, $result);
        self::assertSame(['bad<address'], $result->invalid_email_addresses);
        self::assertSame(['good@example.test', 'bad<address'], $result->additional_email_addresses);
        self::assertSame(1, (int)$db->query('SELECT COUNT(*) FROM issue')->fetchColumn());
        self::assertSame([], PHPMailer::$sent);
        ob_start();
        $tracker->print_new_issue_form($tags, $result);
        $html = ob_get_clean();
        self::assertStringContainsString('&lt;Keep this&gt;', $html);
        self::assertStringContainsString('good@example.test,bad&lt;address', $html);
        self::assertStringContainsString('Ungültige E-Mail-Adressen: bad&lt;address', $html);
        self::assertStringContainsString('value="b" selected', $html);
        self::assertStringContainsString('new TomSelect', $html);
    }

    public function testNonAdminCreationIgnoresCraftedEmailBatch(): void {
        [$tracker, $tags, , , $db] = $this->fixture();
        $GLOBALS['test_admin'] = false;
        $_POST = ['req' => 'new-issue', 'content' => 'Ordinary issue', 'additional_email_addresses' => 'invalid; attacker@example.test'];
        $_POST['request_uuid'] = auth_issue_submission_token(serialize(['issue-tracker', $tracker->config->file_name, $_POST['req'], in_array($_POST['req'], ['new-post', 'new-additional-email'], true) ? (int)$_POST['issue_id'] : null]));
        $result = $tracker->handle_new_issue($tags);
        self::assertInstanceOf(I\SuccessWithMailsNIR::class, $result);
        self::assertSame(0, (int)$db->query('SELECT COUNT(*) FROM issue_x_additional_email')->fetchColumn());
        self::assertNotContains('attacker@example.test', array_column($result->recipients, 'email_address'));
        ob_start();
        $tracker->print_new_issue_form($tags, null);
        self::assertStringNotContainsString('name="additional_email_addresses"', ob_get_clean());
    }

    public function testCreationRollsBackRequiredWrites(): void {
        [$tracker, $tags, , , $db] = $this->fixture();
        foreach (['issue', 'post', 'issue_x_tag', 'issue_x_additional_email'] as $table) {
            PHPMailer::$sent = [];
            $condition = $table === 'issue_x_additional_email' ? " WHEN NEW.email_address = 'two@example.test'" : '';
            $db->exec("CREATE TRIGGER fail_creation BEFORE INSERT ON $table$condition BEGIN SELECT RAISE(ABORT, 'forced failure'); END");
            $_POST = [
                'req' => 'new-issue', 'content' => 'Rollback issue', 'tags' => ['a'],
                'additional_email_addresses' => 'one@example.test; two@example.test'
            ];
            $_POST['request_uuid'] = auth_issue_submission_token(serialize(['issue-tracker', $tracker->config->file_name, $_POST['req'], in_array($_POST['req'], ['new-post', 'new-additional-email'], true) ? (int)$_POST['issue_id'] : null]));
            $result = $tracker->handle_new_issue($tags);
            self::assertInstanceOf(I\ErrorDbNIR::class, $result);
            self::assertFalse($db->inTransaction());
            self::assertSame(1, (int)$db->query('SELECT COUNT(*) FROM issue')->fetchColumn());
            self::assertSame(1, (int)$db->query('SELECT COUNT(*) FROM post')->fetchColumn());
            self::assertSame(0, (int)$db->query('SELECT COUNT(*) FROM issue_x_additional_email')->fetchColumn());
            self::assertSame([], PHPMailer::$sent);
            $db->exec('DROP TRIGGER fail_creation');
        }
    }

    public function testMailFailureRetainsCompleteCreation(): void {
        [$tracker, $tags, , , $db] = $this->fixture();
        PHPMailer::$sendSuccessful = false;
        $_POST = [
            'req' => 'new-issue', 'content' => 'Saved despite mail failure', 'tags' => ['a'],
            'additional_email_addresses' => 'one@example.test; two@example.test'
        ];
        $_POST['request_uuid'] = auth_issue_submission_token(serialize(['issue-tracker', $tracker->config->file_name, $_POST['req'], in_array($_POST['req'], ['new-post', 'new-additional-email'], true) ? (int)$_POST['issue_id'] : null]));
        $result = $tracker->handle_new_issue($tags);
        self::assertInstanceOf(I\SuccessButMailsFailedNIR::class, $result);
        self::assertFalse($db->inTransaction());
        self::assertSame(2, (int)$db->query('SELECT COUNT(*) FROM issue')->fetchColumn());
        self::assertSame(2, (int)$db->query('SELECT COUNT(*) FROM post')->fetchColumn());
        self::assertSame(2, (int)$db->query('SELECT COUNT(*) FROM issue_x_additional_email')->fetchColumn());
        self::assertSame('["test-user"]', $db->query('SELECT author_identity FROM issue_x_additional_email LIMIT 1')->fetchColumn());
        self::assertNotEmpty($GLOBALS['test_mail_errors']);
    }

    public function testNewIssueRecipientsAndTemplates(): void {
        [$tracker, $tags, $expected, $expected_tag_names, $db, $result] = $this->fixture();
        check_notification($tracker, $result, $expected, $this->blockRecipients);
        $expected_tag_names = ['<Room & A>', 'Room B', 'Room without notifications'];
        check_template_tags($expected_tag_names, 'New issue #1: Example issue', 'Example issue');
    }

    public function testReplyRecipientsAreIsolatedPerIssue(): void {
        [$tracker, $tags, $expected, $expected_tag_names, $db, $result] = $this->fixture();
        $db->exec("INSERT INTO issue (id, summary, first_post_created_on, first_post_author_identity, last_post_status, last_post_created_on) VALUES (2, 'Other issue', '2026-01-01', 'other', 'open', '2026-01-01')");
        $db->exec("INSERT INTO post (issue_id, created_on, author_identity, author_email, content, new_status) VALUES (2, '2026-01-01', 'other', 'other-author@example.test', 'Other post', 'open')");
        $db->exec("INSERT INTO issue_x_tag (issue_id, tag_id) VALUES (2, 'other')");
        $query = $db->prepare('INSERT INTO issue_x_additional_email (issue_id, created_on, author_identity, author_email, email_address) VALUES (?, ?, ?, ?, ?)');
        $query->execute([1, '2026-01-01', 'event-author', 'event@example.test', 'added@example.test']);
        $query->execute([1, '2026-01-01', 'event-author', 'EVENT@example.test', 'shared@example.test']);
        $query->execute([2, '2026-01-01', 'other', 'other-event@example.test', 'other-added@example.test']);

        $GLOBALS['test_author_email'] = 'new-author@example.test';
        $_POST = ['req' => 'new-post', 'issue_id' => '1', 'content' => 'Example reply', 'new_status' => 'open'];
        $_POST['request_uuid'] = auth_issue_submission_token(serialize(['issue-tracker', $tracker->config->file_name, $_POST['req'], in_array($_POST['req'], ['new-post', 'new-additional-email'], true) ? (int)$_POST['issue_id'] : null]));
        $result = $tracker->handle_new_post();
        array_splice($expected, 2, 0, [
            ['email_address' => 'new-author@example.test', 'reasons' => ['Participates in issue']],
            ['email_address' => 'event@example.test', 'reasons' => ['Participates in issue']],
            ['email_address' => 'added@example.test', 'reasons' => ['Participates in issue']]
        ]);
        check_notification($tracker, $result, $expected, $this->blockRecipients);
        check_template_tags($expected_tag_names, 'New post #1', 'Example reply');
        self::assertTrue(count(PHPMailer::$sent) === 2, 'Both submissions must send exactly one message');
    }

    public function testTagPreviewAndEmptyNotification(): void {
        [$tracker, $tags, $expected, $expected_tag_names, $db, $result] = $this->fixture();
        $preview = $tracker->notification_recipients_for_tag($tracker->fetch_tag_details('a'));
        self::assertTrue($preview[0] === [
            'email_address' => 'Shared@Example.test',
            'reasons' => ['Always notified', 'Shared@Example.test is responsible for <Room & A>']
        ], 'Tag preview must combine reasons using the same canonical address');
        self::assertTrue(count($preview) === 3, 'Tag preview must deduplicate addresses');
        $append = new ReflectionMethod(I\IssueTracker::class, 'append_notification_block');
        self::assertTrue($append->invoke($tracker, 'Unchanged body', []) === 'Unchanged body', 'No recipients must leave the body unchanged');
    }

    public function testRenamedAndReassignedTags(): void {
        [$tracker, $tags, $expected, $expected_tag_names, $db, $result] = $this->fixture();
        // Subsequent posts must use renamed tags and the issue's current assignments.
        $db->exec("UPDATE tag SET name = 'Renamed, room B' WHERE id = 'b'");
        $db->exec("DELETE FROM issue_x_tag WHERE issue_id = 1 AND tag_id = 'a'");
        $db->exec("INSERT INTO issue_x_tag (issue_id, tag_id) VALUES (1, 'other')");
        $_POST = ['req' => 'new-post', 'issue_id' => '1', 'content' => 'Reply after tag edits', 'new_status' => 'open'];
        $_POST['request_uuid'] = auth_issue_submission_token(serialize(['issue-tracker', $tracker->config->file_name, $_POST['req'], in_array($_POST['req'], ['new-post', 'new-additional-email'], true) ? (int)$_POST['issue_id'] : null]));
        $result = $tracker->handle_new_post();
        self::assertTrue($result instanceof I\SuccessWithMailsNPR, 'Reply after tag edits must succeed');
        check_template_tags(['Other', 'Renamed, room B', 'Room without notifications'], 'New post #1', 'Reply after tag edits');
    }

    public function testUntaggedTemplates(): void {
        [$tracker, $tags, $expected, $expected_tag_names, $db, $result] = $this->fixture();
        // All four callbacks must also receive an empty list for an untagged issue.
        $_POST = ['req' => 'new-issue', 'content' => 'Untagged issue'];
        $_POST['request_uuid'] = auth_issue_submission_token(serialize(['issue-tracker', $tracker->config->file_name, $_POST['req'], in_array($_POST['req'], ['new-post', 'new-additional-email'], true) ? (int)$_POST['issue_id'] : null]));
        $result = $tracker->handle_new_issue($tags);
        self::assertTrue($result instanceof I\SuccessWithMailsNIR, 'Untagged issue must succeed');
        $untagged_issue_id = (int)$db->query('SELECT MAX(id) FROM issue')->fetchColumn();
        check_template_tags([], 'New issue #' . $untagged_issue_id . ': Untagged issue', 'Untagged issue');
        $_POST = ['req' => 'new-post', 'issue_id' => (string)$untagged_issue_id, 'content' => 'Untagged reply', 'new_status' => 'open'];
        $_POST['request_uuid'] = auth_issue_submission_token(serialize(['issue-tracker', $tracker->config->file_name, $_POST['req'], in_array($_POST['req'], ['new-post', 'new-additional-email'], true) ? (int)$_POST['issue_id'] : null]));
        $result = $tracker->handle_new_post();
        self::assertTrue($result instanceof I\SuccessWithMailsNPR, 'Untagged reply must succeed');
        check_template_tags([], 'New post #' . $untagged_issue_id, 'Untagged reply');
    }

    public function testBulkTagMailReplacesClientDataPerTag(): void {
        [$tracker, , , , $db] = $this->fixture();
        $db->exec("UPDATE tag SET client_data = '{\"responsible\":\"Alex\"}', url = 'https://example.test/a' WHERE id = 'a'");
        $db->exec("UPDATE tag SET client_data = '' WHERE id = 'other'");
        PHPMailer::$sent = [];
        $_POST = [
            'req' => 'mail-tags',
            'subject' => '{{tag}}: {{client_data}}',
            'content' => "{{url}}\n{{client_data}} / {{client_data}}"
        ];

        $_POST['request_uuid'] = auth_issue_submission_token(serialize(['issue-tracker', $tracker->config->file_name, $_POST['req'], in_array($_POST['req'], ['new-post', 'new-additional-email'], true) ? (int)$_POST['issue_id'] : null]));

        $result = $tracker->handle_mail_tags();

        self::assertSame('done', $result['kind']);
        self::assertCount(3, $result['sent']);
        self::assertSame(['Room without notifications'], $result['skipped']);
        self::assertSame([], $result['failed']);
        self::assertCount(3, PHPMailer::$sent);
        $deliveries = [];
        foreach (PHPMailer::$sent as $delivery) {
            $deliveries[$delivery['subject']] = $delivery;
        }
        self::assertSame("https://example.test/a\n{\"responsible\":\"Alex\"} / {\"responsible\":\"Alex\"}", $deliveries['<Room & A>: {"responsible":"Alex"}']['body']);
        self::assertSame(["shared@example.test", "tag@example.test"], $deliveries['<Room & A>: {"responsible":"Alex"}']['addresses']);
        self::assertSame("(nicht vorhanden)\n(nicht vorhanden) / (nicht vorhanden)", $deliveries['Room B: (nicht vorhanden)']['body']);
        self::assertSame("(nicht vorhanden)\n / ", $deliveries['Other: ']['body']);
    }

}
