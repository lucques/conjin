<?
    $render_target = function(ModuleLocation $template_self, Module $template, Target $target, string $content, array $placeholders_overrides = []) {

        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $title_for_h1 =
            $target->has_activated_module('title')
            ? implode(' » ', get_html_titles(true))
            : ($target->id ?? 'Unbenannt');


        ///////////////////////
        // Make placeholders //
        ///////////////////////

        $placeholders_default = $template_self->load_def_from_script_and_call('templates/inc/default_placeholders.php', 'default_placeholders', $template, $title_for_h1);
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);


        ////////////////////
        // Render content //
        ////////////////////

        ob_start();
        if ($target->has_activated_module('title') && $target->activated_modules['title']->config->get('is_part_of_content')) {
?>
                <h1><?= $placeholders['title_for_h1'] ?></h1>
<?
        }
        $content = ob_get_clean() . $content;


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

        $sub_template->render_target_with_provided_template($template, $target, $content, $placeholders_for_subtemplate);
    };
?>