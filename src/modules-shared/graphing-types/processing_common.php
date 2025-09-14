<?
    namespace Conjin\ModulesShared\GraphingTypes;
    

    /////////////////////////////////
    // Data types: Common elements //
    /////////////////////////////////

    interface CommonElement {}
    interface ControlElement extends CommonElement {}
    interface SequenceElement extends CommonElement {}
    interface System2DElement extends CommonElement {}
    interface System3DDepElement extends CommonElement {}
    interface System3DIndepElement extends CommonElement {}

    readonly class Slider implements ControlElement {
        public string $dom_id;

        public function __construct(
            public string $provide_getter_fun_js,
            public string $label_tex,
            public float $val_min,
            public float $val_max,
            public float $val_init,
            public float $val_step,
        ) {
            $this->dom_id = 'slider-' . next_unique_id();
        }
    }

    readonly class Checkbox implements ControlElement {
        public string $dom_id;

        public function __construct(
            public string $provide_getter_fun_js,
            public string $label_tex,
            public bool $checked_init = false,
        ) {
            $this->dom_id = 'checkbox-' . next_unique_id();
        }
    }

    readonly class CustomElement implements System2DElement, SequenceElement, System3DDepElement, System3DIndepElement {
        public function __construct(
            public string $js_code  // e.g. `board.create('point', [1,2], {name:'A'});`
        ) {}
    }

    readonly class CoordinateMarker {
        public function __construct(
            public ?string $label = null,
            public Color $color = Color::Black,
            public Stroke $stroke = Stroke::Solid,
            public float  $thickness = 2,  // in pixels, e.g. `2`
        ) {}
    }

    enum Stroke: int {
        case Solid = 0;
        case Dotted = 1;
        case Dashed = 2;
    }

    enum Color: int {
        case Red = 0;
        case Blue = 1;
        case Green = 2;
        case Orange = 3;
        case Purple = 4;
        case Black = 5;
        case Gray = 6;
        case Yellow = 7;
    }

    enum Opacity: int {
        case Full = 1;
        case Medium = 2;
        case Low = 3;
    }


    /////////////
    // Helpers //
    /////////////

    function translate_color_to_hex(Color $color): string {
        return match($color) {
            Color::Red    => '#c74440',
            Color::Blue   => '#2d70b3',
            Color::Green  => '#348543',
            Color::Orange => '#fa7e19',
            Color::Purple => '#6042a6',
            Color::Black  => '#000000',
            Color::Gray   => '#888888',
            Color::Yellow => '#f2b701',
        };
    }

    function translate_opacity_to_float(Opacity $opacity): float {
        return match($opacity) {
            Opacity::Full => 1.0,
            Opacity::Medium => 0.5,
            Opacity::Low => 0.25,
        };
    }
?>
