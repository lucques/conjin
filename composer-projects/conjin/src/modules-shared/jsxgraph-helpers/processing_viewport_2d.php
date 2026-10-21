<?
    namespace Conjin\ModulesShared\JSXGraphHelpers;


    readonly class Viewport2D {
        public function __construct(
            public int $width,
            public int $height,
            public float $x_min,
            public float $x_max,
            public float $y_min,
            public float $y_max,
        ) {
            self::validate_dimensions($width, $height);
            self::validate_bounds('x', $x_min, $x_max);
            self::validate_bounds('y', $y_min, $y_max);
        }

        public static function fromXRange(
            int $width,
            int $height,
            float $x_min,
            float $x_max,
            ?float $x_axis_y_px = null, // Vertical position of the x-axis in pixels from the top edge; null centers it.
        ): self {
            self::validate_dimensions($width, $height);
            self::validate_bounds('x', $x_min, $x_max);
            $x_axis_y_px ??= $height / 2;
            self::validate_finite('x_axis_y_px', $x_axis_y_px);

            $units_per_pixel = ($x_max - $x_min) / $width;

            return new self(
                width: $width,
                height: $height,
                x_min: $x_min,
                x_max: $x_max,
                y_min: -($height - $x_axis_y_px) * $units_per_pixel,
                y_max: $x_axis_y_px * $units_per_pixel,
            );
        }

        public static function fromYRange(
            int $width,
            int $height,
            float $y_min,
            float $y_max,
            ?float $y_axis_x_px = null, // Horizontal position of the y-axis in pixels from the left edge; null centers it.
        ): self {
            self::validate_dimensions($width, $height);
            self::validate_bounds('y', $y_min, $y_max);
            $y_axis_x_px ??= $width / 2;
            self::validate_finite('y_axis_x_px', $y_axis_x_px);

            $units_per_pixel = ($y_max - $y_min) / $height;

            return new self(
                width: $width,
                height: $height,
                x_min: -$y_axis_x_px * $units_per_pixel,
                x_max: ($width - $y_axis_x_px) * $units_per_pixel,
                y_min: $y_min,
                y_max: $y_max,
            );
        }

        // Fits the given coordinate rectangle into a centered viewport with equal pixel scales on both axes.
        public static function fromFittedBounds(
            int $width,
            int $height,
            float $x_min,
            float $x_max,
            float $y_min,
            float $y_max,
        ): self {
            self::validate_dimensions($width, $height);
            self::validate_bounds('x', $x_min, $x_max);
            self::validate_bounds('y', $y_min, $y_max);

            return self::fromCenterAndUnitsPerPixel(
                width: $width,
                height: $height,
                units_per_pixel: max(($x_max - $x_min) / $width, ($y_max - $y_min) / $height),
                center_x: $x_min / 2 + $x_max / 2,
                center_y: $y_min / 2 + $y_max / 2,
            );
        }

        public static function fromCenterAndUnitsPerPixel(
            int $width,
            int $height,
            float $units_per_pixel,
            float $center_x = 0,
            float $center_y = 0,
        ): self {
            self::validate_dimensions($width, $height);
            self::validate_finite('units_per_pixel', $units_per_pixel);
            self::validate_finite('center_x', $center_x);
            self::validate_finite('center_y', $center_y);

            if ($units_per_pixel <= 0) {
                throw new \InvalidArgumentException('Viewport units_per_pixel must be greater than zero');
            }

            return new self(
                width: $width,
                height: $height,
                x_min: $center_x - $width / 2 * $units_per_pixel,
                x_max: $center_x + $width / 2 * $units_per_pixel,
                y_min: $center_y - $height / 2 * $units_per_pixel,
                y_max: $center_y + $height / 2 * $units_per_pixel,
            );
        }

        private static function validate_dimensions(int $width, int $height): void {
            if ($width <= 0 || $height <= 0) {
                throw new \InvalidArgumentException('Viewport width and height must be greater than zero');
            }
        }

        private static function validate_bounds(string $axis, float $min, float $max): void {
            self::validate_finite($axis . '_min', $min);
            self::validate_finite($axis . '_max', $max);

            if ($min >= $max) {
                throw new \InvalidArgumentException("Viewport {$axis}_min must be less than {$axis}_max");
            }
        }

        private static function validate_finite(string $name, float $value): void {
            if (!is_finite($value)) {
                throw new \InvalidArgumentException("Viewport {$name} must be finite");
            }
        }
    }
