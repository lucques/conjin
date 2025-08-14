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
            public string $date_column_width,
            public string $author_column_width,
            public string $status_column_width,
            public string $issue_desc_short,
            public string $issue_desc_long,
            public string $post_desc_short,
            public string $post_desc_long,
            public string $tag_desc,
            public Closure $status_to_variant,
            public Closure $status_to_label
        ) {}
    }

    readonly class Config {
        public function __construct(
            public string $file_name,
            public array $view_target_ids,
            public array $tag_names,
            public ViewConfig $view,
            public ?MailConfig $mail
        ) {}
    }

    enum HandleNewIssueResultKind {
        case SuccessWithMails;
        case SuccessButMailsFailed;
        case SuccessWithoutMails;
        case ErrorIncomplete;
        case ErrorDb;
    }
    interface HandleNewIssueResult { function kind(): HandleNewIssueResultKind; }
    readonly class SuccessWithMailsIR implements HandleNewIssueResult {
        function __construct(public array $addresses) {}
        function kind(): HandleNewIssueResultKind { return HandleNewIssueResultKind::SuccessWithMails; }
    }
    readonly class SuccessButMailsFailedIR implements HandleNewIssueResult {
        function kind(): HandleNewIssueResultKind { return HandleNewIssueResultKind::SuccessButMailsFailed; }
    }
    readonly class SuccessWithoutMailsIR implements HandleNewIssueResult {
        function kind(): HandleNewIssueResultKind { return HandleNewIssueResultKind::SuccessWithoutMails; }
    }
    readonly class ErrorIncompleteIR implements HandleNewIssueResult {
        function __construct(public array $selected_tags) {}
        function kind(): HandleNewIssueResultKind { return HandleNewIssueResultKind::ErrorIncomplete; }
    }
    readonly class ErrorDbIR implements HandleNewIssueResult {
        function kind(): HandleNewIssueResultKind { return HandleNewIssueResultKind::ErrorDb; }
    }

    enum HandleNewPostResultKind {
        case SuccessWithMails;
        case SuccessButMailsFailed;
        case SuccessWithoutMails;
        case ErrorIncomplete;
        case ErrorDb;
    }
    interface HandleNewPostResult { function kind(): HandleNewPostResultKind; }
    readonly class SuccessWithMailsPR implements HandleNewPostResult {
        function __construct(public array $addresses) {}
        function kind(): HandleNewPostResultKind { return HandleNewPostResultKind::SuccessWithMails; }
    }
    readonly class SuccessButMailsFailedPR implements HandleNewPostResult {
        function kind(): HandleNewPostResultKind { return HandleNewPostResultKind::SuccessButMailsFailed; }
    }
    readonly class SuccessWithoutMailsPR implements HandleNewPostResult {
        function kind(): HandleNewPostResultKind { return HandleNewPostResultKind::SuccessWithoutMails; }
    }
    readonly class ErrorIncompletePR implements HandleNewPostResult {
        function __construct(public int $issue_id, public string $selected_status) {}
        function kind(): HandleNewPostResultKind { return HandleNewPostResultKind::ErrorIncomplete; }
    }
    readonly class ErrorDbPR implements HandleNewPostResult {
        function kind(): HandleNewPostResultKind { return HandleNewPostResultKind::ErrorDb; }
    }


    ////////////////
    // Entrypoint //
    ////////////////

    class IssueTracker {

        private ?PDO $db = null;

        function __construct(public Config $config) {
            $this->init_db();
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
        "last_post_status"	TEXT NOT NULL,
        "last_post_created_on"	TEXT NOT NULL,
        "first_post_created_on"	TEXT NOT NULL,
        PRIMARY KEY("id" AUTOINCREMENT)
    );
    CREATE TABLE IF NOT EXISTS "post" (
        "id"	INTEGER NOT NULL,
        "issue_id"	INTEGER NOT NULL,
        "created_on"	TEXT NOT NULL,
        "author"	TEXT NOT NULL,
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

        function fetch_issues_with_posts_closed($how_many = 3) {
            return $this->fetch_issues_with_posts(
                where: 'WHERE issue.last_post_status = "closed"',
                order_by: 'ORDER BY issue.last_post_created_on DESC',
                limit: 'LIMIT ' . $how_many
            );
        }

        private function fetch_issues_with_posts($where = '', $order_by = '', $limit = '') {
            // Collect data in this assoc array.
            $issues_to_posts = [];

            // Select all issues that are open.
            $query  = 'SELECT issue.id, issue.last_post_status, group_concat(tag.name, ", ") AS tags ';
            $query .= 'FROM issue ';
            $query .= 'LEFT JOIN issue_x_tag ON issue.id = issue_x_tag.issue_id ';
            $query .= 'LEFT JOIN tag ON issue_x_tag.tag_id = tag.id ';
            $query .= $where . ' ';
            $query .= 'GROUP BY issue.id, issue.first_post_created_on, issue.first_post_created_on ';
            $query .= $order_by . ' ';
            $query .= $limit;

            $result = $this->db->query($query);

            foreach ($result as $row) {
                $issues_to_posts[$row['id']] = [
                    'id' => $row['id'],
                    'last_post_status' => $row['last_post_status'], 
                    'tags' => $row['tags'],
                    'posts' => []
                ];
            }

            // Select posts for these issues
            $issue_ids = array_keys($issues_to_posts);
            $query  = 'SELECT issue_id, id, created_on, author, content, new_status ';
            $query .= 'FROM post ';
            $query .= 'WHERE issue_id IN (' . implode(',', $issue_ids) . ') ';
            $query .= 'ORDER BY issue_id, created_on';
            $result = $this->db->query($query);

            foreach($result as $row)
            {
                $issues_to_posts[$row['issue_id']]['posts'][] = [
                    'id' => $row['id'],
                    'created_on' => $row['created_on'],
                    'author' => $row['author'],
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
                    isset($_POST['content']) &&
                    auth_has_postdedup_uuid()
                ) {
                    if (auth_register_check_postdedup_uuid()) {
                        if ($_POST['content'] !== '') {
                            $tags_ids = array_map(fn($tag) => $tag['id'], $tags);

                            $date         = date('Y-m-d H:i:s');
                            $content      = $_POST['content'] ?? '';
                            $status       = 'open';

                            $author_email = isset(auth_get_user()['contents']['email']) ? auth_get_user()['contents']['email'] : null;

                            // Insert issue
                            $query = $this->db->prepare("INSERT INTO issue (last_post_status, last_post_created_on, first_post_created_on) VALUES (?, ?, ?)");
                            $query->execute([$status, $date, $date]);
                            $issue_id = $this->db->lastInsertId();

                            // Insert post
                            $query = $this->db->prepare("INSERT INTO post (issue_id, created_on, author, author_email, content, new_status) VALUES (?, ?, ?, ?, ?, ?)");
                            $query->execute([$issue_id, $date, auth_get_user_name(), $author_email, $content, $status]);

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
                                // - Address of current user, if not already in the list
                                $addresses = $this->config->mail->always_notify_addresses;
                                if (isset(auth_get_user()['contents']['email']) &&
                                    !in_array(auth_get_user()['contents']['email'], $addresses)) {
                                    $addresses[] = auth_get_user()['contents']['email'];
                                }

                                $subject = ($this->config->mail->new_issue_template_subject)($issue_id);
                                $body = ($this->config->mail->new_issue_template_body)(
                                    $issue_id,
                                    url_full_collect($this->config->view_target_ids) . '#' . IssueTracker::make_view_issue_html_id($issue_id),
                                    $date,
                                    htmlspecialchars(auth_get_user_name()),
                                    htmlspecialchars($content)
                                );

                                $mail_successful = $this->send_mail_and_log($addresses, $subject, $body);

                                if ($mail_successful) {
                                    return new SuccessWithMailsIR($addresses);
                                }
                                else {
                                    return new SuccessButMailsFailedIR();
                                }
                            }
                            else {
                                return new SuccessWithoutMailsIR();

                            }
                        }
                        else {
                            // Form data incomplete
                            if (isset($_POST['tags']) && is_array($_POST['tags'])) {
                                return new ErrorIncompleteIR($_POST['tags']);
                            }
                            else {
                                return new ErrorIncompleteIR([]);
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
                return new ErrorDbIR();
            }
        }

        function handle_new_post(): ?HandleNewPostResult {
            try {
                if (
                    $_SERVER['REQUEST_METHOD'] === 'POST' &&
                    isset($_POST['issue_id']) &&
                    isset($_POST['new_status']) &&
                    auth_has_postdedup_uuid()
                ) {
                    if (auth_register_check_postdedup_uuid()) {
                        if ($_POST['content'] !== '') {
                            $issue_id     = $_POST['issue_id'];
                            $date         = date('Y-m-d H:i:s');
                            $content      = $_POST['content'] ?? '';
                            $new_status   = $_POST['new_status'];

                            $author_email = isset(auth_get_user()['contents']['email']) ? auth_get_user()['contents']['email'] : null;

                            // Insert post
                            $query = $this->db->prepare("INSERT INTO post (issue_id, created_on, author, author_email, content, new_status) VALUES (?, ?, ?, ?, ?, ?)");
                            $query->execute([$issue_id, $date, auth_get_user_name(), $author_email, $content, $new_status]);

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
                                $addresses = $addresses + $query->fetchAll(PDO::FETCH_COLUMN, 0); // Merge arrays, avoiding duplicates

                                // Prepare e-mail
                                $subject = ($this->config->mail->new_post_template_subject)($issue_id);
                                $body = ($this->config->mail->new_post_template_body)(
                                    $issue_id,
                                    url_full_collect($this->config->view_target_ids) . '#' . IssueTracker::make_view_issue_html_id($issue_id),
                                    $date,
                                    htmlspecialchars(auth_get_user_name()),
                                    htmlspecialchars($content),
                                    $new_status
                                );
                                
                                $mail_successful = $this->send_mail_and_log($addresses, $subject, $body);

                                if ($mail_successful) {
                                    return new SuccessWithMailsPR($addresses);
                                }
                                else {
                                    return new SuccessButMailsFailedPR();
                                }
                            }
                            else {
                                return new SuccessWithoutMailsPR();
                            }
                        }
                        else {
                            // Form data incomplete
                            return new ErrorIncompletePR(
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
                return new ErrorDbPR();
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
                log_error_silently('issue-tracker', 'Failed to send notification e-mails: ' . $mail->ErrorInfo);

                return false;
            }
        }


        //////////
        // View //
        //////////

        // Display success or error message of the new-issue-handling
        function print_handle_new_issue_res(HandleNewIssueResult $res) {
            if (
                $res->kind() == HandleNewIssueResultKind::SuccessWithMails ||
                $res->kind() == HandleNewIssueResultKind::SuccessButMailsFailed ||
                $res->kind() == HandleNewIssueResultKind::SuccessWithoutMails
            ) {
                $message = match ($res->kind()) {
                    HandleNewIssueResultKind::SuccessWithMails =>
                        '<p>' . $this->config->view->issue_desc_long . ' wurde angelegt und E-Mails zur Benachrichtigung wurden gesendet an:</p><ul>'
                        . implode('', array_map(fn($addr) => "<li>{$addr}</li>", $res->addresses))
                        . '</ul>',
                    HandleNewIssueResultKind::SuccessButMailsFailed =>
                        '<p>' . $this->config->view->issue_desc_long . ' wurde angelegt, aber E-Mails zur Benachrichtigung konnten nicht gesendet werden. Das Problem mit der fehlgeschlagenen E-Mail-Benachrichtigung wurde gemeldet.</p>',
                    HandleNewIssueResultKind::SuccessWithoutMails =>
                        '<p>' . $this->config->view->issue_desc_long . ' wurde angelegt, jedoch wurden keine E-Mails zur Benachrichtigung gesendet.</p>'
                };

                echo '<div class="alert alert-success">' . $message . '</div>';
            }
            else {
                $message = match ($res->kind()) {
                    HandleNewIssueResultKind::ErrorIncomplete =>
                        'Fehler: Bitte füllen Sie alle erforderlichen Felder aus.',
                    HandleNewIssueResultKind::ErrorDb =>
                        'Fehler: Es ist ein Datenbankfehler aufgetreten.'
                };

                echo '<div class="alert alert-danger">' . $message . '</div>';
            }
        }

        // Display success or error message of the new-post-handling
        function print_handle_new_post_res(HandleNewPostResult $res) {
            if (
                $res->kind() == HandleNewPostResultKind::SuccessWithMails ||
                $res->kind() == HandleNewPostResultKind::SuccessButMailsFailed ||
                $res->kind() == HandleNewPostResultKind::SuccessWithoutMails
            ) {
                $message = match ($res->kind()) {
                    HandleNewPostResultKind::SuccessWithMails =>
                        '<p>' . $this->config->view->post_desc_long . ' wurde angelegt und E-Mails zur Benachrichtigung wurden gesendet an:</p><ul>'
                        . implode('', array_map(fn($addr) => "<li>{$addr}</li>", $res->addresses))
                        . '</ul>',
                    HandleNewPostResultKind::SuccessButMailsFailed =>
                        '<p>' . $this->config->view->post_desc_long . ' wurde angelegt, aber E-Mails zur Benachrichtigung konnten nicht gesendet werden. Das Problem mit der fehlgeschlagenen E-Mail-Benachrichtigung wurde gemeldet.</p>',
                    HandleNewPostResultKind::SuccessWithoutMails =>
                        '<p>' . $this->config->view->post_desc_long . ' wurde angelegt, jedoch wurden keine E-Mails zur Benachrichtigung gesendet.</p>'
                };

                echo '<div class="alert alert-success">' . $message . '</div>';
            }
            else {
                $message = match ($res->kind()) {
                    HandleNewPostResultKind::ErrorIncomplete =>
                        'Fehler: Bitte füllen Sie alle erforderlichen Felder aus.',
                    HandleNewPostResultKind::ErrorDb =>
                        'Fehler: Es ist ein Datenbankfehler aufgetreten.'
                };

                echo '<div class="alert alert-danger">' . $message . '</div>';
            }
        }

        // Precondition: `$error`'s issue_id must correspond to `$issue`
        function print_issue_as_card(array $issue, ?ErrorIncompletePR $error = null) {
            $issue_status_variant = ($this->config->view->status_to_variant)($issue['last_post_status']);
?>
    <div id="<?= IssueTracker::make_view_issue_html_id($issue['id']) ?>" class="card border-<?= $issue_status_variant ?>">
        <div class="card-header bg-<?= $issue_status_variant ?> bg-opacity-50 d-flex" style="gap:30px; justify-content: space-between;">
            <strong><?= $this->config->view->issue_desc_short ?> #<?= $issue['id'] ?></strong>
<?
            if ($issue['tags'] != '') {
?>
            <span><?= $this->config->view->tag_desc ?>: <strong><?= htmlspecialchars($issue['tags']) ?></strong></span>
<?
            }
?>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
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

                $post_status_variant = ($this->config->view->status_to_variant)($post['new_status']);
                $post_status_label = ($this->config->view->status_to_label)($post['new_status']);
                $post_status_opacity = $is_last_post ? '100' : '50';

                $timestamp = strtotime($post['created_on']);
                $timestamp_short = date('d.m.Y', $timestamp);
                $timestamp_long  = date('d.m.Y, H:i:s', $timestamp);
?>
                    <tr id="issue-<?= $issue['id'] ?>-post-<?= $post['id'] ?>">
                        <td>
                            <span data-bs-toggle="tooltip" title="<?= $timestamp_long ?>">
                                <?= $timestamp_short ?>
                            </span>
                        </td>
                        <td class="text-truncate" style="max-width:<?= $this->config->view->author_column_width ?>;">
                            <span data-bs-toggle="tooltip" title="<?= $post['author'] ?>" data-bs-placement="left">
                                <?= $post['author'] ?>
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
            <div>
                <div class="d-flex justify-content-center">
<?
            $collapse_id = collapse_button('<i class="bi bi-pencil"></i> ' . $this->config->view->post_desc_short . ' hinzufügen', variant: 'secondary');
?>
                </div>
                <div id="<?= $collapse_id ?>" class="collapse<?= $error ? ' show' : '' ?>">
                    <form method="post">
                        <input type="hidden" name="request_uuid" value="<?= auth_get_cur_postdedup_uuid() ?>">
                        <input type="hidden" name="issue_id" value="<?= $issue['id'] ?>">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td style="width:<?= $this->config->view->date_column_width ?>;" class="text-center">
                                        <em>Jetzt</em>
                                    </td>
                                    <td class="text-truncate" style="width:<?= $this->config->view->author_column_width ?>; max-width:<?= $this->config->view->author_column_width ?>;">
                                        <span data-bs-toggle="tooltip" title="<?= htmlspecialchars(auth_get_user_name()) ?>" data-bs-placement="left">
                                            <?= htmlspecialchars(auth_get_user_name()) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="input-group has-validation">
                                            <textarea class="form-control<?= $error ? ' is-invalid' : '' ?>" name="content" rows="3"></textarea>
<?
            if ($error) {
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
                    ($error && $error->selected_status === $status) ||
                    (!$error && $is_last_post && $issue['last_post_status'] === $status)
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
        </div>
    </div>
<?
        }

        function print_new_issue_form(array $tags, ?ErrorIncompleteIR $error = null) {
            $id = next_unique_id();
?>
<form method="post">
    <input type="hidden" name="request_uuid" value="<?= auth_get_cur_postdedup_uuid() ?>">
    <div class="mb-3">
        <label for="form-tags-<?= $id ?>" class="form-label"><?= $this->config->view->tag_desc ?></label>
        <select class="form-select tom-select" id="form-tags-<?= $id ?>" name="tags[]" multiple>
<?
            foreach ($tags as $tag) {
                $selected = ($error && in_array($tag['id'], $error->selected_tags)) ? ' selected' : '';
                echo '<option value="' . $tag['id'] . '"' . $selected . '>' . htmlspecialchars($tag['name']) . '</option>';
            }
?>
        </select>
    </div>
    <div class="mb-3">
        <label for="form-content-<?= $id ?>" class="form-label">Beschreibung</label>
        <div class="input-group has-validation">
            <textarea class="form-control<?= $error ? ' is-invalid' : '' ?>" id="form-content-<?= $id ?>" name="content" rows="3"></textarea>
<?
            if ($error) {
?>
            <div class="invalid-feedback">
                Bitte füllen Sie dieses Feld aus.
            </div>
<?
            }
?>
        </div>
    </div>
    <button type="submit" class="btn btn-primary"><?= $this->config->view->issue_desc_short ?> anlegen</button>
</form>
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