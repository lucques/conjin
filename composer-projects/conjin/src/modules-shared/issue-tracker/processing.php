<?
    namespace Conjin\Modules\IssueTracker;

    use Closure; 
    use PDO;
    use PDOException;
    use PHPMailer\PHPMailer\PHPMailer;


    /////////////////////
    // Pure data types //
    /////////////////////

    readonly class MailConfig {
        public function __construct(
            public array $always_notify_addresses,
            public Closure $new_issue_template_subject,
            public Closure $new_issue_template_body,
            public Closure $new_post_template_subject,
            public Closure $new_post_template_body,
        ) {}
    }

    readonly class ViewConfig {
        public function __construct(
            public string $issue_column_width,
            public string $tag_column_width,
            public string $date_column_width,
            public string $author_column_width,
            public string $status_column_width,
            public string $issue_desc_short,
            public string $issue_desc_long,
            public string $post_desc_short,
            public string $post_desc_long,
            public string $tag_desc,
            public Closure $author_identity_to_label, // deserialized author identity -> string
            public Closure $status_to_variant,        // string -> string
            public Closure $status_to_label           // string -> string
        ) {}
    }

    readonly class Config {
        public function __construct(
            public string $file_name,
            public array $view_target_ids,
            public array $tag_names,
            public string $privilege_admin,
            public string $privilege_write,
            public int $may_edit_own_content_within_n_minutes,
            public ViewConfig $view,
            public ?MailConfig $mail
        ) {
            if ($may_edit_own_content_within_n_minutes < 0) {
                throw new \InvalidArgumentException('may_edit_own_content_within_n_minutes must not be negative');
            }
        }
    }

    // NIR stands for NewIssueResult
    enum HandleNewIssueResultKind {
        case SuccessWithMails;
        case SuccessButMailsFailed;
        case SuccessWithoutMails;
        case ErrorIncomplete;
        case ErrorDb;
    }
    interface HandleNewIssueResult {
        function kind(): HandleNewIssueResultKind;
    }
    readonly class SuccessWithMailsNIR implements HandleNewIssueResult {
        function __construct(public array $addresses) {}
        function kind(): HandleNewIssueResultKind { return HandleNewIssueResultKind::SuccessWithMails; }
    }
    readonly class SuccessButMailsFailedNIR implements HandleNewIssueResult {
        function kind(): HandleNewIssueResultKind { return HandleNewIssueResultKind::SuccessButMailsFailed; }
    }
    readonly class SuccessWithoutMailsNIR implements HandleNewIssueResult {
        function kind(): HandleNewIssueResultKind { return HandleNewIssueResultKind::SuccessWithoutMails; }
    }
    readonly class ErrorIncompleteNIR implements HandleNewIssueResult {
        function __construct(public array $selected_tags) {}
        function kind(): HandleNewIssueResultKind { return HandleNewIssueResultKind::ErrorIncomplete; }
    }
    readonly class ErrorDbNIR implements HandleNewIssueResult {
        function __construct(public string $details) {}
        function kind(): HandleNewIssueResultKind { return HandleNewIssueResultKind::ErrorDb; }
    }

    // NPR stands for NewPostResult
    enum HandleNewPostResultKind {
        case SuccessWithMails;
        case SuccessButMailsFailed;
        case SuccessWithoutMails;
        case ErrorIncomplete;
        case ErrorDb;
    }
    interface HandleNewPostResult {
        function kind(): HandleNewPostResultKind;
        function issue_id(): int;
    }
    readonly class SuccessWithMailsNPR implements HandleNewPostResult {
        function __construct(public int $issue_id, public array $addresses) {}
        function kind(): HandleNewPostResultKind { return HandleNewPostResultKind::SuccessWithMails; }
        function issue_id(): int { return $this->issue_id; }
    }
    readonly class SuccessButMailsFailedNPR implements HandleNewPostResult {
        function __construct(public int $issue_id) {}
        function kind(): HandleNewPostResultKind { return HandleNewPostResultKind::SuccessButMailsFailed; }
        function issue_id(): int { return $this->issue_id; }
    }
    readonly class SuccessWithoutMailsNPR implements HandleNewPostResult {
        function __construct(public int $issue_id) {}
        function kind(): HandleNewPostResultKind { return HandleNewPostResultKind::SuccessWithoutMails; }
        function issue_id(): int { return $this->issue_id; }
    }
    readonly class ErrorIncompleteNPR implements HandleNewPostResult {
        function __construct(public int $issue_id, public string $selected_status) {}
        function kind(): HandleNewPostResultKind { return HandleNewPostResultKind::ErrorIncomplete; }
        function issue_id(): int { return $this->issue_id; }
    }
    readonly class ErrorDbNPR implements HandleNewPostResult {
        function __construct(public int $issue_id, public string $details) {}
        function kind(): HandleNewPostResultKind { return HandleNewPostResultKind::ErrorDb; }
        function issue_id(): int { return $this->issue_id; }
    }

    // EIR stands for EditIssueResult
    enum HandleEditIssueResultKind {
        case Success;
        case ErrorDb;
    }
    interface HandleEditIssueResult {
        function kind(): HandleEditIssueResultKind;
        function issue_id(): int;
    }
    readonly class SuccessEIR implements HandleEditIssueResult {
        function __construct(public int $issue_id) {}
        function kind(): HandleEditIssueResultKind { return HandleEditIssueResultKind::Success; }
        function issue_id(): int { return $this->issue_id; }
    }
    readonly class ErrorDbEIR implements HandleEditIssueResult {
        function __construct(public int $issue_id, public string $details) {}
        function kind(): HandleEditIssueResultKind { return HandleEditIssueResultKind::ErrorDb; }
        function issue_id(): int { return $this->issue_id; }
    }

    // EPR stands for EditPostResult
    enum HandleEditPostResultKind {
        case Success;
        case ErrorIncomplete;
        case ErrorDb;
    }
    interface HandleEditPostResult {
        function kind(): HandleEditPostResultKind;
        function issue_id(): int;
        function post_id(): int;
    }
    readonly class SuccessEPR implements HandleEditPostResult {
        function __construct(public int $issue_id, public int $post_id) {}
        function kind(): HandleEditPostResultKind { return HandleEditPostResultKind::Success; }
        function issue_id(): int { return $this->issue_id; }
        function post_id(): int { return $this->post_id; }
    }
    readonly class ErrorIncompleteEPR implements HandleEditPostResult {
        function __construct(
            public int $issue_id,
            public int $post_id,
            public string $content,
            public string $selected_status
        ) {}
        function kind(): HandleEditPostResultKind { return HandleEditPostResultKind::ErrorIncomplete; }
        function issue_id(): int { return $this->issue_id; }
        function post_id(): int { return $this->post_id; }
    }
    readonly class ErrorDbEPR implements HandleEditPostResult {
        function __construct(public int $issue_id, public int $post_id, public string $details) {}
        function kind(): HandleEditPostResultKind { return HandleEditPostResultKind::ErrorDb; }
        function issue_id(): int { return $this->issue_id; }
        function post_id(): int { return $this->post_id; }
    }

    // DIR stands for DeleteIssueResult
    enum HandleDeleteIssueResultKind {
        case Success;
        case ErrorDb;
    }
    interface HandleDeleteIssueResult {
        function kind(): HandleDeleteIssueResultKind;
        function issue_id(): int;
    }
    readonly class SuccessDIR implements HandleDeleteIssueResult {
        function __construct(public int $issue_id) {}
        function kind(): HandleDeleteIssueResultKind { return HandleDeleteIssueResultKind::Success; }
        function issue_id(): int { return $this->issue_id; }
    }
    readonly class ErrorDbDIR implements HandleDeleteIssueResult {
        function __construct(public int $issue_id, public string $details) {}
        function kind(): HandleDeleteIssueResultKind { return HandleDeleteIssueResultKind::ErrorDb; }
        function issue_id(): int { return $this->issue_id; }
    }

    // DPR stands for DeletePostResult
    enum HandleDeletePostResultKind {
        case Success;
        case ErrorDb;
    }
    interface HandleDeletePostResult {
        function kind(): HandleDeletePostResultKind;
        function issue_id(): int;
        function post_id(): int;
        function issue_deleted(): bool;
    }
    readonly class SuccessDPR implements HandleDeletePostResult {
        function __construct(
            public int $issue_id,
            public int $post_id,
            public bool $issue_deleted
        ) {}
        function kind(): HandleDeletePostResultKind { return HandleDeletePostResultKind::Success; }
        function issue_id(): int { return $this->issue_id; }
        function post_id(): int { return $this->post_id; }
        function issue_deleted(): bool { return $this->issue_deleted; }
    }
    readonly class ErrorDbDPR implements HandleDeletePostResult {
        function __construct(public int $issue_id, public int $post_id, public string $details) {}
        function kind(): HandleDeletePostResultKind { return HandleDeletePostResultKind::ErrorDb; }
        function issue_id(): int { return $this->issue_id; }
        function post_id(): int { return $this->post_id; }
        function issue_deleted(): bool { return false; }
    }


    ////////////////
    // Entrypoint //
    ////////////////

    class IssueTracker {

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
        private function new_issue_author(): array {
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
        "id"	INTEGER NOT NULL,
        "name"	TEXT NOT NULL,
        PRIMARY KEY("id" AUTOINCREMENT)
    );
    CREATE TABLE IF NOT EXISTS "issue_x_tag" (
        "issue_id"	INTEGER NOT NULL,
        "tag_id"	INTEGER NOT NULL,
        FOREIGN KEY("issue_id") REFERENCES "issue"("id"),
        FOREIGN KEY("tag_id") REFERENCES "tag"("id")
    );
    SQL;

                if (count($this->config->tag_names) > 0) {
                    $sql .= 'INSERT INTO "tag" (name) VALUES ' . implode(', ', array_map(fn($name) => "('$name')", $this->config->tag_names)) . ';';
                }

                $sql .= ' COMMIT;';

                $this->db->exec($sql);
            }
        }

        function fetch_issues_with_posts_open() {
            return $this->fetch_issues_with_posts(
                where: 'WHERE issue.last_post_status = "open"',
                order_by: 'ORDER BY issue.first_post_created_on DESC'
            );
        }

        function fetch_issues_with_posts_open_by_tag(string $tag_name) {
            return $this->fetch_issues_with_posts(
                where: 'WHERE issue.last_post_status = "open" AND tag.name = "' . $tag_name . '"',
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
                where: 'WHERE issue.last_post_status = "closed" AND tag.name = "' . $tag_name . '"',
                order_by: 'ORDER BY issue.last_post_created_on DESC',
                limit: $how_many !== null ? 'LIMIT ' . $how_many : ''
            );
        }

        private function fetch_issues_with_posts($where = '', $order_by = '', $limit = '') {
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

            $result = $this->db->query($query);

            foreach ($result as $row) {
                $issues_to_posts[$row['id']] = [
                    'id' => $row['id'],
                    'summary' => $row['summary'],
                    'first_post_created_on' => $row['first_post_created_on'],
                    'first_post_author_identity' => $row['first_post_author_identity'],
                    'last_post_status' => $row['last_post_status'], 
                    'tags' => '',
                    'tag_ids' => [],
                    'posts' => []
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

            return $issues_to_posts;
        }

        function fetch_tags() {
            $query = $this->db->query('SELECT id, name FROM tag ORDER BY name');
            $tags = [];

            foreach ($query as $row) {
                $tags[] = [
                    'id' => $row['id'],
                    'name' => $row['name']
                ];
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
                    $this->may_write() &&
                    auth_has_postdedup_uuid()
                ) {
                    if (auth_register_check_postdedup_uuid()) {
                        if ($_POST['content'] !== '') {
                            $tags_ids = array_map(fn($tag) => $tag['id'], $tags);

                            $date            = date('Y-m-d H:i:s');
                            $content         = $_POST['content'] ?? '';
                            $status          = 'open';
                            $author          = $this->new_issue_author();
                            $author_identity = $author['identity'];
                            $author_email    = $author['email'];

                            // Insert issue
                            $query = $this->db->prepare("INSERT INTO issue (summary, last_post_status, last_post_created_on, first_post_created_on, first_post_author_identity) VALUES (?, ?, ?, ?, ?)");
                            $query->execute([$content, $status, $date, $date, $author_identity]);
                            $issue_id = $this->db->lastInsertId();

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

                            // Send e-mails
                            if (isset_global_config('mail') && $this->config->mail !== null) {
                                // Collect addresses:
                                // - Addresses that are always notified
                                // - Address of author, if available and not already in the list
                                $addresses = $this->config->mail->always_notify_addresses;
                                if ($author_email !== null && !in_array($author_email, $addresses)) {
                                    $addresses[] = $author_email;
                                }

                                $subject = ($this->config->mail->new_issue_template_subject)($issue_id, $content);
                                $body = ($this->config->mail->new_issue_template_body)(
                                    $issue_id,
                                    url_full_collect($this->config->view_target_ids) . '#' . IssueTracker::make_view_issue_html_id($issue_id),
                                    $date,
                                    ($this->config->view->author_identity_to_label)($author_identity),
                                    $content
                                );

                                $mail_successful = $this->send_mail_and_log($addresses, $subject, $body);

                                if ($mail_successful) {
                                    return new SuccessWithMailsNIR($addresses);
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
                return new ErrorDbNIR((string)$e);
            }
        }

        function handle_new_post(): ?HandleNewPostResult {
            try {
                if (
                    $_SERVER['REQUEST_METHOD'] === 'POST' &&
                    isset($_POST['req']) &&
                    $_POST['req'] === 'new-post' &&
                    isset($_POST['issue_id']) &&
                    isset($_POST['new_status']) &&
                    $this->may_write() &&
                    auth_has_postdedup_uuid()
                ) {
                    if (auth_register_check_postdedup_uuid()) {
                        if ($_POST['content'] !== '') {
                            $issue_id     = $_POST['issue_id'];
                            $date         = date('Y-m-d H:i:s');
                            $content      = $_POST['content'] ?? '';
                            $new_status   = $_POST['new_status'];

                            $author_identity = $this->current_user_identity_serialized();
                            $author_email    = auth_get_user_email();

                            // Insert post
                            $query = $this->db->prepare("INSERT INTO post (issue_id, created_on, author_identity, author_email, content, new_status) VALUES (?, ?, ?, ?, ?, ?)");
                            $query->execute([$issue_id, $date, $author_identity, $author_email, $content, $new_status]);

                            // Update the issue's last post status and timestamp
                            $query = $this->db->prepare("UPDATE issue SET last_post_status = ?, last_post_created_on = ? WHERE id = ?");
                            $query->execute([$new_status, $date, $issue_id]);

                            // Send e-mails
                            if (isset_global_config('mail') && $this->config->mail !== null) {
                                // Collect addresses:
                                // - Addresses that are always notified
                                // - Fetch all users from db that are subscribed to this issue
                                $addresses = $this->config->mail->always_notify_addresses;
                                $query = $this->db->prepare("SELECT DISTINCT author_email FROM post WHERE issue_id = ? AND author_email IS NOT NULL");
                                $query->execute([$issue_id]);
                                $addresses = array_values(array_unique(array_merge(
                                    $addresses,
                                    $query->fetchAll(PDO::FETCH_COLUMN, 0)
                                )));

                                // Prepare e-mail
                                $subject = ($this->config->mail->new_post_template_subject)($issue_id);
                                $body = ($this->config->mail->new_post_template_body)(
                                    $issue_id,
                                    url_full_collect($this->config->view_target_ids) . '#' . IssueTracker::make_view_issue_html_id($issue_id),
                                    $date,
                                    auth_get_user_name(),
                                    $content,
                                    $new_status
                                );
                                
                                $mail_successful = $this->send_mail_and_log($addresses, $subject, $body);

                                if ($mail_successful) {
                                    return new SuccessWithMailsNPR($issue_id, $addresses);
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
                !auth_has_postdedup_uuid() ||
                !auth_register_check_postdedup_uuid()
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
                !auth_has_postdedup_uuid() ||
                !auth_register_check_postdedup_uuid()
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
                !auth_has_postdedup_uuid() ||
                !auth_register_check_postdedup_uuid()
            ) {
                return null;
            }

            $issue_id = (int)$_POST['issue_id'];

            try {
                $this->db->beginTransaction();

                $query = $this->db->prepare('DELETE FROM issue_x_tag WHERE issue_id = ?');
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
                !auth_has_postdedup_uuid() ||
                !auth_register_check_postdedup_uuid()
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

        
        /////////////////////////
        // Controller: Helpers //
        /////////////////////////

        private function send_mail_and_log(array $addresses, string $subject, string $body): bool {
            $mail = new PHPMailer();
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(get_global_config('mail', 'senderEmail'));
            foreach ($addresses as $address) {
                $mail->addAddress($address);
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


        //////////
        // View //
        //////////

        private function at_sentence_start(string $text): string {
            return ucfirst($text);
        }

        private function own_content_edit_window_hint(string $content_desc): string {
            $minutes = $this->config->may_edit_own_content_within_n_minutes;
            if ($minutes === 0 || $this->may_administer()) {
                return '';
            }

            $unit = $minutes === 1 ? 'Minute' : 'Minuten';

            return '<p><em>' . $this->at_sentence_start($content_desc)
                . ' kann noch ' . $minutes . ' ' . $unit
                . ' lang von Ihnen bearbeitet oder gelöscht werden.</em></p>';
        }

        // Display success or error message of the new-issue-handling
        function print_handle_new_issue_res(?HandleNewIssueResult $res) {
            // Only display something if there is something to display
            if ($res !== null) {
                if (
                    $res->kind() == HandleNewIssueResultKind::SuccessWithMails ||
                    $res->kind() == HandleNewIssueResultKind::SuccessButMailsFailed ||
                    $res->kind() == HandleNewIssueResultKind::SuccessWithoutMails
                ) {
                    $message = match ($res->kind()) {
                        HandleNewIssueResultKind::SuccessWithMails =>
                            '<p>' . $this->at_sentence_start($this->config->view->issue_desc_long)
                            . ' wurde angelegt und E-Mails zur Benachrichtigung wurden gesendet an:</p><ul>'
                            . implode('', array_map(fn($addr) => "<li>{$addr}</li>", $res->addresses))
                            . '</ul>',
                        HandleNewIssueResultKind::SuccessButMailsFailed =>
                            '<p>' . $this->at_sentence_start($this->config->view->issue_desc_long)
                            . ' wurde angelegt, aber E-Mails zur Benachrichtigung konnten nicht gesendet werden. Das Problem mit der fehlgeschlagenen E-Mail-Benachrichtigung wurde gemeldet.</p>',
                        HandleNewIssueResultKind::SuccessWithoutMails =>
                            '<p>' . $this->at_sentence_start($this->config->view->issue_desc_long)
                            . ' wurde angelegt, jedoch wurden keine E-Mails zur Benachrichtigung gesendet (dies entspricht der gewünschten Konfiguration).</p>'
                    };
                    $message .= $this->own_content_edit_window_hint(
                        $this->config->view->issue_desc_long
                    );

                    echo '<div class="alert alert-success">' . $message . '</div>';
                }
                elseif (
                    // Incomplete form data error is not displayed here (already displayed in modal)
                    $res->kind() == HandleNewIssueResultKind::ErrorDb
                ) {
                    $message =
                        'Fehler: Es ist ein Datenbankfehler aufgetreten.<pre>'
                        . htmlspecialchars($res->details, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                        . '</pre>';

                    echo '<div class="alert alert-danger">' . $message . '</div>';
                }
            }
        }

        // Display success or error message of the new-post-handling
        function print_handle_new_post_res(?HandleNewPostResult $res) {
            // Only display something if there is something to display
            if ($res !== null) {
                if (
                    $res->kind() == HandleNewPostResultKind::SuccessWithMails ||
                    $res->kind() == HandleNewPostResultKind::SuccessButMailsFailed ||
                    $res->kind() == HandleNewPostResultKind::SuccessWithoutMails
                ) {
                    $message = match ($res->kind()) {
                        HandleNewPostResultKind::SuccessWithMails =>
                            '<p>' . $this->at_sentence_start($this->config->view->post_desc_long)
                            . ' wurde angelegt und E-Mails zur Benachrichtigung wurden gesendet an:</p><ul>'
                            . implode('', array_map(fn($addr) => "<li>{$addr}</li>", $res->addresses))
                            . '</ul>',
                        HandleNewPostResultKind::SuccessButMailsFailed =>
                            '<p>' . $this->at_sentence_start($this->config->view->post_desc_long)
                            . ' wurde angelegt, aber E-Mails zur Benachrichtigung konnten nicht gesendet werden. Das Problem mit der fehlgeschlagenen E-Mail-Benachrichtigung wurde gemeldet.</p>',
                        HandleNewPostResultKind::SuccessWithoutMails =>
                            '<p>' . $this->at_sentence_start($this->config->view->post_desc_long)
                            . ' wurde angelegt, jedoch wurden keine E-Mails zur Benachrichtigung gesendet (dies entspricht der gewünschten Konfiguration).</p>'
                    };
                    $message .= $this->own_content_edit_window_hint(
                        $this->config->view->post_desc_long
                    );

                    echo '<div class="alert alert-success">' . $message . '</div>';
                }
                else {
                    $message = match ($res->kind()) {
                        HandleNewPostResultKind::ErrorIncomplete =>
                            'Fehler: Bitte füllen Sie alle erforderlichen Felder aus.',
                        HandleNewPostResultKind::ErrorDb =>
                            'Fehler: Es ist ein Datenbankfehler aufgetreten.<pre>'
                            . htmlspecialchars($res->details, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                            . '</pre>'
                    };

                    echo '<div class="alert alert-danger">' . $message . '</div>';
                }
            }
        }

        // Display success or error message of the edit-issue handling
        function print_handle_edit_issue_res(?HandleEditIssueResult $res) {
            if ($res === null) {
                return;
            }

            if ($res->kind() === HandleEditIssueResultKind::Success) {
                echo '<div class="alert alert-success"><p>'
                    . $this->at_sentence_start($this->config->view->issue_desc_long)
                    . ' #' . $res->issue_id()
                    . ' wurde bearbeitet.</p></div>';
            }
            elseif ($res->kind() === HandleEditIssueResultKind::ErrorDb) {
                $message = 'Fehler: Beim Bearbeiten von '
                    . $this->config->view->issue_desc_long . ' #' . $res->issue_id()
                    . ' ist ein Datenbankfehler aufgetreten.<pre>'
                    . htmlspecialchars($res->details, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '</pre>';

                echo '<div class="alert alert-danger">' . $message . '</div>';
            }
        }

        // Display success or error message of the edit-post handling
        function print_handle_edit_post_res(?HandleEditPostResult $res) {
            if ($res === null) {
                return;
            }

            if ($res->kind() === HandleEditPostResultKind::Success) {
                echo '<div class="alert alert-success"><p>'
                    . $this->at_sentence_start($this->config->view->post_desc_long)
                    . ' #' . $res->post_id()
                    . ' wurde bearbeitet.</p></div>';
            }
            elseif ($res->kind() === HandleEditPostResultKind::ErrorDb) {
                $message = 'Fehler: Beim Bearbeiten von '
                    . $this->config->view->post_desc_long . ' #' . $res->post_id()
                    . ' ist ein Datenbankfehler aufgetreten.<pre>'
                    . htmlspecialchars($res->details, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '</pre>';

                echo '<div class="alert alert-danger">' . $message . '</div>';
            }
        }

        // Display success or error message of the delete-issue handling
        function print_handle_delete_issue_res(?HandleDeleteIssueResult $res) {
            if ($res === null) {
                return;
            }

            if ($res->kind() === HandleDeleteIssueResultKind::Success) {
                echo '<div class="alert alert-success"><p>'
                    . $this->at_sentence_start($this->config->view->issue_desc_long)
                    . ' #' . $res->issue_id()
                    . ' wurde vollständig gelöscht.</p></div>';
            }
            elseif ($res->kind() === HandleDeleteIssueResultKind::ErrorDb) {
                $message = 'Fehler: Beim Löschen von '
                    . $this->config->view->issue_desc_long . ' #' . $res->issue_id()
                    . ' ist ein Datenbankfehler aufgetreten.<pre>'
                    . htmlspecialchars($res->details, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '</pre>';

                echo '<div class="alert alert-danger">' . $message . '</div>';
            }
        }

        // Display success or error message of the delete-post handling
        function print_handle_delete_post_res(?HandleDeletePostResult $res) {
            if ($res === null) {
                return;
            }

            if ($res->kind() === HandleDeletePostResultKind::Success) {
                $message = $this->at_sentence_start($this->config->view->post_desc_long)
                    . ' #' . $res->post_id()
                    . ' wurde vollständig gelöscht.';
                if ($res->issue_deleted()) {
                    $message .= ' Da dies der letzte Eintrag war, wurde auch '
                        . $this->config->view->issue_desc_long . ' #' . $res->issue_id()
                        . ' gelöscht.';
                }

                echo '<div class="alert alert-success"><p>' . $message . '</p></div>';
            }
            elseif ($res->kind() === HandleDeletePostResultKind::ErrorDb) {
                $message = 'Fehler: Beim Löschen von '
                    . $this->config->view->post_desc_long . ' #' . $res->post_id()
                    . ' ist ein Datenbankfehler aufgetreten.<pre>'
                    . htmlspecialchars($res->details, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '</pre>';

                echo '<div class="alert alert-danger">' . $message . '</div>';
            }
        }

        function print_edit_issue_modal(array $tags): ?string {
            $id = next_unique_id();
            $modal_id = 'edit-issue-modal-' . $id;
            $modal_title_id = $modal_id . '-title';
            $summary_id = $modal_id . '-summary';
            $tags_id = $modal_id . '-tags';
?>
<div class="modal" id="<?= $modal_id ?>" tabindex="-1" aria-labelledby="<?= $modal_title_id ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="<?= $modal_title_id ?>"><?= $this->config->view->issue_desc_short ?> bearbeiten</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form method="post">
                <input type="hidden" name="req" value="edit-issue">
                <input type="hidden" name="request_uuid" value="<?= auth_get_cur_postdedup_uuid() ?>">
                <input type="hidden" name="issue_id" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="<?= $tags_id ?>" class="form-label"><?= $this->config->view->tag_desc ?></label>
                        <select class="form-select tom-select" id="<?= $tags_id ?>" name="tags[]" multiple>
<?
            foreach ($tags as $tag) {
                echo '<option value="' . htmlspecialchars((string)$tag['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">'
                    . htmlspecialchars($tag['name']) . '</option>';
            }
?>
                        </select>
                    </div>
                    <div>
                        <label for="<?= $summary_id ?>" class="form-label">Zusammenfassung</label>
                        <input type="text" class="form-control" id="<?= $summary_id ?>" name="summary" value="">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(() => {
    const modalId = <?= json_encode($modal_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const issueLabel = <?= json_encode($this->config->view->issue_desc_short, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const modal = document.getElementById(modalId);
    const form = modal?.querySelector('form');
    const title = modal?.querySelector('.modal-title');
    const issueIdInput = form?.querySelector('[name="issue_id"]');
    const summaryInput = form?.querySelector('[name="summary"]');
    const tagsSelect = form?.querySelector('[name="tags[]"]');

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-issue-edit-modal]');
        if (!trigger || trigger.dataset.issueEditModal !== modalId || !form) return;

        form.reset();
        issueIdInput.value = trigger.dataset.issueId;
        summaryInput.value = trigger.dataset.issueSummary;

        const selectedTagIds = new Set(JSON.parse(trigger.dataset.issueTagIds || '[]').map(String));
        [...tagsSelect.options].forEach(option => {
            option.selected = selectedTagIds.has(option.value);
        });
        tagsSelect.tomselect?.sync?.();

        title.textContent = `${issueLabel} #${trigger.dataset.issueId} bearbeiten`;
    }, true);
})();
</script>
<?
            return $modal_id;
        }

        function print_edit_post_modal(
            ?HandleEditPostResult $handle_edit_post_res = null
        ): ?string {
            $id = next_unique_id();
            $modal_id = 'edit-post-modal-' . $id;
            $modal_title_id = $modal_id . '-title';
            $status_id = $modal_id . '-status';
            $content_id = $modal_id . '-content';
            $form_was_incomplete = $handle_edit_post_res !== null &&
                $handle_edit_post_res->kind() === HandleEditPostResultKind::ErrorIncomplete;
?>
<div class="modal" id="<?= $modal_id ?>" tabindex="-1" aria-labelledby="<?= $modal_title_id ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="<?= $modal_title_id ?>"><?= $this->config->view->post_desc_short ?> bearbeiten</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form method="post">
                <input type="hidden" name="req" value="edit-post">
                <input type="hidden" name="request_uuid" value="<?= auth_get_cur_postdedup_uuid() ?>">
                <input type="hidden" name="issue_id" value="">
                <input type="hidden" name="post_id" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="<?= $status_id ?>" class="form-label">Status</label>
                        <select class="form-select" id="<?= $status_id ?>" name="new_status">
<?
            foreach (['open', 'closed'] as $status) {
                $label = ($this->config->view->status_to_label)($status);
?>
                            <option value="<?= $status ?>"><?= $label ?></option>
<?
            }
?>
                        </select>
                    </div>
                    <div>
                        <label for="<?= $content_id ?>" class="form-label">Beschreibung</label>
                        <textarea class="form-control<?= $form_was_incomplete ? ' is-invalid' : '' ?>" id="<?= $content_id ?>" name="content" rows="5"></textarea>
<?
            if ($form_was_incomplete) {
?>
                        <div class="invalid-feedback">
                            Bitte füllen Sie dieses Feld aus.
                        </div>
<?
            }
?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(() => {
    const modalId = <?= json_encode($modal_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const postLabel = <?= json_encode($this->config->view->post_desc_short, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const incompleteEdit = <?= json_encode($form_was_incomplete ? [
        'issueId' => (string)$handle_edit_post_res->issue_id(),
        'postId' => (string)$handle_edit_post_res->post_id(),
        'content' => $handle_edit_post_res->content,
        'status' => $handle_edit_post_res->selected_status
    ] : null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const modal = document.getElementById(modalId);
    const form = modal?.querySelector('form');
    const title = modal?.querySelector('.modal-title');
    const issueIdInput = form?.querySelector('[name="issue_id"]');
    const postIdInput = form?.querySelector('[name="post_id"]');
    const statusSelect = form?.querySelector('[name="new_status"]');
    const contentInput = form?.querySelector('[name="content"]');

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-post-edit-modal]');
        if (!trigger || trigger.dataset.postEditModal !== modalId || !form) return;

        form.reset();
        issueIdInput.value = trigger.dataset.issueId;
        postIdInput.value = trigger.dataset.postId;
        const isIncompleteEdit = incompleteEdit &&
            incompleteEdit.issueId === trigger.dataset.issueId &&
            incompleteEdit.postId === trigger.dataset.postId;
        statusSelect.value = isIncompleteEdit ? incompleteEdit.status : trigger.dataset.postStatus;
        contentInput.value = isIncompleteEdit ? incompleteEdit.content : trigger.dataset.postContent;
        title.textContent = `${postLabel} #${trigger.dataset.postId} bearbeiten`;
    }, true);

    if (incompleteEdit) {
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelector(
                `[data-post-edit-modal="${modalId}"][data-issue-id="${incompleteEdit.issueId}"][data-post-id="${incompleteEdit.postId}"]`
            )?.click();
        }, { once: true });
    }
})();
</script>
<?
            return $modal_id;
        }

        function print_delete_issue_modal(): ?string {
            $id = next_unique_id();
            $modal_id = 'delete-issue-modal-' . $id;
            $modal_title_id = $modal_id . '-title';
?>
<div class="modal" id="<?= $modal_id ?>" tabindex="-1" aria-labelledby="<?= $modal_title_id ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="<?= $modal_title_id ?>"><?= $this->config->view->issue_desc_short ?> löschen</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form method="post">
                <input type="hidden" name="req" value="delete-issue">
                <input type="hidden" name="request_uuid" value="<?= auth_get_cur_postdedup_uuid() ?>">
                <input type="hidden" name="issue_id" value="">
                <div class="modal-body">
                    <p data-delete-issue-confirmation></p>
                    <p><strong>Alle zugehörigen Einträge und Zuordnungen werden ebenfalls gelöscht. Dieser Vorgang kann nicht rückgängig gemacht werden.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-danger">Endgültig löschen</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(() => {
    const modalId = <?= json_encode($modal_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const issueLabel = <?= json_encode($this->config->view->issue_desc_short, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const modal = document.getElementById(modalId);
    const form = modal?.querySelector('form');
    const title = modal?.querySelector('.modal-title');
    const confirmation = modal?.querySelector('[data-delete-issue-confirmation]');
    const issueIdInput = form?.querySelector('[name="issue_id"]');

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-issue-delete-modal]');
        if (!trigger || trigger.dataset.issueDeleteModal !== modalId || !form) return;

        form.reset();
        issueIdInput.value = trigger.dataset.issueId;
        title.textContent = `${issueLabel} #${trigger.dataset.issueId} löschen`;
        confirmation.textContent = trigger.dataset.issueSummary
            ? `${issueLabel} #${trigger.dataset.issueId} „${trigger.dataset.issueSummary}“ wirklich löschen?`
            : `${issueLabel} #${trigger.dataset.issueId} wirklich löschen?`;
    }, true);
})();
</script>
<?
            return $modal_id;
        }

        function print_delete_post_modal(): ?string {
            $id = next_unique_id();
            $modal_id = 'delete-post-modal-' . $id;
            $modal_title_id = $modal_id . '-title';
?>
<div class="modal" id="<?= $modal_id ?>" tabindex="-1" aria-labelledby="<?= $modal_title_id ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="<?= $modal_title_id ?>"><?= $this->config->view->post_desc_short ?> löschen</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form method="post">
                <input type="hidden" name="req" value="delete-post">
                <input type="hidden" name="request_uuid" value="<?= auth_get_cur_postdedup_uuid() ?>">
                <input type="hidden" name="issue_id" value="">
                <input type="hidden" name="post_id" value="">
                <div class="modal-body">
                    <p data-delete-post-confirmation></p>
                    <p data-delete-post-consequence hidden><strong></strong></p>
                    <p><strong>Dieser Vorgang kann nicht rückgängig gemacht werden.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-danger">Endgültig löschen</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(() => {
    const modalId = <?= json_encode($modal_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const issueLabel = <?= json_encode($this->config->view->issue_desc_short, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const postLabel = <?= json_encode($this->config->view->post_desc_short, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const modal = document.getElementById(modalId);
    const form = modal?.querySelector('form');
    const title = modal?.querySelector('.modal-title');
    const confirmation = modal?.querySelector('[data-delete-post-confirmation]');
    const consequence = modal?.querySelector('[data-delete-post-consequence]');
    const issueIdInput = form?.querySelector('[name="issue_id"]');
    const postIdInput = form?.querySelector('[name="post_id"]');

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-post-delete-modal]');
        if (!trigger || trigger.dataset.postDeleteModal !== modalId || !form) return;

        form.reset();
        issueIdInput.value = trigger.dataset.issueId;
        postIdInput.value = trigger.dataset.postId;
        title.textContent = `${postLabel} vom ${trigger.dataset.postCreatedOn} löschen`;
        confirmation.textContent = `${postLabel} vom ${trigger.dataset.postCreatedOn} aus ${issueLabel} #${trigger.dataset.issueId} wirklich löschen?`;

        const deletesIssue = Number(trigger.dataset.issuePostCount) === 1;
        consequence.hidden = !deletesIssue;
        consequence.querySelector('strong').textContent = deletesIssue
            ? `Dies ist der letzte Eintrag. ${issueLabel} #${trigger.dataset.issueId} wird ebenfalls gelöscht.`
            : '';
    }, true);
})();
</script>
<?
            return $modal_id;
        }

        // Precondition: Any handling result's issue_id must correspond to `$issue`.
        function print_issue_as_acc_item(
            array $issue,
            ?HandleNewPostResult     $handle_new_post_res,
            ?HandleEditIssueResult   $handle_edit_issue_res,
            ?HandleEditPostResult    $handle_edit_post_res,
            ?HandleDeleteIssueResult $handle_delete_issue_res,
            ?HandleDeletePostResult  $handle_delete_post_res,
            bool $show_issue_admin_actions,
            bool $may_administer_issue,
            bool $show_post_admin_actions,
            array $may_administer_posts,
            ?string $edit_issue_modal_id,
            ?string $edit_post_modal_id,
            ?string $delete_issue_modal_id,
            ?string $delete_post_modal_id
        ) {
            $issue_status_variant = ($this->config->view->status_to_variant)($issue['last_post_status']);

            $header_actions_html = '';
            if ($show_issue_admin_actions && ($edit_issue_modal_id !== null || $delete_issue_modal_id !== null)) {
                ob_start();
                if ($edit_issue_modal_id !== null) {
                    if ($may_administer_issue) {
                        $issue_tag_ids_json = json_encode(array_values(array_map('strval', $issue['tag_ids'])));
?>
                <button type="button" class="accordion-header-action" data-bs-toggle="modal" data-bs-target="#<?= $edit_issue_modal_id ?>" data-issue-edit-modal="<?= $edit_issue_modal_id ?>" data-issue-id="<?= htmlspecialchars((string)$issue['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-issue-summary="<?= htmlspecialchars($issue['summary'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-issue-tag-ids="<?= htmlspecialchars($issue_tag_ids_json, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" aria-label="<?= $this->config->view->issue_desc_short ?> bearbeiten" title="<?= $this->config->view->issue_desc_short ?> bearbeiten">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 13l1-3 7.5-7.5a1.4 1.4 0 0 1 2 2L6 12l-3 1z"></path>
                        <path d="M10 4l2 2M4 10l2 2"></path>
                    </svg>
                </button>
<?
                    }
                    else {
?>
                <span class="accordion-header-action" style="box-sizing: border-box; cursor: default; pointer-events: none;" aria-hidden="true"></span>
<?
                    }
                }
                if ($delete_issue_modal_id !== null) {
                    if ($may_administer_issue) {
?>
                <button type="button" class="accordion-header-action" data-bs-toggle="modal" data-bs-target="#<?= $delete_issue_modal_id ?>" data-issue-delete-modal="<?= $delete_issue_modal_id ?>" data-issue-id="<?= htmlspecialchars((string)$issue['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-issue-summary="<?= htmlspecialchars($issue['summary'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" aria-label="<?= $this->config->view->issue_desc_short ?> löschen" title="<?= $this->config->view->issue_desc_short ?> löschen">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 4h10M6 4V2h4v2M5 6l.6 8h4.8l.6-8M7 7v5M9 7v5"></path>
                    </svg>
                </button>
<?
                    }
                    else {
?>
                <span class="accordion-header-action" style="box-sizing: border-box; cursor: default; pointer-events: none;" aria-hidden="true"></span>
<?
                    }
                }
                $header_actions_html = ob_get_clean();
            }

            // Prepare the accordion item's header
            ob_start();
?>
        <span style="display: flex; column-gap: 1rem; width: 100%; text-align: left;">
            <span style="flex: 0 0 <?= $this->config->view->issue_column_width ?>;">
                <strong>#<?= $issue['id'] ?></strong>
            </span>
            <span style="flex: 1 1 <?= $this->config->view->tag_column_width ?>; min-width: 0; overflow-wrap: anywhere;">
                <?= $issue['summary'] !== null ? htmlspecialchars($issue['summary']) : '' ?>
            </span>
            <span style="flex: 0 1 <?= $this->config->view->tag_column_width ?>; min-width: 0; max-width: <?= $this->config->view->tag_column_width ?>; overflow-wrap: anywhere;">
<?
            if ($issue['tags'] != '') {
?>
            <span><?= $this->config->view->tag_desc ?>: <strong><?= htmlspecialchars($issue['tags']) ?></strong></span>
<?
            }
?>
            </span>
        </span>
<?
            $header_html = ob_get_clean();
?>
    <? acc_item_start($header_html, variant: $issue_status_variant, id: IssueTracker::make_view_issue_html_id($issue['id']), open: $handle_new_post_res !== null || $handle_edit_issue_res !== null || $handle_edit_post_res !== null || $handle_delete_issue_res !== null || $handle_delete_post_res !== null, header_actions: $header_actions_html); ?>
    <div class="d-flex flex-column gap-3">
        <table class="table" style="margin-bottom: 0;">
            <thead>
                <tr>
<?
            if ($show_post_admin_actions) {
?>
                    <th scope="col" aria-label="Bearbeiten" style="width: 2.25rem;"></th>
                    <th scope="col" aria-label="Löschen" style="width: 2.25rem;"></th>
<?
            }
?>
                    <th style="width:<?= $this->config->view->date_column_width ?>;">Datum</th>
                    <th style="width:<?= $this->config->view->author_column_width ?>;">Autor/in</th>
                    <th><?= $this->config->view->post_desc_short ?></th>
                    <th style="width:<?= $this->config->view->status_column_width ?>;">Status</th>
                </tr>
            </thead>
            <tbody>
<?
    foreach ($issue['posts'] as $i => $post) {
        $is_last_post = ($i === count($issue['posts']) - 1);
        $may_administer_post = $may_administer_posts[$post['id']];

        $post_status_variant = ($this->config->view->status_to_variant)($post['new_status']);
        $post_status_label = ($this->config->view->status_to_label)($post['new_status']);
        $post_status_opacity = $is_last_post ? '100' : '50';

        $timestamp = strtotime($post['created_on']);
        $timestamp_short = date('d.m.Y', $timestamp);
        $timestamp_long  = date('d.m.Y, H:i:s', $timestamp);
?>
                <tr id="issue-<?= $issue['id'] ?>-post-<?= $post['id'] ?>">
<?
        if ($show_post_admin_actions) {
?>
                    <td>
<?
            if ($edit_post_modal_id !== null && $may_administer_post) {
?>
                        <button type="button" class="btn btn-secondary" style="display: inline-flex; align-items: center; justify-content: center; padding: .25rem; line-height: 1;" data-bs-toggle="modal" data-bs-target="#<?= $edit_post_modal_id ?>" data-post-edit-modal="<?= $edit_post_modal_id ?>" data-issue-id="<?= htmlspecialchars((string)$issue['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-post-id="<?= htmlspecialchars((string)$post['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-post-status="<?= htmlspecialchars($post['new_status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-post-content="<?= htmlspecialchars($post['content'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" aria-label="<?= $this->config->view->post_desc_short ?> bearbeiten" title="<?= $this->config->view->post_desc_short ?> bearbeiten">
                            <svg viewBox="0 0 16 16" width=".9rem" height=".9rem" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 13l1-3 7.5-7.5a1.4 1.4 0 0 1 2 2L6 12l-3 1z"></path>
                                <path d="M10 4l2 2M4 10l2 2"></path>
                            </svg>
                        </button>
<?
            }
?>
                    </td>
                    <td>
<?
            if ($delete_post_modal_id !== null && $may_administer_post) {
?>
                        <button type="button" class="btn btn-secondary" style="display: inline-flex; align-items: center; justify-content: center; padding: .25rem; line-height: 1;" data-bs-toggle="modal" data-bs-target="#<?= $delete_post_modal_id ?>" data-post-delete-modal="<?= $delete_post_modal_id ?>" data-issue-id="<?= htmlspecialchars((string)$issue['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-post-id="<?= htmlspecialchars((string)$post['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-post-created-on="<?= htmlspecialchars($timestamp_long, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-issue-post-count="<?= count($issue['posts']) ?>" aria-label="<?= $this->config->view->post_desc_short ?> löschen" title="<?= $this->config->view->post_desc_short ?> löschen">
                            <svg viewBox="0 0 16 16" width=".9rem" height=".9rem" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 4h10M6 4V2h4v2M5 6l.6 8h4.8l.6-8M7 7v5M9 7v5"></path>
                            </svg>
                        </button>
<?
            }
?>
                    </td>
<?
        }
?>
                    <td>
                        <span data-bs-toggle="tooltip" title="<?= $timestamp_long ?>">
                            <?= $timestamp_short ?>
                        </span>
                    </td>
                    <td class="text-truncate" style="max-width:<?= $this->config->view->author_column_width ?>;">
<?
        $author_label = ($this->config->view->author_identity_to_label)($post['author_identity']);
?>
                        <span data-bs-toggle="tooltip" title="<?= htmlspecialchars($author_label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-bs-placement="left">
                            <?= htmlspecialchars($author_label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </span>
                    </td>
                    <td><?= nl2br(htmlspecialchars($post['content'])) ?></td>
                    <td>
                        <span class="badge bg-<?= $post_status_variant ?> bg-opacity-<?= $post_status_opacity ?>">
                            <?= $post_status_label ?>
                        </span>
                    </td>
                </tr>
<?
    }
?>
            </tbody>
        </table>
<?
    ///////////////////
    // New post form //
    ///////////////////

    if ($this->may_write()) {
        // Show form only if there was a problem with the last submission
        $new_post_form_was_incomplete = $handle_new_post_res !== null && $handle_new_post_res->kind() === HandleNewPostResultKind::ErrorIncomplete;
        $current_author_label = ($this->config->view->author_identity_to_label)(
            $this->current_user_identity_serialized()
        );
?>
        <div>
            <div class="d-flex">
<?
    $new_post_collapse_id = collapse_button($this->config->view->post_desc_short . ' hinzufügen', variant: 'secondary');
?>
            </div>
            <div id="<?= $new_post_collapse_id ?>" class="collapse<?= $new_post_form_was_incomplete ? ' show' : '' ?>">
                <form method="post">
                    <input type="hidden" name="req" value="new-post">
                    <input type="hidden" name="request_uuid" value="<?= auth_get_cur_postdedup_uuid() ?>">
                    <input type="hidden" name="issue_id" value="<?= $issue['id'] ?>">
                    <table class="table">
                        <tbody>
                            <tr>
<?
    if ($show_post_admin_actions) {
?>
                                <td style="width: 2.25rem;"></td>
                                <td style="width: 2.25rem;"></td>
<?
    }
?>
                                <td style="width:<?= $this->config->view->date_column_width ?>;" class="text-center">
                                    <em>Jetzt</em>
                                </td>
                                <td class="text-truncate" style="width:<?= $this->config->view->author_column_width ?>; max-width:<?= $this->config->view->author_column_width ?>;">
                                    <span data-bs-toggle="tooltip" title="<?= htmlspecialchars($current_author_label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-bs-placement="left">
                                        <?= htmlspecialchars($current_author_label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="input-group has-validation">
                                        <textarea class="form-control<?= $new_post_form_was_incomplete ? ' is-invalid' : '' ?>" name="content" rows="3"></textarea>
<?
    if ($new_post_form_was_incomplete) {
?>
                                        <div class="invalid-feedback">
                                            Bitte füllen Sie dieses Feld aus.
                                        </div>
<?
    }
?>
                                    </div>
                                </td>
                                <td style="width:<?= $this->config->view->status_column_width ?>;">
                                    <select class="form-select" id="new_status" name="new_status">
<?
    $status_options = ['open', 'closed'];

    foreach ($status_options as $status) {
        $label = ($this->config->view->status_to_label)($status);

        if (
            ($handle_new_post_res !== null &&
            $handle_new_post_res->kind() === HandleNewPostResultKind::ErrorIncomplete &&
            $handle_new_post_res->selected_status === $status) ||
            ($handle_new_post_res === null && $issue['last_post_status'] === $status)
        ) {
?>
                                        <option value="<?= $status ?>" selected><?= $label ?></option>
<?
        }
        else {
?>
                                        <option value="<?= $status ?>"><?= $label ?></option>
<?
        }
    }
?>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-center">
                        <button type="submit" class="btn btn-primary">Abschicken</button>
                    </div>
                </form>
            </div>
        </div>
<?
    }
?>
    </div>
    <? acc_item_end(); ?>
<?
        }

        function print_issues_as_acc(
            array $issues_to_posts,
            string $message_none,
            ?HandleNewPostResult $handle_new_post_res,
            ?HandleEditIssueResult $handle_edit_issue_res,
            ?HandleEditPostResult $handle_edit_post_res,
            ?HandleDeleteIssueResult $handle_delete_issue_res,
            ?HandleDeletePostResult $handle_delete_post_res,
            ?string $edit_issue_modal_id,
            ?string $edit_post_modal_id,
            ?string $delete_issue_modal_id,
            ?string $delete_post_modal_id
        ) {
            if (count($issues_to_posts) > 0) {
                $may_administer_issues = [];
                $show_issue_admin_actions = false;
                $may_administer_posts = [];
                $show_post_admin_actions = [];
                foreach ($issues_to_posts as $issue) {
                    $may_administer_issue = $this->may_administer_issue($issue);
                    $may_administer_issues[$issue['id']] = $may_administer_issue;
                    $show_issue_admin_actions = $show_issue_admin_actions || $may_administer_issue;

                    $may_administer_posts[$issue['id']] = [];
                    $show_post_admin_actions[$issue['id']] = false;
                    foreach ($issue['posts'] as $post) {
                        $may_administer_post = $this->may_administer_post($post);
                        $may_administer_posts[$issue['id']][$post['id']] = $may_administer_post;
                        $show_post_admin_actions[$issue['id']] =
                            $show_post_admin_actions[$issue['id']] || $may_administer_post;
                    }
                }
?>
<? acc_start(); ?>
<?
                foreach ($issues_to_posts as $issue) {
                    $issue_handle_post_res = (
                        $handle_new_post_res != null &&
                        $handle_new_post_res->issue_id() === $issue['id']
                    ) ? $handle_new_post_res : null;
                    $issue_handle_edit_res = (
                        $handle_edit_issue_res != null &&
                        $handle_edit_issue_res->issue_id() === $issue['id']
                    ) ? $handle_edit_issue_res : null;
                    $issue_handle_edit_post_res = (
                        $handle_edit_post_res != null &&
                        $handle_edit_post_res->issue_id() === $issue['id']
                    ) ? $handle_edit_post_res : null;
                    $issue_handle_delete_res = (
                        $handle_delete_issue_res != null &&
                        $handle_delete_issue_res->issue_id() === $issue['id']
                    ) ? $handle_delete_issue_res : null;
                    $issue_handle_delete_post_res = (
                        $handle_delete_post_res != null &&
                        $handle_delete_post_res->issue_id() === $issue['id']
                    ) ? $handle_delete_post_res : null;

                    $this->print_issue_as_acc_item(
                        $issue,
                        handle_new_post_res: $issue_handle_post_res,
                        handle_edit_issue_res: $issue_handle_edit_res,
                        handle_edit_post_res: $issue_handle_edit_post_res,
                        handle_delete_issue_res: $issue_handle_delete_res,
                        handle_delete_post_res: $issue_handle_delete_post_res,
                        show_issue_admin_actions: $show_issue_admin_actions,
                        may_administer_issue: $may_administer_issues[$issue['id']],
                        show_post_admin_actions: $show_post_admin_actions[$issue['id']],
                        may_administer_posts: $may_administer_posts[$issue['id']],
                        edit_issue_modal_id: $edit_issue_modal_id,
                        edit_post_modal_id: $edit_post_modal_id,
                        delete_issue_modal_id: $delete_issue_modal_id,
                        delete_post_modal_id: $delete_post_modal_id
                    );
                }
?>
<? acc_end(); ?>
<?
            }
            else {
?>
<p>
    <em><?= $message_none ?></em>
</p>
<?
            }
        }

        function print_new_issue_form(
            array $tags,
            ?HandleNewIssueResult $handle_issue_res,
            array $preselected_tag_names = []
        ) {
            if (!$this->may_write()) {
                return;
            }

            $id = next_unique_id();
            $form_was_incomplete = $handle_issue_res !== null && $handle_issue_res->kind() === HandleNewIssueResultKind::ErrorIncomplete;
            $modal_id = 'new-issue-modal-' . $id;
            $modal_title_id = $modal_id . '-title';
            $openid_provider_names = $this->openid_provider_names();
            $show_author_override = $this->may_administer() && count($openid_provider_names) > 0;
            $set_other_openid_author = $show_author_override && $form_was_incomplete && isset($_POST['set_other_openid_author']);
            $selected_openid_provider = $set_other_openid_author && is_string($_POST['author_openid_provider'] ?? null)
                ? $_POST['author_openid_provider']
                : $openid_provider_names[0] ?? '';
            $other_openid_id = $set_other_openid_author && is_string($_POST['author_openid_id'] ?? null)
                ? $_POST['author_openid_id']
                : '';
            $other_openid_email = $set_other_openid_author && is_string($_POST['author_openid_email'] ?? null)
                ? $_POST['author_openid_email']
                : '';
            $author_override_id = 'form-author-override-' . $id;
            $author_override_fields_id = $author_override_id . '-fields';
?>
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#<?= $modal_id ?>">
    Neues <?= $this->config->view->issue_desc_short ?> anlegen
</button>
<div class="modal" id="<?= $modal_id ?>" tabindex="-1" aria-labelledby="<?= $modal_title_id ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="<?= $modal_title_id ?>">Neues <?= $this->config->view->issue_desc_short ?> anlegen</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form method="post">
                <input type="hidden" name="req" value="new-issue">
                <input type="hidden" name="request_uuid" value="<?= auth_get_cur_postdedup_uuid() ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="form-tags-<?= $id ?>" class="form-label"><?= $this->config->view->tag_desc ?></label>
                        <select class="form-select tom-select" id="form-tags-<?= $id ?>" name="tags[]" multiple>
<?
            foreach ($tags as $tag) {
                $selected =
                    (($form_was_incomplete && in_array($tag['id'], $handle_issue_res->selected_tags)) ||
                    (!$form_was_incomplete && in_array($tag['name'], $preselected_tag_names)))
                    ? ' selected'
                    : '';
                echo '<option value="' . $tag['id'] . '"' . $selected . '>' . htmlspecialchars($tag['name']) . '</option>';
            }
?>
                        </select>
                    </div>
                    <div>
                        <label for="form-content-<?= $id ?>" class="form-label">Beschreibung</label>
                        <div class="input-group has-validation">
                            <textarea class="form-control<?= $form_was_incomplete ? ' is-invalid' : '' ?>" id="form-content-<?= $id ?>" name="content" rows="3"></textarea>
<?
            if ($form_was_incomplete) {
?>
                            <div class="invalid-feedback">
                                Bitte füllen Sie dieses Feld aus.
                            </div>
<?
            }
?>
                        </div>
                    </div>
<?
            if ($show_author_override) {
?>
                    <div class="mt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="<?= $author_override_id ?>" name="set_other_openid_author" value="1" aria-controls="<?= $author_override_fields_id ?>"<?= $set_other_openid_author ? ' checked' : '' ?>>
                            <label class="form-check-label" for="<?= $author_override_id ?>">
                                Anderen OpenID-Benutzer als Autor setzen
                            </label>
                        </div>
                        <div id="<?= $author_override_fields_id ?>" class="mt-3<?= $set_other_openid_author ? '' : ' d-none' ?>">
                            <div class="mb-3">
                                <label for="form-author-openid-provider-<?= $id ?>" class="form-label">OpenID-Provider</label>
                                <select class="form-select" id="form-author-openid-provider-<?= $id ?>" name="author_openid_provider" data-author-override-required>
<?
                foreach ($openid_provider_names as $provider_name) {
                    $selected = $provider_name === $selected_openid_provider ? ' selected' : '';
                    echo '<option value="' . htmlspecialchars($provider_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"' . $selected . '>'
                        . htmlspecialchars($provider_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</option>';
                }
?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="form-author-openid-id-<?= $id ?>" class="form-label">OpenID-ID</label>
                                <input class="form-control" type="text" id="form-author-openid-id-<?= $id ?>" name="author_openid_id" value="<?= htmlspecialchars($other_openid_id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-author-override-required>
                            </div>
                            <div>
                                <label for="form-author-openid-email-<?= $id ?>" class="form-label">E-Mail-Adresse (optional)</label>
                                <input class="form-control" type="email" id="form-author-openid-email-<?= $id ?>" name="author_openid_email" value="<?= htmlspecialchars($other_openid_email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                            </div>
                        </div>
                    </div>
<?
            }
?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary"><?= $this->config->view->issue_desc_short ?> anlegen</button>
                </div>
            </form>
        </div>
    </div>
</div>
<? if ($show_author_override) { ?>
<script>
    (() => {
        const checkbox = document.getElementById('<?= $author_override_id ?>');
        const fields = document.getElementById('<?= $author_override_fields_id ?>');
        const controls = fields?.querySelectorAll('select, input') ?? [];
        const update = () => {
            const enabled = checkbox?.checked ?? false;
            fields?.classList.toggle('d-none', !enabled);
            controls.forEach(control => {
                control.disabled = !enabled;
                control.required = enabled && control.hasAttribute('data-author-override-required');
            });
        };
        checkbox?.addEventListener('change', update);
        update();
    })();
</script>
<? } ?>
<? if ($form_was_incomplete) { ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelector('[data-bs-target="#<?= $modal_id ?>"]')?.click();
    }, { once: true });
</script>
<? } ?>
<?
        }


        /////////////
        // Helpers //
        /////////////

        static function make_view_issue_html_id(string $issue_id): string {
            return 'issue-' . $issue_id;
        }
    }
?>
