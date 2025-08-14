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
            'css_screen_url'                    => $template->get_css_url() . '/screen.css',
            'css_print_url'                     => $template->get_css_url() . '/print.css',
            'css_print_mode_screen_url'         => $template->get_css_url() . '/print-mode-screen.css',
            'css_print_mode_print_url'          => $template->get_css_url() . '/print-mode-print.css',

            'library_js_url'                    => $template->get_url() . '/res/js/library.js',
            'logo_url'                          => $res_module->get_url() . '/res/logo/logo.png',
            
            'title_for_logo'                    => $title_for_logo,
            'title_for_head'                    => $title_for_head,

            'body_classes'                      => []  // Allows to add additional classes to the body element
        ];
    }
?>