<?
    //////////////////////
    // Used by template //
    //////////////////////

    function nav_print() {
        // 1. Find highest ancestor that current user is still privileged to view.
        $cur_root_target_ids = $GLOBALS['nav_view_target_ids'];

        while (count($cur_root_target_ids) > 0) {
            $next_root_target_ids = array_slice($cur_root_target_ids, 0, -1);
            $next_privileged_actors = core_load_obj('nav')->find($next_root_target_ids)->privileged_actors;
            
            if (!auth_is_cur_user_among_authorized_actors($next_privileged_actors)) {
                break;
            }
            
            array_pop($cur_root_target_ids);
        }

        // 2. Print everything from `$cur_root_target_ids` on.
        nav_aux_print_rec(core_load_obj('nav')->find($cur_root_target_ids));
    }


    /////////////
    // Helpers //
    /////////////

    function nav_aux_print_rec(NavItem $item) {
        // If current item is not privileged, drop item as well as its children
        if ($item instanceof TargetNavItem && !auth_is_cur_user_among_authorized_actors($item->privileged_actors)) {
            return;
        }

        // Prepare either text or link
        if ($item->has_content) {
            $html = '<a href="' . $item->get_url() . '">' . $item->title . '</a>';
        }
        else {
            $html = '<span class="no-page">' . $item->title . '</span>';
        }

        $level = count($item->get_ids());

        $space = str_repeat('        ', $level);
        if ($item->get_number_of_children() == 0) {
            echo $space . '<div id="nav-item_' . $item->get_css_slug() . '" data-ids="' . implode(' ', $item->get_ids()) . '" class="nav-item item-level-' . $level . '">' . "\n";
            echo $space . '  <div class="bullet">•</div>' . "\n"; 
            echo $space . '  <div class="content">' . $html . '</div>' . "\n";  
            echo $space . '</div>' . "\n";      
        }
        else {
            echo $space . '<div id="nav-item_' . $item->get_css_slug() . '" data-ids="' . implode(' ', $item->get_ids()) . '" class="nav-item item-level-' . $level . ' nested">' . "\n";
            echo $space . '  <div class="caret"><span class="right-triangle">▼</span><span class="down-triangle">▼</span></div>' . "\n";
            echo $space . '  <div class="content">' . "\n";
            echo $space . '    ' . $html . "\n";
            echo $space . '    <ul>' . "\n";
            foreach ($item->iterate_children() as $child) {
                echo $space . '      <li>' . "\n";
                nav_aux_print_rec($child);
                echo $space . '      </li>' . "\n";
            }
            echo $space . '    </ul>' . "\n";
            echo $space . '  </div>' . "\n";
            echo $space . '</div>' . "\n";
        }
    }
?>