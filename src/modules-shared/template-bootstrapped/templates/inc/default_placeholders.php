<?
    $default_placeholders = function(Module $template, string $title_for_head): array {
        return [
            'css_screen_url' => $template->get_css_url() . '/screen.css',
            'css_print_url'  => $template->get_css_url() . '/print.css',
            
            'title_for_head' => $title_for_head,
        ];
    }
?>