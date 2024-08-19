<?
    $render_login = function(Module $template, Syslet $syslet, bool $logout_successful, bool $password_incorrect, array $placeholders_overrides = []) {
        // Placeholders: Merge default values with overrides
        $placeholders_default = [
            'title_for_head' => 'Login',
        ];
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);

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
?>
        <div style="width: 300px; margin:20px auto; border-radius:4px; padding:20px; background-color:white;">
            <form method="post" style="margin:0;">
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
    </body>
</html>
<?
    };
?>