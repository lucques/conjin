<?
    namespace Conjin\Modules\Nuggets\Exercise;

    use Conjin\Modules\Nuggets\LeafNugget;


    ////////////////
    // Data types //
    ////////////////

    readonly class ExerciseNugget extends LeafNugget {
        public function __construct(
            public ExerciseItem $main_item,
            public array $sub_parts = [], // array of ExercisePart
            public ?string $source = null, // Where was this exercise taken from?
            public ?string $source_detail = null,  // Page number etc.
            ?array $config_default = null
        ) {
            parent::__construct(config_default: $config_default);
        }

        public static function make_with_sol(
            ?string $name = null,
            string $main_content_id = 'main',
            ?string $hint_content_id = null,
            string $sol_content_id  = 'sol',
            ?string $source = null,
            ?string $source_detail = null,
            ?array $points = null,
            ?array $points_bonus = null,
            ?array $config_default = null,
        ): self {
            $hints = $hint_content_id ? [new ExerciseHint(content_id: $hint_content_id)] : [];

            return new self(
                main_item: new ExerciseItem(
                    content_id: $main_content_id,
                    name: $name,
                    hints: $hints,
                    solutions: [
                        new ExerciseSol(content_id: $sol_content_id)
                    ],
                    points: $points,
                    points_bonus: $points_bonus
                ),
                source: $source,
                source_detail: $source_detail,
                config_default: $config_default
            );
        }

        public static function make_without_sol(
            ?string $name = null,
            string $content_id = 'main',
            ?string $hint_content_id = null,
            ?string $source = null,
            ?string $source_detail = null,
            ?array $points = null,
            ?array $points_bonus = null,
            ?array $config_default = null
        ): self {
            $hints = $hint_content_id ? [new ExerciseHint(content_id: $hint_content_id)] : [];

            return new self(
                main_item: new ExerciseItem(
                    content_id: $content_id,
                    name: $name,
                    hints: $hints,
                    points: $points,
                    points_bonus: $points_bonus
                ),
                source: $source,
                source_detail: $source_detail,
                config_default: $config_default
            );
        }
    }

    interface ExercisePart {}

    readonly class ExerciseExplanation implements ExercisePart {
        public function __construct(
            public string $content_id,
            public ?string $title = null,
        ) {}
    }

    readonly class ExerciseItem implements ExercisePart {
        public function __construct(
            public string $content_id,
            public ?string $name = null,
            public array $hints = [],     // array of ExerciseHint
            public array $solutions = [], // array of ExerciseSol
            public ?array $points = null,
            public ?array $points_bonus = null,
        ) {}
    }

    readonly class ExerciseHint {
        public function __construct(
            public string $content_id,
            public ?string $title = null,
        ) {}
    }

    readonly class ExerciseSol {
        public function __construct(
            public string $content_id,
            public ?string $title = null,
        ) {}
    }
?>