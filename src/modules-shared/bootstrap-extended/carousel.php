<?
    $init_processing_target = function(Module $module, Target $target) {
        $GLOBALS['carousel_cur_id']            = null; // type null | string
        $GLOBALS['carousel_cur_with_controls'] = null; // type null | bool
        $GLOBALS['carousel_next_id'] = 0;
    };
    

    //////////
    // Core //
    //////////

    // Parameter `$custom_color`: If to use another color outside of the variants as described in `README.md`
    function carousel_start($with_controls = true, $id = null, $class = '', $style = '') {
        assert($GLOBALS['carousel_cur_id'] === null, 'A carousel is already started');

        if ($id !== null) {
            $GLOBALS['carousel_cur_id'] = $id;
        }
        else {
            // Generate a new id
            $GLOBALS['carousel_cur_id'] = 'carousel-' . $GLOBALS['carousel_next_id'];
            $GLOBALS['carousel_next_id']++;
        }
        $GLOBALS['carousel_cur_with_controls'] = $with_controls;
?>
    <div id="<?= $GLOBALS['carousel_cur_id'] ?>" class="carousel carousel-dark slide" data-bs-ride="false">
        <div class="carousel-inner">
            <div class="carousel-item active">
<?
    }

    function carousel_item() {
        assert($GLOBALS['carousel_cur_id'] !== null, 'No carousel started');
?>
            </div>
            <div class="carousel-item">
<?
    }

    function carousel_end() {
        assert($GLOBALS['carousel_cur_id'] !== null, 'No carousel started');
?>
            </div>
<?
        if ($GLOBALS['carousel_cur_with_controls']) {
?>
                <button class="carousel-control-prev" type="button" data-bs-target="#<?= $GLOBALS['carousel_cur_id'] ?>" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#<?= $GLOBALS['carousel_cur_id'] ?>" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
<?
        }
?>
        </div>
    </div>
<?
        // Reset state
        $GLOBALS['carousel_cur_id']            = null;
        $GLOBALS['carousel_cur_with_controls'] = null;
    }
?>