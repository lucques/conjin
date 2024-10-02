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


        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $res_module = $template->config->get('res_module') === null
            ? $template
            : new ModuleLocation($template->config->get('res_module'));

        $placeholders_default = [
            'css_url'                         => $template->get_css_url(),
            'library_js_url'                  => $template->get_url() . '/res/js/library.js',
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
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);


        ////////////
        // Render //
        ////////////
        
        ob_start();
        $template->load_def_from_script_and_call('templates/inc/header-1.php',  'render', $template, $target->get_ids(), $placeholders);
        $template->load_def_from_script_and_call('templates/inc/sidebar.php',   'render', $template, $placeholders);
        $template->load_def_from_script_and_call('templates/inc/header-2.php',  'render', $template, $placeholders);
        echo $content;
        $template->load_def_from_script_and_call('templates/inc/footer.php',    'render', $template, $placeholders);
        ob_end_flush();
    };
?>