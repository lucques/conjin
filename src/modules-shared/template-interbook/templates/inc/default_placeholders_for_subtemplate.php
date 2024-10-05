<?
    $default_placeholders = function(Module $template, string $title_for_head, string $content_width): array {
        
        $title_for_logo =
            function_exists('get_top_level_html_title')
            ? get_top_level_html_title()
            : 'Unbenannt';

        $res_module = $template->config->get('res_module') === null
            ? $template
            : new ModuleLocation($template->config->get('res_module'));

        // Split $content_width by '-':
        // If there are two parts, those are the min-width and max-width.
        // Otherwise, apply it as both the min-width and max-width.
        $content_width_classes = [];
        if (strpos($content_width, '-') !== false) {
            $content_width_parts = explode('-', $content_width);
            $content_width_classes[] = 'content-min-width-' . $content_width_parts[0];
            $content_width_classes[] = 'content-max-width-' . $content_width_parts[1];
        } else {
            $content_width_classes[] = 'content-min-width-' . $content_width;
            $content_width_classes[] = 'content-max-width-' . $content_width;
        }

        return [
            'css_screen_url'                    => $template->get_css_url() . '/screen.css',
            'css_print_url'                     => $template->get_css_url() . '/print.css',
            'css_print_mode_screen_url'         => $template->get_css_url() . '/print-mode-screen.css',
            'css_print_mode_print_url'          => $template->get_css_url() . '/print.css',

            'logo_url'                          => $res_module->get_url() . '/res/logo/logo.png',

            'title_for_logo'                    => $title_for_logo,
            'title_for_head'                    => $title_for_head,

            'nav_enabled'                       => $template->config->get('nav_enabled'),
            'use_img_as_logo'                   => $template->config->get('use_img_as_logo'),
            'link_logo_to_home'                 => $template->config->get('link_logo_to_home'),
            'nav_active_sidebar_by_default'     => $template->config->get('nav_active_sidebar_by_default'),
            'nav_reduce_by_default'             => $template->config->get('nav_reduce_by_default'),
            'nav_reduce_toggleable_with_ctrl'   => $template->config->get('nav_reduce_toggleable_with_ctrl'),
            'nav_reduce_breadcrumb_up_to_level' => $template->config->get('nav_reduce_breadcrumb_up_to_level'),

            'body_classes'                      => $content_width_classes,
        ];
    }
?>