<?
    $render_target = function(ModuleLocation $template_self, Module $template, Target $target, string $content, array $placeholders_overrides = []) {

        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $title_for_head =
            $target->has_activated_module('title')
            ? ($target->has_parent()
                ? implode(' » ', get_plain_titles(true)) . ($template->config->get('title_for_head_contains_top_level') ? ' − ' . get_top_level_plain_title() : '')
                : get_top_level_plain_title())
            : ($target->id ?? 'Unbenannt');


        ///////////////////////
        // Make placeholders //
        ///////////////////////

        $placeholders_default = $template_self->load_def_from_script_and_call(
            'templates/inc/default_placeholders.php',
            'default_placeholders',
            $template,
            $title_for_head
        );
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);


        ////////////
        // Render //
        ////////////
        
        ob_start();
        $template_self->load_def_from_script_and_call('templates/inc/header-1.php', 'render', $template, $target->get_ids(), $placeholders);
        if ($template->config->get('sidebar') !== null) {
            $template_self->load_def_from_script_and_call('templates/inc/sidebar.php', 'render', $template, $placeholders);
        }
        $template_self->load_def_from_script_and_call('templates/inc/header-2.php', 'render', $template, $placeholders);
        echo $content;
        $template_self->load_def_from_script_and_call('templates/inc/footer.php', 'render', $template, $placeholders);
        ob_end_flush();
    };
?>