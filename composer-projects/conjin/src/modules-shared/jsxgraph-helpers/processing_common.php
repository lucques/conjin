<?
    namespace Conjin\ModulesShared\JSXGraphHelpers;

    use Conjin\ModulesShared\GraphingTypes\Checkbox;
    use Conjin\ModulesShared\GraphingTypes\CommonElement;
    use Conjin\ModulesShared\GraphingTypes\FontSize;
    use Conjin\ModulesShared\GraphingTypes\Slider;
    use Conjin\ModulesShared\GraphingTypes\Stroke;


    //////////////////////
    // JSXGraph defaults //
    //////////////////////

    const POINT_LABEL_AUTO_POSITION_MIN_DISTANCE = 8;
    const POINT_LABEL_AUTO_POSITION_MAX_DISTANCE = 14;

    function jsx_validate_point_label_auto_position_distances(
        float $min_distance,
        float $max_distance,
        string $plot_name,
    ): void {
        if (!is_finite($min_distance) || $min_distance <= 0) {
            throw new \InvalidArgumentException("$plot_name point-label auto-position minimum distance must be finite and greater than zero");
        }
        if (!is_finite($max_distance) || $max_distance <= $min_distance) {
            throw new \InvalidArgumentException("$plot_name point-label auto-position maximum distance must be finite and greater than its minimum distance");
        }
    }


    //////////////////////////
    // Print mode resolution //
    //////////////////////////

    // Purpose: Make lines thicker, more contrast etc.
    enum PrintMode {
        case Disabled;
        case Enabled;
        case Auto; // Automatically determine whether print mode module is activated
    }

    function jsx_resolve_print_mode(PrintMode $print_mode): bool {
        return match($print_mode) {
            PrintMode::Disabled => false,
            PrintMode::Enabled  => true,
            PrintMode::Auto     => $GLOBALS['jsxgraph_helpers_print_mode_module_active'] ?? false,
        };
    }

    function jsx_resolve_axis_thickness(float $axis_thickness, bool $print_mode): float {
        return $print_mode ? 2 * $axis_thickness : $axis_thickness;
    }


    /////////////
    // Helpers //
    /////////////

    function translate_stroke_to_dash(Stroke $stroke): int {
        return match($stroke) {
            Stroke::Solid  => 0,
            Stroke::Dotted => 1,
            Stroke::Dashed => 2,
        };
    }

    function translate_font_size_to_pixels(FontSize $font_size, int $font_size_adjust = 0): int {
        $font_size_pixels = match($font_size) {
            FontSize::XS => 10,
            FontSize::SM => 12,
            FontSize::MD => 14,
            FontSize::LG => 16,
            FontSize::XL => 18,
        };

        return $font_size_pixels + 2 * $font_size_adjust;
    }

    function print_slider(Slider $s, int $decimal_places = 2, bool $enabled = true, ?string $hook_oninput_fun_js = null, bool $reset_button = false) {
        $handler_name = 'onInput_' . next_unique_id();
?>
    <script>
        function <?= $handler_name ?>(event) {
            document.getElementById('<?= $s->dom_id ?>-val').innerText = '$' + mj_num(document.getElementById('<?= $s->dom_id ?>').value, <?= $decimal_places ?>) + '$';
<?
        if ($hook_oninput_fun_js !== null) {
?>
            <?= $hook_oninput_fun_js ?>(event);
<?
        }
?>
            if (typeof MathJax.typeset !== 'undefined') {
                MathJax.typeset();
            }
        }
        function <?= $s->provide_getter_fun_js ?>() {
            return Number(document.getElementById('<?= $s->dom_id ?>').value);
        }
    </script>
    <div class="d-flex gap-3">
        <input type="range" class="form-range" id="<?= $s->dom_id ?>" min="<?= $s->val_min ?>" max="<?= $s->val_max ?>" step="<?= $s->val_step ?>" value="<?= $s->val_init ?>" style="width: 200px;" oninput="<?= $handler_name ?>()"<?= $enabled ? '' : ' disabled' ?>>
<?
    if ($reset_button) {
?>
        <input type="button" value="&#8634;" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('<?= $s->dom_id ?>').value = <?= $s->val_init ?>; document.getElementById('<?= $s->dom_id ?>').dispatchEvent(new Event('input', { bubbles: true }));"<?= $enabled ? '' : ' disabled' ?>>
<?
    }
?>
        <label for="<?= $s->dom_id ?>" class="form-label">$ <?= $s->label_tex ?> =~$<span id="<?= $s->dom_id ?>-val">$<?= $s->val_init ?>$</span></label>
    </div>
    <script>
        // TODO automate this
        document.getElementById('<?= $s->dom_id ?>').value = <?= $s->val_init ?>;

        document.addEventListener('DOMContentLoaded', (event) => {
            document.getElementById('<?= $s->dom_id ?>').dispatchEvent(new Event("input", { bubbles: true }));
        });
    </script>
<?
    }

    function print_checkbox(Checkbox $c, ?string $hook_onchange_fun_js = null) {
        $handler_name = 'onChange_' . next_unique_id();
?>
    <script>
        function <?= $handler_name ?>(event) {
<?
        if ($hook_onchange_fun_js !== null) {
?>
            <?= $hook_onchange_fun_js ?>(event);
<?
        }
?>
        }
        function <?= $c->provide_getter_fun_js ?>() {
            return document.getElementById('<?= $c->dom_id ?>').checked;
        }
    </script>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" value="" id="<?= $c->dom_id ?>"<?= $c->checked_init ? ' checked' : '' ?> onchange="<?= $handler_name ?>()">
        <label class="form-check-label" for="<?= $c->dom_id ?>">
            <?= $c->label_tex ?>
        </label>
    </div>
<?
    }

    function print_board_control_update_listener(CommonElement $e, string $name): bool {
        if ($e instanceof Slider) {

            ////////////
            // Slider //
            ////////////

            // There is some bug in the jsxgraph library. Only two calls of board.update() reliably updates the board.
?>
                document.getElementById('<?= $e->dom_id ?>').addEventListener('input', function(event) {
                    board.update();
                    board.update();
                });
<?
            return true;
        }
        elseif ($e instanceof Checkbox) {

            //////////////
            // Checkbox //
            //////////////
?>
                document.getElementById('<?= $e->dom_id ?>').addEventListener('change', function(event) {
                    board.update();
                });
                function get_<?= $name ?>() {
                    return document.getElementById('<?= $e->dom_id ?>').checked;
                }
<?
            return true;
        }

        return false;
    }

?>
