<?
    $render_target = function(Module $template, Target $target, string $content, array $placeholders_overrides = []) {

        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $title_for_head =
            $target->has_activated_module('title')
            ? ($target->has_parent()
                ? implode(' » ', get_plain_titles(true)) . ($template->config->get('title_for_head_contains_top_level') ? ' − ' . get_top_level_plain_title() : '')
                : get_top_level_plain_title())
            : ($target->id ?? 'Unbenannt');

        $title_for_h1 =
            $target->has_activated_module('title')
            ? implode(' » ', get_html_titles(true))
            : ($target->id ?? 'Unbenannt');


        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $placeholders_default = $template->load_def_from_script_and_call('templates/inc/default_placeholders.php', 'default_placeholders', $template, $title_for_h1);
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);


        ///////////////////////////////////////////////
        // Prepare sub-template `template-navigable` //
        ///////////////////////////////////////////////
        
        $placeholders_for_subtemplate_default = $template->load_def_from_script_and_call(
            'templates/inc/default_placeholders_for_subtemplate.php',
            'default_placeholders',
            template:       $template,
            title_for_head: $title_for_head,
            content_width:  $template->config->get('content_width'),
        );
        $placeholders_for_subtemplate = array_merge($placeholders_for_subtemplate_default, $placeholders_overrides);

        $sub_template = $target->activated_modules['template-navigable'];


        ////////////
        // Render //
        ////////////

        // Render content
        ob_start();
        if ($target->has_activated_module('title') && $target->activated_modules['title']->config->get('is_part_of_content')) {
?>
                <h1><?= $placeholders['title_for_h1'] ?></h1>
<?
        }
        $content = ob_get_clean() . $content;

        // Render using sub-template
        $sub_template->render_target($target, $content, $placeholders_for_subtemplate);
    };
?>