<?
    namespace Conjin\ModulesShared\JSXGraphHelpers;

    use Conjin\ModulesShared\GraphingTypes\FontSize;

    final readonly class Spacing {
        private function __construct(public ?float $major, public int $subdivisions) {
            if ($major !== null && (!is_finite($major) || $major <= 0)) {
                throw new \InvalidArgumentException('Major spacing must be finite and greater than zero');
            }
            if ($subdivisions < 1) {
                throw new \InvalidArgumentException('Spacing subdivisions must be at least one');
            }
        }

        public static function automatic(int $subdivisions = 5): self {
            return new self(null, $subdivisions);
        }

        public static function fixed(float $major, int $subdivisions = 5): self {
            return new self($major, $subdivisions);
        }
    }

    final readonly class Spacing2D {
        public function __construct(
            public ?Spacing $default = null,
            public ?Spacing $x = null,
            public ?Spacing $y = null,
        ) {}
    }

    final readonly class Spacing3D {
        public function __construct(
            public ?Spacing $default = null,
            public ?Spacing $x = null,
            public ?Spacing $y = null,
            public ?Spacing $z = null,
        ) {}
    }

    final readonly class Ticks {
        private function __construct(
            public bool $is_enabled,
            public ?bool $minor = null,
            public ?bool $numbers = null,
            public ?int $numbers_font_size_adjust = null,
            public ?string $numbers_formatter_js = null,
        ) {}

        public static function disabled(): self {
            return new self(false);
        }

        public static function enabled(
            ?bool $minor = null,
            ?bool $numbers = null,
            ?int $numbers_font_size_adjust = null,
            ?string $numbers_formatter_js = null,
        ): self {
            return new self(true, $minor, $numbers, $numbers_font_size_adjust, $numbers_formatter_js);
        }
    }

    final readonly class Axis {
        private function __construct(
            public bool $is_enabled,
            public ?string $label = null,
            public ?int $label_font_size_adjust = null,
            public ?float $thickness = null,
            public ?Ticks $ticks = null,
        ) {
            if ($thickness !== null && (!is_finite($thickness) || $thickness <= 0)) {
                throw new \InvalidArgumentException('Axis thickness must be finite and greater than zero');
            }
        }

        public static function disabled(): self {
            return new self(false);
        }

        public static function enabled(
            ?string $label = null,
            ?int $label_font_size_adjust = null,
            ?float $thickness = null,
            ?Ticks $ticks = null,
        ): self {
            return new self(true, $label, $label_font_size_adjust, $thickness, $ticks);
        }
    }

    final readonly class Axes2D {
        private function __construct(
            public bool $is_enabled,
            public ?Axis $default = null,
            public ?Axis $x = null,
            public ?Axis $y = null,
        ) {}

        public static function disabled(): self {
            return new self(false);
        }

        public static function enabled(?Axis $default = null, ?Axis $x = null, ?Axis $y = null): self {
            return new self(true, $default, $x, $y);
        }
    }

    final readonly class Axes3D {
        private function __construct(
            public bool $is_enabled,
            public ?Axis $default = null,
            public ?Axis $x = null,
            public ?Axis $y = null,
            public ?Axis $z = null,
        ) {}

        public static function disabled(): self {
            return new self(false);
        }

        public static function enabled(?Axis $default = null, ?Axis $x = null, ?Axis $y = null, ?Axis $z = null): self {
            return new self(true, $default, $x, $y, $z);
        }
    }

    final readonly class Grid2D {
        private function __construct(public bool $is_enabled, public bool $minor = false) {}

        public static function disabled(): self {
            return new self(false);
        }

        public static function enabled(bool $minor = false): self {
            return new self(true, $minor);
        }
    }

    /** Fully resolved renderer input; nullable author options never reach JavaScript. */
    final readonly class ResolvedTicks {
        public function __construct(
            public bool $is_enabled,
            public bool $minor,
            public bool $numbers,
            public int $numbers_font_size,
            public ?string $numbers_formatter_js,
        ) {}
    }

    final readonly class ResolvedAxis {
        public function __construct(
            public bool $is_enabled,
            public string $label,
            public int $label_font_size,
            public float $thickness,
            public ResolvedTicks $ticks,
        ) {}
    }

    /** @return array<string, ResolvedAxis> */
    function jsx_resolve_axes(Axes2D|Axes3D $axes, FontSize $base_font_size, bool $print_mode, bool $sequence = false): array {
        $result = [];
        $is_3d = $axes instanceof Axes3D;
        foreach ($is_3d ? ['x', 'y', 'z'] : ['x', 'y'] as $coordinate) {
            $default = $axes->default?->is_enabled ? $axes->default : null;
            $axis = $axes->$coordinate ?? $axes->default;
            $enabled = $axes->is_enabled && ($axis?->is_enabled ?? true);
            $axis = $axis?->is_enabled ? $axis : null;
            $default_ticks = $default?->ticks?->is_enabled ? $default->ticks : null;
            $ticks = $axis?->ticks ?? $default?->ticks;
            $ticks_enabled = $enabled && ($ticks?->is_enabled ?? true);
            $ticks = $ticks?->is_enabled ? $ticks : null;
            $label_font_size = translate_font_size_to_pixels($base_font_size, $axis?->label_font_size_adjust ?? $default?->label_font_size_adjust ?? 0);
            $numbers_font_size = translate_font_size_to_pixels($base_font_size, $ticks?->numbers_font_size_adjust ?? $default_ticks?->numbers_font_size_adjust ?? -1);
            if ($label_font_size <= 0 || $numbers_font_size <= 0) {
                throw new \InvalidArgumentException('Resolved axis font sizes must be greater than zero');
            }
            $result[$coordinate] = new ResolvedAxis(
                $enabled,
                $axis?->label ?? $default?->label ?? ($sequence && $coordinate === 'x' ? 'n' : $coordinate),
                $label_font_size,
                jsx_resolve_axis_thickness($axis?->thickness ?? $default?->thickness ?? 1, $print_mode),
                new ResolvedTicks(
                    $ticks_enabled,
                    $ticks?->minor ?? $default_ticks?->minor ?? !$is_3d,
                    $ticks?->numbers ?? $default_ticks?->numbers ?? true,
                    $numbers_font_size,
                    $ticks?->numbers_formatter_js ?? $default_ticks?->numbers_formatter_js,
                ),
            );
        }
        return $result;
    }

    /** @return array<string, Spacing> */
    function jsx_resolve_spacing(Spacing2D|Spacing3D $spacing, bool $sequence = false): array {
        $result = [];
        $is_3d = $spacing instanceof Spacing3D;
        foreach ($is_3d ? ['x', 'y', 'z'] : ['x', 'y'] as $coordinate) {
            $fallback = $is_3d || ($sequence && $coordinate === 'x')
                ? Spacing::fixed(1, subdivisions: 1)
                : Spacing::automatic();
            $result[$coordinate] = $spacing->$coordinate ?? $spacing->default ?? $fallback;
        }
        return $result;
    }

    /** Reject unsupported 3D features instead of silently changing their meaning. */
    function jsx_validate_3d_axes(array $axes, array $spacing, ?Grid2D $grid = null): void {
        foreach ($spacing as $coordinate => $interval) {
            if ($interval->major === null) {
                throw new \InvalidArgumentException("3D $coordinate spacing must be fixed; automatic spacing is supported by 2D plots only");
            }
            $ticks = $axes[$coordinate]->ticks;
            if ($ticks->is_enabled && ($ticks->minor || $ticks->numbers_formatter_js !== null)) {
                throw new \InvalidArgumentException('3D plots support major ticks and default number formatting only');
            }
        }
        if ($grid?->minor) {
            throw new \InvalidArgumentException('The cavalier background grid supports major lines only');
        }
    }

    /** Safely encode literal text and configuration embedded in an HTML script. */
    function jsx_axes_json(mixed $value): string {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    function jsx_print_2d_axes_setup(array $axes, array $spacing, Grid2D $grid, bool $print_mode, bool $sequence = false): void {
?>
                jsx_configure_2d_axes(board, <?= jsx_axes_json($axes) ?>, <?= jsx_axes_json($spacing) ?>, <?= jsx_axes_json($grid) ?>, <?= $print_mode ? 'true' : 'false' ?>, <?= $sequence ? 'true' : 'false' ?>, {
<?
        foreach ($axes as $coordinate => $axis) {
            // Formatters are explicitly JavaScript expressions, unlike literal axis labels.
?>
                    <?= $coordinate ?>: <?= $axis->ticks->numbers_formatter_js ?? 'null' ?>,
<?
        }
?>
                });
<?
    }
