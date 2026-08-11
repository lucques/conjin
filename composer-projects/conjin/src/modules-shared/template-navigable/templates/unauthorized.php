<?
    $render_unauthorized = function(ModuleLocation $template_self, Module $template, Syslet $syslet, ?array $target_ids, $placeholders_overrides = []) {

        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $title_for_head = 'Nicht berechtigt';
        if ($syslet->has_activated_module('title') && $template->config->get('title_for_head_contains_top_level')) {
            $title_for_head .= ' − ' . get_top_level_plain_title();
        }


        ///////////////////////
        // Make placeholders //
        ///////////////////////

        $placeholders_default = $template_self->load_def_from_script_and_call('templates/inc/default_placeholders.php', 'default_placeholders', $template, $syslet, $title_for_head);
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);

        
        ////////////
        // Render //
        ////////////

        ob_start();
        $template_self->load_def_from_script_and_call('templates/inc/header-1.php', 'render', $template, $syslet, $target_ids, $placeholders);
        if ($template->config->get('sidebar') !== null) {
            $template_self->load_def_from_script_and_call('templates/inc/sidebar.php', 'render', $template, $syslet, $placeholders);
        }
        $template_self->load_def_from_script_and_call('templates/inc/header-2.php', 'render', $template, $placeholders);
?>
        <h1>
            Fehlende Berechtigung
        </h1>
        <p>
            Sie haben leider nicht die Berechtigung, um die Seite zu sehen. Sie können sich <a href="<?= auth_get_logout_url() ?>">auszuloggen</a> und mit einem anderen Benutzer anzumelden, der die Berechtigung hat.
        </p>
<?
        $template_self->load_def_from_script_and_call('templates/inc/footer.php',  'render', $template, $syslet, $placeholders);
        ob_end_flush();
    };
?>
