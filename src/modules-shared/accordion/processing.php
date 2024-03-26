<?
    /*
        - An accordion set is introduced by `acc_start` and `acc_end`
        - Item
            - Introduced by `acc_item_start` and `acc_item_end`
        - Block
            - Introduced by `acc_block_start` and `acc_block_end`
        - For single-item or single-block accordion sets, the following helper functions are available:
            - `acc_single_item_start`  and ...`_end`
            - `acc_single_block_start` and ...`_end`
        - Heading hierarchy:
            - Use `acc_heading` for further headings inside item / block. Use level 2 upwards.
    */

    $init_processing = function(Module $module, Target $target) {
        $GLOBALS['acc_cur_id']            = 0;
        $GLOBALS['acc_cur_only_one_open'] = false;
        $GLOBALS['acc_cur_counter']       = null;
    };

    //////////
    // Core //
    //////////

    function acc_start($variant = '', $only_one_open = false, $class = '', $style = '') {
        $GLOBALS['acc_cur_id'] += 1;
        $GLOBALS['acc_cur_only_one_open'] = $only_one_open;
        $GLOBALS['acc_cur_counter'] = 1;
        $html_accordion_id = 'accordion-' . $GLOBALS['acc_cur_id'];
?>
    <div id="<?= $html_accordion_id ?>" class="accordion<? if ($variant != '') { echo ' accordion-' . acc_get_variant($variant); } if ($class != '') { echo ' ' . $class; } ?>" <?= $style != '' ? 'style="' . $style . '"' : '' ?>>
<?
    }

    function acc_end() {
?>
    </div>
<?
    }

    function acc_item_start($title, $variant = '', $open = false) {
        $html_accordion_id      = 'accordion-' . $GLOBALS['acc_cur_id'];
        $html_accordion_item_id = 'accordion-' . $GLOBALS['acc_cur_id'] . '-' . $GLOBALS['acc_cur_counter'];
?>
        <div class="accordion-item<? if ($variant != '') { echo ' accordion-item-' . acc_get_variant($variant); } ?>">
            <span class="accordion-header" id="<?= $html_accordion_item_id . '-header' ?>">
                <button class="accordion-button<? if (!$open) { echo ' collapsed'; } ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $html_accordion_item_id . '-collapse' ?>" aria-expanded="true" aria-controls="<?= $html_accordion_item_id . '-collapse' ?>">
                    <span><?= $title ?></span>
                </button>
            </span>
            <div id="<?= $html_accordion_item_id . '-collapse' ?>" class="accordion-collapse collapse<? if ($open) { echo ' show'; } ?>" aria-labelledby="<?= $html_accordion_item_id . '-header' ?>"<? if ($GLOBALS['acc_cur_only_one_open']) { echo ' data-bs-parent="#' . $html_accordion_id . '"'; } ?>>
                <div class="accordion-body">
<?
    }

    function acc_item_end() {
        $GLOBALS['acc_cur_counter']++;
?>
                </div>
            </div>
        </div>
<?
    }

    function acc_block_start($title = '', $variant = '') {
?>
        <div class="accordion-block<? if ($variant != '') { echo ' accordion-block-' . acc_get_variant($variant); } ?>">
<?
        if ($title != '') {
?>
            <span class="block-header">
                <span class="block-title"><span><?= $title ?></span></span>
            </span>
<?
        }
?>
            <div class="accordion-body">
<?
    }

    function acc_block_end() {
?>
            </div>
        </div>
<?
    }

    function acc_single_item_start($title, $variant = '', $open = false, $class = '') {
        acc_start(class: $class);
        acc_item_start($title, variant: $variant, open: $open);
    }

    function acc_single_item_end() {
        acc_item_end();
        acc_end();
    }

    function acc_single_block_start($title = '', $variant = '', $class = '') {
        acc_start(class: $class);
        acc_block_start($title, variant: $variant);
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

    
    //////////////////////
    // Helper functions //
    //////////////////////

    // Translate from various variants to Bootstrap classes
    function acc_get_variant(string $variant): string {
        if     ($variant == 'info')    { return 'dark'; }
        elseif ($variant == 'opinion') { return 'dark'; }
        else                           { return $variant; }
    }
?>