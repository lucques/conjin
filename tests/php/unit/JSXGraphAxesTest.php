<?
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Conjin\ModulesShared\JSXGraphHelpers as J;
use Conjin\ModulesShared\GraphingTypes\FontSize;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
#[\PHPUnit\Framework\Attributes\Group('module:jsxgraph-helpers')]
final class JSXGraphAxesTest extends TestCase {
    protected function setUp(): void {
        $modules = dirname(__DIR__, 3) . '/composer-projects/conjin/src/modules-shared';
        require $modules . '/graphing-types/processing_common.php';
        require $modules . '/jsxgraph-helpers/processing_common.php';
        require $modules . '/jsxgraph-helpers/processing_axes.php';
    }

    public function testFieldInheritanceAndExplicitFalseZeroAndEmpty(): void {
        $axes = J\jsx_resolve_axes(J\Axes2D::enabled(
            default: J\Axis::enabled(label: 'shared', thickness: 1.5, label_font_size_adjust: 2,
                ticks: J\Ticks::enabled(numbers_font_size_adjust: 2, numbers_formatter_js: '(x) => x')),
            x: J\Axis::enabled(label: '', label_font_size_adjust: 0,
                ticks: J\Ticks::enabled(numbers: false, minor: false, numbers_font_size_adjust: 0)),
            y: J\Axis::disabled(),
        ), FontSize::MD, true);
        self::assertTrue($axes['x']->label === '');
        self::assertTrue($axes['x']->label_font_size === 14);
        self::assertTrue($axes['x']->thickness === 3.0);
        self::assertTrue($axes['x']->ticks->numbers_font_size === 14);
        self::assertTrue(!$axes['x']->ticks->numbers && !$axes['x']->ticks->minor);
        self::assertTrue($axes['x']->ticks->numbers_formatter_js === '(x) => x');
        self::assertTrue(!$axes['y']->is_enabled && !$axes['y']->ticks->is_enabled);
    }

    public function testDisabledDefaultsAllowExplicitReenable(): void {
        $axes = J\jsx_resolve_axes(J\Axes3D::enabled(default: J\Axis::disabled(), z: J\Axis::enabled()), FontSize::MD, false);
        self::assertTrue(!$axes['x']->is_enabled && !$axes['y']->is_enabled);
        self::assertTrue($axes['z']->is_enabled && $axes['z']->label === 'z');
        self::assertTrue($axes['z']->ticks->is_enabled && !$axes['z']->ticks->minor);
        $axes = J\jsx_resolve_axes(J\Axes2D::enabled(
            default: J\Axis::enabled(thickness: 2, ticks: J\Ticks::disabled()),
            x: J\Axis::enabled(ticks: J\Ticks::enabled()),
        ), FontSize::MD, false);
        self::assertTrue($axes['x']->ticks->is_enabled && !$axes['y']->ticks->is_enabled);
        self::assertTrue($axes['x']->thickness === 2.0);
        foreach (J\jsx_resolve_axes(J\Axes3D::disabled(), FontSize::MD, false) as $axis) {
            self::assertTrue(!$axis->is_enabled && !$axis->ticks->is_enabled);
        }
    }

    public function testRendererDefaultsAndSpacingReplacement(): void {
        $sequence = J\jsx_resolve_spacing(new J\Spacing2D(), sequence: true);
        self::assertTrue($sequence['x']->major === 1.0 && $sequence['x']->subdivisions === 1);
        self::assertTrue($sequence['y']->major === null && $sequence['y']->subdivisions === 5);
        $axes = J\jsx_resolve_axes(J\Axes2D::enabled(), FontSize::MD, false, sequence: true);
        self::assertTrue($axes['x']->label === 'n' && $axes['y']->label === 'y');
        self::assertTrue($axes['x']->ticks->numbers_font_size === 12);
        $spacing = J\jsx_resolve_spacing(new J\Spacing2D(default: J\Spacing::fixed(2, 4), x: J\Spacing::automatic(2)));
        self::assertTrue($spacing['x']->major === null && $spacing['x']->subdivisions === 2);
        self::assertTrue($spacing['y']->major === 2.0 && $spacing['y']->subdivisions === 4);
        foreach (J\jsx_resolve_spacing(new J\Spacing3D()) as $interval) {
            self::assertTrue($interval->major === 1.0 && $interval->subdivisions === 1);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidCases')]
    public function testInvalidValuesAndUnsupported3DFeatures(int $index): void {
        $valid_axes = J\jsx_resolve_axes(J\Axes3D::enabled(), FontSize::MD, false);
        $valid_spacing = J\jsx_resolve_spacing(new J\Spacing3D());
        $cases = [
            fn() => J\Spacing::fixed(0), fn() => J\Spacing::fixed(-1), fn() => J\Spacing::fixed(INF),
            fn() => J\Spacing::fixed(NAN), fn() => J\Spacing::automatic(0),
            fn() => J\Axis::enabled(thickness: 0), fn() => J\Axis::enabled(thickness: INF),
            fn() => J\jsx_resolve_axes(J\Axes2D::enabled(x: J\Axis::enabled(label_font_size_adjust: -20)), FontSize::MD, false),
            fn() => J\jsx_validate_3d_axes($valid_axes, J\jsx_resolve_spacing(new J\Spacing3D(x: J\Spacing::automatic()))),
            fn() => J\jsx_validate_3d_axes($valid_axes, $valid_spacing, J\Grid2D::enabled(minor: true)),
        ];
        foreach ([J\Ticks::enabled(minor: true), J\Ticks::enabled(numbers_formatter_js: '(x) => x')] as $ticks) {
            $cases[] = fn() => J\jsx_validate_3d_axes(J\jsx_resolve_axes(J\Axes3D::enabled(x: J\Axis::enabled(ticks: $ticks)), FontSize::MD, false), $valid_spacing);
        }
        $this->expectException(InvalidArgumentException::class);
        $cases[$index]();
    }

    public static function invalidCases(): array {
        return array_combine([
            'zero spacing', 'negative spacing', 'infinite spacing', 'NaN spacing',
            'zero subdivisions', 'zero thickness', 'infinite thickness', 'negative font size',
            'automatic 3D spacing', 'minor 3D grid', 'minor 3D ticks', '3D formatter',
        ], array_map(fn(int $i): array => [$i], range(0, 11)));
    }
}
