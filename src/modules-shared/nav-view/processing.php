<?
    ///////////////
    // Init defs //
    ///////////////

    $init_processing_syslet = function(Module $module, Syslet $syslet, Target $target_root) {
        // Use root target
        $GLOBALS['nav_view_target_ids'] = [];
    };

    $init_processing_target = function(Module $module, Target $target) {
        $GLOBALS['nav_view_target_ids'] = $target->get_ids();
    };
    

    //////////////////////
    // Used by template //
    //////////////////////

    function nav_print($id_breadcrumb, $id_tree, int $breadcrumb_up_to_level) {
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

        $root_target = core_load_obj('nav')->find($cur_root_target_ids);

        // 2. Print breadcrumb
        $breadcrumb_items = nav_aux_get_breadcrumb_items_rec($breadcrumb_up_to_level, $root_target);
        if (count($breadcrumb_items) > 0) {
            echo '<nav id="' . $id_breadcrumb . '">' . "\n";
            echo '  <ol class="breadcrumb">' . "\n";
            foreach ($breadcrumb_items as $item) {
                echo '    ' . $item . "\n";
            }
            echo '  </ol>' . "\n";
            echo '</nav>' . "\n";
        }

        // 2. Print everything from `$cur_root_target_ids` on.
        echo '<nav id="' . $id_tree . '">' . "\n";
        nav_aux_print_tree_rec($root_target, surround_with_li: false);
        echo '</nav>' . "\n";
    }


    /////////////
    // Helpers //
    /////////////

    function nav_aux_get_breadcrumb_items_rec(int $breadcrumb_up_to_level, NavItem $item): array {
        $res = [];

        $level = count($item->get_ids());

        // Only go on if we are not above the level we want to print up to
        if ($level <= $breadcrumb_up_to_level) {
            // Ignore top-level item
            if ($level > 0) {
                // Prepare either text or link
                if ($item->has_content) {
                    $res[] = '<li class="breadcrumb-item nav-item_' . $item->get_css_slug() . '"><a href="' . $item->get_url() . '">' . $item->title . '</a></li>';
                }
                else {
                    $res[] = '<li class="breadcrumb-item nav-item_' . $item->get_css_slug() . '">' . $item->title . '</li>';
                }
            }

            // Next one?
            if ($level < count($GLOBALS['nav_view_target_ids'])) {
                $res = array_merge($res, nav_aux_get_breadcrumb_items_rec($breadcrumb_up_to_level, $item->find([$GLOBALS['nav_view_target_ids'][$level]])));
            }
        }

        return $res;
    }

    function nav_aux_print_tree_rec(NavItem $item, bool $surround_with_li): void {
        // If current item is not privileged, drop item as well as its children
        if ($item instanceof TargetNavItem && !auth_is_cur_user_among_authorized_actors($item->privileged_actors)) {
            return;
        }

        $level = count($item->get_ids());
        $space = '  ' . str_repeat('      ', $level);

        // <li>
        if ($surround_with_li) {
            echo $space . '<li'. ($item->get_number_of_children() > 0 ? ' class="nested"' : '') . '>' . "\n";

            if ($item->get_number_of_children() == 0) {
                echo $space . '  <div class="bullet">•</div>' . "\n";
            }
            else {
                echo $space . '  <div class="caret"><span class="right-triangle">▼</span><span class="down-triangle">▼</span></div>' . "\n";
            }

            $space .= '  ';
        }

        // <div>
        echo $space . '<div class="nav-tree-item nav-item_' . $item->get_css_slug() . '" data-ids="' . implode(' ', $item->get_ids()) . '">' . "\n";

        // Print either text or link (except level-0)
        if ($level > 0) {
            if ($item->has_content) {
                echo $space . '  <a href="' . $item->get_url() . '">' . $item->title . '</a>' . "\n";
            }
            else {
                echo $space . '  <span class="no-page">' . $item->title . '</span>' . "\n";
            }
        }

        // Print children, if available
        if ($item->get_number_of_children() > 0) {
            echo $space . '  <ul>' . "\n";

            foreach ($item->iterate_children() as $child) {
                nav_aux_print_tree_rec($child, surround_with_li: true);
            }

            echo $space . '  </ul>' . "\n";
        }

        // </div>
        echo $space . '</div>' . "\n";

        // </li>
        if ($surround_with_li) {
            $space = substr($space, 2);
            echo $space . '</li>' . "\n";
        }
    }
?>