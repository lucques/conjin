<?
    $render_not_found = function(Module $template, Syslet $syslet, ?array $target_ids, $placeholders_overrides = []) {
        
        //////////////////////
        // Determine titles //
        //////////////////////

        $top_level_plain_title =
            $target->has_activated_module('title')
            ? get_top_level_plain_title()
            : 'Unbenannt';

        $top_level_html_title =
            $target->has_activated_module('title')
            ? get_top_level_html_title()
            : 'Unbenannt';

        $title_for_head = 'Seite nicht gefunden − ' . $top_level_plain_title;


        ///////////////////////////////////////////////
        // Prepare sub-template `template-navigable` //
        ///////////////////////////////////////////////
        
        // Placeholders: Merge default values with overrides
        $placeholders_for_subtemplate_default = [
            'url'                             => $template->get_url(),
            'css_url'                         => $template->get_css_url(),

            'title_for_logo'                  => $top_level_html_title,
            'title_for_head'                  => $title_for_head,

            'use_img_as_logo'                 => $template->config->get('use_img_as_logo'),
            'link_logo_to_home'               => $template->config->get('link_logo_to_home'),
            'nav_show_top_level'              => $template->config->get('nav_show_top_level'),
            'nav_active_sidebar_by_default'   => $template->config->get('nav_active_sidebar_by_default'),
            'nav_reduce_by_default'           => $template->config->get('nav_reduce_by_default'),
            'nav_reduce_toggleable_with_ctrl' => $template->config->get('nav_reduce_toggleable_with_ctrl'),
        ];
        $placeholders_for_subtemplate = array_merge($placeholders_for_subtemplate_default, $placeholders_overrides);
        $sub_template = $target->activated_modules['template-navigable'];


        ////////////
        // Render //
        ////////////

        // Render using sub-template
        $sub_template->render_not_found($target, $target_ids, $placeholders_for_subtemplate);
    };
?>