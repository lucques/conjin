<?
    namespace Conjin\Modules\IssueTracker;

    use Closure;


    /////////////////////
    // Pure data types //
    /////////////////////

    readonly class MailConfig {
        /**
         * @param Closure(int $issue_id, string $issue_summary, list<string> $tag_names): string $new_issue_template_subject
         * @param Closure(int $issue_id, string $view_issue_url, string $date, string $author, string $content, list<string> $tag_names): string $new_issue_template_body The `$date` argument uses the PHP date format `Y-m-d H:i:s`.
         * @param Closure(int $issue_id, list<string> $tag_names): string $new_post_template_subject
         * @param Closure(int $issue_id, string $view_issue_url, string $date, string $author, string $content, string $new_status, list<array{date: string, author: string, content: string, status: string}> $previous_posts, list<string> $tag_names): string $new_post_template_body The `$date` argument and every `$previous_posts` `date` field use the PHP date format `Y-m-d H:i:s`.
         * @param Closure(list<array{email_address: string, reasons: list<string>}> $recipients): string $notification_block
         */
        public function __construct(
            public array $always_notify_addresses,
            public Closure $new_issue_template_subject,
            public Closure $new_issue_template_body,
            public Closure $new_post_template_subject,
            public Closure $new_post_template_body,
            public Closure $notification_block,
            public string $reason_tag_additional_email,
            public string $reason_always_notify,
            public string $reason_participates_in_issue
        ) {}
    }

    readonly class ViewConfig {
        /**
         * @param Closure(string $author_identity_serialized): string $author_identity_to_label
         * @param Closure(string $status): string $status_to_variant
         * @param Closure(string $status): string $status_to_label
         * @param ?Closure(string $client_data): string $tag_client_data_to_text
         */
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
            public string $tag_desc_singular,
            public string $solved_issue_count_desc,
            public string $unsolved_issue_count_desc,
            public Closure $author_identity_to_label,
            public Closure $status_to_variant,
            public Closure $status_to_label,
            public string $author_openid_id_placeholder = '',
            public string $table_min_width = '48rem',
            public string $tag_client_data_desc = 'Client-Daten',
            public ?Closure $tag_client_data_to_text = null
        ) {}
    }

    readonly class Config {
        /**
         * @param list<string> $view_target_ids
         * @param list<array{id: string, name: string, url?: ?string, client_data?: ?string, additional_email_addresses?: list<string>}> $tags
         */
        public function __construct(
            public string $file_name,
            public array $view_target_ids,
            public array $tags,
            public string $privilege_admin,
            public string $privilege_write,
            public int $may_edit_own_content_within_n_minutes,
            public ViewConfig $view,
            public ?MailConfig $mail
        ) {
            if ($may_edit_own_content_within_n_minutes < 0) {
                throw new \InvalidArgumentException('may_edit_own_content_within_n_minutes must not be negative');
            }

            foreach ($tags as $tag) {
                $url = $tag['url'] ?? null;
                if (
                    $url !== null &&
                    (
                        !is_string($url) ||
                        filter_var($url, FILTER_VALIDATE_URL) === false ||
                        !in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true)
                    )
                ) {
                    throw new \InvalidArgumentException('tag url must be a complete HTTP or HTTPS URL');
                }

                $additional_email_addresses = $tag['additional_email_addresses'] ?? [];
                if (!is_array($additional_email_addresses)) {
                    throw new \InvalidArgumentException('additional_email_addresses must be an array');
                }

                foreach ($additional_email_addresses as $address) {
                    if (
                        !is_string($address) ||
                        filter_var(trim($address), FILTER_VALIDATE_EMAIL) === false
                    ) {
                        throw new \InvalidArgumentException('additional_email_addresses must contain valid e-mail addresses');
                    }
                }
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
        /** @param list<array{email_address: string, reasons: list<string>}> $recipients */
        function __construct(public array $recipients) {}
        function kind(): HandleNewIssueResultKind { return HandleNewIssueResultKind::SuccessWithMails; }
    }
    readonly class SuccessButMailsFailedNIR implements HandleNewIssueResult {
        function kind(): HandleNewIssueResultKind { return HandleNewIssueResultKind::SuccessButMailsFailed; }
    }
    readonly class SuccessWithoutMailsNIR implements HandleNewIssueResult {
        function kind(): HandleNewIssueResultKind { return HandleNewIssueResultKind::SuccessWithoutMails; }
    }
    readonly class ErrorIncompleteNIR implements HandleNewIssueResult {
        function __construct(
            public array $selected_tags,
            public string $content = '',
            public array $additional_email_addresses = [],
            public array $invalid_email_addresses = []
        ) {}
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
        /** @param list<array{email_address: string, reasons: list<string>}> $recipients */
        function __construct(
            public int $issue_id,
            public array $recipients
        ) {}
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

    // NAER stands for NewAdditionalEmailResult
    enum HandleNewAdditionalEmailResultKind {
        case Success;
        case ErrorIncomplete;
        case ErrorDuplicate;
        case ErrorDb;
    }
    interface HandleNewAdditionalEmailResult {
        function kind(): HandleNewAdditionalEmailResultKind;
        function issue_id(): int;
    }
    readonly class SuccessNAER implements HandleNewAdditionalEmailResult {
        function __construct(public int $issue_id, public string $email_address) {}
        function kind(): HandleNewAdditionalEmailResultKind { return HandleNewAdditionalEmailResultKind::Success; }
        function issue_id(): int { return $this->issue_id; }
    }
    readonly class ErrorIncompleteNAER implements HandleNewAdditionalEmailResult {
        function __construct(public int $issue_id, public string $email_address) {}
        function kind(): HandleNewAdditionalEmailResultKind { return HandleNewAdditionalEmailResultKind::ErrorIncomplete; }
        function issue_id(): int { return $this->issue_id; }
    }
    readonly class ErrorDuplicateNAER implements HandleNewAdditionalEmailResult {
        function __construct(public int $issue_id, public string $email_address) {}
        function kind(): HandleNewAdditionalEmailResultKind { return HandleNewAdditionalEmailResultKind::ErrorDuplicate; }
        function issue_id(): int { return $this->issue_id; }
    }
    readonly class ErrorDbNAER implements HandleNewAdditionalEmailResult {
        function __construct(public int $issue_id, public string $details) {}
        function kind(): HandleNewAdditionalEmailResultKind { return HandleNewAdditionalEmailResultKind::ErrorDb; }
        function issue_id(): int { return $this->issue_id; }
    }

    // NTR stands for NewTagResult
    enum HandleNewTagResultKind {
        case Success;
        case ErrorIncomplete;
        case ErrorInvalidUrl;
        case ErrorInvalidEmailAddresses;
        case ErrorDb;
    }
    interface HandleNewTagResult {
        function kind(): HandleNewTagResultKind;
    }
    readonly class SuccessNTR implements HandleNewTagResult {
        function __construct(
            public string $tag_id,
            public string $name,
            public ?string $client_data,
            public array $additional_email_addresses,
            public ?string $url = null
        ) {}
        function kind(): HandleNewTagResultKind { return HandleNewTagResultKind::Success; }
    }
    readonly class ErrorIncompleteNTR implements HandleNewTagResult {
        function __construct(
            public string $tag_id,
            public string $name,
            public ?string $client_data,
            public array $additional_email_addresses,
            public ?string $url = null
        ) {}
        function kind(): HandleNewTagResultKind { return HandleNewTagResultKind::ErrorIncomplete; }
    }
    readonly class ErrorInvalidUrlNTR implements HandleNewTagResult {
        function __construct(
            public string $tag_id,
            public string $name,
            public ?string $client_data,
            public array $additional_email_addresses,
            public ?string $url
        ) {}
        function kind(): HandleNewTagResultKind { return HandleNewTagResultKind::ErrorInvalidUrl; }
    }
    readonly class ErrorInvalidEmailAddressesNTR implements HandleNewTagResult {
        function __construct(
            public string $tag_id,
            public string $name,
            public ?string $client_data,
            public array $additional_email_addresses,
            public ?string $url = null
        ) {}
        function kind(): HandleNewTagResultKind { return HandleNewTagResultKind::ErrorInvalidEmailAddresses; }
    }
    readonly class ErrorDbNTR implements HandleNewTagResult {
        function __construct(
            public string $tag_id,
            public string $name,
            public ?string $client_data,
            public array $additional_email_addresses,
            public string $details,
            public ?string $url = null
        ) {}
        function kind(): HandleNewTagResultKind { return HandleNewTagResultKind::ErrorDb; }
    }

    // ETR stands for EditTagResult
    enum HandleEditTagResultKind {
        case Success;
        case ErrorIncomplete;
        case ErrorInvalidUrl;
        case ErrorInvalidEmailAddresses;
        case ErrorDb;
    }
    interface HandleEditTagResult {
        function kind(): HandleEditTagResultKind;
        function tag_id(): string;
    }
    readonly class SuccessETR implements HandleEditTagResult {
        function __construct(
            public string $tag_id,
            public string $name,
            public ?string $client_data,
            public array $additional_email_addresses,
            public ?string $url = null
        ) {}
        function kind(): HandleEditTagResultKind { return HandleEditTagResultKind::Success; }
        function tag_id(): string { return $this->tag_id; }
    }
    readonly class ErrorIncompleteETR implements HandleEditTagResult {
        function __construct(
            public string $tag_id,
            public string $name,
            public ?string $client_data,
            public array $additional_email_addresses,
            public ?string $url = null
        ) {}
        function kind(): HandleEditTagResultKind { return HandleEditTagResultKind::ErrorIncomplete; }
        function tag_id(): string { return $this->tag_id; }
    }
    readonly class ErrorInvalidUrlETR implements HandleEditTagResult {
        function __construct(
            public string $tag_id,
            public string $name,
            public ?string $client_data,
            public array $additional_email_addresses,
            public ?string $url
        ) {}
        function kind(): HandleEditTagResultKind { return HandleEditTagResultKind::ErrorInvalidUrl; }
        function tag_id(): string { return $this->tag_id; }
    }
    readonly class ErrorInvalidEmailAddressesETR implements HandleEditTagResult {
        function __construct(
            public string $tag_id,
            public string $name,
            public ?string $client_data,
            public array $additional_email_addresses,
            public ?string $url = null
        ) {}
        function kind(): HandleEditTagResultKind { return HandleEditTagResultKind::ErrorInvalidEmailAddresses; }
        function tag_id(): string { return $this->tag_id; }
    }
    readonly class ErrorDbETR implements HandleEditTagResult {
        function __construct(
            public string $tag_id,
            public string $name,
            public ?string $client_data,
            public array $additional_email_addresses,
            public string $details,
            public ?string $url = null
        ) {}
        function kind(): HandleEditTagResultKind { return HandleEditTagResultKind::ErrorDb; }
        function tag_id(): string { return $this->tag_id; }
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

    // DAER stands for DeleteAdditionalEmailResult
    enum HandleDeleteAdditionalEmailResultKind {
        case Success;
        case ErrorDb;
    }
    interface HandleDeleteAdditionalEmailResult {
        function kind(): HandleDeleteAdditionalEmailResultKind;
        function issue_id(): int;
        function additional_email_id(): int;
    }
    readonly class SuccessDAER implements HandleDeleteAdditionalEmailResult {
        function __construct(public int $issue_id, public int $additional_email_id) {}
        function kind(): HandleDeleteAdditionalEmailResultKind { return HandleDeleteAdditionalEmailResultKind::Success; }
        function issue_id(): int { return $this->issue_id; }
        function additional_email_id(): int { return $this->additional_email_id; }
    }
    readonly class ErrorDbDAER implements HandleDeleteAdditionalEmailResult {
        function __construct(public int $issue_id, public int $additional_email_id, public string $details) {}
        function kind(): HandleDeleteAdditionalEmailResultKind { return HandleDeleteAdditionalEmailResultKind::ErrorDb; }
        function issue_id(): int { return $this->issue_id; }
        function additional_email_id(): int { return $this->additional_email_id; }
    }
?>
