<?
    ////////////////
    // PROCESSING //
    ////////////////

    $init_processing_target = function(Module $module, Target $target) {
        $GLOBALS['nav_build_target_ids'] = $target->get_ids();
    };


    //////////////////////////
    // Used by content file //
    //////////////////////////

    function nav_h(int $level, string $title, ?string $nav_title = null, ?string $id = null, string $class = '', string $style = '') {
        // If no id is provided, make one out of title
        if ($id === null) {
            $id = kebabize($title);
        }

        anchor_create($level, $id);
        $anchor_ids = get_current_anchor_ids();
        
        // Identify parent item
        $parent_anchor_ids = array_slice($anchor_ids, 0, count($anchor_ids)-1);

        // Parent item is either the target or a previously added anchor
        $parent_item = core_load_obj('nav')->find($GLOBALS['nav_build_target_ids'], $parent_anchor_ids);

        // Add anchor item
        $anchor_item = new AnchorNavItem(
            target_ids: $GLOBALS['nav_build_target_ids'],
            anchor_ids: $anchor_ids,
            title: $nav_title ?? $title
        );
        
        // Update parent
        $parent_item->add_anchor_child($anchor_item);
        
        // Print
        html_h($level, $title, id: anchor_collect($anchor_ids), class: $class, style: $style);
    }
?>