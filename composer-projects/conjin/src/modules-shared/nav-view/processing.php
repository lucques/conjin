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

    function nav_is_tree_empty(): bool {
        // 1. Find highest ancestor that current user is still privileged to view.
        $cur_root_target_ids = nav_aux_find_highest_privileged_ancestor($GLOBALS['nav_view_target_ids']);
        $root_target = core_load_obj('nav')->find($cur_root_target_ids);

        // Tree is empty if either
        // (i)  not privileged to view the root target, or
        // (ii) root target has no visible children
        return
               !nav_aux_is_item_visible($root_target)
            || count(nav_aux_get_visible_children($root_target)) == 0;
    }

    function nav_print(int $breadcrumb_up_to_level, bool $with_decoration = false) {
        // 1. Find highest ancestor that current user is still privileged to view.
        $cur_root_target_ids = nav_aux_find_highest_privileged_ancestor($GLOBALS['nav_view_target_ids']);

        // The current user may or may not be privileged to view the new
        // "root target".
        $root_target = core_load_obj('nav')->find($cur_root_target_ids);

        // 2. Print breadcrumb
        $breadcrumb_items = nav_aux_get_breadcrumb_items_rec($breadcrumb_up_to_level, $root_target);
        if (count($breadcrumb_items) > 0) {
            echo '<nav id="sidebar-nav-breadcrumb">' . "\n";
            echo '  <ol class="breadcrumb">' . "\n";
            foreach ($breadcrumb_items as $item) {
                echo '    ' . $item . "\n";
            }
            echo '  </ol>' . "\n";
            echo '</nav>' . "\n";
        }

        // 3. Print everything from `$cur_root_target_ids` on.
        echo '<nav id="sidebar-nav-tree">' . "\n";
        nav_aux_print_tree_rec($root_target, surround_with_li: false, with_decoration: $with_decoration);
        echo '</nav>' . "\n";
    }


    /////////////
    // Helpers //
    /////////////

    // Returns the targets ids (array) of the highest ancestor of `$target_ids`
    // that the current user is still privileged to view.
    // The ancestor may also be the target itself.
    // If there is no such ancestor, the root target is returned (empty array).
    function nav_aux_find_highest_privileged_ancestor(array $target_ids): array {
        $cur_target_ids = $target_ids;

        while (count($cur_target_ids) > 0) {
            $next_target_ids = array_slice($cur_target_ids, 0, -1);
            $next_privileged_actors = core_load_obj('nav')->find($next_target_ids)->privileged_actors;
            
            if (!auth_is_cur_user_among_authorized_actors($next_privileged_actors)) {
                break;
            }
            
            array_pop($cur_target_ids);
        }

        return $cur_target_ids;
    }

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

    function nav_aux_is_item_visible(NavItem $item): bool {
        return
               !($item instanceof TargetNavItem)
            || auth_is_cur_user_among_authorized_actors($item->privileged_actors);
    }

    function nav_aux_get_visible_children(NavItem $item): array {
        $visible_children = [];

        foreach ($item->iterate_children() as $child) {
            if (nav_aux_is_item_visible($child)) {
                $visible_children[] = $child;
            }
        }

        return $visible_children;
    }

    function nav_aux_print_tree_rec(NavItem $item, bool $surround_with_li, bool $with_decoration): void {
        // If current item is not privileged, drop item as well as its children
        if (!nav_aux_is_item_visible($item)) {
            return;
        }

        $visible_children = nav_aux_get_visible_children($item);
        $number_of_visible_children = count($visible_children);
        $level = count($item->get_ids());
        $space = '  ' . str_repeat('      ', $level);

        // <li>
        if ($surround_with_li) {
            echo $space . '<li'. ($number_of_visible_children > 0 ? ' class="nested"' : '') . '>' . "\n";

            if ($with_decoration) {
                if ($number_of_visible_children == 0) {
                    echo $space . '  <div class="bullet">•</div>' . "\n";
                }
                else {
                    echo $space . '  <div class="caret"><span class="right-triangle">▼</span><span class="down-triangle">▼</span></div>' . "\n";
                }
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
        if ($number_of_visible_children > 0) {
            echo $space . '  <ul>' . "\n";

            foreach ($visible_children as $child) {
                nav_aux_print_tree_rec($child, surround_with_li: true, with_decoration: $with_decoration);
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
