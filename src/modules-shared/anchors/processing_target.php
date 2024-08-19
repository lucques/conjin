<?
    $init_processing_target = function(Module $module, Target $target) {
        $GLOBALS['anchors_tree_root']   = []; // nested assoc array
        $GLOBALS['anchors_current_ids'] = []; // list<id>
    };


    //////////////////////////
    // Used by content file //
    //////////////////////////

    // $level: 1 means "root"
    function anchor_create(int $level, string $id) {
        // Level must be at least 2
        assert($level >= 2, "Level $level must be at least 2");

        // Level must not exceed number of possible parent anchors
        assert($level-2 <= count($GLOBALS['anchors_current_ids']), "Level $level exceeds number of possible parent anchors");
        
        // Identify parent item
        $parent_anchor_ids = array_slice($GLOBALS['anchors_current_ids'], 0, $level-2);

        // Navigate to parent item
        $cur_tree = &$GLOBALS['anchors_tree_root'];
        foreach ($parent_anchor_ids as $parent_id) {
            $cur_tree = &$cur_tree[$parent_id];
        }

        // Add anchor item
        assert(!isset($cur_tree[$id]), "Anchor with id '$id' already exists");
        $cur_tree[$id] = [];

        // Update cur_anchor_ids
        $GLOBALS['anchors_current_ids'] = array_merge($parent_anchor_ids, [$id]);
    }

    function anchor_create_from_title(int $level, string $title) {
        anchor_create($level, kebabize($title));
    }

    function get_current_anchor_ids() {
        return $GLOBALS['anchors_current_ids'];
    }
?>