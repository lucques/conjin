<?
    $default_placeholders = function(Module $template, ModuleLocation $sub_template, string $content_width): array {
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
            'library_js_url' => $sub_template->get_url() . '/res/js/library.js',  // Keep the original URL

            'body_classes' => $content_width_classes,
        ];
    };
?>