<?
    namespace Conjin\ModulesShared\GraphingTypes;

    
    ///////////////////////////////////
    // Data types: Sequence elements //
    ///////////////////////////////////

    readonly class ExplicitSequence implements SequenceElement {
        public function __construct(
            public string $function_js,     // e.g. `(n) => n^2`
            public Color $color = Color::Black,
            public ?int $n_start = null,    // First rendered index only; null uses the intrinsic start.
            public ?int $n_end = null,      // Last rendered index; null means unbounded.
            public bool $start_from_zero = false, // Start at n=0 instead of n=1.
        ) {
            if ($n_start !== null && $n_end !== null && $n_start > $n_end) {
                throw new \InvalidArgumentException('ExplicitSequence n_start must not be greater than n_end');
            }
        }
    }

    readonly class RecursiveSequence implements SequenceElement {
        public function __construct(
            public string $init_val_js,      // e.g. `3`
            public string $step_fun_js,      // e.g. `(a, n) => a + 2`
            public Color $color = Color::Black,
            public ?int $n_start = null,     // First rendered index only; null uses the intrinsic start.
            public ?int $n_end = null,       // Last rendered index; null means unbounded.
            public bool $start_from_zero = false, // init_val_js is a_0 instead of a_1.
        ) {
            if ($n_start !== null && $n_end !== null && $n_start > $n_end) {
                throw new \InvalidArgumentException('RecursiveSequence n_start must not be greater than n_end');
            }
        }
    }
?>
