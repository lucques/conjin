<?
    $render_login = function(Module $template, Syslet $syslet, bool $logout_successful, bool $password_incorrect, mixed $openid_fail, array $openid_provider_names, array $placeholders_overrides = []) {

        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $title_for_head = 'Login';
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
        $template->load_def_from_script_and_call('templates/inc/header-1.php', 'render', $template, null, $placeholders);
        if ($template->config->get('nav_enabled')) {
            $template->load_def_from_script_and_call('templates/inc/sidebar.php', 'render', $template, $placeholders);
        }
        $template->load_def_from_script_and_call('templates/inc/header-2.php', 'render', $template, $placeholders);
?>
        <h1>
            Login
        </h1>
        <form method="post" style="width: 300px; margin-left:auto; margin-right: auto;">
<?
    if ($logout_successful) {
?>
            <p style="color:green;">
                Logout erfolgreich.
            </p>
<? 
    }
    if ($password_incorrect) {
?>
            <p style="color:red;">
                Passwort ist falsch.
            </p>
<? 
    }
?>     
            <div style="display:flex; justify-content:space-between;">
                <label for="password" style="font-weight: bold; margin-right:10px;">Passwort:</label>
                <input type="password" id="password" name="password" autofocus>
            </div>
            <div style="display:flex; justify-content:flex-end; margin-top:20px;">
                <input type="submit" name="submit" value="Login">
            </div>           
        </form>
<?
        $template->load_def_from_script_and_call('templates/inc/footer.php',  'render', $template, $placeholders);
        ob_end_flush();
    };
?>