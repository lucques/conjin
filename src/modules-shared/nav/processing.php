<?
    /*
        This module offers to build up a navigation tree of all the preprocessed
        content.

        The tree is built up mostly in the preprocessing phase, but anchors are
        added in the processing phase. This is because anchors are not known
        until the content is processed. This may change in the future!
    */

    require_once(__DIR__ . '/datatypes.php');


    ////////////////
    // PROCESSING //
    ////////////////

    $init_processing = function(Module $module, Target $target) {
        $GLOBALS['nav']                = core_load_obj('nav');
        $GLOBALS['nav_target_ids']     = $target->ids;
        $GLOBALS['nav_cur_anchor_ids'] = [];
    };


    //////////////////////////
    // Used by content file //
    //////////////////////////

    function nav_heading_anchor(int $local_level, string $title, string $id, string $nav_title = null, string $class = '', string $style = '') {
        // Level must not exceed number of possible parent anchors
        assert($local_level-2 <= count($GLOBALS['nav_cur_anchor_ids']));
        
        // Identify parent item
        $parent_anchor_ids = array_slice($GLOBALS['nav_cur_anchor_ids'], 0, $local_level-2);

        // Parent item is either the target or a previously added anchor
        $parent_item = $GLOBALS['nav']->find($GLOBALS['nav_target_ids'], $parent_anchor_ids);

        // Add anchor item
        $anchor_item = new AnchorNavItem(
            target_ids: $GLOBALS['nav_target_ids'],
            anchor_ids: array_merge($parent_anchor_ids, [$id]),
            nav_title: $nav_title ?? $title
        );
        
        // Update parent
        $parent_item->add_anchor_child($anchor_item);
        
        // Update cur_anchor_ids
        $GLOBALS['nav_cur_anchor_ids'] = $anchor_item->anchor_ids;

        // Print
        nav_heading_print($local_level, $title, anchor: anchor_collect($GLOBALS['nav_cur_anchor_ids']), class: $class, style: $style);
    }

    function nav_heading_print(int $local_level, string $title, string $anchor = '', string $class = '', string $style = '') {
        printf('<h%s id="%s" class="%s" style="%s">%s</h%s>', $local_level, $anchor, $class, $style, $title, $local_level);
    }


    //////////////////////
    // Used by template //
    //////////////////////

    function nav_print() {
        nav_print_rec($GLOBALS['nav']);
    }

    function nav_print_rec(NavItem $item, int $level = 0) {
        $space = str_repeat('        ', $level);
        if ($item->get_number_of_children() == 0) {
            echo $space . '<div id="nav-item_' . $item->get_css_slug() . '" data-ids="' . implode(' ', $item->get_ids()) . '" class="nav-item item-level-' . $level . '">' . "\n";
            echo $space . '  <div class="bullet">•</div>' . "\n"; 
            echo $space . '  <div class="content"><a href="' . $item->get_url() . '">' . $item->nav_title . '</a></div>' . "\n";  
            echo $space . '</div>' . "\n";      
        }
        else {
            echo $space . '<div id="nav-item_' . $item->get_css_slug() . '" data-ids="' . implode(' ', $item->get_ids()) . '" class="nav-item item-level-' . $level . ' nested">' . "\n";
            echo $space . '  <div class="caret"><span class="right-triangle">▼</span><span class="down-triangle">▼</span></div>' . "\n";
            echo $space . '  <div class="content">' . "\n";
            echo $space . '    <a href="' . $item->get_url() . '">' . $item->nav_title . '</a>' . "\n";
            echo $space . '    <ul>' . "\n";
            foreach ($item->iterate_children() as $child) {
                echo $space . '      <li>' . "\n";
                nav_print_rec($child, $level + 1);
                echo $space . '      </li>' . "\n";
            }
            echo $space . '    </ul>' . "\n";
            echo $space . '  </div>' . "\n";
            echo $space . '</div>' . "\n";
        }
    }
?>