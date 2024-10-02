<?
    $render_target = function(Module $template, Target $target, string $content, array $placeholders_overrides = []) {

        //////////////////////
        // Determine titles //
        //////////////////////

        $title_for_logo =
            $target->has_activated_module('title')
            ? get_top_level_html_title()
            : 'Unbenannt';
        $title_for_head =
            $target->has_activated_module('title')
            ? ($target->has_parent()
                ? implode(' » ', get_plain_titles(true)) . ' − ' . get_top_level_plain_title()
                : get_top_level_plain_title())
            : ($target->id ?? 'Unbenannt');
        $title_for_h1 =
            $target->has_activated_module('title')
            ? implode(' » ', get_html_titles(true))
            : ($target->id ?? 'Unbenannt');
    

        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $placeholders_default = [
            'title_for_h1' => $title_for_h1,
        ];
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);


        ///////////////////////////////////////////////
        // Prepare sub-template `template-navigable` //
        ///////////////////////////////////////////////
        
        $res_module = $template->config->get('res_module') === null
            ? $template
            : new ModuleLocation($template->config->get('res_module'));

        $placeholders_for_subtemplate_default = [
            'css_url'                         => $template->get_css_url(),
            'logo_url'                        => $res_module->get_url() . '/res/logo/logo.png',

            'title_for_logo'                  => $title_for_logo,
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

        // Render content
        ob_start();
        if ($target->has_activated_module('title') && $target->activated_modules['title']->config->get('is_part_of_content')) {
?>
                <h1><?= $placeholders['title_for_h1'] ?></h1>
<?
        }
        $content = ob_get_clean() . $content;

        // Render using sub-template
        $sub_template->render_target($target, $content, $placeholders_for_subtemplate);
    };
?>