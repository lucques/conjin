<?
    function get_top_level_plain_title(): string {
        return get_plain_titles(false)[0];
    }

    function get_top_level_html_title(): string {
        return get_html_titles(false)[0];
    }

    function get_plain_titles(bool $only_of_dependent_parents = true): array {
        $all_titles = [];
        $cur_target = $GLOBALS['title_target'];        
        $countdown = $cur_target->activated_modules['title']->config->get('number_of_dependent_parent_titles');

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
        $countdown = $cur_target->activated_modules['title']->config->get('number_of_dependent_parent_titles');

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
        return
            $target->has_activated_module('title')
            ? ($target->activated_modules['title']->config->get('plain')
                ?? $target->id
                ?? 'Unbenannt')
            : ($target->id ?? 'Unbenannt');
    }

    function title_aux_target_to_html_title(Target $target): string {
        return
            $target->has_activated_module('title')
            ? ($target->activated_modules['title']->config->get('html')
                ?? $target->activated_modules['title']->config->get('plain')
                ?? $target->activated_modules['title']->config->get('html_short')
                ?? $target->id
                ?? 'Unbenannt')
            : ($target->id ?? 'Unbenannt');
    }
?>