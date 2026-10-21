<?
    ////////////////
    // PROCESSING //
    ////////////////

    $process_target = function(Module $module, Target $target) {
        $GLOBALS['nav_build_target_ids'] = $target->get_ids();
    };


    //////////////////////////
    // Used by content file //
    //////////////////////////

    function nav_h(int $level, string $title, ?string $nav_title = null, ?string $id = null, string $class = '', string $style = '') {
        $html_id = nav_aux_create_heading_anchor($level, $title, $nav_title, $id);
        html_h($level, $title, id: $html_id, class: $class, style: $style);
    }

    function nav_h_with_anchor(int $level, string $title, ?string $nav_title = null, ?string $id = null, string $class = '', string $style = '') {
        $html_id = nav_aux_create_heading_anchor($level, $title, $nav_title, $id);
        html_h_with_anchor($level, $title, id: $html_id, class: $class, style: $style);
    }


    //////////////
    // Internal //
    //////////////

    function nav_aux_create_heading_anchor(int $level, string $title, ?string $nav_title, ?string $id): string {
        // If no id is provided, make one out of title
        if ($id === null) {
            $id = kebabize($title);
        }

        $anchor_ids = anchor_create($level, $id);
        
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
        
        return anchor_collect($anchor_ids);
    }
?>
