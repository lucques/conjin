<?
    namespace Conjin\Modules\FeedbackCollector;

    use Closure;
    use InvalidArgumentException;

    // QuestionType = Text | SingleChoice | MultipleChoice.
    final readonly class Text {
        public function __construct(
            public bool $multiline = true,
            public int $max_length = 10000
        ) {
            if ($max_length < 1) {
                throw new InvalidArgumentException('Text max_length must be positive');
            }
        }
    }

    final readonly class SingleChoice {
        /** @param list<Option> $options */
        public function __construct(public array $options) {}
    }

    final readonly class MultipleChoice {
        /** @param list<Option> $options */
        public function __construct(public array $options) {}
    }

    final readonly class Option {
        /** @param list<Question|Section> $follow_up_questions */
        public function __construct(
            public string $label,
            public array $follow_up_questions = []
        ) {}
    }

    final readonly class Question {
        public function __construct(
            public string $label,
            public Text|SingleChoice|MultipleChoice $type,
            public bool $required = false
        ) {}
    }

    // A layout container, without an answer or a validation rule of its own.
    final readonly class Section {
        /** @param list<Question|Section> $questions */
        public function __construct(
            public string $title,
            public array $questions
        ) {}
    }

    final readonly class FormConfig {
        /** @param list<Question|Section> $questions */
        public function __construct(
            public string $title,
            public array $questions
        ) {
            if (trim($title) === '') {
                throw new InvalidArgumentException('Form title must not be empty');
            }
            self::validate_questions($questions);
        }

        private static function validate_questions(array $questions): void {
            if (!array_is_list($questions)) {
                throw new InvalidArgumentException('Questions must be a list');
            }
            foreach ($questions as $question) {
                if ($question instanceof Section) {
                    self::validate_label($question->title);
                    self::validate_questions($question->questions);
                    continue;
                }
                if (!($question instanceof Question)) {
                    throw new InvalidArgumentException('Questions must contain Question or Section objects');
                }
                self::validate_label($question->label);
                if ($question->type instanceof Text) {
                    continue;
                }
                $options = $question->type->options;
                if (!array_is_list($options) || $options === []) {
                    throw new InvalidArgumentException('Choices must have a nonempty list of options');
                }
                foreach ($options as $option) {
                    if (!($option instanceof Option)) {
                        throw new InvalidArgumentException('Choices must contain Option objects');
                    }
                    self::validate_label($option->label);
                    self::validate_questions($option->follow_up_questions);
                }
            }
        }

        private static function validate_label(string $label): void {
            if (trim($label) === '') {
                throw new InvalidArgumentException('Labels must not be empty');
            }
        }
    }

    enum Anonymity: string {
        case Anonymous = 'anonymous';
        case NonAnonymous = 'non-anonymous';
        case Optional = 'optional';
    }

    final readonly class Config {
        /** @param ?Closure(string $serialized_identity): string $author_identity_to_label */
        public function __construct(
            public string $file_name,
            public string $email_address,
            public FormConfig $form,
            public ?string $privilege_write = null,
            public ?Closure $author_identity_to_label = null,
            public Anonymity $anonymity = Anonymity::NonAnonymous
        ) {
            if ($file_name === '' || str_contains($file_name, "\0") || str_contains($file_name, '\\') ||
                str_starts_with($file_name, '/') || array_intersect(explode('/', $file_name), ['..', '.']) !== []) {
                throw new InvalidArgumentException('file_name must be a relative path inside the store');
            }
            if (filter_var($email_address, FILTER_VALIDATE_EMAIL) === false) {
                throw new InvalidArgumentException('email_address must be a valid email address');
            }
        }
    }

    enum SubmissionResultKind {
        case Saved;
        case SavedMailFailed;
        case Invalid;
        case Expired;
        case Forbidden;
        case ErrorDb;
        case Duplicate;
    }

    final readonly class SubmissionResult {
        /**
         * @param array<string, string> $errors
         * @param array<string, string|list<string>> $values
         */
        public function __construct(
            public SubmissionResultKind $kind,
            public ?int $submission_id = null,
            public array $errors = [],
            public array $values = [],
            public ?bool $anonymous = null
        ) {}
    }
