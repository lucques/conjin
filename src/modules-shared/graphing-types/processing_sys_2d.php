<?
    namespace Conjin\ModulesShared\GraphingTypes\System2D;

    use Conjin\ModulesShared\GraphingTypes\Color;
    use Conjin\ModulesShared\GraphingTypes\CoordinateMarker;
    use Conjin\ModulesShared\GraphingTypes\Stroke;
    use Conjin\ModulesShared\GraphingTypes\System2DElement;


    ////////////////////////////////
    // Data types: Graph elements //
    ////////////////////////////////

    readonly class Point implements System2DElement {
        public function __construct(
            public string $x_fun_js,         // e.g. `() => 1` or `() => Math.cos(2)`
            public string $y_fun_js,
            public ?string $label = null,
            public int $label_font_size = 14,
            public Color $color = Color::Black,
            public float $size = 3,        // in pixels, e.g. `3`
            public ?CoordinateMarker $coordinate_marker_x = null,
            public ?CoordinateMarker $coordinate_marker_y = null,
            public string $visible_fun_js = '() => true'
        ) {}
    }

    readonly class Line implements System2DElement {
        public function __construct(
            public string $x_0_fun_js,  // e.g. `() => 0` or `() => Math.sin(3)`
            public string $y_0_fun_js,
            public string $x_1_fun_js,
            public string $y_1_fun_js,
            public bool $is_segment = false,
            public ?string $label = null,
            public int $label_font_size = 14,
            public Color $color = Color::Red,
            public Stroke $stroke = Stroke::Solid,
            public float  $thickness = 2,  // in pixels, e.g. `2`
            public string $visible_fun_js = '() => true',
            public bool $first_arrow = false,
            public bool $last_arrow = false
        ) {}
    }

    readonly class FunctionGraph implements System2DElement {
        public function __construct(
            public string $function_js,       // e.g. `(x) => x^2`
            public ?string $x_start_fun_js = null,    // Restrict the domain to some interval
            public ?string $x_end_fun_js = null,
            public ?string $label = null,
            public int $label_font_size = 14,
            public Color $color = Color::Blue,
            public Stroke $stroke = Stroke::Solid,
            public float  $thickness = 2,  // in pixels, e.g. `2`
            public string $visible_fun_js = '() => true'
        ) {}
    }

    readonly class Integral implements System2DElement {
        public function __construct(
            public string $graph_element_id,  // e.g. 'graph_f' if that key is in the board's elements and corresponds to an integratable graph element (like a FunctionGraph)
            // The interval to integrate over
            public string $x_start_fun_js, // e.g. `() => 0` or `() => Math.sin(3)`. May also be just a number, e.g. `0`
            public string $x_end_fun_js,   // same
            public bool $x_start_movable = false,  // Setting this to `true` only has an effect if `x_start_fun_js` is a plain number ("starting point") rather than a function. 
            public bool $x_end_movable   = false, // same
            public ?string $label = null,
            public int $label_font_size = 14,
            public Color $color = Color::Red,
            public string $visible_fun_js = '() => true'
        ) {}
    }

?>
