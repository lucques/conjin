<?
    namespace Conjin\ModulesShared\GraphingTypes;

    
    ///////////////////////////////////
    // Data types: Sequence elements //
    ///////////////////////////////////

    readonly class ExplicitSequence implements SequenceElement {
        public function __construct(
            public string $function_js,     // e.g. `(n) => n^2`
            public Color $color = Color::Black,
        ) {}
    }

    // Sequences start on n=1
    readonly class RecursiveSequence implements SequenceElement {
        public function __construct(
            public string $init_val_js,      // e.g. `3`
            public string $step_fun_js,      // e.g. `(a, n) => a + 2`
            public Color $color = Color::Black,
        ) {}
    }


    /////////////////////////
    // Data types: Further //
    /////////////////////////

    readonly class SequencePlotOptions {
        public function __construct(
            public float $n_max,
            public float $y_min,
            public float $y_max,
            public int   $width,
            public int   $height,
        ) {}
    }
?>
