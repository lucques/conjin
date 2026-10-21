<?
    namespace Conjin\ModulesShared\JSXGraphHelpers;

    use Conjin\ModulesShared\GraphingTypes\CustomElement;
    use Conjin\ModulesShared\GraphingTypes\FontSize;
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
        public Axes3D $axes;

        public function __construct(
            public float $x_min,
            public float $x_max,
            public float $y_min,
            public float $y_max,
            public float $z_min,
            public float $z_max,
            public int   $width,
            public int   $height,
            ?Axes3D $axes = null,
            public Spacing3D $spacing = new Spacing3D(),
            public bool $show_bounding_box = false,
            public bool $axes_extend_to_negative = false,
            public float $azimuth = M_PI / 4 + M_PI / 10,
            public float $elevation = 0.6154797086703874,
            public PrintMode $print_mode = PrintMode::Auto,
            public Zoom $zoom = Zoom::EnabledWithNav,
            public Pan $pan = Pan::EnabledWithNav,
            public Rotation $rotation = Rotation::EnabledWithSliders,
            public WheelBehavior $wheel_behavior = WheelBehavior::Disabled,
            public PinchBehavior $pinch_behavior = PinchBehavior::Disabled,
            public TwoFingerTranslationBehavior $two_finger_translation_behavior = TwoFingerTranslationBehavior::Disabled,
            public OneFingerDragBehavior3D $one_finger_drag_behavior = OneFingerDragBehavior3D::AllowForRotation,
            public MouseDragBehavior3D $mouse_drag_behavior = MouseDragBehavior3D::AllowForRotation,
            public FontSize $base_font_size = FontSize::MD,
            public float $point_label_auto_position_min_distance = POINT_LABEL_AUTO_POSITION_MIN_DISTANCE, // in pixels
            public float $point_label_auto_position_max_distance = POINT_LABEL_AUTO_POSITION_MAX_DISTANCE, // in pixels
        ) {
            $this->axes = $axes ?? Axes3D::enabled();
            jsx_validate_point_label_auto_position_distances(
                $this->point_label_auto_position_min_distance,
                $this->point_label_auto_position_max_distance,
                'Dynamic 3D plot',
            );
            if (!is_finite($this->azimuth) || $this->azimuth < 0 || $this->azimuth > 2 * M_PI) {
                throw new \InvalidArgumentException('Dynamic 3D plot azimuth must be finite and between 0 and 2π');
            }
            if (!is_finite($this->elevation) || $this->elevation < -M_PI / 2 || $this->elevation > M_PI / 2) {
                throw new \InvalidArgumentException('Dynamic 3D plot elevation must be finite and between -π/2 and π/2');
            }
        }
    }


    ////////////////////
    // Print and draw //
    ////////////////////

    function plot_3d_dynamic(
        Plot3DDynamicOptions $options,
        array $elements      // array of System3DIndepElement|ControlElement
    ) {
        $print_mode = jsx_resolve_print_mode($options->print_mode);
        $axes = jsx_resolve_axes($options->axes, $options->base_font_size, $print_mode);
        $spacing = jsx_resolve_spacing($options->spacing);
        jsx_validate_3d_axes($axes, $spacing);
        $interactions = jsx_resolve_3d_plot_interactions(
            plot_name: 'plot_3d_dynamic',
            zoom: $options->zoom,
            pan: $options->pan,
            rotation: $options->rotation,
            wheel_behavior: $options->wheel_behavior,
            pinch_behavior: $options->pinch_behavior,
            two_finger_translation_behavior: $options->two_finger_translation_behavior,
            one_finger_drag_behavior: $options->one_finger_drag_behavior,
            mouse_drag_behavior: $options->mouse_drag_behavior,
        );
        $id = 'jsxgraph-view-3d-' . next_unique_id();
        $reset_view_id = $id . '-reset-view';
        $x_label_pos = $options->x_max + 0.05 * ($options->x_max - $options->x_min);
        $y_label_pos = $options->y_max + 0.05 * ($options->y_max - $options->y_min);
        $z_label_pos = $options->z_max + 0.05 * ($options->z_max - $options->z_min);
        $x_span = abs($options->x_max - $options->x_min);
        $y_span = abs($options->y_max - $options->y_min);
        $z_span = abs($options->z_max - $options->z_min);
        $edge_padding = 20;
        $control_gutter = 30;
        $viewport_x = $edge_padding + ($interactions->rotation_enabled ? $control_gutter : 0);
        $viewport_y = $edge_padding + (($interactions->rotation_enabled || $interactions->show_navigation) ? $control_gutter : 0);
        $viewport_width = $options->width - $viewport_x - $edge_padding;
        $viewport_height = $options->height - $viewport_y - $edge_padding;
        if ($viewport_width <= 0 || $viewport_height <= 0) {
            throw new \InvalidArgumentException('Dynamic 3D plot dimensions are too small for its controls');
        }
        // Project the bounding box at the initial view angles and fit its 2D bounds into the viewport.
        $azimuth_sin = sin($options->azimuth);
        $azimuth_cos = cos($options->azimuth);
        $elevation_sin = sin($options->elevation);
        $elevation_cos = cos($options->elevation);
        $projected_width = $x_span * abs($azimuth_cos) + $y_span * abs($azimuth_sin);
        $projected_height =
            $x_span * abs($elevation_sin * $azimuth_sin)
            + $y_span * abs($elevation_sin * $azimuth_cos)
            + $z_span * abs($elevation_cos);
        $view_unit_scale = min(
            $viewport_width / $projected_width,
            $viewport_height / $projected_height
        );
        $scaled_view_width = $x_span * $view_unit_scale;
        $scaled_view_height = $y_span * $view_unit_scale;
        $scaled_view_x = $viewport_x + ($viewport_width - $scaled_view_width) / 2;
        $scaled_view_y = $viewport_y + ($viewport_height - $scaled_view_height) / 2;
        $rotation_slider_max_length = 250;
        $az_slider_width = min($options->width - 2 * $edge_padding, $rotation_slider_max_length);
        $az_slider_x_min = ($options->width - $az_slider_width) / 2;
        $az_slider_x_max = $az_slider_x_min + $az_slider_width;
        $el_slider_height = min($options->height - 2 * $edge_padding, $rotation_slider_max_length);
        $el_slider_y_min = ($options->height - $el_slider_height) / 2;
        $el_slider_y_max = $el_slider_y_min + $el_slider_height;
        $x_axis_start = $options->axes_extend_to_negative ? $options->x_min : 0;
        $y_axis_start = $options->axes_extend_to_negative ? $options->y_min : 0;
        $z_axis_start = $options->axes_extend_to_negative ? $options->z_min : 0;
?>
        <div class="d-inline-block position-relative">
            <div id="<?= $id ?>" class="jsxgraph-plot" style="width: <?= $options->width ?>px; height: <?= $options->height ?>px;"></div>
<?
        if ($interactions->rotation_enabled) {
?>
            <button id="<?= $reset_view_id ?>" type="button" class="btn btn-outline-secondary btn-sm position-absolute bottom-0 start-0 m-1" style="z-index: 100;" title="Ansicht zurücksetzen" aria-label="Ansicht zurücksetzen">&#8634;</button>
<?
        }
?>
        </div>
        <script>
            jsx_register_initial_board('<?= $id ?>', function() {
                var board = JXG.JSXGraph.initBoard('<?= $id ?>', {
                    showCopyright: false,
                    boundingbox: [0, <?= $options->height ?>, <?= $options->width ?>, 0],
                    axis: false,
                    showNavigation: <?= $interactions->show_navigation ? 'true' : 'false' ?>,
                    showZoom: <?= $interactions->show_zoom ? 'true' : 'false' ?>,
                    keepaspectratio: true,
                    pan: {
                        enabled: <?= $interactions->pan_enabled ? 'true' : 'false' ?>,
                        needTwoFingers: <?= $interactions->pan_need_two_fingers ? 'true' : 'false' ?>
                    },
                    zoom: {
                        enabled: <?= $interactions->zoom_enabled ? 'true' : 'false' ?>,
                        wheel: <?= $interactions->zoom_wheel ? 'true' : 'false' ?>,
                        pinch: <?= $interactions->zoom_pinch ? 'true' : 'false' ?>
                    }
                });
                const azSliderXMin = <?= $az_slider_x_min ?>;
                const azSliderXMax = <?= $az_slider_x_max ?>;
                const azSliderY = <?= $edge_padding ?>;
                const elSliderX = <?= $edge_padding ?>;
                const elSliderYMin = <?= $el_slider_y_min ?>;
                const elSliderYMax = <?= $el_slider_y_max ?>;
                const azimuth = <?= $options->azimuth ?>;
                const elevation = <?= $options->elevation ?>;

                var view = board.create('view3d',
                    [
                        [<?= $scaled_view_x ?>, <?= $scaled_view_y ?>],
                        [<?= $scaled_view_width ?>, <?= $scaled_view_height ?>],
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
                                enabled: <?= $interactions->rotation_drag_enabled ? 'true' : 'false' ?>,
                                speed: 0.25,
                                outside: false
                            },
                            slider: {
                                visible: <?= $interactions->rotation_enabled ? 'true' : 'false' ?>,
                                name: '',
                                withLabel: false,
                                start: azimuth,
                                label: { visible: false },
                                point1: { pos: [azSliderXMin, azSliderY], frozen: true },
                                point2: { pos: [azSliderXMax, azSliderY], frozen: true }
                            }
                        },
                        el: {
                            pointer: {
                                enabled: <?= $interactions->rotation_drag_enabled ? 'true' : 'false' ?>,
                                speed: 0.25,
                                outside: false
                            },
                            slider: {
                                visible: <?= $interactions->rotation_enabled ? 'true' : 'false' ?>,
                                name: '',
                                withLabel: false,
                                start: elevation,
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
<?
        if ($interactions->rotation_enabled) {
?>
                document.getElementById('<?= $reset_view_id ?>').addEventListener('click', function() {
                    view.setView(azimuth, elevation);
                });
<?
        }
?>

<?
        foreach (['x', 'y', 'z'] as $index => $coordinate) {
            $axis = $axes[$coordinate];
            $start = [0, 0, 0];
            $end = [0, 0, 0];
            $label_position = [0, 0, 0];
            $direction = [0, 0, 0];
            $start[$index] = ${$coordinate . '_axis_start'};
            $end[$index] = $options->{$coordinate . '_max'};
            $label_position[$index] = ${$coordinate . '_label_pos'};
            $direction[$index] = $end[$index] >= $start[$index] ? 1 : -1;
            $length = abs($end[$index] - $start[$index]);
            $tick_direction = match ($coordinate) {
                'x' => [0, 1, 0],
                'y' => [1, 0, 0],
                'z' => [1, 1, 0],
            };
            if ($axis->is_enabled) {
?>
                const <?= $coordinate ?>Axis = view.create('axis3d',
                    <?= jsx_axes_json([$start, $end]) ?>,
                    { fixed: true, layer: 13, strokeColor: 'black', strokeWidth: <?= $axis->thickness ?>, lastArrow: true }
                );
<?
                if ($axis->label !== '') {
?>
                view.create('text3d',
                    <?= jsx_axes_json([$label_position, $axis->label]) ?>,
                    { fixed: true, layer: 13, strokeColor: 'black', fontSize: <?= $axis->label_font_size ?>, anchorX: 'middle', anchorY: 'middle', offset: [10, -10] }
                );
<?
                }
            } else {
?>
                const <?= $coordinate ?>Axis = null;
<?
            }
            if ($axis->ticks->is_enabled) {
?>
                const <?= $coordinate ?>Ticks = view.create('ticks3d',
                    <?= jsx_axes_json([$start, $direction, $length, $tick_direction]) ?>,
                    {
                        element3d: true,
                        fixed: true,
                        layer: 13,
                        ticksDistance: <?= $spacing[$coordinate]->major ?>,
                        majorHeight: <?= (6 + $axis->thickness) / $view_unit_scale ?>,
                        tickEndings: [1, 1],
                        drawLabels: <?= $axis->ticks->numbers ? 'true' : 'false' ?>,
                        strokeColor: 'black',
                        strokeWidth: <?= $axis->thickness ?>,
                        label: { strokeColor: 'black', fontSize: <?= $axis->ticks->numbers_font_size ?><?= $coordinate === 'x' ? ", anchorX: 'left'" : ($coordinate === 'y' ? ", anchorX: 'right'" : '') ?> }
                    }
                );
<?
            } else {
?>
                const <?= $coordinate ?>Ticks = null;
<?
            }
        }
?>
                [xTicks, yTicks, zTicks].filter(Boolean).forEach(function(ticks) {
                    jsx_make_ticks3d_labels_zoom_invariant(board, ticks);
                });
                // Prefer z for the origin label, falling back to another numbered axis.
                let originLabelShown = false;
                [zTicks, yTicks, xTicks].filter(Boolean).forEach(function(ticks) {
                    (ticks.labels ?? []).forEach(function(label) {
                        if (Math.abs(Number(label.element2D.plaintext)) < 1e-10) {
                            if (originLabelShown) {
                                label.element2D.setAttribute({ visible: false });
                            }
                            originLabelShown = true;
                        }
                    });
                });

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
                        label: { autoPosition: true, autoPositionMinDistance: <?= $options->point_label_auto_position_min_distance ?>, autoPositionMaxDistance: <?= $options->point_label_auto_position_max_distance ?>, strokeColor: '<?= translate_color_to_hex($e->color) ?>', fontSize: <?= translate_font_size_to_pixels($options->base_font_size, $e->label_font_size_adjust) ?> },
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
                        withLabel: <?= $e->is_segment ? 'false' : 'true' ?>,
<?
                            if (!$e->is_segment) {
?>
                        label: {
                            strokeColor: '<?= translate_color_to_hex($e->color) ?>',
                            fontSize: <?= translate_font_size_to_pixels($options->base_font_size, $e->label_font_size_adjust) ?>,
                        },
<?
                            }
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
                        if ($e->is_segment && $e->label !== null) {
                            $label_font_size = translate_font_size_to_pixels($options->base_font_size, $e->label_font_size_adjust);
?>
                jsx_create_segment_midpoint_label(
                    board,
                    <?= $name ?>.element2D,
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
?>
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
