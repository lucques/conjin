<?
    $render_login = function(ModuleLocation $template_self, Module $template, Syslet $syslet, bool $logout_successful, bool $password_incorrect, mixed $openid_fail, array $openid_provider_names, array $placeholders_overrides = []) {

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

        $placeholders_default = $template_self->load_def_from_script_and_call('templates/inc/default_placeholders.php', 'default_placeholders', $template, $title_for_head);
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);


        ///////////////////////////////////////////////////
        // Use doc-extensions to add a class to the body //
        ///////////////////////////////////////////////////
        
        // Used by CSS for reducing the login form
        if (count($openid_provider_names) > 0) {
            doc_extensions_add_body_class('multiple-login-providers');
        }


        ////////////
        // Render //
        ////////////

        ob_start();
        $template_self->load_def_from_script_and_call('templates/inc/header-1.php', 'render', $template, null, $placeholders);
        if ($template->config->get('sidebar') !== null) {
            $template_self->load_def_from_script_and_call('templates/inc/sidebar.php', 'render', $template, $placeholders);
        }
        $template_self->load_def_from_script_and_call('templates/inc/header-2.php', 'render', $template, $placeholders);

        if ($logout_successful) {
?>
        <div class="alert alert-success">
            Logout erfolgreich.
        </div>
<? 
        }
?>
        <h1>
            Login
        </h1>
        <div class="card ppp" id="static-login-form">
            <div class="card-header">
                Login mit Passwort
            </div>
            <div class="card-body">
<?
        if ($password_incorrect) {
?>
                <div class="alert alert-danger">
                    Passwort ist falsch.
                </div>
<? 
        }
?>
                <form method="post" style="width: 300px; margin-left:auto; margin-right: auto;">
                    <div style="display:flex; justify-content:space-between;">
                        <label for="password" style="font-weight: bold; margin-right:10px;">Passwort:</label>
                        <input type="password" id="password" name="password" autofocus>
                    </div>
                    <div style="display:flex; justify-content:flex-end; margin-top:20px;">
                        <input type="submit" name="submit" value="Login" class="btn btn-primary">
                    </div>           
                </form>
            </div>
        </div>
<?
        foreach ($openid_provider_names as $provider_name) {
?>
        <div class="card ppp">
            <div class="card-header">
                Login mit <?= htmlspecialchars($provider_name) ?>
            </div>
            <div class="card-body">
<?
            // If there is an OpenID failure, show the error message
            if ($openid_fail !== null && $openid_fail['providerName'] === $provider_name) {
?>
                <div class="alert alert-danger">
                    Fehler: <?= htmlspecialchars($openid_fail['message']) ?>
                </div>
<? 
            }
?>
                <form method="get" style="margin:0;" action="<?= auth_get_login_url_for_openid($provider_name) ?>">
                    <input type="hidden" name="open_id_provider" value="<?= htmlspecialchars($provider_name) ?>" />
<?
            // Submit potential redirect URL as form field
            if (isset($_GET['redirect'])) {
?>
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect']) ?>" />
<?
            }
?>
                    <div style="display:flex; justify-content:center;">
                        <input type="submit" name="submit" value="Login" class="btn btn-primary">
                    </div>
                </form>
            </div>
        </div>
<?
        }
        // Setup ctrl key to reduce login
        if ($template->config->get('login') !== null && $template->config->get('login', 'reduce_toggleable_with_ctrl')) {
            doc_extensions_add_js_after_dom_setup("document.querySelector('#content').addEventListener('click', event => { if (event.ctrlKey) { dtToggle('reduced-login'); } });");
        }

        $template_self->load_def_from_script_and_call('templates/inc/footer.php',  'render', $template, $placeholders);
        ob_end_flush();
    };
?>