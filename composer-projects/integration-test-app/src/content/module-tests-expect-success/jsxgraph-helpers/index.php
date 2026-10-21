<?
    use Conjin\ModulesShared\GraphingTypes as GT;
    use Conjin\ModulesShared\GraphingTypes\System2D as G2D;
    use Conjin\ModulesShared\GraphingTypes\System3DIndep as G3D;
    use Conjin\ModulesShared\JSXGraphHelpers as JSX;

    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');
        $c->activate_module('jsxgraph-helpers');
    };
?>

<? $process = function (Target $target) { ?>

<p id="jsxgraph-status" data-ready="false">Loading</p>

<?
    if (isset($_GET['axes'])) {
        $viewport = new JSX\Viewport2D(width: 480, height: 320, x_min: -4, x_max: 8, y_min: -3, y_max: 5);
        $expose = fn(string $key, string $extra = '') => new GT\CustomElement(
            'window.jsxAxesCases ??= {}; window.jsxAxesCases.' . $key . ' = { board' . $extra . ' };'
        );
        $plot = function(string $key, ?JSX\Axes2D $axes = null, JSX\Spacing2D $spacing = new JSX\Spacing2D(), ?JSX\Grid2D $grid = null) use ($viewport, $expose) {
            JSX\plot_2d(new JSX\GraphOptions(viewport: $viewport, axes: $axes, spacing: $spacing, grid: $grid, print_mode: JSX\PrintMode::Enabled), [$expose($key)]);
        };
        $fixed = new JSX\Spacing2D(x: JSX\Spacing::fixed(2, 4), y: JSX\Spacing::fixed(1));
        $plot('fixed', JSX\Axes2D::enabled(
            default: JSX\Axis::enabled(thickness: 1.5, ticks: JSX\Ticks::enabled(numbers_font_size_adjust: 1)),
            x: JSX\Axis::enabled(label: "t's", ticks: JSX\Ticks::enabled(numbers_formatter_js: "(value) => value + ' s'")),
            y: JSX\Axis::disabled(),
        ), $fixed, JSX\Grid2D::enabled(minor: true));
        $plot('gridOnly', JSX\Axes2D::disabled(), $fixed, JSX\Grid2D::enabled(minor: true));
        $plot('autoVisible');
        $plot('autoHidden', axes: JSX\Axes2D::disabled());
        $plot('noGrid', JSX\Axes2D::enabled(
            x: JSX\Axis::enabled(label: '', ticks: JSX\Ticks::enabled(minor: false, numbers: false)),
            y: JSX\Axis::enabled(ticks: JSX\Ticks::disabled()),
        ), $fixed, JSX\Grid2D::disabled());
        JSX\plot_sequences(new JSX\SequencePlotOptions(y_min: -1, y_max: 5, width: 480, height: 320), [$expose('sequence')]);
        JSX\plot_3d_dynamic(new JSX\Plot3DDynamicOptions(
            x_min: 0, x_max: 4, y_min: 0, y_max: 4, z_min: 0, z_max: 4, width: 480, height: 400,
            axes: JSX\Axes3D::enabled(default: JSX\Axis::disabled(), z: JSX\Axis::enabled(label: 'w', thickness: 2)),
            spacing: new JSX\Spacing3D(z: JSX\Spacing::fixed(2)),
        ), [$expose('dynamic', ', view, axes: [xAxis, yAxis, zAxis], ticks: [xTicks, yTicks, zTicks]')]);
        JSX\plot_3d_dynamic(new JSX\Plot3DDynamicOptions(
            x_min: 0, x_max: 4, y_min: 0, y_max: 4, z_min: 0, z_max: 4, width: 480, height: 400,
            axes: JSX\Axes3D::disabled(),
        ), [$expose('dynamicHidden', ', view, axes: [xAxis, yAxis, zAxis], ticks: [xTicks, yTicks, zTicks]')]);
        JSX\plot_3d_cavalier(new JSX\Plot3DCavalierOptions(
            x_min: 0, x_max: 4, y_min: 0, y_max: 4, z_min: 0, z_max: 4, width: 480, height: 320,
            axes: JSX\Axes3D::enabled(default: JSX\Axis::disabled(), y: JSX\Axis::enabled(label: 'u', thickness: 2)),
            grid: JSX\Grid2D::disabled(),
            spacing: new JSX\Spacing3D(y: JSX\Spacing::fixed(2)),
        ), [$expose('cavalier')]);
        JSX\plot_3d_cavalier(new JSX\Plot3DCavalierOptions(
            x_min: 0, x_max: 4, y_min: 0, y_max: 4, z_min: 0, z_max: 4, width: 480, height: 320,
            axes: JSX\Axes3D::disabled(),
            spacing: new JSX\Spacing3D(y: JSX\Spacing::fixed(2), z: JSX\Spacing::fixed(3)),
        ), [$expose('cavalierGrid')]);
        JSX\plot_3d_dynamic(new JSX\Plot3DDynamicOptions(
            x_min: 0, x_max: 4, y_min: 0, y_max: 4, z_min: 0, z_max: 4, width: 480, height: 400,
            axes: JSX\Axes3D::enabled(default: JSX\Axis::disabled(), z: JSX\Axis::enabled(label: 'w', thickness: 2, ticks: JSX\Ticks::enabled(numbers: false))),
            spacing: new JSX\Spacing3D(z: JSX\Spacing::fixed(2)),
        ), [$expose('dynamicNoNumbers', ', view, axes: [xAxis, yAxis, zAxis], ticks: [xTicks, yTicks, zTicks]')]);
        JSX\plot_3d_cavalier(new JSX\Plot3DCavalierOptions(
            x_min: 0, x_max: 4, y_min: 0, y_max: 4, z_min: 0, z_max: 4, width: 480, height: 320,
            axes: JSX\Axes3D::enabled(default: JSX\Axis::disabled(), y: JSX\Axis::enabled(label: 'u', thickness: 2, ticks: JSX\Ticks::enabled(numbers: false))),
            grid: JSX\Grid2D::disabled(),
            spacing: new JSX\Spacing3D(y: JSX\Spacing::fixed(2)),
        ), [$expose('cavalierNoNumbers')]);
        return;
    }
?>

<?
    JSX\plot_2d(
        new JSX\GraphOptions(
            viewport: new JSX\Viewport2D(
                width: 560,
                height: 360,
                x_min: -4,
                x_max: 4,
                y_min: -4,
                y_max: 4,
            ),
            print_mode: JSX\PrintMode::Enabled,
            zoom: JSX\Zoom::EnabledWithNav,
            pan: JSX\Pan::EnabledWithNav,
            wheel_behavior: JSX\WheelBehavior::AllowForZoom,
            pinch_behavior: JSX\PinchBehavior::AllowPinchForZoom,
            one_finger_drag_behavior: JSX\OneFingerDragBehavior2D::AllowForPan,
            mouse_drag_behavior: JSX\MouseDragBehavior2D::AllowForPan,
            base_font_size: GT\FontSize::XL,
            axes: JSX\Axes2D::enabled(
                default: JSX\Axis::enabled(
                    thickness: 1.5,
                    label_font_size_adjust: 0,
                    ticks: JSX\Ticks::enabled(numbers_font_size_adjust: -4),
                ),
            ),
        ),
        [
            'graph_f' => new G2D\FunctionGraph(
                function_js: '(x) => x * x - 1',
                label: 'f',
                label_font_size_adjust: -2,
                color: GT\Color::Blue
            ),
            'graph_restricted' => new G2D\FunctionGraph(
                function_js: '(x) => x + 1',
                x_start_fun_js: '() => -2',
                x_end_fun_js: '() => 2',
                color: GT\Color::Orange
            ),
            'ready' => new GT\CustomElement(
                "window.jsxGraphFunctionBoard = board; window.jsxGraphFunction = graph_f; window.jsxGraphRestrictedFunction = graph_restricted; document.getElementById('jsxgraph-status').dataset.ready = 'true';"
            ),
        ]
    );

    JSX\plot_sequences(
        new JSX\SequencePlotOptions(
            x_min: -0.5,
            x_max: 3.5,
            y_min: 0,
            y_max: 10,
            width: 560,
            height: 360,
            print_mode: JSX\PrintMode::Enabled,
            zoom: JSX\Zoom::EnabledWithNav,
            pan: JSX\Pan::EnabledWithNav,
            axes: JSX\Axes2D::enabled(
                default: JSX\Axis::enabled(thickness: 0.75),
            ),
        ),
        [
            'sequence' => new GT\ExplicitSequence(
                function_js: '(n) => n'
            ),
            'expose_board' => new GT\CustomElement(
                'window.jsxGraphSequenceBoard = board;'
            ),
        ]
    );

    JSX\plot_3d_dynamic(
        new JSX\Plot3DDynamicOptions(
            x_min: -4,
            x_max: 4,
            y_min: -2,
            y_max: 2,
            z_min: -3,
            z_max: 3,
            width: 500,
            height: 400,
            azimuth: 5 * M_PI / 6,
            elevation: -M_PI / 8,
            print_mode: JSX\PrintMode::Enabled,
            axes: JSX\Axes3D::enabled(
                default: JSX\Axis::enabled(thickness: 1.25),
            ),
        ),
        [
            'point_3d' => new G3D\Point(new G3D\Vec('1', '1', '1')),
            'expose_view_3d' => new GT\CustomElement(
                'window.jsxGraph3DView = view; window.jsxGraph3DAxes = [xAxis, yAxis, zAxis]; window.jsxGraph3DTicks = [xTicks, yTicks, zTicks];'
            ),
        ]
    );

    JSX\plot_3d_cavalier(
        new JSX\Plot3DCavalierOptions(
            x_min: -3,
            x_max: 3,
            y_min: -3,
            y_max: 3,
            z_min: -1,
            z_max: 4,
            width: 500,
            height: 400,
            axes_extend_to_negative: true,
            print_mode: JSX\PrintMode::Enabled,
            axes: JSX\Axes3D::enabled(
                default: JSX\Axis::enabled(thickness: 2.5),
            ),
        ),
        [
            'cavalier_point_3d' => new G3D\Point(
                vec: new G3D\Vec('1', '1', '1'),
                label: 'P',
                label_font_size_adjust: 1,
            ),
            'cavalier_line_3d' => new G3D\Line(
                point_a: new G3D\Vec('0', '0', '0'),
                point_b: new G3D\Vec('2', '1', '1'),
                label: 'g',
                label_font_size_adjust: -1,
            ),
            'expose_cavalier_board' => new GT\CustomElement(
                'window.jsxGraphCavalierBoard = board; window.jsxGraphCavalierPoint = cavalier_point_3d; window.jsxGraphCavalierLine = cavalier_line_3d;'
            ),
        ]
    );
?>

<? }; ?>
