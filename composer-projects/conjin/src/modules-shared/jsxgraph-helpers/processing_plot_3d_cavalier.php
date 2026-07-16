<?
    namespace Conjin\ModulesShared\JSXGraphHelpers;

    use Conjin\ModulesShared\GraphingTypes\ControlElement;
    use Conjin\ModulesShared\GraphingTypes\CustomElement;
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
        public function __construct(
            public float $x_min,
            public float $x_max,
            public float $y_min,
            public float $y_max,
            public float $z_min,
            public float $z_max,
            public int $width,
            public int $height,
            public ?float $board_x_min = null,
            public ?float $board_x_max = null,
            public ?float $board_y_min = null,
            public ?float $board_y_max = null,
            public ?float $x_ticks_distance = null,
            public ?float $y_ticks_distance = null,
            public ?float $z_ticks_distance = null,
            public float $ticks_distance = 1,
            public bool $show_grid = true,
            public bool $show_ticks = true,
            public bool $show_axes = true,
            public bool $show_navigation = true,
            public bool $enable_pan = true,
            public bool $enable_zoom = true,
            public bool $axes_extend_to_negative = false,
            public string $x_label = 'x',
            public string $y_label = 'y',
            public string $z_label = 'z',
            public float $x_axis_angle_degrees = -135,
            public float $x_axis_scale = 1.4142135623730951,
            public float $y_axis_scale = 2,
            public float $z_axis_scale = 2,
            public float $axis_tick_length = 0.12,
            public float $plane_fallback_range = 8,
            public float $plane_mesh_step = 1,
        ) {}
    }


    ////////////////////
    // Print and draw //
    ////////////////////

    function plot_3d_cavalier(
        Plot3DCavalierOptions $options,
        array $elements      // array of System3DIndepElement|ControlElement
    ) {
        $id = 'jsxgraph-plot-3d-cavalier-' . next_unique_id();
        [$board_x_min, $board_x_max, $board_y_min, $board_y_max] = jsx_cavalier_board_bounds($options);
        $x_ticks_distance = $options->x_ticks_distance ?? $options->ticks_distance;
        $y_ticks_distance = $options->y_ticks_distance ?? $options->ticks_distance;
        $z_ticks_distance = $options->z_ticks_distance ?? $options->ticks_distance;
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
            document.addEventListener("DOMContentLoaded", function() {
                var board = JXG.JSXGraph.initBoard('<?= $id ?>', {
                    showCopyright: false,
                    boundingbox: [<?= jsx_cavalier_num($board_x_min) ?>, <?= jsx_cavalier_num($board_y_max) ?>, <?= jsx_cavalier_num($board_x_max) ?>, <?= jsx_cavalier_num($board_y_min) ?>],
                    axis: false,
                    grid: <?= $options->show_grid ? "{ majorStep: [" . jsx_cavalier_num($y_ticks_distance) . ", " . jsx_cavalier_num($z_ticks_distance) . "] }" : 'false' ?>,
                    showNavigation: <?= $options->show_navigation ? 'true' : 'false' ?>,
                    keepaspectratio: true,
                    pan: { enabled: <?= $options->enable_pan ? 'true' : 'false' ?> },
                    zoom: { enabled: <?= $options->enable_zoom ? 'true' : 'false' ?> }
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

        if ($options->show_axes) {
            jsx_cavalier_print_axes(
                $options,
                $x_axis_start,
                $y_axis_start,
                $z_axis_start,
                $x_ticks_distance,
                $y_ticks_distance,
                $z_ticks_distance,
                $axis_label_margin
            );
        }

        foreach ($elements as $name => $e) {
            if ($e instanceof Line) {
                jsx_cavalier_print_line((string)$name, $e);
            }
        }

        foreach ($elements as $name => $e) {
            if ($e instanceof Point) {
                jsx_cavalier_print_point((string)$name, $e);
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

    function jsx_cavalier_print_axes(
        Plot3DCavalierOptions $options,
        float $x_axis_start,
        float $y_axis_start,
        float $z_axis_start,
        float $x_ticks_distance,
        float $y_ticks_distance,
        float $z_ticks_distance,
        float $axis_label_margin
    ): void {
        [$x0_u, $x0_v] = jsx_cavalier_project_php($x_axis_start, 0, 0, $options);
        [$x1_u, $x1_v] = jsx_cavalier_project_php($options->x_max, 0, 0, $options);
        [$y0_u, $y0_v] = jsx_cavalier_project_php(0, $y_axis_start, 0, $options);
        [$y1_u, $y1_v] = jsx_cavalier_project_php(0, $options->y_max, 0, $options);
        [$z0_u, $z0_v] = jsx_cavalier_project_php(0, 0, $z_axis_start, $options);
        [$z1_u, $z1_v] = jsx_cavalier_project_php(0, 0, $options->z_max, $options);
        [$x_label_u, $x_label_v] = jsx_cavalier_project_php($options->x_max + $axis_label_margin, 0, 0, $options);
        [$y_label_u, $y_label_v] = jsx_cavalier_project_php(0, $options->y_max + $axis_label_margin, 0, $options);
        [$z_label_u, $z_label_v] = jsx_cavalier_project_php(0, 0, $options->z_max + $axis_label_margin, $options);
?>
                const __cavAxisAttrs = {
                    fixed: true,
                    straightFirst: false,
                    straightLast: false,
                    lastArrow: true,
                    strokeColor: 'black',
                    highlightStrokeColor: 'black',
                    strokeWidth: 2
                };
                const __cavTickAttrs = {
                    fixed: true,
                    straightFirst: false,
                    straightLast: false,
                    strokeColor: 'black',
                    highlightStrokeColor: 'black',
                    strokeWidth: 1
                };
                board.create('line', [[<?= jsx_cavalier_num($x0_u) ?>, <?= jsx_cavalier_num($x0_v) ?>], [<?= jsx_cavalier_num($x1_u) ?>, <?= jsx_cavalier_num($x1_v) ?>]], __cavAxisAttrs);
                board.create('line', [[<?= jsx_cavalier_num($y0_u) ?>, <?= jsx_cavalier_num($y0_v) ?>], [<?= jsx_cavalier_num($y1_u) ?>, <?= jsx_cavalier_num($y1_v) ?>]], __cavAxisAttrs);
                board.create('line', [[<?= jsx_cavalier_num($z0_u) ?>, <?= jsx_cavalier_num($z0_v) ?>], [<?= jsx_cavalier_num($z1_u) ?>, <?= jsx_cavalier_num($z1_v) ?>]], __cavAxisAttrs);
                board.create('text', [<?= jsx_cavalier_num($x_label_u) ?>, <?= jsx_cavalier_num($x_label_v) ?>, <?= json_encode($options->x_label) ?>], { fixed: true, anchorX: 'middle', anchorY: 'middle', fontSize: 14, cssStyle: 'font-weight: 900;' });
                board.create('text', [<?= jsx_cavalier_num($y_label_u) ?>, <?= jsx_cavalier_num($y_label_v) ?>, <?= json_encode($options->y_label) ?>], { fixed: true, anchorX: 'middle', anchorY: 'middle', fontSize: 14, cssStyle: 'font-weight: 900;' });
                board.create('text', [<?= jsx_cavalier_num($z_label_u) ?>, <?= jsx_cavalier_num($z_label_v) ?>, <?= json_encode($options->z_label) ?>], { fixed: true, anchorX: 'middle', anchorY: 'middle', fontSize: 14, cssStyle: 'font-weight: 900;' });
<?
        if (!$options->show_ticks) {
            return;
        }

        jsx_cavalier_print_x_ticks($options, $x_axis_start, $options->x_max, $x_ticks_distance);
        jsx_cavalier_print_y_ticks($options, $y_axis_start, $options->y_max, $y_ticks_distance);
        jsx_cavalier_print_z_ticks($options, $z_axis_start, $options->z_max, $z_ticks_distance);
    }

    function jsx_cavalier_print_x_ticks(Plot3DCavalierOptions $options, float $min, float $max, float $step): void {
        $angle = deg2rad($options->x_axis_angle_degrees);
        $nx = -sin($angle);
        $ny = cos($angle);
        $half_tick = $options->axis_tick_length / 2;
        $label_offset = 2.5 * $options->axis_tick_length;

        foreach (jsx_cavalier_tick_values($min, $max, $step) as $tick) {
            if (abs($tick) < 1e-10) {
                continue;
            }

            [$u, $v] = jsx_cavalier_project_php($tick, 0, 0, $options);
?>
                board.create('line', [[<?= jsx_cavalier_num($u - $nx * $half_tick) ?>, <?= jsx_cavalier_num($v - $ny * $half_tick) ?>], [<?= jsx_cavalier_num($u + $nx * $half_tick) ?>, <?= jsx_cavalier_num($v + $ny * $half_tick) ?>]], __cavTickAttrs);
                board.create('text', [<?= jsx_cavalier_num($u + $nx * $label_offset) ?>, <?= jsx_cavalier_num($v + $ny * $label_offset) ?>, <?= json_encode(jsx_cavalier_tick_label($tick)) ?>], { fixed: true, anchorX: 'middle', anchorY: 'middle', fontSize: 12 });
<?
        }
    }

    function jsx_cavalier_print_y_ticks(Plot3DCavalierOptions $options, float $min, float $max, float $step): void {
        $half_tick = $options->axis_tick_length / 2;
        $label_offset = 2.5 * $options->axis_tick_length;

        foreach (jsx_cavalier_tick_values($min, $max, $step) as $tick) {
            if (abs($tick) < 1e-10) {
                continue;
            }

            [$u, $v] = jsx_cavalier_project_php(0, $tick, 0, $options);
?>
                board.create('line', [[<?= jsx_cavalier_num($u) ?>, <?= jsx_cavalier_num($v - $half_tick) ?>], [<?= jsx_cavalier_num($u) ?>, <?= jsx_cavalier_num($v + $half_tick) ?>]], __cavTickAttrs);
                board.create('text', [<?= jsx_cavalier_num($u) ?>, <?= jsx_cavalier_num($v - $label_offset) ?>, <?= json_encode(jsx_cavalier_tick_label($tick)) ?>], { fixed: true, anchorX: 'middle', anchorY: 'top', fontSize: 12 });
<?
        }
    }

    function jsx_cavalier_print_z_ticks(Plot3DCavalierOptions $options, float $min, float $max, float $step): void {
        $half_tick = $options->axis_tick_length / 2;
        $label_offset = 2.5 * $options->axis_tick_length;

        foreach (jsx_cavalier_tick_values($min, $max, $step) as $tick) {
            if (abs($tick) < 1e-10) {
                continue;
            }

            [$u, $v] = jsx_cavalier_project_php(0, 0, $tick, $options);
?>
                board.create('line', [[<?= jsx_cavalier_num($u - $half_tick) ?>, <?= jsx_cavalier_num($v) ?>], [<?= jsx_cavalier_num($u + $half_tick) ?>, <?= jsx_cavalier_num($v) ?>]], __cavTickAttrs);
                board.create('text', [<?= jsx_cavalier_num($u - $label_offset) ?>, <?= jsx_cavalier_num($v) ?>, <?= json_encode(jsx_cavalier_tick_label($tick)) ?>], { fixed: true, anchorX: 'right', anchorY: 'middle', fontSize: 12 });
<?
        }
    }

    function jsx_cavalier_print_point(string $name, Point $point): void {
?>
                const <?= $name ?> = board.create('point',
                    <?= jsx_cavalier_projected_vec_js($point->vec) ?>,
                    {
<?
        if ($point->label !== null) {
?>
                        label: { autoPosition: true, strokeColor: '<?= translate_color_to_hex($point->color) ?>', fontSize: <?= $point->label_font_size ?> },
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

    function jsx_cavalier_print_line(string $name, Line $line): void {
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
                        label: { strokeColor: '<?= translate_color_to_hex($line->color) ?>', fontSize: <?= $line->label_font_size ?>, position: '50% left', anchorX: 'middle', anchorY: 'middle' },
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
            $options->board_x_min ?? $u_min - $margin,
            $options->board_x_max ?? $u_max + $margin,
            $options->board_y_min ?? $v_min - $margin,
            $options->board_y_max ?? $v_max + $margin,
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
