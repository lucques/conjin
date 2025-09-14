<?
    namespace Conjin\ModulesShared\JSXGraphHelpers;

    use Conjin\ModulesShared\GraphingTypes\Checkbox;
    use Conjin\ModulesShared\GraphingTypes\CommonElement;
    use Conjin\ModulesShared\GraphingTypes\Slider;
    use Conjin\ModulesShared\GraphingTypes\Stroke;


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
