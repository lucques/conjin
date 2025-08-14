<?
    $default_config = function(): array {
        return [

            ///////////////////////////////
            // Options from sub template //
            ///////////////////////////////

            'title_for_head_contains_top_level' => true,
            'res_module'                        => null,

            'sidebar' => [
                'use_img_as_logo'               => false,
                'link_logo_to_home'             => true,
                'active_by_default'             => true,
                'reduce_by_default'             => true,
                'reduce_toggleable_with_ctrl'   => true,
                'reduce_breadcrumb_up_to_level' => 1,
            ],

            'login' => null,


            ////////////////////////
            // Additional options //
            ////////////////////////

            // Additional options for this template
            'institution'                     => 'Unbenannt',
            'class'                           => 'Unbenannt',
            
            // Printed only if it cannot be determined from target id
            'date_default'                    => 'Unbekannt',

            'title'                           => 'Unbenannt'
        ];
    }
?>