<?
    $default_config = function(): array {
        return [
            'title_for_head_contains_top_level' => false,
            'res_module'                        => null,
            
            // Type: null or...
            'sidebar' => [
                'use_img_as_logo'               => false,
                'link_logo_to_home'             => true,
                'active_by_default'             => true,
                'reduce_by_default'             => true,
                'reduce_toggleable_with_ctrl'   => true,
                'reduce_breadcrumb_up_to_level' => 1,
                'show_language_switcher_always' => false,  // Show even if there is only one alternative?
            ],

            // Type:
            // null or...
            // [
            //     'reduce_by_default'            => false,
            //     'reduce_toggleable_with_ctrl'  => true,
            // ]
            'login' => null
        ];
    }
?>