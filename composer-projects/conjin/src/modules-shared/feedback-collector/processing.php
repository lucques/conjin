<?
    namespace Conjin\Modules\FeedbackCollector;

    use PDO;
    use PHPMailer\PHPMailer\PHPMailer;

    require_once __DIR__ . '/datatypes.php';
    require_once __DIR__ . '/processing_view.php';

    $process_target = $process_syslet = function(\Module $m, \Processable $p) {
        doc_extensions_add_head_element('<script defer src="' . htmlspecialchars($m->get_url(), ENT_QUOTES, 'UTF-8') . '/res/form.js"></script>');
    };

    class FeedbackCollector {
        use View;

        private ?PDO $db = null;

        public function __construct(public readonly Config $config) {
            try {
                $this->db = new PDO('sqlite:' . path_store($config->file_name), options: [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                $this->db->exec('PRAGMA busy_timeout = 5000');
                $this->db->exec('CREATE TABLE IF NOT EXISTS submissions (
                    submission_id INTEGER PRIMARY KEY,
                    author_identity TEXT NOT NULL,
                    created_on TEXT NOT NULL,
                    questions_answers TEXT NOT NULL
                )');
            }
            catch (\PDOException $error) {
                $this->db = null;
                log_error_silently('Feedback collector database: ' . $error->getMessage());
            }
        }

        private function may_submit(): bool {
            return auth_is_logged_in() && ($this->config->privilege_write === null ||
                auth_is_cur_user_privileged_for_custom_privilege($this->config->privilege_write));
        }

        private function identity(): string {
            return auth_aux_serialize_user(auth_get_user()->get_user_identity());
        }

        private function form_id(): string {
            return 'feedback-' . hash('sha256', $this->config->file_name);
        }

        // Bind issued tokens to the displayed questions, anonymity policy, and current identity.
        private function token_scope(): string {
            return hash('sha256', serialize(['feedback-collector', $this->config->file_name, $this->config->form, $this->config->anonymity, $this->identity()]));
        }

        private function issue_token(): string {
            return \auth_issue_submission_token($this->token_scope());
        }

        public function handle_submission(): ?SubmissionResult {
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || ($_POST['feedback_form'] ?? null) !== $this->form_id()) {
                return null;
            }
            if (!$this->may_submit()) {
                return new SubmissionResult(SubmissionResultKind::Forbidden);
            }

            $token_result = \auth_consume_submission_token($_POST['feedback_token'] ?? null, $this->token_scope());
            if ($token_result === \SubmissionTokenResult::Duplicate) {
                return new SubmissionResult(SubmissionResultKind::Duplicate);
            }
            if ($token_result !== \SubmissionTokenResult::Valid) {
                // Positional field names may refer to different questions in a changed form.
                return new SubmissionResult(SubmissionResultKind::Expired);
            }

            $raw = $_POST['answers'] ?? [];
            $errors = [];
            $values = [];
            $anonymous = match ($this->config->anonymity) {
                Anonymity::Anonymous => true,
                Anonymity::NonAnonymous => false,
                Anonymity::Optional => ($_POST['feedback_anonymous'] ?? '1') !== '0'
            };
            if ($this->config->anonymity === Anonymity::Optional &&
                !in_array($_POST['feedback_anonymous'] ?? '1', ['0', '1'], true)) {
                $errors['_anonymous'] = 'Bitte wählen Sie, ob Sie anonym antworten möchten.';
            }
            if (!is_array($raw)) {
                $raw = [];
                $errors['_form'] = 'Die übermittelten Antworten sind ungültig.';
            }
            $questions = $this->snapshot_questions($this->config->form->questions, $raw, true, $values, $errors);
            if ($errors !== []) {
                return new SubmissionResult(SubmissionResultKind::Invalid, errors: $errors, values: $values, anonymous: $anonymous);
            }
            if ($this->db === null) {
                return new SubmissionResult(SubmissionResultKind::ErrorDb, values: $values, anonymous: $anonymous);
            }

            $identity = $anonymous ? '' : $this->identity();
            $author_label = $anonymous ? 'Anonym' : ($this->config->author_identity_to_label === null
                ? $identity : ($this->config->author_identity_to_label)($identity));
            $author_email = $anonymous ? null : auth_get_user_email();
            $snapshot = [
                'version' => 1,
                'title' => $this->config->form->title,
                'anonymous' => $anonymous,
                'author_label' => $author_label,
                'author_email' => $author_email,
                'questions' => $questions
            ];
            $created_on = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z');
            try {
                $json = json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $query = $this->db->prepare('INSERT INTO submissions (author_identity, created_on, questions_answers) VALUES (?, ?, ?)');
                $query->execute([$identity, $created_on, $json]);
                $submission_id = (int)$this->db->lastInsertId();
            }
            catch (\PDOException|\JsonException $error) {
                log_error_silently('Feedback collector submission: ' . $error->getMessage());
                return new SubmissionResult(SubmissionResultKind::ErrorDb, values: $values, anonymous: $anonymous);
            }

            $sent = $this->send_notification($submission_id, $identity, $created_on, $snapshot);
            return new SubmissionResult($sent ? SubmissionResultKind::Saved : SubmissionResultKind::SavedMailFailed, $submission_id, anonymous: $anonymous);
        }

        /** @param list<Question|Section> $questions */
        private function snapshot_questions(array $questions, array $raw, bool $active, array &$values, array &$errors, string $path = '', int &$question_index = 0): array {
            $snapshot = [];
            foreach ($questions as $question) {
                if ($question instanceof Section) {
                    $snapshot[] = [
                        'type' => 'section',
                        'title' => $question->title,
                        'questions' => $this->snapshot_questions($question->questions, $raw, $active, $values, $errors, $path, $question_index)
                    ];
                    continue;
                }
                $question_key = $path . 'q' . $question_index++;
                $entry = [
                    'id' => $question_key,
                    'label' => $question->label,
                    'required' => $question->required,
                    'active' => $active
                ];
                $value = $active ? ($raw[$question_key] ?? null) : null;
                if ($question->type instanceof Text) {
                    $text = is_string($value) ? trim($value) : '';
                    if ($active) {
                        $values[$question_key] = $text;
                        if (($value !== null && !is_string($value)) || !mb_check_encoding($text, 'UTF-8')) {
                            $errors[$question_key] = 'Bitte geben Sie einen gültigen Text ein.';
                        }
                        elseif (mb_strlen($text, 'UTF-8') > $question->type->max_length) {
                            $errors[$question_key] = 'Bitte verwenden Sie höchstens ' . $question->type->max_length . ' Zeichen.';
                        }
                        elseif ($question->required && $text === '') {
                            $errors[$question_key] = 'Bitte beantworten Sie diese Frage.';
                        }
                    }
                    $entry += [
                        'type' => 'text',
                        'multiline' => $question->type->multiline,
                        'max_length' => $question->type->max_length,
                        'answer' => $active && $text !== '' ? $text : null
                    ];
                }
                else {
                    $multiple = $question->type instanceof MultipleChoice;
                    $selected = [];
                    $valid = true;
                    if ($value !== null && $value !== '') {
                        if ($multiple) {
                            $valid = is_array($value) && array_is_list($value);
                            if ($valid) {
                                foreach ($value as $id) {
                                    if (!is_string($id)) {
                                        $valid = false;
                                    }
                                    else {
                                        $selected[] = $id;
                                    }
                                }
                            }
                        }
                        else {
                            $valid = is_string($value);
                            $selected = $valid ? [$value] : [];
                        }
                    }
                    $known_ids = array_map(fn(int $index): string => 'o' . $index, array_keys($question->type->options));
                    if (array_diff($selected, $known_ids) !== []) {
                        $valid = false;
                    }
                    $selected = array_values(array_filter($known_ids, fn(string $id): bool => in_array($id, $selected, true)));
                    if ($active) {
                        $values[$question_key] = $multiple ? $selected : ($selected[0] ?? '');
                        if (!$valid) {
                            $errors[$question_key] = 'Bitte wählen Sie eine gültige Antwort aus.';
                        }
                        elseif ($question->required && $selected === []) {
                            $errors[$question_key] = 'Bitte wählen Sie mindestens eine Antwort aus.';
                        }
                    }
                    $entry += ['type' => $multiple ? 'multiple_choice' : 'single_choice', 'options' => []];
                    foreach ($question->type->options as $option_index => $option) {
                        $option_key = 'o' . $option_index;
                        $is_selected = in_array($option_key, $selected, true);
                        $entry['options'][] = [
                            'id' => $option_key,
                            'label' => $option->label,
                            'selected' => $is_selected,
                            'follow_up_questions' => $this->snapshot_questions(
                                $option->follow_up_questions, $raw, $active && $is_selected, $values, $errors, $question_key . '-' . $option_key . '-'
                            )
                        ];
                    }
                }
                $snapshot[] = $entry;
            }
            return $snapshot;
        }

        // This renderer needs only the stored snapshot, never the current form configuration.
        public static function questions_answers_to_text(array $snapshot): string {
            return $snapshot['title'] . "\n\n" . self::questions_to_text($snapshot['questions']);
        }

        private static function questions_to_text(array $questions, int $depth = 0): string {
            $text = '';
            $indent = str_repeat('    ', $depth);
            foreach ($questions as $question) {
                if ($question['type'] === 'section') {
                    $text .= $indent . $question['title'] . "\n\n" . self::questions_to_text($question['questions'], $depth);
                    continue;
                }
                $text .= $indent . $question['label'] . "\n";
                if (!$question['active']) {
                    $text .= $indent . "    (Nicht zutreffend.)\n";
                }
                if ($question['type'] === 'text') {
                    $answer = $question['answer'] ?? '(Keine Antwort.)';
                    $text .= $indent . '    ' . str_replace("\n", "\n" . $indent . '    ', $answer) . "\n";
                }
                else {
                    foreach ($question['options'] as $option) {
                        $text .= $indent . '    [' . ($option['selected'] ? 'x' : ' ') . '] ' . $option['label'] . "\n";
                        $text .= self::questions_to_text($option['follow_up_questions'], $depth + 2);
                    }
                }
                $text .= "\n";
            }
            return $text;
        }

        private function send_notification(int $id, string $identity, string $created_on, array $snapshot): bool {
            try {
                if (!isset_global_config('mail')) {
                    throw new \RuntimeException('Global mail configuration is missing');
                }
                $mail = new PHPMailer(true);
                $mail->CharSet = 'UTF-8';
                $mail->setFrom(get_global_config('mail', 'senderEmail'));
                $mail->addAddress($this->config->email_address);
                $mail->Subject = $snapshot['title'] . ' – Rückmeldung #' . $id;
                $mail->Body = 'Rückmeldung #' . $id . "\nZeitpunkt: " . $created_on
                    . "\nAbsender: " . $snapshot['author_label'];
                if (!$snapshot['anonymous']) {
                    $mail->Body .= "\nIdentität: " . $identity
                        . "\nE-Mail-Adresse: " . ($snapshot['author_email'] ?? '(Nicht verfügbar.)');
                }
                $mail->Body .= "\n\n" . self::questions_answers_to_text($snapshot);
                if (!$mail->send()) {
                    throw new \RuntimeException($mail->ErrorInfo);
                }
                return true;
            }
            catch (\Throwable $error) {
                log_error_silently('Feedback collector email for submission #' . $id . ': ' . $error->getMessage());
                return false;
            }
        }
    }
