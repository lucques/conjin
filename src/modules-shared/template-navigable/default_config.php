<?
    $default_config = function(): array {
        return [
            'title_for_head_contains_top_level' => false,
            'nav_enabled'                       => true,
            'use_img_as_logo'                   => false,
            'link_logo_to_home'                 => true,
            'nav_active_sidebar_by_default'     => true,
            'nav_reduce_by_default'             => true,
            'nav_reduce_toggleable_with_ctrl'   => true,
            'nav_reduce_breadcrumb_up_to_level' => 1,
            // The module that holds the logo file under `res/`
            // If this is null, the currently set template is used
            'res_module'                        => null,
        ];
    }
?>