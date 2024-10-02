<?
    $default_config = function(): array {
        return [
            'use_img_as_logo'                 => false,
            'link_logo_to_home'               => true,
            'nav_show_top_level'              => true,
            'nav_active_sidebar_by_default'   => true,
            'nav_reduce_by_default'           => true,
            'nav_reduce_toggleable_with_ctrl' => true,
            // The module that holds the logo file under `res/`
            // If this is null, the currently set template is used
            'res_module'                      => null,
        ];
    }
?>