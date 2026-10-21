<?
    namespace Conjin\ModulesShared\JSXGraphHelpers;

    use Conjin\ModulesShared\GraphingTypes\ControlElement;
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


    ////////////////////////////////
    // Cavalier 3D plot options //
    ////////////////////////////////

    readonly class Plot3DCavalierOptions {
        public Axes3D $axes;
        public Grid2D $grid;

        public function __construct(
            public float $x_min,
            public float $x_max,
            public float $y_min,
            public float $y_max,
            public float $z_min,
            public float $z_max,
            public int $width,
            public int $height,
            ?Axes3D $axes = null,
            public Spacing3D $spacing = new Spacing3D(),
            ?Grid2D $grid = null,
            public bool $show_bounding_box = false,
            public bool $axes_extend_to_negative = false,
            public float $x_axis_angle_degrees = -135,
            public float $x_axis_scale = 1.4142135623730951,
            public float $y_axis_scale = 2,
            public float $z_axis_scale = 2,
            public float $plane_fallback_range = 8, // if a plane is infinite, this is the range used to draw it
            public float $plane_mesh_step = 1, // if a plane is drawn, this is the step size of the mesh drawn on its surface
            public PrintMode $print_mode = PrintMode::Auto,
            public Zoom $zoom = Zoom::Disabled,
            public Pan $pan = Pan::Disabled,
            public WheelBehavior $wheel_behavior = WheelBehavior::Disabled,
            public PinchBehavior $pinch_behavior = PinchBehavior::Disabled,
            public TwoFingerTranslationBehavior $two_finger_translation_behavior = TwoFingerTranslationBehavior::Disabled,
            public OneFingerDragBehavior2D $one_finger_drag_behavior = OneFingerDragBehavior2D::AllowForPan,
            public MouseDragBehavior2D $mouse_drag_behavior = MouseDragBehavior2D::AllowForPan,
            public FontSize $base_font_size = FontSize::MD,
            public float $point_label_auto_position_min_distance = POINT_LABEL_AUTO_POSITION_MIN_DISTANCE, // in pixels
            public float $point_label_auto_position_max_distance = POINT_LABEL_AUTO_POSITION_MAX_DISTANCE, // in pixels
        ) {
            $this->axes = $axes ?? Axes3D::enabled();
            $this->grid = $grid ?? Grid2D::enabled();
            jsx_validate_point_label_auto_position_distances(
                $this->point_label_auto_position_min_distance,
                $this->point_label_auto_position_max_distance,
                'Cavalier 3D plot',
            );
        }
    }


    ////////////////////
    // Print and draw //
    ////////////////////

    function plot_3d_cavalier(
        Plot3DCavalierOptions $options,
        array $elements      // array of System3DIndepElement|ControlElement
    ) {
        $print_mode = jsx_resolve_print_mode($options->print_mode);
        $axes = jsx_resolve_axes($options->axes, $options->base_font_size, $print_mode);
        $spacing = jsx_resolve_spacing($options->spacing);
        jsx_validate_3d_axes($axes, $spacing, $options->grid);
        $interactions = jsx_resolve_2d_plot_interactions(
            plot_name: 'plot_3d_cavalier',
            zoom: $options->zoom,
            pan: $options->pan,
            wheel_behavior: $options->wheel_behavior,
            pinch_behavior: $options->pinch_behavior,
            two_finger_translation_behavior: $options->two_finger_translation_behavior,
            one_finger_drag_behavior: $options->one_finger_drag_behavior,
            mouse_drag_behavior: $options->mouse_drag_behavior,
        );
        $id = 'jsxgraph-plot-3d-cavalier-' . next_unique_id();
        [$board_x_min, $board_x_max, $board_y_min, $board_y_max] = jsx_cavalier_board_bounds($options);
        $y_ticks_distance = $spacing['y']->major;
        $z_ticks_distance = $spacing['z']->major;
        $x_axis_start = jsx_cavalier_axis_start($options->x_min, $options->x_max, $options->axes_extend_to_negative);
        $y_axis_start = jsx_cavalier_axis_start($options->y_min, $options->y_max, $options->axes_extend_to_negative);
        $z_axis_start = jsx_cavalier_axis_start($options->z_min, $options->z_max, $options->axes_extend_to_negative);
        $axis_label_margin = 0.05 * max(
            $options->x_max - $x_axis_start,
            $options->y_max - $y_axis_start,
            $options->z_max - $z_axis_start,
            1
        );
?>
        <div id="<?= $id ?>" class="jsxgraph-plot" style="width: <?= $options->width ?>px; height: <?= $options->height ?>px;"></div>
        <script>
            jsx_register_initial_board('<?= $id ?>', function() {
                var board = JXG.JSXGraph.initBoard('<?= $id ?>', {
                    showCopyright: false,
                    boundingbox: [<?= jsx_cavalier_num($board_x_min) ?>, <?= jsx_cavalier_num($board_y_max) ?>, <?= jsx_cavalier_num($board_x_max) ?>, <?= jsx_cavalier_num($board_y_min) ?>],
                    axis: false,
<?
    if ($options->grid->is_enabled) {
?>
                    grid: {
                        majorStep: [<?= jsx_cavalier_num($y_ticks_distance) ?>, <?= jsx_cavalier_num($z_ticks_distance) ?>],
<?
        if ($print_mode) {
?>
                        strokeColor: 'black',
<?
        }
?>
                    },
<?
    }
    else {
?>
                    grid: false,
<?
    }
?>
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

                const __cavXScale = <?= jsx_cavalier_num($options->x_axis_scale) ?>;
                const __cavYScale = <?= jsx_cavalier_num($options->y_axis_scale) ?>;
                const __cavZScale = <?= jsx_cavalier_num($options->z_axis_scale) ?>;
                const __cavAngle = <?= jsx_cavalier_num(deg2rad($options->x_axis_angle_degrees)) ?>;
                const __cavCos = Math.cos(__cavAngle);
                const __cavSin = Math.sin(__cavAngle);
                const __cavValue = function(value) {
                    return typeof value === 'function' ? value() : value;
                };
                const __cavNumber = function(value) {
                    const resolved = __cavValue(value);
                    return typeof resolved === 'number' ? resolved : Number(resolved);
                };
                const __cavVec = function(vec) {
                    return [__cavNumber(vec[0]), __cavNumber(vec[1]), __cavNumber(vec[2])];
                };
                const __cavProject = function(vec) {
                    const v = __cavVec(vec);
                    return [
                        v[1] * __cavYScale + v[0] * __cavXScale * __cavCos,
                        v[2] * __cavZScale + v[0] * __cavXScale * __cavSin
                    ];
                };
                const __cavPlanePoint = function(support, dir1, r1, dir2, r2) {
                    const s = __cavVec(support);
                    const d1 = __cavVec(dir1);
                    const d2 = __cavVec(dir2);
                    const f1 = __cavNumber(r1);
                    const f2 = __cavNumber(r2);
                    return [
                        s[0] + f1 * d1[0] + f2 * d2[0],
                        s[1] + f1 * d1[1] + f2 * d2[1],
                        s[2] + f1 * d1[2] + f2 * d2[2]
                    ];
                };

<?
        foreach ($elements as $name => $e) {
            if (print_board_control_update_listener($e, (string)$name)) {
                continue;
            }
        }

        foreach ($elements as $name => $e) {
            if ($e instanceof ControlElement) {
                continue;
            }

            assert(
                $e instanceof System3DIndepElement,
                'JSXGraph cavalier 3D plot elements must implement System3DIndepElement or ControlElement'
            );

            if ($e instanceof Plane) {
                jsx_cavalier_print_plane((string)$name, $e, $options);
            }
        }

        foreach ($elements as $name => $e) {
            if ($e instanceof Polygon) {
                jsx_cavalier_print_polygon((string)$name, $e);
            }
        }

        if ($options->show_bounding_box) {
            jsx_cavalier_print_bounding_box($options);
        }

        jsx_cavalier_print_axes($options, $axes, $spacing, $axis_label_margin);

        foreach ($elements as $name => $e) {
            if ($e instanceof Line) {
                jsx_cavalier_print_line((string)$name, $e, $options);
            }
        }

        foreach ($elements as $name => $e) {
            if ($e instanceof Point) {
                jsx_cavalier_print_point((string)$name, $e, $options);
            }
        }

        foreach ($elements as $e) {
            if ($e instanceof CustomElement) {
                echo $e->js_code . "\n";
            }
        }
?>
            });
        </script>
<?
    }


    /////////////
    // Helpers //
    /////////////

    function jsx_cavalier_print_bounding_box(Plot3DCavalierOptions $options): void {
?>
                const __cavBoundingBoxEdges = [
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
                const __cavBoundingBoxAttrs = {
                    fixed: true,
                    strokeColor: '#888888',
                    highlightStrokeColor: '#888888',
                    strokeWidth: 1,
                    strokeOpacity: 0.7,
                    highlightStrokeOpacity: 0.7
                };
                __cavBoundingBoxEdges.forEach(function(edge) {
                    board.create('segment', [__cavProject(edge[0]), __cavProject(edge[1])], __cavBoundingBoxAttrs);
                });
<?
    }

    function jsx_cavalier_print_axes(Plot3DCavalierOptions $options, array $axes, array $spacing, float $axis_label_margin): void {
?>
                const __cavInitialUnitX = board.unitX;
                const __cavInitialUnitY = board.unitY;
                const __cavTickScaleX = function() { return __cavInitialUnitX / board.unitX; };
                const __cavTickScaleY = function() { return __cavInitialUnitY / board.unitY; };
<?
        $origin_tick_owner = null;
        $origin_number_owner = null;
        foreach (['x', 'y', 'z'] as $coordinate) {
            if ($axes[$coordinate]->ticks->is_enabled) {
                $origin_tick_owner ??= $coordinate;
                if ($axes[$coordinate]->ticks->numbers) {
                    $origin_number_owner ??= $coordinate;
                }
            }
        }
        foreach (['x', 'y', 'z'] as $index => $coordinate) {
            $axis = $axes[$coordinate];
            if (!$axis->is_enabled) {
                continue;
            }
            $start = [0, 0, 0];
            $end = [0, 0, 0];
            $label_position = [0, 0, 0];
            $start[$index] = jsx_cavalier_axis_start($options->{$coordinate . '_min'}, $options->{$coordinate . '_max'}, $options->axes_extend_to_negative);
            $end[$index] = $options->{$coordinate . '_max'};
            $label_position[$index] = $end[$index] + $axis_label_margin;
            $projected_start = jsx_cavalier_project_php($start[0], $start[1], $start[2], $options);
            $projected_end = jsx_cavalier_project_php($end[0], $end[1], $end[2], $options);
            $projected_label = jsx_cavalier_project_php($label_position[0], $label_position[1], $label_position[2], $options);
?>
                {
                    const __cavAxisThickness = <?= $axis->thickness ?>;
                    const __cavTickAttrs = { fixed: true, straightFirst: false, straightLast: false, strokeColor: 'black', highlightStrokeColor: 'black', strokeWidth: __cavAxisThickness };
                    board.create('line', <?= jsx_axes_json([$projected_start, $projected_end]) ?>, { ...__cavTickAttrs, lastArrow: true });
<?
            if ($axis->label !== '') {
?>
                    board.create('text', <?= jsx_axes_json([...$projected_label, $axis->label]) ?>, { fixed: true, anchorX: 'middle', anchorY: 'middle', fontSize: <?= $axis->label_font_size ?>, cssStyle: 'font-weight: 900;' });
<?
            }
            if ($axis->ticks->is_enabled) {
                $print_ticks = __NAMESPACE__ . '\\jsx_cavalier_print_' . $coordinate . '_ticks';
                $print_ticks($options, $axis, $start[$index], $end[$index], $spacing[$coordinate]->major, $origin_tick_owner === $coordinate, $origin_number_owner === $coordinate);
            }
?>
                }
<?
        }
    }

    function jsx_cavalier_print_x_ticks(Plot3DCavalierOptions $options, ResolvedAxis $axis, float $min, float $max, float $step, bool $show_origin_tick, bool $show_origin_number): void {
        $half_tick = 0.4 / 2;
        $label_margin_px = $axis->ticks->numbers_font_size / 2;

        foreach (jsx_cavalier_tick_values($min, $max, $step) as $tick) {
            [$u, $v] = jsx_cavalier_project_php($tick, 0, 0, $options);
            if ($show_origin_tick || abs($tick) >= 1e-10) {
?>
                board.create('line', [[function() { return <?= jsx_cavalier_num($u) ?> - <?= jsx_cavalier_num($half_tick) ?> * __cavTickScaleX() - 0.5 * __cavAxisThickness / board.unitX; }, <?= jsx_cavalier_num($v) ?>], [function() { return <?= jsx_cavalier_num($u) ?> + <?= jsx_cavalier_num($half_tick) ?> * __cavTickScaleX() + 0.5 * __cavAxisThickness / board.unitX; }, <?= jsx_cavalier_num($v) ?>]], __cavTickAttrs);
<?
            }
            if ($axis->ticks->numbers && ($show_origin_number || abs($tick) >= 1e-10)) {
?>
                board.create('text', [function() { return <?= jsx_cavalier_num($u) ?> - (<?= jsx_cavalier_num($label_margin_px) ?> + __cavAxisThickness) / board.unitX; }, <?= jsx_cavalier_num($v) ?>, <?= json_encode(jsx_cavalier_tick_label($tick)) ?>], { fixed: true, anchorX: 'right', anchorY: 'middle', fontSize: <?= $axis->ticks->numbers_font_size ?> });
<?
            }
        }
    }

    function jsx_cavalier_print_y_ticks(Plot3DCavalierOptions $options, ResolvedAxis $axis, float $min, float $max, float $step, bool $show_origin_tick, bool $show_origin_number): void {
        $half_tick = 0.4 / 2;
        $label_margin_px = $axis->ticks->numbers_font_size / 4;

        foreach (jsx_cavalier_tick_values($min, $max, $step) as $tick) {

            [$u, $v] = jsx_cavalier_project_php(0, $tick, 0, $options);
            if ($show_origin_tick || abs($tick) >= 1e-10) {
?>
                board.create('line', [[<?= jsx_cavalier_num($u) ?>, function() { return <?= jsx_cavalier_num($v) ?> - <?= jsx_cavalier_num($half_tick) ?> * __cavTickScaleY() - 0.5 * __cavAxisThickness / board.unitY; }], [<?= jsx_cavalier_num($u) ?>, function() { return <?= jsx_cavalier_num($v) ?> + <?= jsx_cavalier_num($half_tick) ?> * __cavTickScaleY() + 0.5 * __cavAxisThickness / board.unitY; }]], __cavTickAttrs);
<?
            }
            if ($axis->ticks->numbers && ($show_origin_number || abs($tick) >= 1e-10)) {
?>
                board.create('text', [<?= jsx_cavalier_num($u) ?>, function() { return <?= jsx_cavalier_num($v) ?> - (<?= jsx_cavalier_num($label_margin_px) ?> + __cavAxisThickness) / board.unitY; }, <?= json_encode(jsx_cavalier_tick_label($tick)) ?>], { fixed: true, anchorX: 'middle', anchorY: 'top', fontSize: <?= $axis->ticks->numbers_font_size ?> });
<?
            }
        }
    }

    function jsx_cavalier_print_z_ticks(Plot3DCavalierOptions $options, ResolvedAxis $axis, float $min, float $max, float $step, bool $show_origin_tick, bool $show_origin_number): void {
        $half_tick = 0.4 / 2;
        $label_offset = 0.75 * 0.4;

        foreach (jsx_cavalier_tick_values($min, $max, $step) as $tick) {

            [$u, $v] = jsx_cavalier_project_php(0, 0, $tick, $options);
            if ($show_origin_tick || abs($tick) >= 1e-10) {
?>
                board.create('line', [[function() { return <?= jsx_cavalier_num($u) ?> - <?= jsx_cavalier_num($half_tick) ?> * __cavTickScaleX() - 0.5 * __cavAxisThickness / board.unitX; }, <?= jsx_cavalier_num($v) ?>], [function() { return <?= jsx_cavalier_num($u) ?> + <?= jsx_cavalier_num($half_tick) ?> * __cavTickScaleX() + 0.5 * __cavAxisThickness / board.unitX; }, <?= jsx_cavalier_num($v) ?>]], __cavTickAttrs);
<?
            }
            if ($axis->ticks->numbers && ($show_origin_number || abs($tick) >= 1e-10)) {
?>
                board.create('text', [function() { return <?= jsx_cavalier_num($u) ?> - <?= jsx_cavalier_num($label_offset) ?> * __cavTickScaleX() - __cavAxisThickness / board.unitX; }, <?= jsx_cavalier_num($v) ?>, <?= json_encode(jsx_cavalier_tick_label($tick)) ?>], { fixed: true, anchorX: 'right', anchorY: 'middle', fontSize: <?= $axis->ticks->numbers_font_size ?> });
<?
            }
        }
    }

    function jsx_cavalier_print_point(string $name, Point $point, Plot3DCavalierOptions $options): void {
?>
                const <?= $name ?> = board.create('point',
                    <?= jsx_cavalier_projected_vec_js($point->vec) ?>,
                    {
<?
        if ($point->label !== null) {
?>
                        label: { autoPosition: true, autoPositionMinDistance: <?= $options->point_label_auto_position_min_distance ?>, autoPositionMaxDistance: <?= $options->point_label_auto_position_max_distance ?>, strokeColor: '<?= translate_color_to_hex($point->color) ?>', fontSize: <?= translate_font_size_to_pixels($options->base_font_size, $point->label_font_size_adjust) ?> },
                        withLabel: true,
<?
        }
?>
                        name: <?= json_encode($point->label ?? '') ?>,
                        size: <?= $point->size ?>,
                        fixed: true,
                        strokeColor: '<?= translate_color_to_hex($point->color) ?>',
                        highlightStrokeColor: '<?= translate_color_to_hex($point->color) ?>',
                        fillColor: '<?= translate_color_to_hex($point->color) ?>',
                        highlightFillColor: '<?= translate_color_to_hex($point->color) ?>',
                        face: 'x',
                        visible: <?= $point->visible_fun_js ?>,
                    }
                );
<?
    }

    function jsx_cavalier_print_line(string $name, Line $line, Plot3DCavalierOptions $options): void {
        $element_type = 'line';
        $point_a = jsx_cavalier_projected_vec_js($line->point_a);
        $point_b = jsx_cavalier_projected_vec_js($line->point_b);
        $straight_first = $line->extend_before_point_a;
        $straight_last = $line->extend_after_point_b;

        if (!$line->extend_before_point_a && !$line->extend_after_point_b) {
            $element_type = 'segment';
        }
?>
                const <?= $name ?> = board.create(
                    '<?= $element_type ?>',
                    [<?= $point_a ?>, <?= $point_b ?>],
                    {
<?
        if ($line->label !== null) {
?>
                        name: <?= json_encode($line->label) ?>,
                        withLabel: true,
                        label: { strokeColor: '<?= translate_color_to_hex($line->color) ?>', fontSize: <?= translate_font_size_to_pixels($options->base_font_size, $line->label_font_size_adjust) ?>, position: '50% left', anchorX: 'middle', anchorY: 'middle' },
<?
        }
?>
                        fixed: true,
                        straightFirst: <?= $straight_first ? 'true' : 'false' ?>,
                        straightLast: <?= $straight_last ? 'true' : 'false' ?>,
                        strokeColor: '<?= translate_color_to_hex($line->color) ?>',
                        highlightStrokeColor: '<?= translate_color_to_hex($line->color) ?>',
                        strokeWidth: <?= $line->thickness ?>,
                        firstArrow: <?= $line->first_arrow ? 'true' : 'false' ?>,
                        lastArrow: <?= $line->last_arrow ? 'true' : 'false' ?>,
                        dash: <?= translate_stroke_to_dash($line->stroke) ?>,
                        visible: <?= $line->visible_fun_js ?>,
                    }
                );
<?
    }

    function jsx_cavalier_print_plane(string $name, Plane $plane, Plot3DCavalierOptions $options): void {
        $r1_min = jsx_cavalier_range_js($plane->range_1_min_fun_js, -$options->plane_fallback_range);
        $r1_max = jsx_cavalier_range_js($plane->range_1_max_fun_js, $options->plane_fallback_range);
        $r2_min = jsx_cavalier_range_js($plane->range_2_min_fun_js, -$options->plane_fallback_range);
        $r2_max = jsx_cavalier_range_js($plane->range_2_max_fun_js, $options->plane_fallback_range);
        $support = jsx_cavalier_vec_js($plane->vec_support);
        $dir_1 = jsx_cavalier_vec_js($plane->vec_dir_1);
        $dir_2 = jsx_cavalier_vec_js($plane->vec_dir_2);
?>
                const <?= $name ?> = board.create('polygon',
                    [
                        <?= jsx_cavalier_projected_plane_point_js($support, $dir_1, $r1_min, $dir_2, $r2_min) ?>,
                        <?= jsx_cavalier_projected_plane_point_js($support, $dir_1, $r1_max, $dir_2, $r2_min) ?>,
                        <?= jsx_cavalier_projected_plane_point_js($support, $dir_1, $r1_max, $dir_2, $r2_max) ?>,
                        <?= jsx_cavalier_projected_plane_point_js($support, $dir_1, $r1_min, $dir_2, $r2_max) ?>
                    ],
                    {
                        fixed: true,
                        fillColor: '<?= translate_color_to_hex($plane->color) ?>',
                        fillOpacity: <?= translate_opacity_to_float($plane->opacity) ?>,
                        highlightFillColor: '<?= translate_color_to_hex($plane->color) ?>',
                        highlightFillOpacity: <?= translate_opacity_to_float($plane->opacity) ?>,
                        borders: {
                            strokeColor: '<?= translate_color_to_hex($plane->color) ?>',
                            strokeWidth: <?= $plane->thickness ?>,
                            dash: <?= translate_stroke_to_dash($plane->stroke) ?>,
                            highlightStrokeColor: '<?= translate_color_to_hex($plane->color) ?>',
                            highlightStrokeWidth: <?= $plane->thickness ?>
                        },
                        vertices: { visible: false, withLabel: false, fixed: true, size: 0, label: { visible: false } },
                        visible: <?= $plane->visible_fun_js ?>,
                    }
                );
<?
        if (!$plane->mesh_visible) {
            return;
        }

        jsx_cavalier_print_plane_mesh($plane, $options, $support, $dir_1, $dir_2, $r1_min, $r1_max, $r2_min, $r2_max);
    }

    function jsx_cavalier_print_plane_mesh(
        Plane $plane,
        Plot3DCavalierOptions $options,
        string $support,
        string $dir_1,
        string $dir_2,
        string $r1_min,
        string $r1_max,
        string $r2_min,
        string $r2_max
    ): void {
        $r1_min_num = jsx_cavalier_static_number($r1_min);
        $r1_max_num = jsx_cavalier_static_number($r1_max);
        $r2_min_num = jsx_cavalier_static_number($r2_min);
        $r2_max_num = jsx_cavalier_static_number($r2_max);

        if ($r1_min_num === null || $r1_max_num === null || $r2_min_num === null || $r2_max_num === null) {
            return;
        }

        $r1_values = jsx_cavalier_tick_values($r1_min_num, $r1_max_num, $options->plane_mesh_step);
        $r2_values = jsx_cavalier_tick_values($r2_min_num, $r2_max_num, $options->plane_mesh_step);
        $max_lines = 60;

        if (count($r1_values) + count($r2_values) > $max_lines) {
            return;
        }

        foreach ($r1_values as $r1) {
?>
                board.create('segment',
                    [
                        <?= jsx_cavalier_projected_plane_point_js($support, $dir_1, jsx_cavalier_num($r1), $dir_2, $r2_min) ?>,
                        <?= jsx_cavalier_projected_plane_point_js($support, $dir_1, jsx_cavalier_num($r1), $dir_2, $r2_max) ?>
                    ],
                    {
                        fixed: true,
                        strokeColor: '<?= translate_color_to_hex($plane->color) ?>',
                        strokeOpacity: 0.45,
                        strokeWidth: 1,
                        dash: 1,
                        visible: <?= $plane->visible_fun_js ?>,
                    }
                );
<?
        }

        foreach ($r2_values as $r2) {
?>
                board.create('segment',
                    [
                        <?= jsx_cavalier_projected_plane_point_js($support, $dir_1, $r1_min, $dir_2, jsx_cavalier_num($r2)) ?>,
                        <?= jsx_cavalier_projected_plane_point_js($support, $dir_1, $r1_max, $dir_2, jsx_cavalier_num($r2)) ?>
                    ],
                    {
                        fixed: true,
                        strokeColor: '<?= translate_color_to_hex($plane->color) ?>',
                        strokeOpacity: 0.45,
                        strokeWidth: 1,
                        dash: 1,
                        visible: <?= $plane->visible_fun_js ?>,
                    }
                );
<?
        }
    }

    function jsx_cavalier_print_polygon(string $name, Polygon $polygon): void {
?>
                const <?= $name ?> = board.create('polygon',
                    [
<?
        foreach ($polygon->vertices as $vertex) {
            assert($vertex instanceof Point, 'JSXGraph cavalier 3D polygon vertices must be Point instances');
?>
                        <?= jsx_cavalier_projected_vec_js($vertex->vec) ?>,
<?
        }
?>
                    ],
                    {
                        fixed: true,
                        fillColor: '<?= translate_color_to_hex($polygon->color) ?>',
                        fillOpacity: <?= translate_opacity_to_float($polygon->opacity) ?>,
                        highlightFillColor: '<?= translate_color_to_hex($polygon->color) ?>',
                        highlightFillOpacity: <?= translate_opacity_to_float($polygon->opacity) ?>,
                        borders: {
                            strokeColor: '<?= translate_color_to_hex($polygon->color) ?>',
                            strokeWidth: <?= $polygon->thickness ?>,
                            dash: <?= translate_stroke_to_dash($polygon->stroke) ?>,
                            highlightStrokeColor: '<?= translate_color_to_hex($polygon->color) ?>',
                            highlightStrokeWidth: <?= $polygon->thickness ?>
                        },
                        vertices: { visible: false, withLabel: false, fixed: true, size: 0, label: { visible: false } },
                        visible: <?= $polygon->visible_fun_js ?>,
                    }
                );
<?
    }

    function jsx_cavalier_board_bounds(Plot3DCavalierOptions $options): array {
        $points = [];

        foreach ([$options->x_min, $options->x_max] as $x) {
            foreach ([$options->y_min, $options->y_max] as $y) {
                foreach ([$options->z_min, $options->z_max] as $z) {
                    $points[] = jsx_cavalier_project_php($x, $y, $z, $options);
                }
            }
        }

        $u_values = array_map(fn($p) => $p[0], $points);
        $v_values = array_map(fn($p) => $p[1], $points);
        $u_min = min($u_values);
        $u_max = max($u_values);
        $v_min = min($v_values);
        $v_max = max($v_values);
        $span = max($u_max - $u_min, $v_max - $v_min, 1);
        $margin = 0.08 * $span + 0.5;

        return [
            $u_min - $margin,
            $u_max + $margin,
            $v_min - $margin,
            $v_max + $margin,
        ];
    }

    function jsx_cavalier_axis_start(float $min, float $max, bool $extend_to_negative): float {
        if ($extend_to_negative) {
            return $min;
        }

        return $min <= 0 && $max >= 0 ? 0 : $min;
    }

    function jsx_cavalier_project_php(float $x, float $y, float $z, Plot3DCavalierOptions $options): array {
        $angle = deg2rad($options->x_axis_angle_degrees);

        return [
            $y * $options->y_axis_scale + $x * $options->x_axis_scale * cos($angle),
            $z * $options->z_axis_scale + $x * $options->x_axis_scale * sin($angle),
        ];
    }

    function jsx_cavalier_vec_js(Vec $vec): string {
        return '[' . $vec->x_fun_js . ', ' . $vec->y_fun_js . ', ' . $vec->z_fun_js . ']';
    }

    function jsx_cavalier_projected_vec_js(Vec $vec): string {
        $vec_js = jsx_cavalier_vec_js($vec);

        return '[() => __cavProject(' . $vec_js . ')[0], () => __cavProject(' . $vec_js . ')[1]]';
    }

    function jsx_cavalier_projected_plane_point_js(
        string $support,
        string $dir_1,
        string $r1,
        string $dir_2,
        string $r2
    ): string {
        $point = '__cavPlanePoint(' . $support . ', ' . $dir_1 . ', ' . $r1 . ', ' . $dir_2 . ', ' . $r2 . ')';

        return '[() => __cavProject(' . $point . ')[0], () => __cavProject(' . $point . ')[1]]';
    }

    function jsx_cavalier_range_js(string $value, float $fallback): string {
        $trimmed = trim($value);

        if ($trimmed === 'Infinity' || $trimmed === '+Infinity' || $trimmed === '-Infinity') {
            return jsx_cavalier_num($fallback);
        }

        return $value;
    }

    function jsx_cavalier_static_number(string $value): ?float {
        $trimmed = trim($value);

        return is_numeric($trimmed) ? (float)$trimmed : null;
    }

    function jsx_cavalier_tick_values(float $min, float $max, float $step): array {
        assert($step > 0, 'Tick distances must be positive');

        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        $values = [];
        $start = ceil(($min - 1e-10) / $step) * $step;

        for ($value = $start; $value <= $max + 1e-10; $value += $step) {
            $values[] = abs($value) < 1e-10 ? 0.0 : round($value, 10);
        }

        return $values;
    }

    function jsx_cavalier_tick_label(float $value): string {
        return jsx_cavalier_num($value);
    }

    function jsx_cavalier_num(float $value): string {
        if (abs($value) < 1e-10) {
            $value = 0.0;
        }

        $formatted = rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');

        return $formatted === '-0' ? '0' : $formatted;
    }
?>
