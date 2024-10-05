<?
    $render_login = function(Module $template, Syslet $syslet, bool $logout_successful, bool $password_incorrect, mixed $openid_fail, array $openid_provider_names, array $placeholders_overrides = []) {
        
        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $title_for_head = 'Login';
        if ($template->config->get('title_for_head_contains_top_level')) {
            $title_for_head .= ' − ';
            $title_for_head .=
                $syslet->has_activated_module('title')
                ? get_top_level_plain_title()
                : 'Unbenannt';
        }


        ///////////////////////////////////////////////
        // Prepare sub-template `template-navigable` //
        ///////////////////////////////////////////////

        $placeholders_for_subtemplate_default = $template->load_def_from_script_and_call(
            'templates/inc/default_placeholders_for_subtemplate.php',
            'default_placeholders',
            template:       $template,
            title_for_head: $title_for_head,
            content_width:  'sm',
        );
        $placeholders_for_subtemplate = array_merge($placeholders_for_subtemplate_default, $placeholders_overrides);

        $sub_template = $syslet->activated_modules['template-navigable'];


        ////////////
        // Render //
        ////////////

        // Render using sub-template
        $sub_template->render_login($syslet, $logout_successful, $password_incorrect, $openid_fail, $openid_provider_names, $placeholders_for_subtemplate);
    };
?>