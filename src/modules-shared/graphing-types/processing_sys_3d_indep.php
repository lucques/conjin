<?
    namespace Conjin\ModulesShared\GraphingTypes\System3DIndep;

    use Conjin\ModulesShared\GraphingTypes\Color;
    use Conjin\ModulesShared\GraphingTypes\Opacity;
    use Conjin\ModulesShared\GraphingTypes\Stroke;
    use Conjin\ModulesShared\GraphingTypes\System3DIndepElement;


    ////////////////////////
    // Data types: Common //
    ////////////////////////

    readonly class Vec {
        public function __construct(
            public string $x_fun_js,         // e.g. `1` or `() => Math.cos(2)`
            public string $y_fun_js,
            public string $z_fun_js,
        ) {}
    }


    /////////////////////////////////
    // Data types: View3D elements //
    /////////////////////////////////

    readonly class Point implements System3DIndepElement {
        public function __construct(
            public Vec $vec,
            public ?string $label = null,
            public int $label_font_size = 14,
            public Color $color = Color::Black,
            public float $size = 3,          // in pixels, e.g. `3`
            public string $visible_fun_js = '() => true'
        ) {}
    }

    readonly class Line implements System3DIndepElement {
        public bool $extend_before_point_a;
        public bool $extend_after_point_b;

        public function __construct(
            public Vec $point_a,
            public Vec $point_b,
            public bool $is_segment = false,
            ?bool $extend_before_point_a = null,
            ?bool $extend_after_point_b = null,
            public ?string $label = null,
            public int $label_font_size = 14,
            public Color $color = Color::Red,
            public Stroke $stroke = Stroke::Solid,
            public float $thickness = 2,     // in pixels, e.g. `2`
            public string $visible_fun_js = '() => true',
            public bool $first_arrow = false,
            public bool $last_arrow = false
        ) {
            $this->extend_before_point_a = $extend_before_point_a ?? !$is_segment;
            $this->extend_after_point_b = $extend_after_point_b ?? !$is_segment;
        }
    }

    readonly class Plane implements System3DIndepElement {
        public function __construct(
            public Vec $vec_support,
            public Vec $vec_dir_1,
            public Vec $vec_dir_2,
            public string $range_1_min_fun_js = '-Infinity',
            public string $range_1_max_fun_js = 'Infinity',
            public string $range_2_min_fun_js = '-Infinity',
            public string $range_2_max_fun_js = 'Infinity',
            public Color $color = Color::Blue,
            public Opacity $opacity = Opacity::Medium,
            public Stroke $stroke = Stroke::Solid,
            public float $thickness = 1,     // in pixels, e.g. `1`
            public bool $mesh_visible = false,
            public string $visible_fun_js = '() => true'
        ) {}
    }

    readonly class Polygon implements System3DIndepElement {
        public function __construct(
            public array $vertices,          // array of Point
            public Color $color = Color::Blue,
            public Opacity $opacity = Opacity::Medium,
            public Stroke $stroke = Stroke::Solid,
            public float $thickness = 1,     // in pixels, e.g. `2`
            public string $visible_fun_js = '() => true'
        ) {}
    }

?>
