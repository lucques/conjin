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
        <form method="post" action="<?= htmlspecialchars(auth_get_logout_url(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="logout_token" value="<?= htmlspecialchars(auth_issue_submission_token('auth:logout'), ENT_QUOTES, 'UTF-8') ?>">
            <p>
                Sie haben leider nicht die Berechtigung, um die Seite zu sehen. Sie können sich
                <button type="submit" style="background: none; border: 0; padding: 0; color: inherit; text-decoration: underline; font: inherit; cursor: pointer;">ausloggen</button>
                und mit einem anderen Benutzer anmelden, der die Berechtigung hat.
            </p>
        </form>
<?
        $template_self->load_def_from_script_and_call('templates/inc/footer.php',  'render', $template, $syslet, $placeholders);
        ob_end_flush();
    };
?>
