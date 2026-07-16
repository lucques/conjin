<?
    $render_not_found = function(ModuleLocation $template_self, Module $template, Syslet $syslet, ?array $target_ids, $placeholders_overrides = []) {       
        
        ///////////////////////////
        // Sub-template: Prepare //
        ///////////////////////////
        
        $sub_template = new ModuleLocation('template-navigable');
        $placeholders_for_subtemplate_default = $template_self->load_def_from_script_and_call(
            'templates/inc/overridden_placeholders_for_subtemplate.php',
            'default_placeholders',
            template:       $template,
            sub_template:   $sub_template,
            content_width:  $template->config->get('content_width'),
        );
        $placeholders_for_subtemplate = array_merge($placeholders_for_subtemplate_default, $placeholders_overrides);


        //////////////////////////
        // Sub-template: Render //
        //////////////////////////

        $sub_template->render_not_found_with_provided_template($template, $syslet, $target_ids, $placeholders_for_subtemplate);
    };
?>