<?
    $render_login = function(Module $template, Syslet $syslet, bool $logout_successful, bool $password_incorrect, mixed $openid_fail, array $openid_provider_names, array $placeholders_overrides = []) {

        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $title_for_head = 'Login';


        ///////////////////////
        // Make placeholders //
        ///////////////////////
        
        $placeholders_default = $template->load_def_from_script_and_call('templates/inc/default_placeholders.php', 'default_placeholders', $title_for_head);
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);

        
        ////////////
        // Render //
        ////////////

        $module_doc_extensions_active = function_exists('doc_extensions_get_head_elements');
?>
<!doctype html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <title><?= $placeholders['title_for_head'] ?></title>
<?
        if ($module_doc_extensions_active) {
            array_map(function($extension) {
                echo '        ' . str_replace("\n", "\n        ", $extension) . "\n";
            }, doc_extensions_get_head_elements());
        }
?>
    </head>
    <body style="background-color:rgb(52, 58, 64); color:rgb(33, 37, 41);">
<?
    if ($module_doc_extensions_active) {
        echo implode("\n", doc_extensions_get_body_top_elements());
    }

    if ($logout_successful) {
?>
        <div style="width: 300px; margin:20px auto; border-radius:4px; padding:20px; background-color:white;">
            <p style="color:green; margin: 0;">
                Logout erfolgreich.
            </p>
        </div>
<? 
    }
?>
        <div style="width: 300px; margin:20px auto; border-radius:4px; padding:20px; background-color:white;">
            <form method="post" style="margin:0;">
<?
    if ($password_incorrect) {
?>
                <p style="color:red; margin-top:0;">
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
    foreach ($openid_provider_names as $provider_name) {
?>
        <div style="width: 300px; margin:20px auto; border-radius:4px; padding:20px; background-color:white;">
            <form method="get" style="margin:0;" action="<?= auth_get_login_url_for_openid($provider_name) ?>">
                <input type="hidden" name="open_id_provider" value="<?= htmlspecialchars($provider_name) ?>" />
<?
    // Submit potential redirect URL as form field
    if (isset($_GET['redirect'])) {
?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect']) ?>" />
<?
    }

    // If there is an OpenID failure, show the error message
    if ($openid_fail !== null && $openid_fail['providerName'] === $provider_name) {
?>
                <p style="color:red; margin-top:0;">
                    <?= htmlspecialchars($openid_fail['message']) ?>
                </p>
<? 
    }
?>
                <div style="display:flex; justify-content:space-between;">
                    <label style="font-weight: bold; margin-right:10px;">Login mit <?= htmlspecialchars($provider_name) ?>:</label>
                    <input type="submit" name="submit" value="Login">
                </div>
            </form>
        </div>
<?
    }

    if ($module_doc_extensions_active) {
        echo implode("\n", doc_extensions_get_body_bottom_elements());
    }
?>
    </body>
</html>
<?
    };
?>