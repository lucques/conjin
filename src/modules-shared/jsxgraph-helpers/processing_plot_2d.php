<?
    namespace Conjin\ModulesShared\JSXGraphHelpers;

    use Conjin\ModulesShared\GraphingTypes\Color;
    use Conjin\ModulesShared\GraphingTypes\CustomElement;
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
        public function __construct(
            public float   $x_min,
            public float   $x_max,
            public float   $y_min,
            public float   $y_max,
            public int     $width,
            public int     $height,
            public ?float  $x_ticks_distance = null,
            public ?float  $y_ticks_distance = null,
            public int     $x_ticks_minors = 4,
            public int     $y_ticks_minors = 4,
            // JavaScript formatter function taking the x coordinate and returning the tick label text, e.g. "(x) => x*60 + '/60'".
            public ?string $x_ticks_label_formatter = null,
            // JavaScript formatter function taking the y coordinate and returning the tick label text, e.g. "(y) => y*60 + '/60'".
            public ?string $y_ticks_label_formatter = null,
            public string  $x_label = 'x',
            public string  $y_label = 'y',
            public bool    $show_grid  = true,  // Grid has same distances as ticks.
            public bool    $show_ticks = true,  // When ticks are shown, grid is implicitly also shown.
            public bool    $high_contrast = false,
        ) {}
    }


    ////////////////////
    // Print and draw //
    ////////////////////

    function plot_2d(
        GraphOptions $options,
        array $elements      // array of System2DElement|ControlElement
    ) {
        $id = 'jsxgraph-plot-' . next_unique_id();
?>
        <div id="<?= $id ?>" class="jsxgraph-plot" style="width: <?= $options->width ?>px; height: <?= $options->height ?>px;"></div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var board = JXG.JSXGraph.initBoard('<?= $id ?>', {
                    showCopyright: false,
                    boundingbox: [<?= $options->x_min ?>, <?= $options->y_max ?>, <?= $options->x_max ?>, <?= $options->y_min ?>],
                    axis: true,
                    grid: <?= $options->show_grid ? "{ majorStep: [" . ($options->x_ticks_distance ?? "'auto'") . ", " . ($options->y_ticks_distance ?? "'auto'") . "] }" : 'false' ?>,
                    showNavigation: false,
                    keepaspectratio: false,
                    pan: { enabled: false },
                    zoom: { enabled: false },
                    defaultAxes: {
                        x : {
<?
    if ($options->high_contrast) {
?>
                            strokeColor: 'black',
<?
    }
?>
                            name: '<?= $options->x_label ?>',
                            withLabel: true,
                            label: { fontSize: 14, cssStyle: 'font-weight: 900;', position: 'last', offset: [5, 3], anchorX: 'right', anchorY: 'bottom'},
<?
    if (!$options->show_ticks) {
?>
                            ticks: { visible: false },
<?
    }
    else {
?>
                            ticks: { insertTicks: <?= $options->x_ticks_distance !== null ? 'false' : 'true' ?>, generateLabelText: function(tick, zero) { const coord = tick.usrCoords[1]; const formatter = <?= $options->x_ticks_label_formatter ?? 'null' ?>; return formatter === null ? String(Math.round(coord*100)/100) : formatter(coord); }, <?= $options->x_ticks_distance !== null ? 'ticksDistance: ' . $options->x_ticks_distance . ', ' : '' ?>minorTicks: <?= $options->x_ticks_minors ?> },
<?
    }
?>
                        },
                        y : {
<?
    if ($options->high_contrast) {
?>
                            strokeColor: 'black',
<?
    }
?>
                            withLabel: true,
                            name: '<?= $options->y_label ?>',
                            label: { fontSize: 14, cssStyle: 'font-weight: 900;', position: 'last', offset: [6, 5], anchorX: 'left'},
<?
    if (!$options->show_ticks) {
?>
                            ticks: { visible: false },
<?
    }
    else {
?>
                            ticks: { insertTicks: <?= $options->y_ticks_distance !== null ? 'false' : 'true' ?>, generateLabelText: function(tick, zero) { const coord = tick.usrCoords[2]; const formatter = <?= $options->y_ticks_label_formatter ?? 'null' ?>; return formatter === null ? String(Math.round(coord*100)/100) : formatter(coord); }, <?= $options->y_ticks_distance !== null ? 'ticksDistance: ' . $options->y_ticks_distance . ', ' : '' ?>minorTicks: <?= $options->y_ticks_minors ?> },
<?
    }
?>
                        }
                    }
                });
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
                        label: { autoPosition: true, strokeColor: '<?= translate_color_to_hex($e->color) ?>', fontSize: <?= $e->label_font_size ?> },
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
                        withLabel: true,
                        label: { strokeColor: '<?= translate_color_to_hex($e->color) ?>', fontSize: <?= $e->label_font_size ?>, position: '50% left', anchorX: 'middle', anchorY: 'middle'},
<?
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
                    }
                    elseif ($e instanceof FunctionGraph) {

                        ///////////////////
                        // FunctionGraph //
                        ///////////////////
?>
                const <?= $name ?> = board.create(
                    'functiongraph',
                    [<?= $e->function_js ?>, <?= $e->x_start_fun_js !== null ? $e->x_start_fun_js : $options->x_min ?>, <?= $e->x_end_fun_js !== null ? $e->x_end_fun_js : $options->x_max ?>],
                    {
                        strokeColor: '<?= translate_color_to_hex($e->color) ?>',
                        strokeWidth: <?= $e->thickness ?>,
<?
                        if ($e->label !== null) {   
?>
                        name: '<?= $e->label ?>',
                        withLabel: true,
                        label: { strokeColor: '<?= translate_color_to_hex($e->color) ?>', fontSize: <?= $e->label_font_size ?>, position: '50% left', anchorX: 'middle', anchorY: 'middle'},
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
                        strokeColor: '<?= translate_color_to_hex($e->color) ?>', fontSize: <?= $e->label_font_size ?>
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
                    x_min: -5,
                    x_max: 5,
                    y_min: -5,
                    y_max: 5,
                    width: 250,
                    height: 250
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
