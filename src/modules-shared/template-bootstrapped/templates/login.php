<?
    $render_login = function(Module $template, Syslet $syslet, bool $logout_successful, bool $password_incorrect, array $placeholders_overrides = []) {

        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $placeholders_default = [
            'css_url'        => $template->get_css_url(),
            
            'title_for_head' => 'Login',
        ];
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);
        
        
        ////////////
        // Render //
        ////////////
        
        ob_start();
        $template->load_def_from_script_and_call('templates/inc/header.php', 'render', $placeholders);
?>
        <div class="container">
            <h1>
                Login
            </h1>
            <form method="post" style="width: 300px; ">
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
        </div>
<?
        $template->load_def_from_script_and_call('templates/inc/footer.php', 'render');
        ob_end_flush();
    };
?>