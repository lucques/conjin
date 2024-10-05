<?
    $default_placeholders = function(Module $template, string $title_for_head): array {
        
        $title_for_logo =
            function_exists('get_top_level_html_title')
            ? get_top_level_html_title()
            : 'Unbenannt';

        $res_module =
            $template->config->get('res_module') === null
            ? $template
            : new ModuleLocation($template->config->get('res_module'));

        return [
            'css_screen_url'                  => $template->get_css_url() . '/print-mode-screen.css',
            'css_print_url'                   => $template->get_css_url() . '/print.css',
            'css_print_mode_screen_url'       => $template->get_css_url() . '/print-mode-screen.css',
            'css_print_mode_print_url'        => $template->get_css_url() . '/print.css',

            'logo_url'                        => $res_module->get_url() . '/res/logo/logo.png',

            'title_for_logo'                  => $title_for_logo,
            'title_for_head'                  => $title_for_head,

            'use_img_as_logo'                 => $template->config->get('use_img_as_logo'),
            'link_logo_to_home'               => $template->config->get('link_logo_to_home'),
            'nav_active_sidebar_by_default'   => $template->config->get('nav_active_sidebar_by_default'),
            'nav_reduce_by_default'           => $template->config->get('nav_reduce_by_default'),
            'nav_reduce_toggleable_with_ctrl' => $template->config->get('nav_reduce_toggleable_with_ctrl'),
        ];
    }
?>