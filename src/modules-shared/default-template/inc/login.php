<?
    $render_generic = function(Module $template, Target $root_target, array $params)
    {
        // Forward by setting sub_template := template
        $template->render_component_generic_with_sub_template($template, 'login', $root_target, $params);
    };

    $render_generic_with_sub_template = function(Module $template, Module $sub_template, Target $root_target, array $params)
    {
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Login − <?= $root_target->all_titles[0] ?></title>
<?
    load_defs_from_script(__DIR__ . '/aux.php')['print_favicons']($template);
?>
    </head>
    <body style="background-color:rgb(52, 58, 64); color:rgb(33, 37, 41);">
        <div style="width: 300px; margin:20px auto; border-radius:4px; padding:20px; background-color:white;">
            <form method="post" style="margin:0;">
<?
    if ($params['logout_successful']) {
?>
                <p style="color:green;">
                    Logout erfolgreich.
                </p>
<? 
    }
    if ($params['password_incorrect']) {
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
<? }; ?>