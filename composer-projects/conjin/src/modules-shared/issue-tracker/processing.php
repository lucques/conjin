<?
    namespace Conjin\Modules\IssueTracker;

    use PDO;
    use PDOException;
    use PHPMailer\PHPMailer\PHPMailer;


    require_once __DIR__ . '/datatypes.php';
    require_once __DIR__ . '/processing_view.php';


    ////////////////
    // Entrypoint //
    ////////////////

    class IssueTracker {
        use View;

        private ?PDO $db = null;

        function __construct(public Config $config) {
            $this->init_db();
        }

        private function may_administer(): bool {
            return auth_is_cur_user_privileged_for_custom_privilege(
                $this->config->privilege_admin
            );
        }

        private function may_write(): bool {
            return auth_is_cur_user_privileged_for_custom_privilege(
                $this->config->privilege_write
            );
        }

        // Shared modals select their record in JavaScript, so their scope binds
        // to tracker/action. Inline per-issue forms also bind to the issue ID.
        private function submission_scope(string $action, ?int $issue_id = null): string {
            return serialize(['issue-tracker', $this->config->file_name, $action, $issue_id]);
        }

        private function issue_submission_token(string $action, ?int $issue_id = null): string {
            return \auth_issue_submission_token($this->submission_scope($action, $issue_id));
        }

        private function accept_submission_token(string $action, ?int $issue_id = null): bool {
            $result = \auth_consume_submission_token($_POST['request_uuid'] ?? null, $this->submission_scope($action, $issue_id));
            if ($result === \SubmissionTokenResult::Invalid) {
                send_response_and_exit(403, 'Das Formular ist abgelaufen oder ungültig. Bitte laden Sie die Seite neu und versuchen Sie es erneut.');
            }
            return $result === \SubmissionTokenResult::Valid;
        }

        private function current_user_identity_serialized(): string {
            return auth_aux_serialize_user(auth_get_user()->get_user_identity());
        }

        /** @return list<string> */
        private function openid_provider_names(): array {
            return array_values(array_map(
                fn(array $provider): string => $provider['name'],
                get_global_config('authentication', 'openIdProviders')
            ));
        }

        /** @return array{identity: string, email: ?string} */
        private function submitted_author(): array {
            $author = [
                'identity' => $this->current_user_identity_serialized(),
                'email' => auth_get_user_email()
            ];

            if (!$this->may_administer() || !isset($_POST['set_other_openid_author'])) {
                return $author;
            }

            $provider_name = $_POST['author_openid_provider'] ?? null;
            $id = $_POST['author_openid_id'] ?? null;
            $email = $_POST['author_openid_email'] ?? null;
            if (
                !is_string($provider_name) ||
                !in_array($provider_name, $this->openid_provider_names(), true) ||
                !is_string($id) ||
                trim($id) === ''
            ) {
                return $author;
            }

            $author['identity'] = auth_aux_serialize_user([
                'tag' => 'OpenId',
                'contents' => [
                    'providerName' => $provider_name,
                    'id' => trim($id)
                ]
            ]);
            $author['email'] = is_string($email) && filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false
                ? trim($email)
                : null;
            return $author;
        }

        private function may_administer_issue(array $issue): bool {
            if ($this->may_administer()) {
                return true;
            }

            $created_on = strtotime($issue['first_post_created_on']);

            return $this->config->may_edit_own_content_within_n_minutes > 0 &&
                $issue['first_post_author_identity'] === $this->current_user_identity_serialized() &&
                $created_on !== false &&
                time() <= $created_on + $this->config->may_edit_own_content_within_n_minutes * 60;
        }

        private function may_administer_issue_by_id(int $issue_id): bool {
            if ($this->may_administer()) {
                return true;
            }

            $query = $this->db->prepare(
                'SELECT first_post_author_identity, first_post_created_on FROM issue WHERE id = ?'
            );
            $query->execute([$issue_id]);
            $issue = $query->fetch(PDO::FETCH_ASSOC);

            return $issue !== false && $this->may_administer_issue($issue);
        }

        private function may_administer_post(array $post): bool {
            if ($this->may_administer()) {
                return true;
            }

            $created_on = strtotime($post['created_on']);

            return $this->config->may_edit_own_content_within_n_minutes > 0 &&
                $post['author_identity'] === $this->current_user_identity_serialized() &&
                $created_on !== false &&
                time() <= $created_on + $this->config->may_edit_own_content_within_n_minutes * 60;
        }

        private function may_administer_post_by_id(int $issue_id, int $post_id): bool {
            if ($this->may_administer()) {
                return true;
            }

            $query = $this->db->prepare(
                'SELECT author_identity, created_on FROM post WHERE id = ? AND issue_id = ?'
            );
            $query->execute([$post_id, $issue_id]);
            $post = $query->fetch(PDO::FETCH_ASSOC);

            return $post !== false && $this->may_administer_post($post);
        }


        ///////////
        // Model //
        ///////////

        private function init_db() {
            $path = path_store($this->config->file_name);
            $initialized = file_exists($path);

            $this->db = new PDO('sqlite:' . path_store($this->config->file_name));
            $this->db->exec('PRAGMA foreign_keys = ON');

            if (!$initialized) {
                $sql = <<<SQL
    BEGIN TRANSACTION;
    CREATE TABLE IF NOT EXISTS "issue" (
        "id"	INTEGER NOT NULL,
        "summary"	TEXT,
        "first_post_created_on"	TEXT NOT NULL,
        "first_post_author_identity" TEXT NOT NULL,
        "last_post_status"	TEXT NOT NULL,
        "last_post_created_on"	TEXT NOT NULL,
        PRIMARY KEY("id" AUTOINCREMENT)
    );
    CREATE TABLE IF NOT EXISTS "post" (
        "id"	INTEGER NOT NULL,
        "issue_id"	INTEGER NOT NULL,
        "created_on"	TEXT NOT NULL,
        "author_identity"	TEXT NOT NULL,
        "author_email"  TEXT,
        "content"	TEXT,
        "new_status"	TEXT NOT NULL,
        PRIMARY KEY("id" AUTOINCREMENT),
        FOREIGN KEY("issue_id") REFERENCES "issue"("id")
    );
    CREATE TABLE IF NOT EXISTS "tag" (
        "id"	TEXT NOT NULL,
        "name"	TEXT NOT NULL,
        "client_data"	TEXT,
        "url"	TEXT,
        PRIMARY KEY("id")
    );
    CREATE TABLE IF NOT EXISTS "issue_x_tag" (
        "issue_id"	INTEGER NOT NULL,
        "tag_id"	TEXT NOT NULL,
        FOREIGN KEY("issue_id") REFERENCES "issue"("id"),
        FOREIGN KEY("tag_id") REFERENCES "tag"("id")
    );
    CREATE TABLE IF NOT EXISTS "tag_x_additional_email" (
        "tag_id"	TEXT NOT NULL,
        "email_address"	TEXT NOT NULL COLLATE NOCASE,
        PRIMARY KEY("tag_id", "email_address"),
        FOREIGN KEY("tag_id") REFERENCES "tag"("id") ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS "issue_x_additional_email" (
        "id"	INTEGER NOT NULL,
        "issue_id"	INTEGER NOT NULL,
        "created_on"	TEXT NOT NULL,
        "author_identity"	TEXT NOT NULL,
        "author_email"	TEXT,
        "email_address"	TEXT NOT NULL COLLATE NOCASE,
        PRIMARY KEY("id" AUTOINCREMENT),
        UNIQUE("issue_id", "email_address"),
        FOREIGN KEY("issue_id") REFERENCES "issue"("id") ON DELETE CASCADE
    );
    SQL;

                if (count($this->config->tags) > 0) {
                    $sql .= 'INSERT INTO "tag" (id, name, client_data, url) VALUES ' . implode(', ', array_map(
                        fn(array $tag) => '('
                            . $this->db->quote($tag['id']) . ', '
                            . $this->db->quote($tag['name']) . ', '
                            . (($tag['client_data'] ?? null) === null ? 'NULL' : $this->db->quote($tag['client_data']))
                            . ', '
                            . (($tag['url'] ?? null) === null ? 'NULL' : $this->db->quote($tag['url']))
                            . ')',
                        $this->config->tags
                    )) . ';';

                    $initial_tag_additional_email_rows = [];
                    foreach ($this->config->tags as $tag) {
                        $seen_addresses = [];
                        foreach ($tag['additional_email_addresses'] ?? [] as $address) {
                            $address = trim($address);
                            $deduplication_key = strtolower($address);
                            if (isset($seen_addresses[$deduplication_key])) {
                                continue;
                            }

                            $seen_addresses[$deduplication_key] = true;
                            $initial_tag_additional_email_rows[] = '('
                                . $this->db->quote($tag['id']) . ', '
                                . $this->db->quote($address)
                                . ')';
                        }
                    }

                    if (count($initial_tag_additional_email_rows) > 0) {
                        $sql .= 'INSERT INTO "tag_x_additional_email" (tag_id, email_address) VALUES '
                            . implode(', ', $initial_tag_additional_email_rows) . ';';
                    }
                }

                $sql .= ' COMMIT;';

                $this->db->exec($sql);
            }

            $tag_columns = $this->db->query('PRAGMA table_info(tag)')->fetchAll(PDO::FETCH_ASSOC);
            if (!in_array('url', array_column($tag_columns, 'name'), true)) {
                $this->db->exec('ALTER TABLE tag ADD COLUMN url TEXT');
            }

            // Existing databases are reused, so additive schema changes must
            // also be applied after their initial creation.
            $this->db->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS "issue_x_additional_email" (
        "id"	INTEGER NOT NULL,
        "issue_id"	INTEGER NOT NULL,
        "created_on"	TEXT NOT NULL,
        "author_identity"	TEXT NOT NULL,
        "author_email"	TEXT,
        "email_address"	TEXT NOT NULL COLLATE NOCASE,
        PRIMARY KEY("id" AUTOINCREMENT),
        UNIQUE("issue_id", "email_address"),
        FOREIGN KEY("issue_id") REFERENCES "issue"("id") ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS "tag_x_additional_email" (
        "tag_id"	TEXT NOT NULL,
        "email_address"	TEXT NOT NULL COLLATE NOCASE,
        PRIMARY KEY("tag_id", "email_address"),
        FOREIGN KEY("tag_id") REFERENCES "tag"("id") ON DELETE CASCADE
    );
    SQL);
        }

        function fetch_issues_with_posts_open() {
            return $this->fetch_issues_with_posts(
                where: 'WHERE issue.last_post_status = "open"',
                order_by: 'ORDER BY issue.first_post_created_on DESC'
            );
        }

        function fetch_issues_with_posts_open_by_tag(string $tag_name) {
            return $this->fetch_issues_with_posts(
                where: 'WHERE issue.last_post_status = "open" AND tag.name = ?',
                where_parameters: [$tag_name],
                order_by: 'ORDER BY issue.first_post_created_on DESC'
            );
        }

        function fetch_issues_with_posts_open_by_tag_id(string $tag_id) {
            return $this->fetch_issues_with_posts(
                where: 'WHERE issue.last_post_status = "open" AND tag.id = ?',
                where_parameters: [$tag_id],
                order_by: 'ORDER BY issue.first_post_created_on DESC'
            );
        }

        function fetch_open_issue_counts_by_tag(): array {
            $query = $this->db->query(
                'SELECT tag.name, COUNT(DISTINCT issue.id) AS open_issue_count ' .
                'FROM tag ' .
                'LEFT JOIN issue_x_tag ON tag.id = issue_x_tag.tag_id ' .
                'LEFT JOIN issue ON issue_x_tag.issue_id = issue.id ' .
                    'AND issue.last_post_status = "open" ' .
                'GROUP BY tag.id, tag.name ' .
                'ORDER BY tag.name'
            );

            $counts = [];
            foreach ($query as $row) {
                $counts[$row['name']] = (int)$row['open_issue_count'];
            }

            return $counts;
        }

        function fetch_issues_with_posts_closed(?int $how_many = null) {
            return $this->fetch_issues_with_posts(
                where: 'WHERE issue.last_post_status = "closed"',
                order_by: 'ORDER BY issue.last_post_created_on DESC',
                limit: $how_many !== null ? 'LIMIT ' . $how_many : ''
            );
        }

        function fetch_issues_with_posts_closed_by_tag(string $tag_name, ?int $how_many = 3) {
            return $this->fetch_issues_with_posts(
                where: 'WHERE issue.last_post_status = "closed" AND tag.name = ?',
                where_parameters: [$tag_name],
                order_by: 'ORDER BY issue.last_post_created_on DESC',
                limit: $how_many !== null ? 'LIMIT ' . $how_many : ''
            );
        }

        function fetch_issues_with_posts_closed_by_tag_id(string $tag_id, ?int $how_many = 3) {
            return $this->fetch_issues_with_posts(
                where: 'WHERE issue.last_post_status = "closed" AND tag.id = ?',
                where_parameters: [$tag_id],
                order_by: 'ORDER BY issue.last_post_created_on DESC',
                limit: $how_many !== null ? 'LIMIT ' . $how_many : ''
            );
        }

        private function fetch_issues_with_posts(
            string $where = '',
            array $where_parameters = [],
            string $order_by = '',
            string $limit = ''
        ) {
            // Collect data in this assoc array.
            $issues_to_posts = [];

            // Select all issues that are open.
            $query  = 'SELECT issue.id, issue.summary, issue.first_post_created_on, ';
            $query .= 'issue.first_post_author_identity, issue.last_post_status ';
            $query .= 'FROM issue ';
            $query .= 'LEFT JOIN issue_x_tag ON issue.id = issue_x_tag.issue_id ';
            $query .= 'LEFT JOIN tag ON issue_x_tag.tag_id = tag.id ';
            $query .= $where . ' ';
            $query .= 'GROUP BY issue.id, issue.summary, issue.first_post_created_on, ';
            $query .= 'issue.first_post_author_identity, issue.last_post_status ';
            $query .= $order_by . ' ';
            $query .= $limit;

            $result = $this->db->prepare($query);
            $result->execute($where_parameters);

            foreach ($result as $row) {
                $issues_to_posts[$row['id']] = [
                    'id' => $row['id'],
                    'summary' => $row['summary'],
                    'first_post_created_on' => $row['first_post_created_on'],
                    'first_post_author_identity' => $row['first_post_author_identity'],
                    'last_post_status' => $row['last_post_status'], 
                    'tags' => '',
                    'tag_ids' => [],
                    'posts' => [],
                    'additional_emails' => []
                ];
            }

            $issue_ids = array_keys($issues_to_posts);

            if (count($issue_ids) === 0) {
                return $issues_to_posts;
            }

            // Select all tags for these issues. This is done separately so a
            // tag used to filter the issues does not hide their other tags.
            $query  = 'SELECT issue_x_tag.issue_id, tag.id, tag.name ';
            $query .= 'FROM issue_x_tag ';
            $query .= 'JOIN tag ON issue_x_tag.tag_id = tag.id ';
            $query .= 'WHERE issue_x_tag.issue_id IN (' . implode(',', $issue_ids) . ') ';
            $query .= 'ORDER BY tag.name';
            $result = $this->db->query($query);

            foreach ($result as $row) {
                $issue = &$issues_to_posts[$row['issue_id']];
                $issue['tag_ids'][] = $row['id'];
                $issue['tags'] .= ($issue['tags'] === '' ? '' : ', ') . $row['name'];
                unset($issue);
            }

            // Select posts for these issues
            $query  = 'SELECT issue_id, id, created_on, author_identity, content, new_status ';
            $query .= 'FROM post ';
            $query .= 'WHERE issue_id IN (' . implode(',', $issue_ids) . ') ';
            $query .= 'ORDER BY issue_id, created_on, id';
            $result = $this->db->query($query);

            foreach($result as $row)
            {
                $issues_to_posts[$row['issue_id']]['posts'][] = [
                    'id' => $row['id'],
                    'created_on' => $row['created_on'],
                    'author_identity' => $row['author_identity'],
                    'content' => $row['content'],
                    'new_status' => $row['new_status']
                ];
            }

            // Select additional e-mail events for these issues. They are kept
            // separate from posts in the model and merged only for rendering,
            // because they do not change an issue's status.
            $query  = 'SELECT issue_id, id, created_on, author_identity, author_email, email_address ';
            $query .= 'FROM issue_x_additional_email ';
            $query .= 'WHERE issue_id IN (' . implode(',', $issue_ids) . ') ';
            $query .= 'ORDER BY issue_id, created_on, id';
            $result = $this->db->query($query);

            foreach ($result as $row) {
                $issues_to_posts[$row['issue_id']]['additional_emails'][] = [
                    'id' => $row['id'],
                    'created_on' => $row['created_on'],
                    'author_identity' => $row['author_identity'],
                    'author_email' => $row['author_email'],
                    'email_address' => $row['email_address']
                ];
            }

            return $issues_to_posts;
        }

        function fetch_tags() {
            $query = $this->db->query('SELECT id, name FROM tag ORDER BY name COLLATE NOCASE, id');
            $tags = [];

            foreach ($query as $row) {
                $tags[] = [
                    'id' => $row['id'],
                    'name' => $row['name']
                ];
            }

            return $tags;
        }

        /** @return list<string> */
        private function fetch_issue_tag_names(int $issue_id): array {
            $query = $this->db->prepare(
                'SELECT tag.name FROM tag '
                . 'WHERE tag.id IN (SELECT tag_id FROM issue_x_tag WHERE issue_id = ?) '
                . 'ORDER BY tag.name COLLATE NOCASE, tag.id'
            );
            $query->execute([$issue_id]);

            return $query->fetchAll(PDO::FETCH_COLUMN);
        }

        function fetch_tag_client_data(string $tag_id): ?string {
            $query = $this->db->prepare('SELECT client_data FROM tag WHERE id = ?');
            $query->execute([$tag_id]);
            $client_data = $query->fetchColumn();

            return $client_data === false ? null : $client_data;
        }

        /**
         * @return ?array{
         *     id: string,
         *     name: string,
         *     client_data: ?string,
         *     url: ?string,
         *     additional_email_addresses: list<string>
         * }
         */
        function fetch_tag_details(string $tag_id): ?array {
            $query = $this->db->prepare(
                'SELECT tag.id, tag.name, tag.client_data, tag.url, tag_x_additional_email.email_address '
                . 'FROM tag '
                . 'LEFT JOIN tag_x_additional_email ON tag.id = tag_x_additional_email.tag_id '
                . 'WHERE tag.id = ? '
                . 'ORDER BY tag_x_additional_email.email_address COLLATE NOCASE'
            );
            $query->execute([$tag_id]);

            $tag = null;
            foreach ($query as $row) {
                if ($tag === null) {
                    $tag = [
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'client_data' => $row['client_data'],
                        'url' => $row['url'],
                        'additional_email_addresses' => []
                    ];
                }

                if ($row['email_address'] !== null) {
                    $tag['additional_email_addresses'][] = $row['email_address'];
                }
            }

            return $tag;
        }

        /**
         * @param ?array{name: string, additional_email_addresses: list<string>} $tag
         * @return list<array{email_address: string, reasons: list<string>}>
         */
        function notification_recipients_for_tag(?array $tag): array {
            if ($this->config->mail === null) {
                return [];
            }

            $recipients = [];
            foreach ($this->config->mail->always_notify_addresses as $email_address) {
                if (is_string($email_address)) {
                    $this->add_notification_recipient(
                        $recipients,
                        $email_address,
                        $this->config->mail->reason_always_notify
                    );
                }
            }

            if ($tag !== null) {
                foreach ($tag['additional_email_addresses'] as $email_address) {
                    $this->add_tag_notification_recipient(
                        $recipients,
                        $email_address,
                        $tag['name']
                    );
                }
            }

            return array_values($recipients);
        }

        function fetch_tags_with_issue_counts(): array {
            $query = $this->db->query(
                'SELECT tag.id, tag.name, tag.client_data, tag.url, '
                . 'COUNT(DISTINCT CASE WHEN issue.last_post_status = "closed" THEN issue.id END) AS solved_issue_count, '
                . 'COUNT(DISTINCT CASE WHEN issue.last_post_status = "open" THEN issue.id END) AS unsolved_issue_count '
                . 'FROM tag '
                . 'LEFT JOIN issue_x_tag ON tag.id = issue_x_tag.tag_id '
                . 'LEFT JOIN issue ON issue_x_tag.issue_id = issue.id '
                . 'GROUP BY tag.id, tag.name, tag.client_data, tag.url '
                . 'ORDER BY tag.name COLLATE NOCASE, tag.id'
            );

            $tags = [];
            foreach ($query as $row) {
                $tags[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'client_data' => $row['client_data'],
                    'url' => $row['url'],
                    'additional_email_addresses' => [],
                    'solved_issue_count' => (int)$row['solved_issue_count'],
                    'unsolved_issue_count' => (int)$row['unsolved_issue_count']
                ];
            }

            $tag_indexes_by_id = [];
            foreach ($tags as $index => $tag) {
                $tag_indexes_by_id[$tag['id']] = $index;
            }

            $query = $this->db->query(
                'SELECT tag_id, email_address FROM tag_x_additional_email '
                . 'ORDER BY tag_id, email_address COLLATE NOCASE'
            );
            foreach ($query as $row) {
                if (isset($tag_indexes_by_id[$row['tag_id']])) {
                    $tags[$tag_indexes_by_id[$row['tag_id']]]['additional_email_addresses'][] =
                        $row['email_address'];
                }
            }

            return $tags;
        }


        ////////////////
        // Controller //
        ////////////////

        function handle_new_issue(array $tags): ?HandleNewIssueResult {
            try {
                if (
                    $_SERVER['REQUEST_METHOD'] === 'POST' &&
                    isset($_POST['req']) &&
                    $_POST['req'] === 'new-issue' &&
                    isset($_POST['content']) &&
                    $this->may_write()
                ) {
                    if ($this->accept_submission_token('new-issue')) {
                        $email_addresses = $this->parse_email_address_list(
                            $this->may_administer() && is_string($_POST['additional_email_addresses'] ?? null)
                                ? $_POST['additional_email_addresses']
                                : ''
                        );
                        if ($_POST['content'] === '' || $email_addresses['invalid'] !== []) {
                            return new ErrorIncompleteNIR(
                                is_array($_POST['tags'] ?? null) ? $_POST['tags'] : [],
                                $_POST['content'],
                                $email_addresses['submitted'],
                                $email_addresses['invalid']
                            );
                        }
                        if ($_POST['content'] !== '') {
                            $tags_ids = array_map(fn($tag) => $tag['id'], $tags);

                            $date            = date('Y-m-d H:i:s');
                            $content         = $_POST['content'] ?? '';
                            $status          = 'open';
                            $author          = $this->submitted_author();
                            $author_identity = $author['identity'];
                            $author_email    = $author['email'];

                            $this->db->beginTransaction();

                            // Insert issue
                            $query = $this->db->prepare("INSERT INTO issue (summary, last_post_status, last_post_created_on, first_post_created_on, first_post_author_identity) VALUES (?, ?, ?, ?, ?)");
                            $query->execute([$content, $status, $date, $date, $author_identity]);
                            $issue_id = (int)$this->db->lastInsertId();

                            // Insert post
                            $query = $this->db->prepare("INSERT INTO post (issue_id, created_on, author_identity, author_email, content, new_status) VALUES (?, ?, ?, ?, ?, ?)");
                            $query->execute([$issue_id, $date, $author_identity, $author_email, $content, $status]);

                            // Add tags
                            if (isset($_POST['tags']) && is_array($_POST['tags'])) {
                                $tag_ids = $_POST['tags'];
                                $query = $this->db->prepare("INSERT INTO issue_x_tag (issue_id, tag_id) VALUES (?, ?)");
                                foreach ($tag_ids as $tag_id) {
                                    if (in_array($tag_id, $tags_ids)) {
                                        $query->execute([$issue_id, $tag_id]);
                                    }
                                }
                            }

                            // Attribute additional-email events to the acting administrator.
                            $query = $this->db->prepare(
                                'INSERT INTO issue_x_additional_email '
                                . '(issue_id, created_on, author_identity, author_email, email_address) '
                                . 'VALUES (?, ?, ?, ?, ?)'
                            );
                            foreach ($email_addresses['valid'] as $email_address) {
                                $query->execute([
                                    $issue_id, $date, $this->current_user_identity_serialized(),
                                    auth_get_user_email(), $email_address
                                ]);
                            }
                            $this->db->commit();

                            // Send e-mails
                            if (isset_global_config('mail') && $this->config->mail !== null) {
                                $notification_recipients = $this->collect_notification_recipients(
                                    (int)$issue_id
                                );

                                $tag_names = $this->fetch_issue_tag_names($issue_id);
                                $subject = ($this->config->mail->new_issue_template_subject)(
                                    $issue_id,
                                    $content,
                                    $tag_names
                                );
                                $body = ($this->config->mail->new_issue_template_body)(
                                    $issue_id,
                                    url_full_collect($this->config->view_target_ids) . '#' . IssueTracker::make_view_issue_html_id($issue_id),
                                    $date,
                                    ($this->config->view->author_identity_to_label)($author_identity),
                                    $content,
                                    $tag_names
                                );
                                $body = $this->append_notification_block(
                                    $body,
                                    $notification_recipients
                                );

                                $mail_successful = $this->send_mail_and_log(
                                    $notification_recipients,
                                    $subject,
                                    $body
                                );

                                if ($mail_successful) {
                                    return new SuccessWithMailsNIR(
                                        $notification_recipients
                                    );
                                }
                                else {
                                    return new SuccessButMailsFailedNIR();
                                }
                            }
                            else {
                                return new SuccessWithoutMailsNIR();

                            }
                        }
                        else {
                            // Form data incomplete
                            if (isset($_POST['tags']) && is_array($_POST['tags'])) {
                                return new ErrorIncompleteNIR($_POST['tags']);
                            }
                            else {
                                return new ErrorIncompleteNIR([]);
                            }
                        }
                    }
                    else {
                        return null; // Duplicate request, ignore.
                    }
                }
                else {
                    return null; // Not a full request.
                }
            }
            catch (PDOException $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return new ErrorDbNIR((string)$e);
            }
        }

        function handle_new_post(): ?HandleNewPostResult {
            $issue_id = (int)($_POST['issue_id'] ?? 0);

            try {
                if (
                    $_SERVER['REQUEST_METHOD'] === 'POST' &&
                    isset($_POST['req']) &&
                    $_POST['req'] === 'new-post' &&
                    isset($_POST['issue_id']) &&
                    isset($_POST['new_status']) &&
                    $this->may_write()
                ) {
                    if ($this->accept_submission_token('new-post', (int)$_POST['issue_id'])) {
                        if ($_POST['content'] !== '') {
                            $date         = date('Y-m-d H:i:s');
                            $content      = $_POST['content'] ?? '';
                            $new_status   = $_POST['new_status'];

                            $author          = $this->submitted_author();
                            $author_identity = $author['identity'];
                            $author_email    = $author['email'];

                            // Insert post
                            $query = $this->db->prepare("INSERT INTO post (issue_id, created_on, author_identity, author_email, content, new_status) VALUES (?, ?, ?, ?, ?, ?)");
                            $query->execute([$issue_id, $date, $author_identity, $author_email, $content, $new_status]);
                            $post_id = (int)$this->db->lastInsertId();

                            // Update the issue's last post status and timestamp
                            $query = $this->db->prepare("UPDATE issue SET last_post_status = ?, last_post_created_on = ? WHERE id = ?");
                            $query->execute([$new_status, $date, $issue_id]);

                            // Send e-mails
                            if (isset_global_config('mail') && $this->config->mail !== null) {
                                $notification_recipients = $this->collect_notification_recipients(
                                    (int)$issue_id
                                );

                                // Prepare e-mail
                                $query = $this->db->prepare(
                                    'SELECT created_on, author_identity, content, new_status '
                                    . 'FROM post WHERE issue_id = ? AND id < ? '
                                    . 'ORDER BY created_on, id'
                                );
                                $query->execute([$issue_id, $post_id]);

                                $previous_posts = [];
                                foreach ($query as $previous_post) {
                                    $previous_posts[] = [
                                        'date' => $previous_post['created_on'],
                                        'author' => ($this->config->view->author_identity_to_label)(
                                            $previous_post['author_identity']
                                        ),
                                        'content' => $previous_post['content'],
                                        'status' => $previous_post['new_status']
                                    ];
                                }

                                $tag_names = $this->fetch_issue_tag_names($issue_id);
                                $subject = ($this->config->mail->new_post_template_subject)(
                                    $issue_id,
                                    $tag_names
                                );
                                $body = ($this->config->mail->new_post_template_body)(
                                    $issue_id,
                                    url_full_collect($this->config->view_target_ids) . '#' . IssueTracker::make_view_issue_html_id($issue_id),
                                    $date,
                                    ($this->config->view->author_identity_to_label)($author_identity),
                                    $content,
                                    $new_status,
                                    $previous_posts,
                                    $tag_names
                                );
                                $body = $this->append_notification_block(
                                    $body,
                                    $notification_recipients
                                );

                                $mail_successful = $this->send_mail_and_log(
                                    $notification_recipients,
                                    $subject,
                                    $body
                                );

                                if ($mail_successful) {
                                    return new SuccessWithMailsNPR(
                                        $issue_id,
                                        $notification_recipients
                                    );
                                }
                                else {
                                    return new SuccessButMailsFailedNPR($issue_id);
                                }
                            }
                            else {
                                return new SuccessWithoutMailsNPR($issue_id);
                            }
                        }
                        else {
                            // Form data incomplete
                            return new ErrorIncompleteNPR(
                                issue_id: $_POST['issue_id'],
                                selected_status: $_POST['new_status']
                            );
                        }
                    }
                    else {
                        return null; // Duplicate request, ignore.
                    }
                }
                else {
                    return null; // Not a full request.
                }
            }
            catch (PDOException $e) {
                return new ErrorDbNPR($issue_id, (string)$e);
            }
        }

        function handle_new_additional_email(): ?HandleNewAdditionalEmailResult {
            if (
                $_SERVER['REQUEST_METHOD'] !== 'POST' ||
                !isset($_POST['req']) ||
                $_POST['req'] !== 'new-additional-email' ||
                !isset($_POST['issue_id']) ||
                !(is_string($_POST['issue_id']) || is_int($_POST['issue_id'])) ||
                !isset($_POST['email_address']) ||
                !is_string($_POST['email_address']) ||
                !$this->may_administer() ||
                !$this->accept_submission_token('new-additional-email', (int)$_POST['issue_id'])
            ) {
                return null;
            }

            $issue_id = (int)$_POST['issue_id'];
            $email_address = trim($_POST['email_address']);
            if (filter_var($email_address, FILTER_VALIDATE_EMAIL) === false) {
                return new ErrorIncompleteNAER($issue_id, $email_address);
            }

            try {
                $query = $this->db->prepare(
                    'INSERT OR IGNORE INTO issue_x_additional_email '
                    . '(issue_id, created_on, author_identity, author_email, email_address) '
                    . 'VALUES (?, ?, ?, ?, ?)'
                );
                $query->execute([
                    $issue_id,
                    date('Y-m-d H:i:s'),
                    $this->current_user_identity_serialized(),
                    auth_get_user_email(),
                    $email_address
                ]);

                if ($query->rowCount() === 0) {
                    return new ErrorDuplicateNAER($issue_id, $email_address);
                }

                return new SuccessNAER($issue_id, $email_address);
            }
            catch (PDOException $e) {
                return new ErrorDbNAER($issue_id, (string)$e);
            }
        }

        function handle_new_tag(): ?HandleNewTagResult {
            if (
                $_SERVER['REQUEST_METHOD'] !== 'POST' ||
                !isset($_POST['req']) ||
                $_POST['req'] !== 'new-tag' ||
                !isset($_POST['tag_id']) ||
                !is_string($_POST['tag_id']) ||
                !isset($_POST['name']) ||
                !is_string($_POST['name']) ||
                (isset($_POST['has_client_data']) && (!isset($_POST['client_data']) || !is_string($_POST['client_data']))) ||
                (isset($_POST['additional_email_addresses']) && !is_string($_POST['additional_email_addresses'])) ||
                (isset($_POST['url']) && !is_string($_POST['url'])) ||
                !$this->may_administer() ||
                !$this->accept_submission_token('new-tag')
            ) {
                return null;
            }

            $tag_id = trim($_POST['tag_id']);
            $name = trim($_POST['name']);
            $client_data = isset($_POST['has_client_data']) ? $_POST['client_data'] : null;
            $url = trim($_POST['url'] ?? '');
            $url = $url === '' ? null : $url;
            $parsed_email_addresses = $this->parse_email_address_list(
                $_POST['additional_email_addresses'] ?? ''
            );
            if ($tag_id === '' || $name === '') {
                return new ErrorIncompleteNTR(
                    $tag_id,
                    $name,
                    $client_data,
                    $parsed_email_addresses['submitted'],
                    $url
                );
            }
            if (!$this->valid_tag_url($url)) {
                return new ErrorInvalidUrlNTR($tag_id, $name, $client_data, $parsed_email_addresses['submitted'], $url);
            }
            if (count($parsed_email_addresses['invalid']) > 0) {
                return new ErrorInvalidEmailAddressesNTR(
                    $tag_id,
                    $name,
                    $client_data,
                    $parsed_email_addresses['submitted'],
                    $url
                );
            }

            try {
                $this->db->beginTransaction();
                $query = $this->db->prepare('INSERT INTO tag (id, name, client_data, url) VALUES (?, ?, ?, ?)');
                $query->execute([$tag_id, $name, $client_data, $url]);
                $this->insert_tag_additional_email_addresses(
                    $tag_id,
                    $parsed_email_addresses['valid']
                );
                $this->db->commit();

                return new SuccessNTR(
                    $tag_id,
                    $name,
                    $client_data,
                    $parsed_email_addresses['valid'],
                    $url
                );
            }
            catch (PDOException $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return new ErrorDbNTR(
                    $tag_id,
                    $name,
                    $client_data,
                    $parsed_email_addresses['submitted'],
                    (string)$e,
                    $url
                );
            }
        }

        function handle_edit_tag(): ?HandleEditTagResult {
            if (
                $_SERVER['REQUEST_METHOD'] !== 'POST' ||
                !isset($_POST['req']) ||
                $_POST['req'] !== 'edit-tag' ||
                !isset($_POST['tag_id']) ||
                !is_string($_POST['tag_id']) ||
                !isset($_POST['name']) ||
                !is_string($_POST['name']) ||
                (isset($_POST['has_client_data']) && (!isset($_POST['client_data']) || !is_string($_POST['client_data']))) ||
                (isset($_POST['additional_email_addresses']) && !is_string($_POST['additional_email_addresses'])) ||
                (isset($_POST['url']) && !is_string($_POST['url'])) ||
                !$this->may_administer() ||
                !$this->accept_submission_token('edit-tag')
            ) {
                return null;
            }

            $tag_id = $_POST['tag_id'];
            $name = trim($_POST['name']);
            $client_data = isset($_POST['has_client_data']) ? $_POST['client_data'] : null;
            $url = trim($_POST['url'] ?? '');
            $url = $url === '' ? null : $url;
            $parsed_email_addresses = $this->parse_email_address_list(
                $_POST['additional_email_addresses'] ?? ''
            );
            if ($tag_id === '' || $name === '') {
                return new ErrorIncompleteETR(
                    $tag_id,
                    $name,
                    $client_data,
                    $parsed_email_addresses['submitted'],
                    $url
                );
            }
            if (!$this->valid_tag_url($url)) {
                return new ErrorInvalidUrlETR($tag_id, $name, $client_data, $parsed_email_addresses['submitted'], $url);
            }
            if (count($parsed_email_addresses['invalid']) > 0) {
                return new ErrorInvalidEmailAddressesETR(
                    $tag_id,
                    $name,
                    $client_data,
                    $parsed_email_addresses['submitted'],
                    $url
                );
            }

            try {
                $query = $this->db->prepare('SELECT 1 FROM tag WHERE id = ?');
                $query->execute([$tag_id]);
                if ($query->fetchColumn() === false) {
                    return null;
                }

                $this->db->beginTransaction();
                $query = $this->db->prepare('UPDATE tag SET name = ?, client_data = ?, url = ? WHERE id = ?');
                $query->execute([$name, $client_data, $url, $tag_id]);
                $query = $this->db->prepare('DELETE FROM tag_x_additional_email WHERE tag_id = ?');
                $query->execute([$tag_id]);
                $this->insert_tag_additional_email_addresses(
                    $tag_id,
                    $parsed_email_addresses['valid']
                );
                $this->db->commit();

                return new SuccessETR(
                    $tag_id,
                    $name,
                    $client_data,
                    $parsed_email_addresses['valid'],
                    $url
                );
            }
            catch (PDOException $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return new ErrorDbETR(
                    $tag_id,
                    $name,
                    $client_data,
                    $parsed_email_addresses['submitted'],
                    (string)$e,
                    $url
                );
            }
        }

        /** @return ?array{kind: string, subject?: string, content?: string, sent?: list<array{name: string, addresses: list<string>}>, skipped?: list<string>, failed?: list<array{name: string, addresses: list<string>, error: string}>, details?: string} */
        function handle_mail_tags(): ?array {
            if (
                ($_SERVER['REQUEST_METHOD'] ?? null) !== 'POST' ||
                ($_POST['req'] ?? null) !== 'mail-tags' ||
                !isset($_POST['subject'], $_POST['content']) ||
                !is_string($_POST['subject']) ||
                !is_string($_POST['content']) ||
                !$this->may_administer() ||
                $this->config->mail === null ||
                !$this->accept_submission_token('mail-tags')
            ) {
                return null;
            }

            $subject = $_POST['subject'];
            $content = $_POST['content'];
            if (trim($subject) === '' || trim($content) === '') {
                return ['kind' => 'incomplete', 'subject' => $subject, 'content' => $content];
            }

            try {
                $tags = $this->fetch_tags_with_issue_counts();
            }
            catch (PDOException $e) {
                return ['kind' => 'error_db', 'subject' => $subject, 'content' => $content, 'details' => (string)$e];
            }

            $sent = [];
            $skipped = [];
            $failed = [];
            foreach ($tags as $tag) {
                if ($tag['additional_email_addresses'] === []) {
                    $skipped[] = $tag['name'];
                    continue;
                }

                $values = [
                    '{{url}}' => $tag['url'] ?? '(nicht vorhanden)',
                    '{{tag}}' => $tag['name'],
                    '{{client_data}}' => $tag['client_data'] ?? '(nicht vorhanden)'
                ];
                $error = $this->send_tag_mail(
                    $tag['additional_email_addresses'],
                    strtr($subject, $values),
                    strtr($content, $values)
                );
                if ($error === null) {
                    $sent[] = ['name' => $tag['name'], 'addresses' => $tag['additional_email_addresses']];
                }
                else {
                    $failed[] = ['name' => $tag['name'], 'addresses' => $tag['additional_email_addresses'], 'error' => $error];
                    log_error_silently('Failed to send tag e-mail for ' . $tag['id'] . ': ' . $error);
                }
            }

            return ['kind' => 'done', 'sent' => $sent, 'skipped' => $skipped, 'failed' => $failed];
        }

        /** @param list<string> $addresses Returns an error message, or NULL on success. */
        private function send_tag_mail(array $addresses, string $subject, string $body): ?string {
            try {
                $mail = new PHPMailer();
                $mail->CharSet = 'UTF-8';
                if (!$mail->setFrom(get_global_config('mail', 'senderEmail'))) {
                    return $mail->ErrorInfo !== '' ? $mail->ErrorInfo : 'Unbekannter E-Mail-Fehler';
                }
                foreach ($addresses as $address) {
                    if (!$mail->addAddress($address)) {
                        return $mail->ErrorInfo !== '' ? $mail->ErrorInfo : 'Unbekannter E-Mail-Fehler';
                    }
                }
                $mail->Subject = $subject;
                $mail->Body = $body;
                return $mail->send() ? null : ($mail->ErrorInfo !== '' ? $mail->ErrorInfo : 'Unbekannter E-Mail-Fehler');
            }
            catch (\Throwable $e) {
                return $e->getMessage();
            }
        }

        function handle_edit_issue(array $tags): ?HandleEditIssueResult {
            if (
                $_SERVER['REQUEST_METHOD'] !== 'POST' ||
                !isset($_POST['req']) ||
                $_POST['req'] !== 'edit-issue' ||
                !isset($_POST['issue_id']) ||
                !(is_string($_POST['issue_id']) || is_int($_POST['issue_id'])) ||
                !isset($_POST['summary']) ||
                !is_string($_POST['summary']) ||
                !$this->may_administer_issue_by_id((int)$_POST['issue_id']) ||
                !$this->accept_submission_token('edit-issue')
            ) {
                return null;
            }

            $issue_id = (int)$_POST['issue_id'];
            $valid_tag_ids = array_map(fn($tag) => (string)$tag['id'], $tags);
            $selected_tag_ids = isset($_POST['tags']) && is_array($_POST['tags'])
                ? array_values(array_unique(array_filter(
                    array_map('strval', array_filter(
                        $_POST['tags'],
                        fn($tag_id) => is_string($tag_id) || is_int($tag_id)
                    )),
                    fn($tag_id) => in_array($tag_id, $valid_tag_ids, strict: true)
                )))
                : [];

            try {
                $this->db->beginTransaction();

                $query = $this->db->prepare('UPDATE issue SET summary = ? WHERE id = ?');
                $query->execute([$_POST['summary'], $issue_id]);

                $query = $this->db->prepare('DELETE FROM issue_x_tag WHERE issue_id = ?');
                $query->execute([$issue_id]);

                $query = $this->db->prepare('INSERT INTO issue_x_tag (issue_id, tag_id) VALUES (?, ?)');
                foreach ($selected_tag_ids as $tag_id) {
                    $query->execute([$issue_id, $tag_id]);
                }

                $this->db->commit();

                return new SuccessEIR($issue_id);
            }
            catch (PDOException $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }

                return new ErrorDbEIR($issue_id, (string)$e);
            }
        }

        function handle_edit_post(): ?HandleEditPostResult {
            if (
                $_SERVER['REQUEST_METHOD'] !== 'POST' ||
                !isset($_POST['req']) ||
                $_POST['req'] !== 'edit-post' ||
                !isset($_POST['issue_id']) ||
                !(is_string($_POST['issue_id']) || is_int($_POST['issue_id'])) ||
                !isset($_POST['post_id']) ||
                !(is_string($_POST['post_id']) || is_int($_POST['post_id'])) ||
                !isset($_POST['content']) ||
                !is_string($_POST['content']) ||
                !isset($_POST['new_status']) ||
                !is_string($_POST['new_status']) ||
                !in_array($_POST['new_status'], ['open', 'closed'], strict: true) ||
                !$this->may_administer_post_by_id((int)$_POST['issue_id'], (int)$_POST['post_id']) ||
                !$this->accept_submission_token('edit-post')
            ) {
                return null;
            }

            $issue_id = (int)$_POST['issue_id'];
            $post_id = (int)$_POST['post_id'];

            if ($_POST['content'] === '') {
                return new ErrorIncompleteEPR(
                    issue_id: $issue_id,
                    post_id: $post_id,
                    content: $_POST['content'],
                    selected_status: $_POST['new_status']
                );
            }

            try {
                $this->db->beginTransaction();

                $query = $this->db->prepare(
                    'UPDATE post SET content = ?, new_status = ? WHERE id = ? AND issue_id = ?'
                );
                $query->execute([$_POST['content'], $_POST['new_status'], $post_id, $issue_id]);

                $query = $this->db->prepare(
                    'UPDATE issue SET last_post_status = ('
                    . 'SELECT new_status FROM post WHERE issue_id = ? '
                    . 'ORDER BY created_on DESC, id DESC LIMIT 1'
                    . ') WHERE id = ?'
                );
                $query->execute([$issue_id, $issue_id]);

                $this->db->commit();

                return new SuccessEPR($issue_id, $post_id);
            }
            catch (PDOException $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }

                return new ErrorDbEPR($issue_id, $post_id, (string)$e);
            }
        }

        function handle_delete_issue(): ?HandleDeleteIssueResult {
            if (
                $_SERVER['REQUEST_METHOD'] !== 'POST' ||
                !isset($_POST['req']) ||
                $_POST['req'] !== 'delete-issue' ||
                !isset($_POST['issue_id']) ||
                !(is_string($_POST['issue_id']) || is_int($_POST['issue_id'])) ||
                !$this->may_administer_issue_by_id((int)$_POST['issue_id']) ||
                !$this->accept_submission_token('delete-issue')
            ) {
                return null;
            }

            $issue_id = (int)$_POST['issue_id'];

            try {
                $this->db->beginTransaction();

                $query = $this->db->prepare('DELETE FROM issue_x_tag WHERE issue_id = ?');
                $query->execute([$issue_id]);

                $query = $this->db->prepare('DELETE FROM issue_x_additional_email WHERE issue_id = ?');
                $query->execute([$issue_id]);

                $query = $this->db->prepare('DELETE FROM post WHERE issue_id = ?');
                $query->execute([$issue_id]);

                $query = $this->db->prepare('DELETE FROM issue WHERE id = ?');
                $query->execute([$issue_id]);

                $this->db->commit();

                return new SuccessDIR($issue_id);
            }
            catch (PDOException $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }

                return new ErrorDbDIR($issue_id, (string)$e);
            }
        }

        function handle_delete_post(): ?HandleDeletePostResult {
            if (
                $_SERVER['REQUEST_METHOD'] !== 'POST' ||
                !isset($_POST['req']) ||
                $_POST['req'] !== 'delete-post' ||
                !isset($_POST['issue_id']) ||
                !(is_string($_POST['issue_id']) || is_int($_POST['issue_id'])) ||
                !isset($_POST['post_id']) ||
                !(is_string($_POST['post_id']) || is_int($_POST['post_id'])) ||
                !$this->may_administer_post_by_id((int)$_POST['issue_id'], (int)$_POST['post_id']) ||
                !$this->accept_submission_token('delete-post')
            ) {
                return null;
            }

            $issue_id = (int)$_POST['issue_id'];
            $post_id = (int)$_POST['post_id'];

            try {
                $this->db->beginTransaction();

                $query = $this->db->prepare('DELETE FROM post WHERE id = ? AND issue_id = ?');
                $query->execute([$post_id, $issue_id]);
                if ($query->rowCount() === 0) {
                    $this->db->rollBack();
                    return null;
                }

                $query = $this->db->prepare(
                    'SELECT created_on, author_identity FROM post WHERE issue_id = ? '
                    . 'ORDER BY created_on ASC, id ASC LIMIT 1'
                );
                $query->execute([$issue_id]);
                $first_post = $query->fetch(PDO::FETCH_ASSOC);

                $issue_deleted = $first_post === false;
                if ($issue_deleted) {
                    $query = $this->db->prepare('DELETE FROM issue_x_tag WHERE issue_id = ?');
                    $query->execute([$issue_id]);

                    $query = $this->db->prepare('DELETE FROM issue_x_additional_email WHERE issue_id = ?');
                    $query->execute([$issue_id]);

                    $query = $this->db->prepare('DELETE FROM issue WHERE id = ?');
                    $query->execute([$issue_id]);
                }
                else {
                    $query = $this->db->prepare(
                        'SELECT created_on, new_status FROM post WHERE issue_id = ? '
                        . 'ORDER BY created_on DESC, id DESC LIMIT 1'
                    );
                    $query->execute([$issue_id]);
                    $last_post = $query->fetch(PDO::FETCH_ASSOC);

                    $query = $this->db->prepare(
                        'UPDATE issue SET first_post_created_on = ?, first_post_author_identity = ?, '
                        . 'last_post_created_on = ?, last_post_status = ? WHERE id = ?'
                    );
                    $query->execute([
                        $first_post['created_on'],
                        $first_post['author_identity'],
                        $last_post['created_on'],
                        $last_post['new_status'],
                        $issue_id
                    ]);
                }

                $this->db->commit();

                return new SuccessDPR($issue_id, $post_id, $issue_deleted);
            }
            catch (PDOException $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }

                return new ErrorDbDPR($issue_id, $post_id, (string)$e);
            }
        }

        function handle_delete_additional_email(): ?HandleDeleteAdditionalEmailResult {
            if (
                $_SERVER['REQUEST_METHOD'] !== 'POST' ||
                !isset($_POST['req']) ||
                $_POST['req'] !== 'delete-additional-email' ||
                !isset($_POST['issue_id']) ||
                !(is_string($_POST['issue_id']) || is_int($_POST['issue_id'])) ||
                !isset($_POST['additional_email_id']) ||
                !(is_string($_POST['additional_email_id']) || is_int($_POST['additional_email_id'])) ||
                !$this->may_administer() ||
                !$this->accept_submission_token('delete-additional-email')
            ) {
                return null;
            }

            $issue_id = (int)$_POST['issue_id'];
            $additional_email_id = (int)$_POST['additional_email_id'];

            try {
                $query = $this->db->prepare(
                    'DELETE FROM issue_x_additional_email WHERE id = ? AND issue_id = ?'
                );
                $query->execute([$additional_email_id, $issue_id]);
                if ($query->rowCount() === 0) {
                    return null;
                }

                return new SuccessDAER($issue_id, $additional_email_id);
            }
            catch (PDOException $e) {
                return new ErrorDbDAER($issue_id, $additional_email_id, (string)$e);
            }
        }

        
        /////////////////////////
        // Controller: Helpers //
        /////////////////////////

        private function valid_tag_url(?string $url): bool {
            if ($url === null) {
                return true;
            }
            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                return false;
            }
            return in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true);
        }

        /**
         * @return array{submitted: list<string>, valid: list<string>, invalid: list<string>}
         */
        private function parse_email_address_list(string $input): array {
            $tokens = preg_split('/[\s,;]+/u', trim($input), -1, PREG_SPLIT_NO_EMPTY);
            $tokens = $tokens === false ? [] : $tokens;
            $submitted = [];
            $valid = [];
            $invalid = [];
            $seen_addresses = [];

            foreach ($tokens as $address) {
                $address = trim($address);
                $deduplication_key = strtolower($address);
                if ($address === '' || isset($seen_addresses[$deduplication_key])) {
                    continue;
                }

                $seen_addresses[$deduplication_key] = true;
                $submitted[] = $address;
                if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
                    $invalid[] = $address;
                }
                else {
                    $valid[] = $address;
                }
            }

            return [
                'submitted' => $submitted,
                'valid' => $valid,
                'invalid' => $invalid
            ];
        }

        /** @param list<string> $addresses */
        private function insert_tag_additional_email_addresses(
            string $tag_id,
            array $addresses
        ): void {
            $query = $this->db->prepare(
                'INSERT INTO tag_x_additional_email (tag_id, email_address) VALUES (?, ?)'
            );
            foreach ($addresses as $address) {
                $query->execute([$tag_id, $address]);
            }
        }

        /** @return list<array{email_address: string, reasons: list<string>}> */
        private function collect_notification_recipients(int $issue_id): array {
            $recipients = [];
            foreach ($this->config->mail->always_notify_addresses as $email_address) {
                if (is_string($email_address)) {
                    $this->add_notification_recipient(
                        $recipients,
                        $email_address,
                        $this->config->mail->reason_always_notify
                    );
                }
            }

            $query = $this->db->prepare(
                'SELECT author_email AS email_address FROM post '
                . 'WHERE issue_id = ? AND author_email IS NOT NULL '
                . 'UNION ALL '
                . 'SELECT author_email AS email_address FROM issue_x_additional_email '
                . 'WHERE issue_id = ? AND author_email IS NOT NULL '
                . 'UNION ALL '
                . 'SELECT email_address FROM issue_x_additional_email WHERE issue_id = ?'
            );
            $query->execute([$issue_id, $issue_id, $issue_id]);
            foreach ($query as $row) {
                $this->add_notification_recipient(
                    $recipients,
                    $row['email_address'],
                    $this->config->mail->reason_participates_in_issue
                );
            }

            $query = $this->db->prepare(
                'SELECT tag_x_additional_email.email_address, tag.name AS tag_name '
                . 'FROM issue_x_tag '
                . 'JOIN tag ON issue_x_tag.tag_id = tag.id '
                . 'JOIN tag_x_additional_email ON tag.id = tag_x_additional_email.tag_id '
                . 'WHERE issue_x_tag.issue_id = ? '
                . 'ORDER BY tag.name COLLATE NOCASE, tag.id, '
                . 'tag_x_additional_email.email_address COLLATE NOCASE'
            );
            $query->execute([$issue_id]);

            foreach ($query as $row) {
                $this->add_tag_notification_recipient(
                    $recipients,
                    $row['email_address'],
                    $row['tag_name']
                );
            }

            return array_values($recipients);
        }

        /** @param array<string, array{email_address: string, reasons: list<string>}> $recipients */
        private function add_notification_recipient(
            array &$recipients,
            string $email_address,
            string $reason
        ): void {
            $email_address = trim($email_address);
            $reason = trim($reason);
            if ($email_address === '') {
                return;
            }

            $deduplication_key = strtolower($email_address);
            $recipients[$deduplication_key] ??= [
                'email_address' => $email_address,
                'reasons' => []
            ];
            if (!in_array($reason, $recipients[$deduplication_key]['reasons'], true)) {
                $recipients[$deduplication_key]['reasons'][] = $reason;
            }
        }

        /** @param array<string, array{email_address: string, reasons: list<string>}> $recipients */
        private function add_tag_notification_recipient(
            array &$recipients,
            string $email_address,
            string $tag_name
        ): void {
            $email_address = trim($email_address);
            $email_address = $recipients[strtolower($email_address)]['email_address'] ?? $email_address;
            $this->add_notification_recipient(
                $recipients,
                $email_address,
                strtr($this->config->mail->reason_tag_additional_email, [
                    '{email_address}' => $email_address,
                    '{tag_name}' => $tag_name
                ])
            );
        }

        /** @param list<array{email_address: string, reasons: list<string>}> $recipients */
        private function append_notification_block(string $body, array $recipients): string {
            if (count($recipients) === 0) {
                return $body;
            }

            $notification_block = trim(
                ($this->config->mail->notification_block)($recipients)
            );

            return $notification_block === ''
                ? $body
                : $body . "\n\n" . $notification_block;
        }

        /** @param list<array{email_address: string, reasons: list<string>}> $recipients */
        private function send_mail_and_log(array $recipients, string $subject, string $body): bool {
            $mail = new PHPMailer();
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(get_global_config('mail', 'senderEmail'));
            foreach ($recipients as $recipient) {
                $mail->addAddress($recipient['email_address']);
            }
            $mail->Subject = $subject;
            $mail->Body = $body;
            $send_res = $mail->send();

            if ($send_res) {
                return true;
            }
            else {
                log_error_silently('Failed to send notification e-mails: ' . $mail->ErrorInfo);

                return false;
            }
        }
    }
?>
