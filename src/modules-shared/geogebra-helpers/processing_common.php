<?
    namespace Conjin\ModulesShared\GeoGebraHelpers;

    use Conjin\ModulesShared\GraphingTypes\Color;
    use Conjin\ModulesShared\GraphingTypes\Stroke;
    use function Conjin\ModulesShared\GraphingTypes\translate_color_to_hex;


    //////////////////
    // Applet config //
    //////////////////

    enum AppName: string {
        case Classic = 'classic';
        case Graphing = 'graphing';
        case Geometry = 'geometry';
        case ThreeD = '3d';
        case Suite = 'suite';
        case Evaluator = 'evaluator';
        case Scientific = 'scientific';
        case Notes = 'notes';
    }

    enum AlgebraInputPosition: string {
        case Algebra = 'algebra';
        case Top = 'top';
        case Bottom = 'bottom';
    }

    enum KeyboardType: string {
        case Scientific = 'scientific';
        case Normal = 'normal';
        case Notes = 'notes';
    }

    readonly class AppletOptions {
        public function __construct(
            public AppName|string $app_name = AppName::Graphing,
            public int $width = 800,
            public int $height = 600,
            public ?string $id = null,
            public ?string $material_id = null,
            public ?string $filename = null,
            public ?string $ggb_base64 = null,
            public ?string $language = null,
            public ?string $country = null,
            public bool $show_tool_bar = false,
            public bool $show_menu_bar = false,
            public bool $show_algebra_input = false,
            public ?bool $show_tool_bar_help = null,
            public bool $show_reset_icon = false,
            public bool $allow_style_bar = false,
            public bool $enable_right_click = true,
            public bool $enable_label_drags = true,
            public bool $enable_shift_drag_zoom = true,
            public ?bool $enable_file_features = null,
            public ?bool $enable_undo_redo = null,
            public ?bool $show_zoom_buttons = null,
            public ?bool $show_animation_button = null,
            public ?bool $show_fullscreen_button = null,
            public ?bool $show_suggestion_buttons = null,
            public ?bool $show_start_tooltip = null,
            public ?bool $play_button = null,
            public ?string $custom_tool_bar = null,
            public ?string $custom_toolbox = null,
            public ?string $perspective = null,
            public ?AlgebraInputPosition $algebra_input_position = null,
            public ?bool $prevent_focus = null,
            public ?bool $randomize = null,
            public ?int $random_seed = null,
            public ?float $scale = null,
            public ?bool $allow_upscale = null,
            public ?string $rounding = null,
            public ?bool $transparent_graphics = null,
            public ?bool $disable_javascript = null,
            public bool|string|null $show_keyboard_on_focus = null,
            public ?KeyboardType $keyboard_type = null,
            public ?bool $auto_height = null,
            public ?string $scale_container_class = null,
            public array $extra_params = [],
            public ?string $applet_on_load_js = null,
            public ?string $html5_codebase_url = null,
            public bool $html5_no_web_simple = true,
            public string $class = 'geogebra-applet',
            public string $style = '',
        ) {}

        public function to_params(string $id): array {
            $params = [
                'appName' => ggb_enum_or_string_value($this->app_name),
                'width' => $this->width,
                'height' => $this->height,
                'id' => $this->id ?? $id,
                'showToolBar' => $this->show_tool_bar,
                'showMenuBar' => $this->show_menu_bar,
                'showAlgebraInput' => $this->show_algebra_input,
                'showResetIcon' => $this->show_reset_icon,
                'allowStyleBar' => $this->allow_style_bar,
                'enableRightClick' => $this->enable_right_click,
                'enableLabelDrags' => $this->enable_label_drags,
                'enableShiftDragZoom' => $this->enable_shift_drag_zoom,
            ];

            ggb_put_param($params, 'material_id', $this->material_id);
            ggb_put_param($params, 'filename', $this->filename);
            ggb_put_param($params, 'ggbBase64', $this->ggb_base64);
            ggb_put_param($params, 'language', $this->language);
            ggb_put_param($params, 'country', $this->country);
            ggb_put_param($params, 'showToolBarHelp', $this->show_tool_bar_help);
            ggb_put_param($params, 'enableFileFeatures', $this->enable_file_features);
            ggb_put_param($params, 'enableUndoRedo', $this->enable_undo_redo);
            ggb_put_param($params, 'showZoomButtons', $this->show_zoom_buttons);
            ggb_put_param($params, 'showAnimationButton', $this->show_animation_button);
            ggb_put_param($params, 'showFullscreenButton', $this->show_fullscreen_button);
            ggb_put_param($params, 'showSuggestionButtons', $this->show_suggestion_buttons);
            ggb_put_param($params, 'showStartTooltip', $this->show_start_tooltip);
            ggb_put_param($params, 'playButton', $this->play_button);
            ggb_put_param($params, 'customToolBar', $this->custom_tool_bar);
            ggb_put_param($params, 'customToolbox', $this->custom_toolbox);
            ggb_put_param($params, 'perspective', $this->perspective);
            ggb_put_param($params, 'algebraInputPosition', $this->algebra_input_position?->value);
            ggb_put_param($params, 'preventFocus', $this->prevent_focus);
            ggb_put_param($params, 'randomize', $this->randomize);
            ggb_put_param($params, 'randomSeed', $this->random_seed);
            ggb_put_param($params, 'scale', $this->scale);
            ggb_put_param($params, 'allowUpscale', $this->allow_upscale);
            ggb_put_param($params, 'rounding', $this->rounding);
            ggb_put_param($params, 'transparentGraphics', $this->transparent_graphics);
            ggb_put_param($params, 'disableJavaScript', $this->disable_javascript);
            ggb_put_param($params, 'showKeyboardOnFocus', $this->show_keyboard_on_focus);
            ggb_put_param($params, 'keyboardType', $this->keyboard_type?->value);
            ggb_put_param($params, 'autoHeight', $this->auto_height);
            ggb_put_param($params, 'scaleContainerClass', $this->scale_container_class);

            return array_merge($params, $this->extra_params);
        }
    }

    readonly class AppletRef {
        public function __construct(
            public string $container_id,
            public string $applet_id,
            public string $applet_var_name,
            public string $api_var_name,
        ) {}
    }


    ///////////////////
    // API primitives //
    ///////////////////

    interface AppletAction {
        public function to_js(string $api_ref): string;
    }

    readonly class CustomAction implements AppletAction {
        public function __construct(
            public string $js_code  // e.g. `api.evalCommand('A = (1, 2)');`
        ) {}

        public function to_js(string $api_ref): string {
            return $this->js_code;
        }
    }

    readonly class EvalCommand implements AppletAction {
        public function __construct(public string $command) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.evalCommand(' . ggb_js_literal($this->command) . ');';
        }
    }

    readonly class EvalCommands implements AppletAction {
        public function __construct(public array $commands) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.evalCommand(' . ggb_js_literal(implode("\n", $this->commands)) . ');';
        }
    }

    readonly class EvalLaTeX implements AppletAction {
        public function __construct(public string $input) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.evalLaTex(' . ggb_js_literal($this->input) . ');';
        }
    }

    readonly class DeleteObject implements AppletAction {
        public function __construct(public string $object_name) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.deleteObject(' . ggb_js_literal($this->object_name) . ');';
        }
    }

    readonly class SetValue implements AppletAction {
        public function __construct(public string $object_name, public float $value) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.setValue(' . ggb_js_literal($this->object_name) . ', ' . ggb_js_literal($this->value) . ');';
        }
    }

    readonly class SetTextValue implements AppletAction {
        public function __construct(public string $object_name, public string $value) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.setTextValue(' . ggb_js_literal($this->object_name) . ', ' . ggb_js_literal($this->value) . ');';
        }
    }

    readonly class SetCoords implements AppletAction {
        public function __construct(
            public string $object_name,
            public float $x,
            public float $y,
            public ?float $z = null,
        ) {}

        public function to_js(string $api_ref): string {
            $args = [
                ggb_js_literal($this->object_name),
                ggb_js_literal($this->x),
                ggb_js_literal($this->y),
            ];
            if ($this->z !== null) {
                $args[] = ggb_js_literal($this->z);
            }

            return $api_ref . '.setCoords(' . implode(', ', $args) . ');';
        }
    }

    readonly class SetCaption implements AppletAction {
        public function __construct(public string $object_name, public string $caption) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.setCaption(' . ggb_js_literal($this->object_name) . ', ' . ggb_js_literal($this->caption) . ');';
        }
    }

    readonly class SetVisible implements AppletAction {
        public function __construct(public string $object_name, public bool $visible) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.setVisible(' . ggb_js_literal($this->object_name) . ', ' . ggb_js_literal($this->visible) . ');';
        }
    }

    readonly class SetLabelVisible implements AppletAction {
        public function __construct(public string $object_name, public bool $visible) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.setLabelVisible(' . ggb_js_literal($this->object_name) . ', ' . ggb_js_literal($this->visible) . ');';
        }
    }

    enum LabelStyle: int {
        case Name = 0;
        case NameValue = 1;
        case Value = 2;
        case Caption = 3;
    }

    readonly class SetLabelStyle implements AppletAction {
        public function __construct(public string $object_name, public LabelStyle|int $style) {}

        public function to_js(string $api_ref): string {
            $style = $this->style instanceof LabelStyle ? $this->style->value : $this->style;
            return $api_ref . '.setLabelStyle(' . ggb_js_literal($this->object_name) . ', ' . ggb_js_literal($style) . ');';
        }
    }

    readonly class SetColor implements AppletAction {
        public function __construct(public string $object_name, public Color|string $color) {}

        public function to_js(string $api_ref): string {
            [$r, $g, $b] = ggb_color_to_rgb($this->color);
            return $api_ref . '.setColor(' . ggb_js_literal($this->object_name) . ', ' . $r . ', ' . $g . ', ' . $b . ');';
        }
    }

    readonly class SetFilling implements AppletAction {
        public function __construct(public string $object_name, public float $filling) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.setFilling(' . ggb_js_literal($this->object_name) . ', ' . ggb_js_literal($this->filling) . ');';
        }
    }

    readonly class SetFixed implements AppletAction {
        public function __construct(
            public string $object_name,
            public bool $fixed = true,
            public bool $selection_allowed = false,
        ) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.setFixed(' . ggb_js_literal($this->object_name) . ', ' . ggb_js_literal($this->fixed) . ', ' . ggb_js_literal($this->selection_allowed) . ');';
        }
    }

    readonly class SetTrace implements AppletAction {
        public function __construct(public string $object_name, public bool $flag) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.setTrace(' . ggb_js_literal($this->object_name) . ', ' . ggb_js_literal($this->flag) . ');';
        }
    }

    readonly class SetLineStyle implements AppletAction {
        public function __construct(public string $object_name, public Stroke|int $style) {}

        public function to_js(string $api_ref): string {
            $style = ggb_stroke_to_line_style($this->style);
            return $api_ref . '.setLineStyle(' . ggb_js_literal($this->object_name) . ', ' . ggb_js_literal($style) . ');';
        }
    }

    readonly class SetLineThickness implements AppletAction {
        public function __construct(public string $object_name, public int $thickness) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.setLineThickness(' . ggb_js_literal($this->object_name) . ', ' . ggb_js_literal($this->thickness) . ');';
        }
    }

    enum PointStyle: int {
        case Default = -1;
        case FilledCircle = 0;
        case Cross = 1;
        case Circle = 2;
        case Plus = 3;
        case FilledDiamond = 4;
        case Diamond = 5;
        case TriangleNorth = 6;
        case TriangleSouth = 7;
        case TriangleEast = 8;
        case TriangleWest = 9;
    }

    readonly class SetPointStyle implements AppletAction {
        public function __construct(public string $object_name, public PointStyle|int $style) {}

        public function to_js(string $api_ref): string {
            $style = $this->style instanceof PointStyle ? $this->style->value : $this->style;
            return $api_ref . '.setPointStyle(' . ggb_js_literal($this->object_name) . ', ' . ggb_js_literal($style) . ');';
        }
    }

    readonly class SetPointSize implements AppletAction {
        public function __construct(public string $object_name, public int $size) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.setPointSize(' . ggb_js_literal($this->object_name) . ', ' . ggb_js_literal($this->size) . ');';
        }
    }

    readonly class SetAnimating implements AppletAction {
        public function __construct(public string $object_name, public bool $animate) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.setAnimating(' . ggb_js_literal($this->object_name) . ', ' . ggb_js_literal($this->animate) . ');';
        }
    }

    readonly class SetAnimationSpeed implements AppletAction {
        public function __construct(public string $object_name, public float $speed) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.setAnimationSpeed(' . ggb_js_literal($this->object_name) . ', ' . ggb_js_literal($this->speed) . ');';
        }
    }

    readonly class StartAnimation implements AppletAction {
        public function to_js(string $api_ref): string {
            return $api_ref . '.startAnimation();';
        }
    }

    readonly class StopAnimation implements AppletAction {
        public function to_js(string $api_ref): string {
            return $api_ref . '.stopAnimation();';
        }
    }

    readonly class SetCoordSystem implements AppletAction {
        public function __construct(
            public float $x_min,
            public float $x_max,
            public float $y_min,
            public float $y_max,
            public ?float $z_min = null,
            public ?float $z_max = null,
            public bool $y_vertical = true,
        ) {}

        public function to_js(string $api_ref): string {
            $args = [
                ggb_js_literal($this->x_min),
                ggb_js_literal($this->x_max),
                ggb_js_literal($this->y_min),
                ggb_js_literal($this->y_max),
            ];
            if ($this->z_min !== null && $this->z_max !== null) {
                $args[] = ggb_js_literal($this->z_min);
                $args[] = ggb_js_literal($this->z_max);
                $args[] = ggb_js_literal($this->y_vertical);
            }

            return $api_ref . '.setCoordSystem(' . implode(', ', $args) . ');';
        }
    }

    readonly class SetAxesVisible implements AppletAction {
        public function __construct(
            public bool $x_axis,
            public bool $y_axis,
            public ?int $view_number = null,
            public ?bool $z_axis = null,
        ) {}

        public function to_js(string $api_ref): string {
            if ($this->view_number === null) {
                return $api_ref . '.setAxesVisible(' . ggb_js_literal($this->x_axis) . ', ' . ggb_js_literal($this->y_axis) . ');';
            }

            return $api_ref . '.setAxesVisible(' . ggb_js_literal($this->view_number) . ', ' . ggb_js_literal($this->x_axis) . ', ' . ggb_js_literal($this->y_axis) . ', ' . ggb_js_literal($this->z_axis ?? false) . ');';
        }
    }

    readonly class SetGridVisible implements AppletAction {
        public function __construct(
            public bool $visible,
            public ?int $view_number = null,
        ) {}

        public function to_js(string $api_ref): string {
            if ($this->view_number === null) {
                return $api_ref . '.setGridVisible(' . ggb_js_literal($this->visible) . ');';
            }

            return $api_ref . '.setGridVisible(' . ggb_js_literal($this->view_number) . ', ' . ggb_js_literal($this->visible) . ');';
        }
    }

    readonly class SetAxisLabels implements AppletAction {
        public function __construct(
            public int $view_number,
            public string $x_axis,
            public string $y_axis,
            public string $z_axis = '',
        ) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.setAxisLabels(' . ggb_js_literal($this->view_number) . ', ' . ggb_js_literal($this->x_axis) . ', ' . ggb_js_literal($this->y_axis) . ', ' . ggb_js_literal($this->z_axis) . ');';
        }
    }

    readonly class SetAxisSteps implements AppletAction {
        public function __construct(
            public int $view_number,
            public float $x_axis,
            public float $y_axis,
            public float $z_axis = 1,
        ) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.setAxisSteps(' . ggb_js_literal($this->view_number) . ', ' . ggb_js_literal($this->x_axis) . ', ' . ggb_js_literal($this->y_axis) . ', ' . ggb_js_literal($this->z_axis) . ');';
        }
    }

    readonly class SetAxisUnits implements AppletAction {
        public function __construct(
            public int $view_number,
            public string $x_axis,
            public string $y_axis,
            public string $z_axis = '',
        ) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.setAxisUnits(' . ggb_js_literal($this->view_number) . ', ' . ggb_js_literal($this->x_axis) . ', ' . ggb_js_literal($this->y_axis) . ', ' . ggb_js_literal($this->z_axis) . ');';
        }
    }

    readonly class SetPerspective implements AppletAction {
        public function __construct(public string $perspective) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.setPerspective(' . ggb_js_literal($this->perspective) . ');';
        }
    }

    readonly class SetGraphicsOptions implements AppletAction {
        public function __construct(public int $view_id, public array $options) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.setGraphicsOptions(' . ggb_js_literal($this->view_id) . ', ' . ggb_js_literal($this->options) . ');';
        }
    }

    readonly class SetAlgebraOptions implements AppletAction {
        public function __construct(public array $options) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.setAlgebraOptions(' . ggb_js_literal($this->options) . ');';
        }
    }

    readonly class RegisterUpdateListener implements AppletAction {
        public function __construct(public string $function_js) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.registerUpdateListener(' . $this->function_js . ');';
        }
    }

    readonly class RegisterObjectUpdateListener implements AppletAction {
        public function __construct(public string $object_name, public string $function_js) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.registerObjectUpdateListener(' . ggb_js_literal($this->object_name) . ', ' . $this->function_js . ');';
        }
    }

    readonly class RegisterClickListener implements AppletAction {
        public function __construct(public string $function_js) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.registerClickListener(' . $this->function_js . ');';
        }
    }

    readonly class RegisterObjectClickListener implements AppletAction {
        public function __construct(public string $object_name, public string $function_js) {}

        public function to_js(string $api_ref): string {
            return $api_ref . '.registerObjectClickListener(' . ggb_js_literal($this->object_name) . ', ' . $this->function_js . ');';
        }
    }


    ////////////////////
    // Print and draw //
    ////////////////////

    function ggb_print_applet(
        AppletOptions $options = new AppletOptions(),
        array $actions = []
    ): AppletRef {
        $container_id = 'geogebra-applet-container-' . \next_unique_id();
        $applet_id = $options->id ?? 'geogebraApplet' . \next_unique_id();
        $applet_var_name = $applet_id . 'Applet';
        $api_var_name = $applet_id;
        $params = $options->to_params($applet_id);
        $codebase_url = $options->html5_codebase_url ?? ggb_default_html5_codebase_url();
        $style = 'width: ' . $options->width . 'px; height: ' . $options->height . 'px;' . ($options->style !== '' ? ' ' . $options->style : '');
?>
        <div id="<?= ggb_html_attr($container_id) ?>" class="<?= ggb_html_attr($options->class) ?>" style="<?= ggb_html_attr($style) ?>"></div>
        <script>
            (function() {
                const inject = function() {
                    const params = <?= ggb_js_literal($params) ?>;
                    params.appletOnLoad = function(api) {
                        window[<?= ggb_js_literal($api_var_name) ?>] = api;
<?= ggb_actions_to_js($actions, 'api', '                            ') ?>
<? if ($options->applet_on_load_js !== null) { ?>
                        <?= $options->applet_on_load_js . "\n" ?>
<? } ?>
                    };

                    const applet = new GGBApplet(params, <?= ggb_js_literal($options->html5_no_web_simple) ?>);
                    applet.setHTML5Codebase(<?= ggb_js_literal($codebase_url) ?>);
                    window[<?= ggb_js_literal($applet_var_name) ?>] = applet;
                    applet.inject(<?= ggb_js_literal($container_id) ?>);
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', inject);
                }
                else {
                    inject();
                }
            })();
        </script>
<?
        return new AppletRef($container_id, $applet_id, $applet_var_name, $api_var_name);
    }


    /////////////
    // Helpers //
    /////////////

    function ggb_default_html5_codebase_url(): string {
        $geogebra = new \ModuleLocation('geogebra');
        return $geogebra->get_url() . '/res/HTML5/5.0/web3d/';
    }

    function ggb_actions_to_js(array $actions, string $api_ref, string $indent = ''): string {
        $lines = [];

        foreach ($actions as $action) {
            assert($action instanceof AppletAction, 'GeoGebra applet actions must implement AppletAction');
            $js = trim($action->to_js($api_ref));

            foreach (explode("\n", $js) as $line) {
                $lines[] = $indent . $line;
            }
        }

        return implode("\n", $lines) . (count($lines) > 0 ? "\n" : '');
    }

    function ggb_put_param(array &$params, string $name, mixed $value): void {
        if ($value !== null) {
            $params[$name] = $value;
        }
    }

    function ggb_enum_or_string_value(\BackedEnum|string $value): string|int {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    function ggb_js_literal(mixed $value): string {
        return json_encode(
            $value,
            JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
                | JSON_THROW_ON_ERROR
        );
    }

    function ggb_html_attr(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    function ggb_color_to_hex(Color|string $color): string {
        if (is_string($color)) {
            return $color;
        }

        return translate_color_to_hex($color);
    }

    function ggb_color_to_rgb(Color|string $color): array {
        $hex = ltrim(ggb_color_to_hex($color), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        assert(strlen($hex) === 6, 'GeoGebra colors must use #RGB or #RRGGBB hex notation');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    function ggb_stroke_to_line_style(Stroke|int $stroke): int {
        if (is_int($stroke)) {
            return $stroke;
        }

        return match($stroke) {
            Stroke::Solid  => 0,
            Stroke::Dotted => 1,
            Stroke::Dashed => 2,
        };
    }
?>
