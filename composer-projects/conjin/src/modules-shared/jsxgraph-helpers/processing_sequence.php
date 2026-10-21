<?
    namespace Conjin\ModulesShared\JSXGraphHelpers;

    use Conjin\ModulesShared\GraphingTypes\CustomElement;
    use Conjin\ModulesShared\GraphingTypes\ExplicitSequence;
    use Conjin\ModulesShared\GraphingTypes\FontSize;
    use Conjin\ModulesShared\GraphingTypes\RecursiveSequence;
    use function Conjin\ModulesShared\GraphingTypes\translate_color_to_hex;


    ///////////////////////////
    // Sequence plot options //
    ///////////////////////////

    readonly class SequencePlotOptions {
        public Axes2D $axes;
        public Grid2D $grid;

        public function __construct(
            public float $y_min,
            public float $y_max,
            public int $width,
            public int $height,
            ?Axes2D $axes = null,
            public Spacing2D $spacing = new Spacing2D(),
            ?Grid2D $grid = null,
            public ?float $x_min = null,     // View bound; null uses the smallest sequence start.
            public ?float $x_max = null,     // View bound; null uses the largest finite end or 10.

            public PrintMode $print_mode = PrintMode::Auto,
            public Zoom $zoom = Zoom::Disabled,
            public Pan $pan = Pan::Disabled,
            public WheelBehavior $wheel_behavior = WheelBehavior::Disabled,
            public PinchBehavior $pinch_behavior = PinchBehavior::Disabled,
            public TwoFingerTranslationBehavior $two_finger_translation_behavior = TwoFingerTranslationBehavior::Disabled,
            public OneFingerDragBehavior2D $one_finger_drag_behavior = OneFingerDragBehavior2D::Disabled,
            public MouseDragBehavior2D $mouse_drag_behavior = MouseDragBehavior2D::Disabled,
            public FontSize $base_font_size = FontSize::MD,
        ) {
            $this->axes = $axes ?? Axes2D::enabled();
            $this->grid = $grid ?? Grid2D::enabled();
        }
    }


    ////////////////////
    // Print and draw //
    ////////////////////

    function plot_sequences(
        SequencePlotOptions $options,
        array $elements      // array of SequenceElement|ControlElement
    ) {
        $print_mode = jsx_resolve_print_mode($options->print_mode);
        $axes = jsx_resolve_axes($options->axes, $options->base_font_size, $print_mode, sequence: true);
        $spacing = jsx_resolve_spacing($options->spacing, sequence: true);
        $id = 'jsxgraph-plot-' . next_unique_id();
        $sequences = array_values(array_filter(
            $elements,
            fn($element) => $element instanceof ExplicitSequence || $element instanceof RecursiveSequence
        ));
        $sequence_n_starts = array_map(
            fn($sequence) => max($sequence->n_start ?? ($sequence->start_from_zero ? 0 : 1), $sequence->start_from_zero ? 0 : 1),
            $sequences
        );
        $sequence_n_ends = array_values(array_filter(
            array_map(fn($sequence) => $sequence->n_end, $sequences),
            fn($n_end) => $n_end !== null
        ));
        $view_x_min = $options->x_min ?? ($sequence_n_starts === [] ? 0 : min($sequence_n_starts));
        $view_x_max = $options->x_max ?? ($sequence_n_ends === [] ? 10 : max($sequence_n_ends));

        if (!is_finite($view_x_min) || !is_finite($view_x_max) || $view_x_min >= $view_x_max) {
            throw new \InvalidArgumentException('Sequence plot bounds must be finite and x_min must be less than x_max');
        }

        $interactions = jsx_resolve_2d_plot_interactions(
            plot_name: 'plot_sequences',
            zoom: $options->zoom,
            pan: $options->pan,
            wheel_behavior: $options->wheel_behavior,
            pinch_behavior: $options->pinch_behavior,
            two_finger_translation_behavior: $options->two_finger_translation_behavior,
            one_finger_drag_behavior: $options->one_finger_drag_behavior,
            mouse_drag_behavior: $options->mouse_drag_behavior,
        );

?>
        <div id="<?= $id ?>" class="jsxgraph-plot" style="width: <?= $options->width ?>px; height: <?= $options->height ?>px;"></div>
        <script>
            jsx_register_initial_board('<?= $id ?>', function() {
                var board = JXG.JSXGraph.initBoard('<?= $id ?>', {
                    showCopyright: false,
                    boundingbox: [<?= $view_x_min ?>, <?= $options->y_max ?>, <?= $view_x_max ?>, <?= $options->y_min ?>],
                    axis: false,
                    grid: false,
                    showNavigation: <?= $interactions->show_navigation ? 'true' : 'false' ?>,
                    showZoom: <?= $interactions->show_zoom ? 'true' : 'false' ?>,
                    pan: {
                        enabled: <?= $interactions->pan_enabled ? 'true' : 'false' ?>,
                        needTwoFingers: <?= $interactions->pan_need_two_fingers ? 'true' : 'false' ?>,
                        needShift: false,
                        needMeta: false,
                        needCtrl: false,
                        needAlt: false,
                        needKey: false
                    },
                    zoom: {
                        enabled: <?= $interactions->zoom_enabled ? 'true' : 'false' ?>,
                        wheel: <?= $interactions->zoom_wheel ? 'true' : 'false' ?>,
                        pinch: <?= $interactions->zoom_pinch ? 'true' : 'false' ?>,
                        needShift: false,
                        needMeta: false,
                        needCtrl: false,
                        needAlt: false,
                        needKey: false
                    },
                });
<?
        jsx_print_2d_axes_setup($axes, $spacing, $options->grid, $print_mode, sequence: true);
?>
                const createSequencePoints = function(sequenceValue, nStart, nEnd, attributes) {
                    const points = new Map();
                    const syncPointsWithViewport = function() {
                        const boardBounds = board.getBoundingBox();
                        const visibleNStart = Math.max(nStart, Math.ceil(boardBounds[0]));
                        const visibleNEnd = Math.min(nEnd, Math.floor(boardBounds[2]));

                        points.forEach(function(point, n) {
                            if (n < visibleNStart || n > visibleNEnd) {
                                board.removeObject(point);
                                points.delete(n);
                            }
                        });

                        for (let n = visibleNStart; n <= visibleNEnd; n++) {
                            if (!points.has(n)) {
                                const nCur = n;
                                points.set(n, board.create('point', [nCur, () => sequenceValue(nCur)], attributes));
                            }
                        }
                    };

                    syncPointsWithViewport();
                    board.on('boundingbox', syncPointsWithViewport);
                };
<?
                // Go through elements
                foreach ($elements as $name => $e) {

                    if (print_board_control_update_listener($e, $name)) {
                        continue;
                    }

                    if ($e instanceof ExplicitSequence) {
                        $sequence_first_n = $e->start_from_zero ? 0 : 1;
                        $render_n_start = max($sequence_first_n, $e->n_start ?? $sequence_first_n);
                        $render_n_end_js = $e->n_end ?? 'Infinity';

                        //////////////////////
                        // ExplicitSequence //
                        //////////////////////
?>
                {
                    const sequenceFunction = <?= $e->function_js ?>;
                    createSequencePoints(sequenceFunction, <?= $render_n_start ?>, <?= $render_n_end_js ?>, {
                        name: '',
                        size: 3,
                        fixed: true,
                        strokeColor: '<?= translate_color_to_hex($e->color) ?>',
                        fillColor: '<?= translate_color_to_hex($e->color) ?>',
                        face: 'x'
                    });
                }
<?
                    }
                    elseif ($e instanceof RecursiveSequence) {
                        $sequence_first_n = $e->start_from_zero ? 0 : 1;
                        $render_n_start = max($sequence_first_n, $e->n_start ?? $sequence_first_n);
                        $render_n_end_js = $e->n_end ?? 'Infinity';

                        ////////////////////////
                        // RecursiveSequence //
                        ////////////////////////
?>
                {
                    const initialValue = () => <?= $e->init_val_js ?>;
                    const stepFunction = <?= $e->step_fun_js ?>;
                    const sequenceValue = (targetN) => {
                        let current = initialValue();
                        for (let n = <?= $sequence_first_n ?>; n < targetN; n++) {
                            current = stepFunction(current, n + 1);
                        }
                        return current;
                    };
                    createSequencePoints(sequenceValue, <?= $render_n_start ?>, <?= $render_n_end_js ?>, {
                        name: '',
                        size: 3,
                        fixed: true,
                        strokeColor: '<?= translate_color_to_hex($e->color) ?>',
                        fillColor: '<?= translate_color_to_hex($e->color) ?>',
                        face: 'x'
                    });
                }
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
