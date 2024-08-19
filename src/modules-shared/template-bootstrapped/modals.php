<?
    $init_processing_target = function(Module $module, Target $target) {
        $GLOBALS['modals_started'] = false;
        $GLOBALS['modals_next_id'] = 0;

        // Support `sync-dims` module, only heights need to be resynced
        // TODO
    };
    

    //////////
    // Core //
    //////////

    function modal_start($class = '', $style = '') {
        assert(!$GLOBALS['modals_started'], 'A modal is already started');
        $GLOBALS['modals_started'] = true;

        $html_modal_id = 'modal-' . $GLOBALS['modals_next_id'];

        body_top_element_start();
?>
    <div id="<?= $html_modal_id ?>" class="modal<? if ($class != '') { echo ' ' . $class; } ?>" <?= $style != '' ? 'style="' . $style . '"' : '' ?> tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
<?
    }

    function modal_end() {
        assert($GLOBALS['modals_started'], 'No modal is already started');
        $GLOBALS['modals_started'] = false;

?>
            </div>
        </div>
    </div>
<?
        body_top_element_end();

        // Update next id
        $GLOBALS['modals_next_id']++;
    }

    function modal_get_id() {
        assert($GLOBALS['modals_started'], 'No modal is already started');
        return 'modal-' . $GLOBALS['modals_next_id'];
    }
?>