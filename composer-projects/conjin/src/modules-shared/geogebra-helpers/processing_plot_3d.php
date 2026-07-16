<?
    namespace Conjin\ModulesShared\GeoGebraHelpers;

    use Conjin\ModulesShared\GraphingTypes\Checkbox;
    use Conjin\ModulesShared\GraphingTypes\Color;
    use Conjin\ModulesShared\GraphingTypes\CustomElement;
    use Conjin\ModulesShared\GraphingTypes\Opacity;
    use Conjin\ModulesShared\GraphingTypes\Slider;
    use Conjin\ModulesShared\GraphingTypes\Stroke;
    use Conjin\ModulesShared\GraphingTypes\System3DDepElement;
    use Conjin\ModulesShared\GraphingTypes\System3DDep\Line;
    use Conjin\ModulesShared\GraphingTypes\System3DDep\Plane;
    use Conjin\ModulesShared\GraphingTypes\System3DDep\Point;
    use Conjin\ModulesShared\GraphingTypes\System3DDep\Polygon;
    use Conjin\ModulesShared\GraphingTypes\System3DDep\Vec;
    use function Conjin\ModulesShared\GraphingTypes\translate_opacity_to_float;


    const GGB_VIEW_3D = -1;
    const GGB_VIEW_3D_DEFAULT_DIRECTION_COMMAND = 'SetViewDirection(Vector((1; 235°; -30°)), false)';


    /////////////////////
    // Plot 3D options //
    /////////////////////

    readonly class Plot3DOptions {
        public function __construct(
            public float $x_min,
            public float $x_max,
            public float $y_min,
            public float $y_max,
            public float $z_min,
            public float $z_max,
            public int $width,
            public int $height,
            public AppName|string $app_name = AppName::ThreeD,
            public bool $show_grid = true,
            public bool $show_axes = true,
            public string $x_label = 'x',
            public string $y_label = 'y',
            public string $z_label = 'z',
            public ?float $x_axis_step = null,
            public ?float $y_axis_step = null,
            public ?float $z_axis_step = null,
            public bool $show_tool_bar = false,
            public bool $show_menu_bar = false,
            public bool $show_algebra_input = false,
            public bool $enable_right_click = false,
            public bool $enable_shift_drag_zoom = true,
            public bool $show_zoom_buttons = true,
            public bool $show_fullscreen_button = true,
            public ?string $perspective = null,
            public array $extra_params = [],
            public string $class = 'geogebra-applet',
            public string $style = '',
        ) {}
    }


    ////////////////////
    // Print and draw //
    ////////////////////

    function plot_3d(
        Plot3DOptions $options,
        array $elements,       // array of View3DElement|ControlElement
        array $actions = []
    ): AppletRef {
        $setup_actions = [
            new SetCoordSystem(
                $options->x_min,
                $options->x_max,
                $options->y_min,
                $options->y_max,
                $options->z_min,
                $options->z_max,
                false,
            ),
            new SetAxesVisible($options->show_axes, $options->show_axes, GGB_VIEW_3D, $options->show_axes),
            new SetGridVisible($options->show_grid, GGB_VIEW_3D),
            new SetAxisLabels(GGB_VIEW_3D, $options->x_label, $options->y_label, $options->z_label),
        ];

        if ($options->x_axis_step !== null && $options->y_axis_step !== null && $options->z_axis_step !== null) {
            $setup_actions[] = new SetAxisSteps(
                GGB_VIEW_3D,
                $options->x_axis_step,
                $options->y_axis_step,
                $options->z_axis_step
            );
        }

        $setup_actions[] = new GeoGebraView3DElements($elements);
        $setup_actions[] = new EvalCommand(GGB_VIEW_3D_DEFAULT_DIRECTION_COMMAND);
        $setup_actions = array_merge($setup_actions, $actions);

        return ggb_print_applet(
            new AppletOptions(
                app_name: $options->app_name,
                width: $options->width,
                height: $options->height,
                show_tool_bar: $options->show_tool_bar,
                show_menu_bar: $options->show_menu_bar,
                show_algebra_input: $options->show_algebra_input,
                enable_right_click: $options->enable_right_click,
                enable_label_drags: false,
                enable_shift_drag_zoom: $options->enable_shift_drag_zoom,
                show_zoom_buttons: $options->show_zoom_buttons,
                show_fullscreen_button: $options->show_fullscreen_button,
                perspective: $options->perspective,
                extra_params: $options->extra_params,
                class: $options->class,
                style: $options->style,
            ),
            $setup_actions
        );
    }


    /////////////////////
    // Element actions //
    /////////////////////

    readonly class GeoGebraView3DElements implements AppletAction {
        public function __construct(public array $elements) {}

        public function to_js(string $api_ref): string {
            $js = $this->helper_js($api_ref);
            $update_functions = [];
            $control_ids = [];
            $used_names = [];

            $index = 0;
            foreach ($this->elements as $key => $element) {
                if ($element instanceof Slider || $element instanceof Checkbox) {
                    $control_ids[] = $element->dom_id;
                    continue;
                }

                if ($element instanceof CustomElement) {
                    $js .= trim($element->js_code) . "\n";
                    continue;
                }

                assert(
                    $element instanceof System3DDepElement,
                    'GeoGebra 3D plot elements must implement View3DElement or ControlElement'
                );

                $name = $this->object_name($key, $element, $index);
                assert(!in_array($name, $used_names, true), 'GeoGebra 3D plot element names must be unique');
                $used_names[] = $name;

                $update_function = '__ggbUpdate' . \next_unique_id();
                $update_functions[] = $update_function;

                if ($element instanceof Point) {
                    $js .= $this->point_js($api_ref, $update_function, $name, $element);
                }
                elseif ($element instanceof Line) {
                    $js .= $this->line_js($api_ref, $update_function, $name, $element);
                }
                elseif ($element instanceof Plane) {
                    $js .= $this->plane_js($api_ref, $update_function, $name, $element);
                }
                elseif ($element instanceof Polygon) {
                    $js .= $this->polygon_js($api_ref, $update_function, $name, $element);
                }
                else {
                    assert(false, 'Unsupported GeoGebra 3D plot element type');
                }

                $index++;
            }

            $update_all_function = '__ggbUpdateAll' . \next_unique_id();
            $js .= 'const ' . $update_all_function . " = function() {\n";
            foreach ($update_functions as $update_function) {
                $js .= '    ' . $update_function . "();\n";
            }
            $js .= "};\n";
            $js .= $update_all_function . "();\n";

            if (count($control_ids) > 0) {
                $js .= ggb_js_literal(array_values(array_unique($control_ids))) . ".forEach(function(controlId) {\n";
                $js .= "    const control = document.getElementById(controlId);\n";
                $js .= "    if (control !== null) {\n";
                $js .= '        control.addEventListener(\'input\', ' . $update_all_function . ");\n";
                $js .= '        control.addEventListener(\'change\', ' . $update_all_function . ");\n";
                $js .= "    }\n";
                $js .= "});\n";
            }

            return $js;
        }

        private function helper_js(string $api_ref): string {
            return <<<JS
const __ggbApi = {$api_ref};
const __ggbValue = function(value) {
    return typeof value === 'function' ? value() : value;
};
const __ggbNumber = function(value) {
    const resolved = __ggbValue(value);
    return typeof resolved === 'number' ? resolved : Number(resolved);
};
const __ggbBool = function(value) {
    return Boolean(__ggbValue(value));
};
const __ggbCoord = function(value) {
    const number = __ggbNumber(value);
    if (!Number.isFinite(number)) {
        throw new Error('GeoGebra coordinates must be finite numbers.');
    }
    return String(number);
};
const __ggbPointExpr = function(vec) {
    return '(' + __ggbCoord(vec[0]) + ', ' + __ggbCoord(vec[1]) + ', ' + __ggbCoord(vec[2]) + ')';
};
const __ggbPointCommand = function(name, vec) {
    return name + ' = ' + __ggbPointExpr(vec);
};
const __ggbLinearCombination = function(support, dir1, factor1, dir2, factor2) {
    return [
        support[0] + factor1 * dir1[0] + factor2 * dir2[0],
        support[1] + factor1 * dir1[1] + factor2 * dir2[1],
        support[2] + factor1 * dir1[2] + factor2 * dir2[2]
    ];
};
const __ggbExists = function(name) {
    if (typeof __ggbApi.exists === 'function') {
        return __ggbApi.exists(name);
    }
    try {
        const objectType = __ggbApi.getObjectType(name);
        return typeof objectType === 'string' && objectType !== '';
    }
    catch (error) {
        return false;
    }
};
const __ggbEnsurePoint = function(name, vec) {
    if (__ggbExists(name)) {
        __ggbApi.setCoords(name, __ggbNumber(vec[0]), __ggbNumber(vec[1]), __ggbNumber(vec[2]));
    }
    else {
        __ggbApi.evalCommand(__ggbPointCommand(name, vec));
    }
};
const __ggbEnsureCommand = function(name, command) {
    if (!__ggbExists(name)) {
        __ggbApi.evalCommand(command);
    }
};

JS;
        }

        private function point_js(string $api_ref, string $update_function, string $name, Point $point): string {
            $name_js = ggb_js_literal($name);
            $vec_js = $this->vec_expr_js($point->vec);
            $style_js = $this->point_style_js($api_ref, $name, $point);
            $visible_js = $point->visible_fun_js;

            return <<<JS
const {$update_function} = function() {
    __ggbEnsurePoint({$name_js}, {$vec_js});
{$style_js}
    {$api_ref}.setVisible({$name_js}, __ggbBool({$visible_js}));
};

JS;
        }

        private function line_js(string $api_ref, string $update_function, string $name, Line $line): string {
            assert(
                is_string($line->point_a) && is_string($line->point_b),
                'GeoGebra 3D line endpoints must be point object names'
            );

            $name_js = ggb_js_literal($name);
            $point_a_name = ggb_assert_geogebra_object_name($line->point_a);
            $point_b_name = ggb_assert_geogebra_object_name($line->point_b);
            $command = $this->line_command($name, $point_a_name, $point_b_name, $line);
            $command_js = ggb_js_literal($command);
            $style_js = $this->line_style_js($api_ref, $name, $line);
            $visible_js = $line->visible_fun_js;

            return <<<JS
const {$update_function} = function() {
    __ggbEnsureCommand({$name_js}, {$command_js});
{$style_js}
    {$api_ref}.setVisible({$name_js}, __ggbBool({$visible_js}));
};

JS;
        }

        private function plane_js(string $api_ref, string $update_function, string $name, Plane $plane): string {
            $name_js = ggb_js_literal($name);
            $style_js = $this->surface_style_js(
                $api_ref,
                $name,
                $plane->color,
                $plane->opacity,
                $plane->stroke,
                $plane->thickness
            );
            $visible_js = $plane->visible_fun_js;

            if ($this->plane_has_finite_ranges($plane)) {
                return $this->finite_plane_js($api_ref, $update_function, $name, $plane, $style_js, $visible_js);
            }

            $support_js = $this->vec_expr_js($plane->vec_support);
            $dir_1_js = $this->vec_expr_js($plane->vec_dir_1);
            $dir_2_js = $this->vec_expr_js($plane->vec_dir_2);
            $command_prefix_js = ggb_js_literal($name . ' = Plane(');

            return <<<JS
const {$update_function} = function() {
    const support = {$support_js};
    const dir1 = {$dir_1_js};
    const dir2 = {$dir_2_js};
    const pointQ = [support[0] + dir1[0], support[1] + dir1[1], support[2] + dir1[2]];
    const pointR = [support[0] + dir2[0], support[1] + dir2[1], support[2] + dir2[2]];
    const command = {$command_prefix_js}
        + __ggbPointExpr(support) + ', '
        + __ggbPointExpr(pointQ) + ', '
        + __ggbPointExpr(pointR) + ')';
    {$api_ref}.evalCommand(command);
{$style_js}
    {$api_ref}.setVisible({$name_js}, __ggbBool({$visible_js}));
};

JS;
        }

        private function finite_plane_js(
            string $api_ref,
            string $update_function,
            string $name,
            Plane $plane,
            string $style_js,
            string $visible_js
        ): string {
            $name_js = ggb_js_literal($name);
            $support_js = $this->vec_expr_js($plane->vec_support);
            $dir_1_js = $this->vec_expr_js($plane->vec_dir_1);
            $dir_2_js = $this->vec_expr_js($plane->vec_dir_2);
            $command_prefix_js = ggb_js_literal($name . ' = Polygon(');

            return <<<JS
const {$update_function} = function() {
    const support = {$support_js};
    const dir1 = {$dir_1_js};
    const dir2 = {$dir_2_js};
    const r1Min = __ggbNumber({$plane->range_1_min_fun_js});
    const r1Max = __ggbNumber({$plane->range_1_max_fun_js});
    const r2Min = __ggbNumber({$plane->range_2_min_fun_js});
    const r2Max = __ggbNumber({$plane->range_2_max_fun_js});
    const corners = [
        __ggbLinearCombination(support, dir1, r1Min, dir2, r2Min),
        __ggbLinearCombination(support, dir1, r1Max, dir2, r2Min),
        __ggbLinearCombination(support, dir1, r1Max, dir2, r2Max),
        __ggbLinearCombination(support, dir1, r1Min, dir2, r2Max)
    ];
    const command = {$command_prefix_js} + corners.map(__ggbPointExpr).join(', ') + ')';
    {$api_ref}.evalCommand(command);
{$style_js}
    {$api_ref}.setVisible({$name_js}, __ggbBool({$visible_js}));
};

JS;
        }

        private function polygon_js(string $api_ref, string $update_function, string $name, Polygon $polygon): string {
            assert(count($polygon->vertices) >= 3, 'GeoGebra 3D polygons need at least three vertices');

            $name_js = ggb_js_literal($name);
            $point_names = [];

            foreach ($polygon->vertices as $vertex) {
                assert(is_string($vertex), 'GeoGebra 3D polygon vertices must be point object names');
                $point_names[] = ggb_assert_geogebra_object_name($vertex);
            }

            $command_js = ggb_js_literal($name . ' = Polygon(' . implode(', ', $point_names) . ')');
            $style_js = $this->surface_style_js(
                $api_ref,
                $name,
                $polygon->color,
                $polygon->opacity,
                $polygon->stroke,
                $polygon->thickness
            );
            $visible_js = $polygon->visible_fun_js;

            return <<<JS
const {$update_function} = function() {
    __ggbEnsureCommand({$name_js}, {$command_js});
{$style_js}
    {$api_ref}.setVisible({$name_js}, __ggbBool({$visible_js}));
};

JS;
        }

        private function object_name(string|int $key, System3DDepElement $element, int $index): string {
            if (is_string($key) && $key !== '') {
                return ggb_assert_geogebra_object_name($key);
            }

            $prefix = match (true) {
                $element instanceof Point => 'ggbPoint',
                $element instanceof Line => 'ggbLine',
                $element instanceof Plane => 'ggbPlane',
                $element instanceof Polygon => 'ggbPolygon',
                default => 'ggbObject',
            };

            return $prefix . $index;
        }

        private function vec_expr_js(Vec $vec): string {
            return '[__ggbNumber(' . $vec->x_fun_js . '), __ggbNumber(' . $vec->y_fun_js . '), __ggbNumber(' . $vec->z_fun_js . ')]';
        }

        private function line_command(string $name, string $point_a_name, string $point_b_name, Line $line): string {
            if ($line->extend_before_point_a && $line->extend_after_point_b) {
                return $name . ' = Line(' . $point_a_name . ', ' . $point_b_name . ')';
            }

            if (!$line->extend_before_point_a && !$line->extend_after_point_b) {
                return $name . ' = Segment(' . $point_a_name . ', ' . $point_b_name . ')';
            }

            if (!$line->extend_before_point_a && $line->extend_after_point_b) {
                return $name . ' = Ray(' . $point_a_name . ', ' . $point_b_name . ')';
            }

            return $name . ' = Ray(' . $point_b_name . ', ' . $point_a_name . ')';
        }

        private function point_style_js(string $api_ref, string $name, Point $point): string {
            $name_js = ggb_js_literal($name);
            $label_js = $this->label_js($api_ref, $name, $point->label);
            [$r, $g, $b] = ggb_color_to_rgb($point->color);
            $size = max(1, (int)round($point->size));

            return <<<JS
    {$api_ref}.setColor({$name_js}, {$r}, {$g}, {$b});
    {$api_ref}.setPointSize({$name_js}, {$size});
    {$api_ref}.setPointStyle({$name_js}, 0);
    {$api_ref}.setFixed({$name_js}, true, false);
{$label_js}
JS;
        }

        private function line_style_js(string $api_ref, string $name, Line $line): string {
            $name_js = ggb_js_literal($name);
            $label_js = $this->label_js($api_ref, $name, $line->label);
            [$r, $g, $b] = ggb_color_to_rgb($line->color);
            $stroke = ggb_stroke_to_line_style($line->stroke);
            $thickness = max(1, (int)round($line->thickness));

            return <<<JS
    {$api_ref}.setColor({$name_js}, {$r}, {$g}, {$b});
    {$api_ref}.setLineStyle({$name_js}, {$stroke});
    {$api_ref}.setLineThickness({$name_js}, {$thickness});
    {$api_ref}.setFixed({$name_js}, true, false);
{$label_js}
JS;
        }

        private function surface_style_js(
            string $api_ref,
            string $name,
            Color $color,
            Opacity $opacity,
            Stroke $stroke,
            float $thickness
        ): string {
            $name_js = ggb_js_literal($name);
            [$r, $g, $b] = ggb_color_to_rgb($color);
            $stroke_value = ggb_stroke_to_line_style($stroke);
            $thickness_value = max(1, (int)round($thickness));
            $filling = translate_opacity_to_float($opacity);

            return <<<JS
    {$api_ref}.setColor({$name_js}, {$r}, {$g}, {$b});
    {$api_ref}.setFilling({$name_js}, {$filling});
    {$api_ref}.setLineStyle({$name_js}, {$stroke_value});
    {$api_ref}.setLineThickness({$name_js}, {$thickness_value});
    {$api_ref}.setLabelVisible({$name_js}, false);
    {$api_ref}.setFixed({$name_js}, true, false);
JS;
        }

        private function label_js(string $api_ref, string $name, ?string $label): string {
            $name_js = ggb_js_literal($name);

            if ($label === null) {
                return '    ' . $api_ref . '.setLabelVisible(' . $name_js . ", false);\n";
            }

            return
                '    ' . $api_ref . '.setCaption(' . $name_js . ', ' . ggb_js_literal($label) . ");\n" .
                '    ' . $api_ref . '.setLabelStyle(' . $name_js . ", 3);\n" .
                '    ' . $api_ref . '.setLabelVisible(' . $name_js . ", true);\n";
        }

        private function plane_has_finite_ranges(Plane $plane): bool {
            return $plane->range_1_min_fun_js !== '-Infinity'
                && $plane->range_1_max_fun_js !== 'Infinity'
                && $plane->range_2_min_fun_js !== '-Infinity'
                && $plane->range_2_max_fun_js !== 'Infinity';
        }
    }


    /////////////
    // Helpers //
    /////////////

    function ggb_assert_geogebra_object_name(string $name): string {
        assert(
            preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $name) === 1,
            'GeoGebra object names must start with a letter and contain only letters, digits, and underscores'
        );

        return $name;
    }
?>
