<?
    namespace Conjin\ModulesShared\JSXGraphHelpers;

    use Conjin\ModulesShared\GraphingTypes\Color;
    use Conjin\ModulesShared\GraphingTypes\CustomElement;
    use Conjin\ModulesShared\GraphingTypes\FontSize;
    use Conjin\ModulesShared\GraphingTypes\Stroke;
    use Conjin\ModulesShared\GraphingTypes\System2D\FunctionGraph;
    use Conjin\ModulesShared\GraphingTypes\System2D\Integral;
    use Conjin\ModulesShared\GraphingTypes\System2D\Line;
    use Conjin\ModulesShared\GraphingTypes\System2D\Point;
    use function Conjin\ModulesShared\GraphingTypes\translate_color_to_hex;


    /////////////////////
    // Plot 2D options //
    /////////////////////

    readonly class GraphOptions {
        public Axes2D $axes;
        public Grid2D $grid;

        public function __construct(
            public Viewport2D $viewport,
            ?Axes2D $axes = null,
            public Spacing2D $spacing = new Spacing2D(),
            ?Grid2D $grid = null,
            public PrintMode $print_mode = PrintMode::Auto,
            public Zoom $zoom = Zoom::Disabled,
            public Pan $pan = Pan::Disabled,
            public WheelBehavior $wheel_behavior = WheelBehavior::Disabled,
            public PinchBehavior $pinch_behavior = PinchBehavior::Disabled,
            public TwoFingerTranslationBehavior $two_finger_translation_behavior = TwoFingerTranslationBehavior::Disabled,
            public OneFingerDragBehavior2D $one_finger_drag_behavior = OneFingerDragBehavior2D::Disabled,
            public MouseDragBehavior2D $mouse_drag_behavior = MouseDragBehavior2D::Disabled,
            public FontSize $base_font_size = FontSize::MD,
            public float $point_label_auto_position_min_distance = POINT_LABEL_AUTO_POSITION_MIN_DISTANCE, // in pixels
            public float $point_label_auto_position_max_distance = POINT_LABEL_AUTO_POSITION_MAX_DISTANCE, // in pixels
        ) {
            $this->axes = $axes ?? Axes2D::enabled();
            $this->grid = $grid ?? Grid2D::enabled();
            jsx_validate_point_label_auto_position_distances(
                $this->point_label_auto_position_min_distance,
                $this->point_label_auto_position_max_distance,
                '2D plot',
            );
        }
    }


    ////////////////////
    // Print and draw //
    ////////////////////

    function plot_2d(
        GraphOptions $options,
        array $elements      // array of System2DElement|ControlElement
    ) {
        $print_mode = jsx_resolve_print_mode($options->print_mode);
        $axes = jsx_resolve_axes($options->axes, $options->base_font_size, $print_mode);
        $spacing = jsx_resolve_spacing($options->spacing);
        $interactions = jsx_resolve_2d_plot_interactions(
            plot_name: 'plot_2d',
            zoom: $options->zoom,
            pan: $options->pan,
            wheel_behavior: $options->wheel_behavior,
            pinch_behavior: $options->pinch_behavior,
            two_finger_translation_behavior: $options->two_finger_translation_behavior,
            one_finger_drag_behavior: $options->one_finger_drag_behavior,
            mouse_drag_behavior: $options->mouse_drag_behavior,
        );
        $id = 'jsxgraph-plot-' . next_unique_id();
?>
        <div id="<?= $id ?>" class="jsxgraph-plot" style="width: <?= $options->viewport->width ?>px; height: <?= $options->viewport->height ?>px;"></div>
        <script>
            jsx_register_initial_board('<?= $id ?>', function() {
                var board = JXG.JSXGraph.initBoard('<?= $id ?>', {
                    showCopyright: false,
                    boundingbox: [<?= $options->viewport->x_min ?>, <?= $options->viewport->y_max ?>, <?= $options->viewport->x_max ?>, <?= $options->viewport->y_min ?>],
                    axis: false,
                    grid: false,
                    showNavigation: <?= $interactions->show_navigation ? 'true' : 'false' ?>,
                    showZoom: <?= $interactions->show_zoom ? 'true' : 'false' ?>,
                    keepaspectratio: false,
                    pan: {
                        enabled: <?= $interactions->pan_enabled ? 'true' : 'false' ?>,
                        needTwoFingers: <?= $interactions->pan_need_two_fingers ? 'true' : 'false' ?>
                    },
                    zoom: {
                        enabled: <?= $interactions->zoom_enabled ? 'true' : 'false' ?>,
                        wheel: <?= $interactions->zoom_wheel ? 'true' : 'false' ?>,
                        pinch: <?= $interactions->zoom_pinch ? 'true' : 'false' ?>
                    },
                });
<?
        jsx_print_2d_axes_setup($axes, $spacing, $options->grid, $print_mode);
?>
<?
                // Go through elements
                foreach ($elements as $name => $e) {

                    if (print_board_control_update_listener($e, $name)) {
                        continue;
                    }

                    if ($e instanceof Point) {

                        ///////////
                        // Point //
                        ///////////
?>
                const <?= $name ?> = board.create('point',
                    [<?= $e->x_fun_js ?>, <?= $e->y_fun_js ?>],
                    {
<?
                        if ($e->label !== null) {   
?>
                        label: { autoPosition: true, autoPositionMinDistance: <?= $options->point_label_auto_position_min_distance ?>, autoPositionMaxDistance: <?= $options->point_label_auto_position_max_distance ?>, strokeColor: '<?= translate_color_to_hex($e->color) ?>', fontSize: <?= translate_font_size_to_pixels($options->base_font_size, $e->label_font_size_adjust) ?> },
<?
                        }
?>
                        name: <?= $e->label == null ? '\'\'' : '\'' . $e->label . '\'' ?>,
                        size: <?= $e->size ?>,
                        fixed: true,
                        strokeColor: '<?= translate_color_to_hex($e->color) ?>',
                        fillColor: '<?= translate_color_to_hex($e->color) ?>',
                        face: 'x',
                        visible: <?= $e->visible_fun_js ?>,
                    }
                );
<?
                        // Coordinate marker on x axis
                        if ($e->coordinate_marker_x !== null) {
?>
                board.create('segment',
                    [[<?= $e->x_fun_js ?>, 0], [<?= $e->x_fun_js ?>, <?= $e->y_fun_js ?>]],
                    {
                        fixed: true,
                        strokeColor: '<?= translate_color_to_hex($e->coordinate_marker_x->color) ?>',
                        strokeWidth: <?= $e->coordinate_marker_x->thickness ?>,
                        dash: <?= translate_stroke_to_dash($e->coordinate_marker_x->stroke) ?>,
                        visible: <?= $e->visible_fun_js ?>,
<?
                            if ($e->coordinate_marker_x->label !== null) {
?>
                        name: '<?= $e->coordinate_marker_x->label ?>',
                        withLabel: true,
                        label: { position: 'first', offset: [0, -15], anchorX: 'middle', anchorY: 'top'},
<?
                            }
?>
                    }
                );
                board.create('point',
                    [<?= $e->x_fun_js ?>, 0],
                    {
                        name: '',
                        fixed: true,
                        size: 1,
                        strokeColor: 'black',
                        face: 'o',
                        visible: <?= $e->visible_fun_js ?>,
                    }
                );
                board.create('segment',
                    [
                        [<?= $e->x_fun_js ?>, () => - 6 * jsx_compute_units_per_pixel_x(board)],
                        [<?= $e->x_fun_js ?>, () => + 6 * jsx_compute_units_per_pixel_x(board)]
                    ],
                    {
                        fixed: true,
                        strokeColor: 'black',
                        strokeWidth: 3,
                        visible: <?= $e->visible_fun_js ?>,
                    }
                );
<?
                        }
                        // Coordinate marker on y axis
                        if ($e->coordinate_marker_y !== null) {
?>
                board.create('segment',
                    [[0, <?= $e->y_fun_js ?>], [<?= $e->x_fun_js ?>, <?= $e->y_fun_js ?>]],
                    {
                        fixed: true,
                        strokeColor: '<?= translate_color_to_hex($e->coordinate_marker_y->color) ?>',
                        strokeWidth: <?= $e->coordinate_marker_y->thickness ?>,
                        dash: <?= translate_stroke_to_dash($e->coordinate_marker_y->stroke) ?>,
                        visible: <?= $e->visible_fun_js ?>,
<?
                            if ($e->coordinate_marker_y->label !== null) {
?>
                        name: '<?= $e->coordinate_marker_y->label ?>',
                        withLabel: true,
                        label: { position: 'first', offset: [-20, 0], anchorX: 'right'},
<?
                            }
?>
                    }
                );
                board.create('point',
                    [0, <?= $e->y_fun_js ?>],
                    {
                        fixed: true,
                        name: '',
                        size: 1,
                        strokeColor: 'black',
                        face: 'o',
                        visible: <?= $e->visible_fun_js ?>,
                    }
                );
                board.create('segment',
                    [
                        [() => -6 * jsx_compute_units_per_pixel_y(board), <?= $e->y_fun_js ?>],
                        [() => +6 * jsx_compute_units_per_pixel_y(board), <?= $e->y_fun_js ?>]
                    ],
                    {
                        fixed: true,
                        strokeColor: 'black',
                        strokeWidth: 3,
                        visible: <?= $e->visible_fun_js ?>,
                    }
                );
<?
                        }
                    }
                    elseif ($e instanceof Line) {

                        //////////
                        // Line //
                        //////////
?>
                const <?= $name ?> = board.create(
                    '<?= $e->is_segment ? 'segment' : 'line' ?>',
                    [[<?= $e->x_0_fun_js ?>, <?= $e->y_0_fun_js ?>], [<?= $e->x_1_fun_js ?>, <?= $e->y_1_fun_js ?>]],
                    {
<?
                        if ($e->label !== null) {   
?>
                        name: '<?= $e->label ?>',
                        withLabel: <?= $e->is_segment ? 'false' : 'true' ?>,
<?
                            if (!$e->is_segment) {
?>
                        label: { strokeColor: '<?= translate_color_to_hex($e->color) ?>', fontSize: <?= translate_font_size_to_pixels($options->base_font_size, $e->label_font_size_adjust) ?>, position: '50% left', anchorX: 'middle', anchorY: 'middle'},
<?
                            }
                        }
?>
                        fixed: true,
                        strokeColor: '<?= translate_color_to_hex($e->color) ?>',
                        strokeWidth: <?= $e->thickness ?>,
                        firstArrow: <?= $e->first_arrow ? 'true' : 'false' ?>,
                        lastArrow: <?= $e->last_arrow ? 'true' : 'false' ?>,
                        dash: <?= translate_stroke_to_dash($e->stroke) ?>,
                        visible: <?= $e->visible_fun_js ?>,
                    }
                );
<?
                        if ($e->is_segment && $e->label !== null) {
                            $label_font_size = translate_font_size_to_pixels($options->base_font_size, $e->label_font_size_adjust);
?>
                jsx_create_segment_midpoint_label(
                    board,
                    <?= $name ?>,
                    '<?= $e->label ?>',
                    <?= 0.75 * $label_font_size ?>,
                    {
                        fixed: true,
                        anchorX: 'middle',
                        anchorY: 'middle',
                        strokeColor: '<?= translate_color_to_hex($e->color) ?>',
                        highlightStrokeColor: '<?= translate_color_to_hex($e->color) ?>',
                        fontSize: <?= $label_font_size ?>,
                        useMathJax: true,
                        visible: <?= $e->visible_fun_js ?>,
                    }
                );
<?
                        }
                    }
                    elseif ($e instanceof FunctionGraph) {

                        $graph_x_start_js = $e->x_start_fun_js ?? '() => board.getBoundingBox()[0]';
                        $graph_x_end_js = $e->x_end_fun_js ?? '() => board.getBoundingBox()[2]';

                        ///////////////////
                        // FunctionGraph //
                        ///////////////////
?>
                const <?= $name ?> = board.create(
                    'functiongraph',
                    [<?= $e->function_js ?>, <?= $graph_x_start_js ?>, <?= $graph_x_end_js ?>],
                    {
                        strokeColor: '<?= translate_color_to_hex($e->color) ?>',
                        strokeWidth: <?= $e->thickness ?>,
<?
                        if ($e->label !== null) {   
?>
                        name: '<?= $e->label ?>',
                        withLabel: true,
                        label: { strokeColor: '<?= translate_color_to_hex($e->color) ?>', fontSize: <?= translate_font_size_to_pixels($options->base_font_size, $e->label_font_size_adjust) ?>, position: '50% left', anchorX: 'middle', anchorY: 'middle'},
<?
                        }
?>
                        dash: <?= translate_stroke_to_dash($e->stroke) ?>,
                        visible: <?= $e->visible_fun_js ?>,
                    }
                );
<?
                    }
                    elseif ($e instanceof Integral) {

                        //////////////
                        // Integral //
                        //////////////
?>
                const <?= $name ?> = board.create('integral',
                    [[<?= $e->x_start_fun_js ?>, <?= $e->x_end_fun_js ?>], <?= $e->graph_element_id ?>],
                    {
                        curveLeft: { visible: <?= $e->x_start_movable ? 'true' : 'false' ?> },
                        curveRight: { visible: <?= $e->x_end_movable ? 'true' : 'false' ?> },
                        fillColor: '<?= translate_color_to_hex($e->color) ?>',
                        withLabel: false,
                        visible: <?= $e->visible_fun_js ?>,
                    }
                );
<?
                        if ($e->label !== null) {   
?>
                const <?= $name ?>_label = board.create('text',
                    [
                        () => ((<?= $e->x_start_fun_js ?>)() + (<?= $e->x_end_fun_js ?>)()) / 2,
                        () => {
                            const width = Math.abs((<?= $e->x_end_fun_js ?>)() - (<?= $e->x_start_fun_js ?>)());
                            const height = <?= $name ?>.Value() / width * 0.5;
                            return height;
                        },
                        '<?= $e->label ?>'
                    ],
                    {
                        anchorX: 'middle',
                        anchorY: 'middle',
                        strokeColor: '<?= translate_color_to_hex($e->color) ?>', fontSize: <?= translate_font_size_to_pixels($options->base_font_size, $e->label_font_size_adjust) ?>
                    }
                );
<?
                        }
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

    // $primary_fun_js is a function like `(x) => x*x` 
    function jsx_plot_two_fun_graphs_card_begin($title, $primary_fun_js = null, $background_fun_js = null) {
?>
    <div class="card" style="width: 260px;">
        <div class="card-img-top d-flex justify-content-center" style="margin-top:5px;">
<?
            $elements = [];

            if ($primary_fun_js !== null) {
                $elements['graph_bold'] = new FunctionGraph(
                    function_js: $primary_fun_js,
                );
            }
                    
            if ($background_fun_js !== null) {
                $elements['graph_background'] = new FunctionGraph(
                    function_js: $background_fun_js,
                    color: Color::Blue,
                    stroke: Stroke::Dashed,
                    thickness: 3
                );
            }

            plot_2d(
                new GraphOptions(
                    viewport: Viewport2D::fromXRange(
                        width: 250,
                        height: 250,
                        x_min: -5,
                        x_max: 5,
                    ),
                ),
                $elements
            );
?>
        </div>
        <div class="card-body">
            <p class="card-title"><strong><?= $title ?></strong></p>
<?
    }

    function jsx_plot_two_fun_graphs_card_end() {
?>
        </div>
    </div>
<?
    }
?>
