<?
    $default_placeholders = function(Module $template, ModuleLocation $sub_template): array {
        return [
            // Change some CSS files
            'css_screen_url'            => $template->get_css_url() . '/print-mode-screen.css',
            'css_print_mode_print_url'  => $template->get_css_url() . '/print.css',

            'library_js_url' => $sub_template->get_url() . '/res/js/library.js',  // Keep the original URL
        ];
    }
?>