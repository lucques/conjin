<?
    $render_login = function(Module $template, Syslet $syslet, bool $logout_successful, bool $password_incorrect, array $placeholders_overrides = []) {

        //////////////////////
        // Determine titles //
        //////////////////////

        $title_for_logo =
            function_exists('get_top_level_html_title')
            ? get_top_level_html_title()
            : 'Unbenannt';


        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $res_module = $template->config->get('res_module') === null
        ? $template
        : new ModuleLocation($template->config->get('res_module'));

        $placeholders_default = [
            'css_url'                         => $template->get_css_url(),
            'library_js_url'                  => $template->get_url() . '/res/js/library.js',
            'logo_url'                        => $res_module->get_url() . '/res/logo/logo.png',

            'title_for_logo'                  => $title_for_logo,
            'title_for_head'                  => 'Login',

            'use_img_as_logo'                 => $template->config->get('use_img_as_logo'),
            'link_logo_to_home'               => $template->config->get('link_logo_to_home'),
            'nav_show_top_level'              => $template->config->get('nav_show_top_level'),
            'nav_active_sidebar_by_default'   => $template->config->get('nav_active_sidebar_by_default'),
            'nav_reduce_by_default'           => $template->config->get('nav_reduce_by_default'),
            'nav_reduce_toggleable_with_ctrl' => $template->config->get('nav_reduce_toggleable_with_ctrl'),
        ];
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);

        
        ////////////
        // Render //
        ////////////

        ob_start();
        $template->load_def_from_script_and_call('templates/inc/header-1.php',  'render', $template, null, $placeholders);
        $template->load_def_from_script_and_call('templates/inc/sidebar.php',   'render', $template, $placeholders);
        $template->load_def_from_script_and_call('templates/inc/header-2.php',  'render', $template, $placeholders);
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