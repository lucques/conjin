<?
    use Conjin\ModulesShared\GraphingTypes as GT;
    use Conjin\ModulesShared\GraphingTypes\System2D as G2D;
    use Conjin\ModulesShared\GraphingTypes\System3DIndep as G3DI;
    use Conjin\ModulesShared\JSXGraphHelpers as JSX;

    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('jsxgraph-helpers');
    };
?>

<? $process = function (Target $target) { ?>

<h1>jsxgraph-helpers</h1>

<p>The typed helpers render sequences, Cartesian plots, and independent three-dimensional systems with JSXGraph.</p>

<h2>Sequences</h2>

<p>Explicit and recursive sequences are displayed as discrete points indexed by \(n\).</p>

<?
    JSX\plot_sequences(
        new GT\SequencePlotOptions(
            n_max: 10,
            y_min: 0,
            y_max: 12,
            width: 700,
            height: 400
        ),
        [
            'explicit' => new GT\ExplicitSequence(
                function_js: '(n) => Math.sqrt(n) + 1',
                color: GT\Color::Blue
            ),
            'recursive' => new GT\RecursiveSequence(
                init_val_js: '10',
                step_fun_js: '(a, n) => 0.7 * a + 1',
                color: GT\Color::Orange
            ),
        ]
    );
?>

<h2>Two-dimensional system</h2>

<p>A Cartesian board can combine function graphs, integrals, lines, and points.</p>

<?
    JSX\plot_2d(
        new JSX\GraphOptions(
            x_min: -5,
            x_max: 5,
            y_min: -4,
            y_max: 6,
            width: 700,
            height: 450
        ),
        [
            'graph_f' => new G2D\FunctionGraph(
                function_js: '(x) => 0.5 * x * x - 2',
                label: 'f',
                color: GT\Color::Blue
            ),
            'integral' => new G2D\Integral(
                graph_element_id: 'graph_f',
                x_start_fun_js: '-2',
                x_end_fun_js: '2',
                label: 'Area',
                color: GT\Color::Orange
            ),
            'A' => new G2D\Point(
                x_fun_js: '() => 2',
                y_fun_js: '() => 0',
                label: 'A',
                color: GT\Color::Red
            ),
        ]
    );
?>

<h2>Dynamic three-dimensional system</h2>

<p>The dynamic renderer uses JSXGraph's three-dimensional view and lets the view direction be changed interactively.</p>

<?
    $point_a = new G3DI\Point(
        new G3DI\Vec('1', '1', '2'),
        label: 'A',
        color: GT\Color::Red
    );
    $point_b = new G3DI\Point(
        new G3DI\Vec('-2', '1', '0'),
        label: 'B',
        color: GT\Color::Blue
    );
    $point_c = new G3DI\Point(
        new G3DI\Vec('1', '-2', '1'),
        label: 'C',
        color: GT\Color::Green
    );
    $scene_3d = [
        'plane' => new G3DI\Plane(
            vec_support: new G3DI\Vec('0', '0', '0'),
            vec_dir_1: new G3DI\Vec('1', '0', '0'),
            vec_dir_2: new G3DI\Vec('0', '1', '0'),
            range_1_min_fun_js: '-3',
            range_1_max_fun_js: '3',
            range_2_min_fun_js: '-3',
            range_2_max_fun_js: '3',
            color: GT\Color::Gray,
            opacity: GT\Opacity::Low,
            mesh_visible: true
        ),
        'triangle' => new G3DI\Polygon(
            vertices: [$point_a, $point_b, $point_c],
            color: GT\Color::Orange,
            opacity: GT\Opacity::Medium
        ),
        'line_g' => new G3DI\Line(
            point_a: new G3DI\Vec('-2', '-1', '0'),
            point_b: new G3DI\Vec('2', '2', '3'),
            label: 'g',
            color: GT\Color::Purple
        ),
        'A' => $point_a,
        'B' => $point_b,
        'C' => $point_c,
    ];

    JSX\plot_3d_dynamic(
        new JSX\Plot3DDynamicOptions(
            x_min: -3,
            x_max: 3,
            y_min: -3,
            y_max: 3,
            z_min: -1,
            z_max: 4,
            width: 700,
            height: 500,
            show_bounding_box: true,
            axes_extend_to_negative: true
        ),
        $scene_3d
    );
?>

<h2>Cavalier three-dimensional system</h2>

<p>The cavalier renderer projects the same independent three-dimensional elements onto a fixed two-dimensional board.</p>

<?
    JSX\plot_3d_cavalier(
        new JSX\Plot3DCavalierOptions(
            x_min: -3,
            x_max: 3,
            y_min: -3,
            y_max: 3,
            z_min: -1,
            z_max: 4,
            width: 700,
            height: 500,
            enable_pan: false,
            enable_zoom: false,
            axes_extend_to_negative: true
        ),
        $scene_3d
    );
?>

<? }; ?>
