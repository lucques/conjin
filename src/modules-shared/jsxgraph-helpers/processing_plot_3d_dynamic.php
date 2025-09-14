<?
    namespace Conjin\ModulesShared\JSXGraphHelpers;

    use Conjin\ModulesShared\GraphingTypes\CustomElement;
    use Conjin\ModulesShared\GraphingTypes\System3DIndepElement;
    use Conjin\ModulesShared\GraphingTypes\System3DIndep\Line;
    use Conjin\ModulesShared\GraphingTypes\System3DIndep\Plane;
    use Conjin\ModulesShared\GraphingTypes\System3DIndep\Point;
    use Conjin\ModulesShared\GraphingTypes\System3DIndep\Polygon;
    use Conjin\ModulesShared\GraphingTypes\System3DIndep\Vec;
    use function Conjin\ModulesShared\GraphingTypes\translate_color_to_hex;
    use function Conjin\ModulesShared\GraphingTypes\translate_opacity_to_float;


    /////////////////////
    // View 3D options //
    /////////////////////

    readonly class Plot3DDynamicOptions {
        public function __construct(
            public float $x_min,
            public float $x_max,
            public float $y_min,
            public float $y_max,
            public float $z_min,
            public float $z_max,
            public int   $width,
            public int   $height,
            public float $board_x_min = -8,
            public float $board_x_max = 8,
            public float $board_y_min = -8,
            public float $board_y_max = 8,
            public float $view_x = -4,
            public float $view_y = -4,
            public float $view_width = 8,
            public float $view_height = 8,
            public ?float $x_ticks_distance = null,
            public ?float $y_ticks_distance = null,
            public ?float $z_ticks_distance = null,
            public float $ticks_distance = 1,       // Fallback, if the other specific *_ticks_distance's are not set
            public bool $show_view_sliders = true,
            public bool $show_bounding_box = false,
            public bool $axes_extend_to_negative = false
        ) {}
    }


    ////////////////////
    // Print and draw //
    ////////////////////

    function plot_3d_dynamic(
        Plot3DDynamicOptions $options,
        array $elements      // array of System3DIndepElement|ControlElement
    ) {
        $id = 'jsxgraph-view-3d-' . next_unique_id();
        $reset_view_id = $id . '-reset-view';
        $x_label_pos = $options->x_max + 0.05 * ($options->x_max - $options->x_min);
        $y_label_pos = $options->y_max + 0.05 * ($options->y_max - $options->y_min);
        $z_label_pos = $options->z_max + 0.05 * ($options->z_max - $options->z_min);
        $x_ticks_distance = $options->x_ticks_distance ?? $options->ticks_distance;
        $y_ticks_distance = $options->y_ticks_distance ?? $options->ticks_distance;
        $z_ticks_distance = $options->z_ticks_distance ?? $options->ticks_distance;
        $tick_height_at_reference_span = 10;
        $tick_reference_span = 16;
        $x_tick_height = $tick_height_at_reference_span * abs($options->x_max - $options->x_min) / $tick_reference_span;
        $y_tick_height = $tick_height_at_reference_span * abs($options->y_max - $options->y_min) / $tick_reference_span;
        $z_tick_height = $tick_height_at_reference_span * abs($options->z_max - $options->z_min) / $tick_reference_span;
        $x_axis_start = $options->axes_extend_to_negative ? $options->x_min : 0;
        $y_axis_start = $options->axes_extend_to_negative ? $options->y_min : 0;
        $z_axis_start = $options->axes_extend_to_negative ? $options->z_min : 0;
        $x_axis_direction = $options->x_max >= $x_axis_start ? 1 : -1;
        $y_axis_direction = $options->y_max >= $y_axis_start ? 1 : -1;
        $z_axis_direction = $options->z_max >= $z_axis_start ? 1 : -1;
        $x_axis_length = abs($options->x_max - $x_axis_start);
        $y_axis_length = abs($options->y_max - $y_axis_start);
        $z_axis_length = abs($options->z_max - $z_axis_start);
?>
        <div class="d-inline-block position-relative">
            <div id="<?= $id ?>" class="jsxgraph-plot" style="width: <?= $options->width ?>px; height: <?= $options->height ?>px;"></div>
            <button id="<?= $reset_view_id ?>" type="button" class="btn btn-outline-secondary btn-sm position-absolute bottom-0 start-0 m-1" style="z-index: 100;" title="Ansicht zurücksetzen" aria-label="Ansicht zurücksetzen">&#8634;</button>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var board = JXG.JSXGraph.initBoard('<?= $id ?>', {
                    showCopyright: false,
                    boundingbox: [<?= $options->board_x_min ?>, <?= $options->board_y_max ?>, <?= $options->board_x_max ?>, <?= $options->board_y_min ?>],
                    axis: false,
                    showNavigation: true,
                    keepaspectratio: true,
                    pan: { enabled: false },
                    zoom: { enabled: false }
                });
                const boardBounds = board.getBoundingBox();
                const unitsPerPixelX = jsx_compute_units_per_pixel_x(board);
                const unitsPerPixelY = jsx_compute_units_per_pixel_y(board);
                const elSliderX = boardBounds[0] + 20 * unitsPerPixelX;
                const elSliderLength = (<?= $options->view_width ?> + 2) / unitsPerPixelX * unitsPerPixelY;
                const elSliderYCenter = <?= $options->view_y ?> + <?= $options->view_height ?> / 2;
                const elSliderYMin = elSliderYCenter - elSliderLength / 2;
                const elSliderYMax = elSliderYCenter + elSliderLength / 2;
                const defaultAz = Math.PI / 4;
                const defaultEl = Math.atan(1 / Math.sqrt(2));

                var view = board.create('view3d',
                    [
                        [<?= $options->view_x ?>, <?= $options->view_y ?>],
                        [<?= $options->view_width ?>, <?= $options->view_height ?>],
                        [
                            [<?= $options->x_min ?>, <?= $options->x_max ?>],
                            [<?= $options->y_min ?>, <?= $options->y_max ?>],
                            [<?= $options->z_min ?>, <?= $options->z_max ?>]
                        ]
                    ],
                    {
                        xAxis: {
                            visible: false,
                            withLabel: false,
                        },
                        yAxis: {
                            visible: false,
                            withLabel: false,
                        },
                        zAxis: {
                            visible: false,
                            withLabel: false,
                        },

                        xPlaneRear: { visible: false },
                        yPlaneRear: { visible: false },
                        zPlaneRear: { visible: false },
                        projection: 'parallel',
                        depthOrder: {
                            enabled: true,
                            layers: [12, 13]
                        },
                        trackball: {
                            enabled: false,
                            outside: false
                        },
                        az: {
                            pointer: {
                                enabled: true,
                                speed: 0.25,
                                outside: false
                            },
                            slider: {
                                visible: <?= $options->show_view_sliders ? 'true' : 'false' ?>,
                                name: '',
                                withLabel: false,
                                start: defaultAz,
                                label: { visible: false },
                                point1: { pos: 'auto', frozen: true },
                                point2: { pos: 'auto', frozen: true }
                            }
                        },
                        el: {
                            pointer: {
                                enabled: true,
                                speed: 0.25,
                                outside: false
                            },
                            slider: {
                                visible: <?= $options->show_view_sliders ? 'true' : 'false' ?>,
                                name: '',
                                withLabel: false,
                                start: defaultEl,
                                label: { visible: false },
                                min: -Math.PI/2,
                                max: Math.PI/2,
                                point1: { pos: [elSliderX, elSliderYMin], frozen: true },
                                point2: { pos: [elSliderX, elSliderYMax], frozen: true }
                            }
                        },
                        bank: {
                            pointer: { enabled: false },
                            keyboard: { enabled: false },
                            slider: { visible: false }
                        }
                    }
                );
                document.getElementById('<?= $reset_view_id ?>').addEventListener('click', function() {
                    view.setView(defaultAz, defaultEl);
                });

                const xAxis = view.create('axis3d',
                    [[<?= $x_axis_start ?>, 0, 0], [<?= $options->x_max ?>, 0, 0]],
                    {
                        fixed: true,
                        layer: 13,
                        strokeColor: 'black',
                        strokeWidth: 2,
                        lastArrow: true
                    }
                );
                const xTicks = view.create('ticks3d',
                    [[<?= $x_axis_start ?>, 0, 0], [<?= $x_axis_direction ?>, 0, 0], <?= $x_axis_length ?>, [0, 1, 0]],
                    {
                        element3d: true,
                        fixed: true,
                        layer: 13,
                        ticksDistance: <?= $x_ticks_distance ?>,
                        majorHeight: <?= $x_tick_height ?>,
                        tickEndings: [1, 1],
                        drawLabels: true,
                        strokeColor: 'black',
                        label: { strokeColor: 'black', fontSize: 14 }
                    }
                );
                const yAxis = view.create('axis3d',
                    [[0, <?= $y_axis_start ?>, 0], [0, <?= $options->y_max ?>, 0]],
                    {
                        fixed: true,
                        layer: 13,
                        strokeColor: 'black',
                        strokeWidth: 2,
                        lastArrow: true
                    }
                );
                const yTicks = view.create('ticks3d',
                    [[0, <?= $y_axis_start ?>, 0], [0, <?= $y_axis_direction ?>, 0], <?= $y_axis_length ?>, [1, 0, 0]],
                    {
                        element3d: true,
                        fixed: true,
                        layer: 13,
                        ticksDistance: <?= $y_ticks_distance ?>,
                        majorHeight: <?= $y_tick_height ?>,
                        tickEndings: [1, 1],
                        drawLabels: true,
                        strokeColor: 'black',
                        label: { strokeColor: 'black', fontSize: 14 }
                    }
                );
                const zAxis = view.create('axis3d',
                    [[0, 0, <?= $z_axis_start ?>], [0, 0, <?= $options->z_max ?>]],
                    {
                        fixed: true,
                        layer: 13,
                        strokeColor: 'black',
                        strokeWidth: 2,
                        lastArrow: true
                    }
                );
                const zTicks = view.create('ticks3d',
                    [[0, 0, <?= $z_axis_start ?>], [0, 0, <?= $z_axis_direction ?>], <?= $z_axis_length ?>, [1, 1, 0]],
                    {
                        element3d: true,
                        fixed: true,
                        layer: 13,
                        ticksDistance: <?= $z_ticks_distance ?>,
                        majorHeight: <?= $z_tick_height ?>,
                        tickEndings: [1, 1],
                        drawLabels: true,
                        strokeColor: 'black',
                        label: { strokeColor: 'black', fontSize: 14 }
                    }
                );

                view.create('text3d',
                    [[<?= $x_label_pos ?>, 0, 0], 'x'],
                    {
                        fixed: true,
                        layer: 13,
                        strokeColor: 'black',
                        fontSize: 14,
                        anchorX: 'middle',
                        anchorY: 'middle',
                        offset: [10, -10]
                    }
                );
                view.create('text3d',
                    [[0, <?= $y_label_pos ?>, 0], 'y'],
                    {
                        fixed: true,
                        layer: 13,
                        strokeColor: 'black',
                        fontSize: 14,
                        anchorX: 'middle',
                        anchorY: 'middle',
                        offset: [10, -10]
                    }
                );
                view.create('text3d',
                    [[0, 0, <?= $z_label_pos ?>], 'z'],
                    {
                        fixed: true,
                        layer: 13,
                        strokeColor: 'black',
                        fontSize: 14,
                        anchorX: 'middle',
                        anchorY: 'middle',
                        offset: [10, -10]
                    }
                );

<?
                if ($options->show_bounding_box) {
?>
                const boundingBoxEdges = [
                    [[<?= $options->x_min ?>, <?= $options->y_min ?>, <?= $options->z_min ?>], [<?= $options->x_max ?>, <?= $options->y_min ?>, <?= $options->z_min ?>]],
                    [[<?= $options->x_min ?>, <?= $options->y_max ?>, <?= $options->z_min ?>], [<?= $options->x_max ?>, <?= $options->y_max ?>, <?= $options->z_min ?>]],
                    [[<?= $options->x_min ?>, <?= $options->y_min ?>, <?= $options->z_max ?>], [<?= $options->x_max ?>, <?= $options->y_min ?>, <?= $options->z_max ?>]],
                    [[<?= $options->x_min ?>, <?= $options->y_max ?>, <?= $options->z_max ?>], [<?= $options->x_max ?>, <?= $options->y_max ?>, <?= $options->z_max ?>]],
                    [[<?= $options->x_min ?>, <?= $options->y_min ?>, <?= $options->z_min ?>], [<?= $options->x_min ?>, <?= $options->y_max ?>, <?= $options->z_min ?>]],
                    [[<?= $options->x_max ?>, <?= $options->y_min ?>, <?= $options->z_min ?>], [<?= $options->x_max ?>, <?= $options->y_max ?>, <?= $options->z_min ?>]],
                    [[<?= $options->x_min ?>, <?= $options->y_min ?>, <?= $options->z_max ?>], [<?= $options->x_min ?>, <?= $options->y_max ?>, <?= $options->z_max ?>]],
                    [[<?= $options->x_max ?>, <?= $options->y_min ?>, <?= $options->z_max ?>], [<?= $options->x_max ?>, <?= $options->y_max ?>, <?= $options->z_max ?>]],
                    [[<?= $options->x_min ?>, <?= $options->y_min ?>, <?= $options->z_min ?>], [<?= $options->x_min ?>, <?= $options->y_min ?>, <?= $options->z_max ?>]],
                    [[<?= $options->x_max ?>, <?= $options->y_min ?>, <?= $options->z_min ?>], [<?= $options->x_max ?>, <?= $options->y_min ?>, <?= $options->z_max ?>]],
                    [[<?= $options->x_min ?>, <?= $options->y_max ?>, <?= $options->z_min ?>], [<?= $options->x_min ?>, <?= $options->y_max ?>, <?= $options->z_max ?>]],
                    [[<?= $options->x_max ?>, <?= $options->y_max ?>, <?= $options->z_min ?>], [<?= $options->x_max ?>, <?= $options->y_max ?>, <?= $options->z_max ?>]]
                ];
                boundingBoxEdges.forEach(function(edge) {
                    view.create('line3d',
                        edge,
                        {
                            fixed: true,
                            straightFirst: false,
                            straightLast: false,
                            strokeColor: '#888888',
                            strokeWidth: 1,
                            strokeOpacity: 0.7,
                            point1: { visible: false },
                            point2: { visible: false }
                        }
                    );
                });
<?
                }
?>
<?
                // Go through elements
                foreach ($elements as $name => $e) {

                    if (print_board_control_update_listener($e, $name)) {
                        continue;
                    }

                    assert(
                        $e instanceof System3DIndepElement,
                        'JSXGraph 3D plot elements must implement System3DIndepElement or ControlElement'
                    );

                    if ($e instanceof Point) {

                        /////////////
                        // Point //
                        /////////////
?>
                const <?= $name ?> = view.create('point3d',
                    [<?= $e->vec->x_fun_js ?>, <?= $e->vec->y_fun_js ?>, <?= $e->vec->z_fun_js ?>],
                    {
<?
                        if ($e->label !== null) {
?>
                        label: { autoPosition: true, strokeColor: '<?= translate_color_to_hex($e->color) ?>', fontSize: <?= $e->label_font_size ?> },
                        withLabel: true,
<?
                        }
?>
                        name: <?= $e->label === null ? '\'\'' : '\'' . $e->label . '\'' ?>,
                        size: <?= $e->size ?>,
                        fixed: true,
                        gradient: null,
                        strokeColor: '<?= translate_color_to_hex($e->color) ?>',
                        strokeWidth: 2,
                        highlightStrokeColor: '<?= translate_color_to_hex($e->color) ?>',
                        highlightStrokeWidth: 2,
                        fillColor: '<?= translate_color_to_hex($e->color) ?>',
                        face: 'x',
                        visible: <?= $e->visible_fun_js ?>,
                    }
                );
<?
                    }
                    elseif ($e instanceof Line) {
                        assert(
                            $e->point_a instanceof Vec && $e->point_b instanceof Vec,
                            'JSXGraph 3D line endpoints must be Vec instances'
                        );

                        ////////////
                        // Line //
                        ////////////
?>
                const <?= $name ?> = view.create('line3d',
                    [
                        [<?= $e->point_a->x_fun_js ?>, <?= $e->point_a->y_fun_js ?>, <?= $e->point_a->z_fun_js ?>],
                        [<?= $e->point_b->x_fun_js ?>, <?= $e->point_b->y_fun_js ?>, <?= $e->point_b->z_fun_js ?>]
                    ],
                    {
<?
                        if ($e->label !== null) {
?>
                        name: '<?= $e->label ?>',
                        withLabel: true,
                        label: { strokeColor: '<?= translate_color_to_hex($e->color) ?>', fontSize: <?= $e->label_font_size ?> },
<?
                        }
?>
                        fixed: true,
                        straightFirst: <?= $e->extend_before_point_a ? 'true' : 'false' ?>,
                        straightLast: <?= $e->extend_after_point_b ? 'true' : 'false' ?>,
                        strokeColor: '<?= translate_color_to_hex($e->color) ?>',
                        strokeWidth: <?= $e->thickness ?>,
                        firstArrow: <?= $e->first_arrow ? 'true' : 'false' ?>,
                        lastArrow: <?= $e->last_arrow ? 'true' : 'false' ?>,
                        dash: <?= translate_stroke_to_dash($e->stroke) ?>,
                        vertices: { visible: false, withLabel: false },
                        visible: <?= $e->visible_fun_js ?>,
                    }
                );
<?
                    }
                    elseif ($e instanceof Plane) {

                        /////////////
                        // Plane //
                        /////////////
?>
                const <?= $name ?> = view.create('plane3d',
                    [
                        [<?= $e->vec_support->x_fun_js ?>, <?= $e->vec_support->y_fun_js ?>, <?= $e->vec_support->z_fun_js ?>],
                        [<?= $e->vec_dir_1->x_fun_js ?>, <?= $e->vec_dir_1->y_fun_js ?>, <?= $e->vec_dir_1->z_fun_js ?>],
                        [<?= $e->vec_dir_2->x_fun_js ?>, <?= $e->vec_dir_2->y_fun_js ?>, <?= $e->vec_dir_2->z_fun_js ?>],
                        [<?= $e->range_1_min_fun_js ?>, <?= $e->range_1_max_fun_js ?>],
                        [<?= $e->range_2_min_fun_js ?>, <?= $e->range_2_max_fun_js ?>]
                    ],
                    {
                        fixed: true,
                        fillColor: '<?= translate_color_to_hex($e->color) ?>',
                        fillOpacity: <?= translate_opacity_to_float($e->opacity) ?>,
                        highlightFillColor: '<?= translate_color_to_hex($e->color) ?>',
                        highlightFillOpacity: <?= translate_opacity_to_float($e->opacity) ?>,
                        strokeColor: '<?= translate_color_to_hex($e->color) ?>',
                        strokeWidth: <?= $e->thickness ?>,
                        dash: <?= translate_stroke_to_dash($e->stroke) ?>,
                        mesh3d: { visible: <?= $e->mesh_visible ? 'true' : 'false' ?> },
                        visible: <?= $e->visible_fun_js ?>,
                    }
                );
<?
                    }
                    elseif ($e instanceof Polygon) {

                        ///////////////
                        // Polygon //
                        ///////////////
?>
                const <?= $name ?> = view.create('polygon3d',
                    [
<?
                        foreach ($e->vertices as $vertex) {
                            assert(
                                $vertex instanceof Point,
                                'JSXGraph 3D polygon vertices must be Point instances'
                            );
?>
                        [<?= $vertex->vec->x_fun_js ?>, <?= $vertex->vec->y_fun_js ?>, <?= $vertex->vec->z_fun_js ?>],
<?
                        }
?>
                    ],
                    {
                        fixed: true,
                        fillColor: '<?= translate_color_to_hex($e->color) ?>',
                        fillOpacity: <?= translate_opacity_to_float($e->opacity) ?>,
                        highlightFillColor: '<?= translate_color_to_hex($e->color) ?>',
                        highlightFillOpacity: <?= translate_opacity_to_float($e->opacity) ?>,
                        borders: {
                            strokeColor: '<?= translate_color_to_hex($e->color) ?>',
                            strokeWidth: <?= $e->thickness ?>,
                            dash: <?= translate_stroke_to_dash($e->stroke) ?>,
                            highlightStrokeColor: '<?= translate_color_to_hex($e->color) ?>',
                            highlightStrokeWidth: <?= $e->thickness ?>
                        },
                        vertices: {
                            visible: false,
                            withLabel: false,
                            fixed: true,
                            size: 0,
                            strokeOpacity: 0,
                            fillOpacity: 0,
                            label: { visible: false }
                        },
                        visible: <?= $e->visible_fun_js ?>,
                    }
                );
<?
                    }
                    elseif ($e instanceof CustomElement) {

                        ///////////////////
                        // CustomElement //
                        ///////////////////
                        echo $e->js_code . "\n";
                    }
                }
?>
            });
        </script>
<?
    }
?>
