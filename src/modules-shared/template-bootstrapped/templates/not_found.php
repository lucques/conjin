<?
    $render_not_found = function(Module $template, Syslet $syslet, ?array $target_ids, array $placeholders_overrides = []) {

        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $title_for_head = 'Nicht gefunden';


        ///////////////////////
        // Make placeholders //
        ///////////////////////

        $placeholders_default = $template->load_def_from_script_and_call('templates/inc/default_placeholders.php', 'default_placeholders', $template, $title_for_head);
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);
        
        
        ////////////
        // Render //
        ////////////
        
        ob_start();
        $template->load_def_from_script_and_call('templates/inc/header.php', 'render', $placeholders);
?>
        <div class="container">
            <h1>
                Seite nicht gefunden
            </h1>
            <p>Die Seite konnte leider nicht gefunden werden.</p>
        </div>
<?
        $template->load_def_from_script_and_call('templates/inc/footer.php', 'render');
        ob_end_flush();
    };
?>