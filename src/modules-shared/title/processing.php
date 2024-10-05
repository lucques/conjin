<?
    ///////////////
    // Init defs //
    ///////////////

    $init_processing_syslet = function(Module $module, Syslet $syslet, Target $root_target) {
        // Cache root target for later use
        $GLOBALS['title_target'] = &$root_target;
    };

    $init_processing_target = function(Module $module, Target $target) {
        // Cache target for later use
        $GLOBALS['title_target'] = &$target;
    };

    
    ////////////
    // Macros //
    ////////////

    function get_top_level_plain_title(): ?string {
        $titles = get_plain_titles(false);
        if (count($titles) > 0) {
            return $titles[0];
        }
        else {
            return null;
        }
    }

    function get_top_level_html_title(): ?string {
        $titles = get_html_titles(false);
        if (count($titles) > 0) {
            return $titles[0];
        }
        else {
            return null;
        }
    }

    function get_plain_titles(bool $only_of_dependent_parents = true): array {
        $all_titles = [];
        $cur_target = $GLOBALS['title_target'];        
        $countdown = $cur_target->activated_modules['title']->config->get('show_parents');

        while ($cur_target !== null && ($countdown >= 0 || !$only_of_dependent_parents)) {
            $cur_title = title_aux_target_to_plain_title($cur_target);

            array_unshift($all_titles, $cur_title);
            $cur_target = $cur_target->get_parent();
            $countdown--;
        }
        
        return $all_titles;
    }

    function get_html_titles(bool $only_of_dependent_parents = true): array {
        $all_titles = [];
        $cur_target = $GLOBALS['title_target'];
        $countdown = $cur_target->activated_modules['title']->config->get('show_parents');

        while ($cur_target !== null && ($countdown >= 0 || !$only_of_dependent_parents)) {
            $cur_title = title_aux_target_to_html_title($cur_target);
            
            array_unshift($all_titles, $cur_title);
            $cur_target = $cur_target->get_parent();
            $countdown--;
        }
        
        return $all_titles;
    }


    /////////////
    // Helpers //
    /////////////

    function title_aux_target_to_plain_title(Target $target): string {
        $res = null;
        if ($target->has_activated_module('title')) {
            $res = $target->activated_modules['title']->config->get('plain');
        }
        return $res ?? $target->id ?? 'Unbenannt';
    }

    function title_aux_target_to_html_title(Target $target): string {
        $res = null;
        if ($target->has_activated_module('title')) {
            $res = $target->activated_modules['title']->config->get('html')
                ?? $target->activated_modules['title']->config->get('plain')
                ?? $target->activated_modules['title']->config->get('html_short');
        }
        return $res ?? $target->id ?? 'Unbenannt';
    }
?>