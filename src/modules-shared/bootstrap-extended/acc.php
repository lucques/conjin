<?
    $init_processing_target = function(Module $module, Target $target) {
        $GLOBALS['acc_stack']           = [];
        $GLOBALS['acc_next_id_numeric'] = 0;

        // Support `sync-dims` module, only heights need to be resynced
        if ($target->has_activated_module('sync-dims')) {
            doc_extensions_add_js_after_dom_setup("document.querySelectorAll('.collapse').forEach(e => { e.addEventListener('shown.bs.collapse', syncHeights); });");
        }
    };
    

    //////////////////////////////////////////
    // Internal datatype for stack elements //
    //////////////////////////////////////////

    // Usually the id's will be generated automatically, resulting in `0-1-0`
    // etc. These id's will persist (e.g. for child elements) but can be
    // overridden by the user which results in a `id_extra` field.

    // Accordion set
    // [
    //     'type' => 'acc',
    //     'id_numeric' => $GLOBALS['acc_next_id_numeric'],
    //     'only_one_open' => $only_one_open,
    //     'id_extra' => 'some-special-id',    (optional)
    // ]
    //
    // or
    //
    // Accordion item
    // [
    //     'type' => 'item',
    //     'id_numeric' => $GLOBALS['acc_next_id_numeric'],
    //     'id_extra' => 'some-special-id'      (optional),
    //     'hidden' => $hidden,
    // ]


    //////////
    // Core //
    //////////

    // Parameter `$custom_color`: If to use another color outside of the variants as described in `README.md`
    function acc_start($variant = null, $custom_color = null, $only_one_open = false, $id = null, $class = '', $style = '') {
        // If there is already a last element, it must not be of type 'acc'
        assert(count($GLOBALS['acc_stack']) == 0 || end($GLOBALS['acc_stack'])['type'] != 'acc', 'An accordion set is already started');
        // `$variant` and `$custom_color` must not be set together
        assert($variant === null || $custom_color === null, 'Variant and custom color cannot be set together');

        // Push on stack
        $cur = [
            'type' => 'acc',
            'id_numeric' => $GLOBALS['acc_next_id_numeric'],
            'only_one_open' => $only_one_open,
        ];
        if ($id !== null) { $cur['id_extra'] = $id; }
        $GLOBALS['acc_stack'][] = $cur;
        
        // Update next id_numeric (fresh level)
        $GLOBALS['acc_next_id_numeric'] = 0;

        $html_accordion_id = acc_aux_get_html_id($GLOBALS['acc_stack']);
?>
    <div id="<?= $html_accordion_id ?>" class="accordion<? if ($variant !== null) { echo ' accordion-' . variant_to_color($variant); } elseif ($custom_color !== null) { echo ' accordion-' . $custom_color; } if ($class != '') { echo ' ' . $class; } ?>"<?= $style != '' ? ' style="' . $style . '"' : '' ?>>
<?
    }

    function acc_end() {
        // The last element must be of type 'acc'
        assert(count($GLOBALS['acc_stack']) > 0 && end($GLOBALS['acc_stack'])['type'] == 'acc', 'No accordion set started');

        // Pop from stack
        $last_element = array_pop($GLOBALS['acc_stack']);

        // Update next id_numeric (track back)
        $GLOBALS['acc_next_id_numeric'] = $last_element['id_numeric'] + 1;
?>
    </div>
<?
    }

    // Parameter `$custom_color`: If to use another color outside of the variants as described in `README.md`
    function acc_item_start($title, $variant = null, $custom_color = null, $hidden = false, $open = false, $id = null, $class = '', $style = '') {
        // The last element must be of type 'acc'
        assert(count($GLOBALS['acc_stack']) > 0 && end($GLOBALS['acc_stack'])['type'] == 'acc', 'No accordion set started');
        // `$variant` and `$custom_color` must not be set together
        assert($variant === null || $custom_color === null, 'Variant and custom color cannot be set together');


        $html_accordion_id = acc_aux_get_html_id($GLOBALS['acc_stack']);
        $accordion_only_one_open = end($GLOBALS['acc_stack'])['only_one_open'];

        // Push on stack
        $cur = [
            'type' => 'item',
            'id_numeric' => $GLOBALS['acc_next_id_numeric'],
            'hidden' => $hidden,
        ];
        if ($id !== null) { $cur['id_extra'] = $id; }
        $GLOBALS['acc_stack'][] = $cur;

        // Update next id_numeric (fresh level)
        $GLOBALS['acc_next_id_numeric'] = 0;

        $html_accordion_item_id = acc_aux_get_html_id($GLOBALS['acc_stack']);

        if ($hidden) {
            ob_start();
        }
        else {
?>
        <div id="<?= $html_accordion_item_id ?>" class="accordion-item<? if ($variant !== null) { echo ' accordion-item-' . variant_to_color($variant); } elseif ($custom_color !== null) { echo ' accordion-item-' . $custom_color; } if ($class != '') { echo ' ' . $class; } ?>"<?= $style != '' ? ' style="' . $style . '"' : '' ?>>
            <span class="accordion-header" id="<?= $html_accordion_item_id . '-header' ?>">
                <button class="accordion-button<? if (!$open) { echo ' collapsed'; } ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $html_accordion_item_id . '-collapse' ?>" aria-expanded="true" aria-controls="<?= $html_accordion_item_id . '-collapse' ?>">
                    <span><?= $title ?></span>
                </button>
            </span>
            <div id="<?= $html_accordion_item_id . '-collapse' ?>" class="accordion-collapse collapse<? if ($open) { echo ' show'; } ?>" aria-labelledby="<?= $html_accordion_item_id . '-header' ?>"<? if ($accordion_only_one_open) { echo ' data-bs-parent="#' . $html_accordion_id . '"'; } ?>>
                <div class="accordion-body">
<?
        }
    }

    function acc_item_end() {
        // The last element must be of type 'item'
        assert(count($GLOBALS['acc_stack']) > 0 && end($GLOBALS['acc_stack'])['type'] == 'item', 'No accordion item started');

        // Pop from stack
        $last_element = array_pop($GLOBALS['acc_stack']);

        // Update next id_numeric (track back)
        $GLOBALS['acc_next_id_numeric'] = $last_element['id_numeric'] + 1;

        if ($last_element['hidden']) {
            ob_end_clean();
        }
        else {
?>
                </div>
            </div>
        </div>
<?
        }
    }

    // Parameter `$custom_color`: If to use another color outside of the variants as described in `README.md`
    function acc_block_start($title = '', $variant = null, $custom_color = null, $bg_as_collapsed = false, $id = null, $class = '', $style = '') {
        // The last item must be of type 'acc'
        assert(count($GLOBALS['acc_stack']) > 0 && end($GLOBALS['acc_stack'])['type'] == 'acc', 'No accordion set started');
        // `$variant` and `$custom_color` must not be set together
        assert($variant === null || $custom_color === null, 'Variant and custom color cannot be set together');

        // Push on stack
        $cur = [
            'type' => 'block',
            'id_numeric' => $GLOBALS['acc_next_id_numeric'],
        ];
        if ($id !== null) { $cur['id_extra'] = $id; }
        $GLOBALS['acc_stack'][] = $cur;

        // Update next id_numeric (fresh level)
        $GLOBALS['acc_next_id_numeric'] = 0;

        $html_accordion_block_id = acc_aux_get_html_id($GLOBALS['acc_stack']);
?>
        <div id="<?= $html_accordion_block_id ?>" class="accordion-block<? if ($variant !== null) { echo ' accordion-block-' . variant_to_color($variant); } elseif ($custom_color !== null) { echo ' accordion-item-' . $custom_color; } if ($class != '') { echo ' ' . $class; } ?>"<?= $style != '' ? ' style="' . $style . '"' : '' ?>>
<?
        if ($title != '') {
?>
            <span class="block-header">
                <span class="block-title<?= $bg_as_collapsed ? ' collapsed' : '' ?>"><span><?= $title ?></span></span>
            </span>
<?
        }
?>
            <div class="accordion-body">
<?
    }

    function acc_block_end() {
        // The last element must be of type 'block'
        assert(count($GLOBALS['acc_stack']) > 0 && end($GLOBALS['acc_stack'])['type'] == 'block', 'No accordion block started');

        // Pop from stack
        $last_element = array_pop($GLOBALS['acc_stack']);

        // Update next id_numeric (track back)
        $GLOBALS['acc_next_id_numeric'] = $last_element['id_numeric'] + 1;
?>
            </div>
        </div>
<?
    }

    // Parameter `$custom_color`: If to use another color outside of the variants as described in `README.md`
    function acc_header_only($title, $variant = null, $custom_color = null, $bg_as_collapsed = false, $acc_id = null, $acc_class = '', $acc_style = '') {
        acc_start(variant: $variant, custom_color: $custom_color, id: $acc_id, class: $acc_class, style: $acc_style);
?>
            <span class="block-title border-bottom-0<?= $bg_as_collapsed ? ' collapsed' : '' ?>"><span><?= $title ?></span></span>
<?
        acc_end();
    }


    ////////////////
    // Shorthands //
    ////////////////

    function acc_single_item_start($title, $variant = null, $custom_color = null, $open = false, $acc_id = null, $acc_class = null, $acc_style = null, $item_id = null, $item_class = '', $item_style = '') {
        acc_start(id: $acc_id, class: $acc_class, style: $acc_style);
        acc_item_start($title, variant: $variant, custom_color: $custom_color, open: $open, id: $item_id, class: $item_class, style: $item_style);
    }

    function acc_single_item_end() {
        acc_item_end();
        acc_end();
    }

    function acc_single_block_start($title = '', $variant = null, $custom_color = null, $acc_id = null, $acc_class = null, $acc_style = null, $block_id = null, $block_class = '', $block_style = '') {
        acc_start(id: $acc_id, class: $acc_class, style: $acc_style);
        acc_block_start($title, variant: $variant, custom_color: $custom_color, id: $block_id, class: $block_class, style: $block_style);
    }

    function acc_single_block_end() {
        acc_block_end();
        acc_end();
    }


    //////////////
    // Headings //
    //////////////

    function acc_heading($level, $title, $class = '') {
        echo '<span class="acc-h' . $level . ($class !== '' ? ' ' . $class : '') . '">' . $title . '</span>';
    }


    //////////////
    // Internal //
    //////////////

    function acc_aux_get_html_id(&$stack): string {
        if (isset($stack[count($stack) - 1]['id_extra'])) {
            return $stack[count($stack) - 1]['id_extra'];
        }
        else {
            // Loop through $stack and concat the id's
            $html_id = 'accordion';
            foreach ($stack as $element) {
                $html_id .= '-' . $element['id_numeric'];
            }
            return $html_id;
        }
    }
?>