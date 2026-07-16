<?
    $init_processing_target = function(Module $module, Target $target) {
        $GLOBALS['tabset_cur_id']              = null; // type null | string
        $GLOBALS['tabset_cur_tabs']            = null; // type null | [ 'tab_id' => ['title' => 'some_title', 'content' => 'some_content'] ]

        $GLOBALS['tabset_cur_next_item_id']    = null; // type null | string
        $GLOBALS['tabset_cur_next_item_title'] = null; // type null | string

        $GLOBALS['tabset_next_id'] = 0;
    };
    

    //////////
    // Core //
    //////////

    function tabs_start($id = null, $class = '', $style = '') {
        assert($GLOBALS['tabset_cur_id'] === null, 'A tabset is already started');

        if ($id !== null) {
            $GLOBALS['tabset_cur_id'] = $id;
        }
        else {
            // Generate a new id
            $GLOBALS['tabset_cur_id'] = 'tabset-' . $GLOBALS['tabset_next_id'];
            $GLOBALS['tabset_next_id']++;
        }

        $GLOBALS['tabset_cur_tabs'] = [];
    }

    function tabs_item_start(string $title) {
        assert($GLOBALS['tabset_cur_id'] !== null, 'No tabset started');
        assert($GLOBALS['tabset_cur_next_item_id'] === null, 'A tab item is already started');

        $GLOBALS['tabset_cur_next_item_id']    = 'tab-' . count($GLOBALS['tabset_cur_tabs']);
        $GLOBALS['tabset_cur_next_item_title'] = $title;
        ob_start();
    }

    function tabs_item_end() {
        assert($GLOBALS['tabset_cur_id'] !== null, 'No tabset started');
        assert($GLOBALS['tabset_cur_next_item_id'] !== null, 'No tab item to end');

        $content = ob_get_clean();
        $id      = $GLOBALS['tabset_cur_next_item_id'];
        $title   = $GLOBALS['tabset_cur_next_item_title'];

        $GLOBALS['tabset_cur_tabs'][$id] = [
            'title'   => $title,
            'content' => $content
        ];

        // Reset state
        $GLOBALS['tabset_cur_next_item_id']    = null;
        $GLOBALS['tabset_cur_next_item_title'] = null;
    }

    function tabs_end() {
        assert($GLOBALS['tabset_cur_id'] !== null, 'No tabset started');
        assert($GLOBALS['tabset_cur_next_item_id'] === null, 'A tab item is still open');

        $tabset_id = $GLOBALS['tabset_cur_id'];
?>
<div>
    <nav>
        <div class="nav nav-tabs">
<?
    $first = true;
    foreach ($GLOBALS['tabset_cur_tabs'] as $id => $tab) {
?>
        <button class="nav-link<?= $first ? ' active' : '' ?>" data-bs-toggle="tab" data-bs-target="#<?= $tabset_id ?>-<?= $id ?>" type="button" role="tab"><?= htmlspecialchars($tab['title']) ?></button>
<?
        $first = false;
    }
?>
        </div>
    </nav>
    <div class="tab-content">
<?
        $first = true;
        foreach ($GLOBALS['tabset_cur_tabs'] as $id => $tab) {
?>
        <div class="tab-pane<?= $first ? ' show active' : '' ?>" id="<?= $tabset_id ?>-<?= $id ?>">
            <?= $tab['content'] ?>
        </div> 
<?
            $first = false;
        }
?>
    </div>
</div>
<?
        // Reset state
        $GLOBALS['tabset_cur_id']   = null;
        $GLOBALS['tabset_cur_tabs'] = null;
    }
?>