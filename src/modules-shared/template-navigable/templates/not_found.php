<?
    $render_not_found = function(Module $template, Syslet $syslet, ?array $target_ids, $placeholders_overrides = []) {

        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $title_for_head = 'Nicht gefunden';
        if ($syslet->has_activated_module('title') && $template->config->get('title_for_head_contains_top_level')) {
            $title_for_head .= ' − ' . get_top_level_plain_title();
        }


        ///////////////////////
        // Make placeholders //
        ///////////////////////

        $placeholders_default = $template->load_def_from_script_and_call('templates/inc/default_placeholders.php', 'default_placeholders', $template, $title_for_head);
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);

        
        ////////////
        // Render //
        ////////////

        ob_start();
        $template->load_def_from_script_and_call('templates/inc/header-1.php', 'render', $template, $target_ids, $placeholders);
        if ($template->config->get('nav_enabled')) {
            $template->load_def_from_script_and_call('templates/inc/sidebar.php', 'render', $template, $placeholders);
        }
        $template->load_def_from_script_and_call('templates/inc/header-2.php', 'render', $template, $placeholders);
?>
        <h1>
            Seite nicht gefunden
        </h1>
        <p>
            Die Seite konnte leider nicht gefunden werden.
        </p>
<?
        $template->load_def_from_script_and_call('templates/inc/footer.php',  'render', $template, $placeholders);
        ob_end_flush();
    };
?>