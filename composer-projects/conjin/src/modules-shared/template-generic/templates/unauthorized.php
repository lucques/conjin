<?
    $render_not_found = function(ModuleLocation $template_self, Module $template, Syslet $syslet, ?array $target_ids, $placeholders_overrides = []) {

        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $title_for_head = 'Nicht berechtigt';


        ///////////////////////
        // Make placeholders //
        ///////////////////////
        
        $placeholders_default = $template_self->load_def_from_script_and_call('templates/inc/default_placeholders.php', 'default_placeholders', $title_for_head);
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);

        
        ////////////
        // Render //
        ////////////
        
        $module_doc_extensions_active = $syslet->has_activated_module('doc-extensions');
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
<?
        $body_classes =
            $module_doc_extensions_active
                ? doc_extensions_get_body_classes()
                : [];

        $body_classes_html = count($body_classes) > 0
            ? ' class="' . implode(' ', $body_classes) . '"'
            : '';
?>
    <body<?= $body_classes_html ?> style="background-color:rgb(52, 58, 64); color:rgb(33, 37, 41);">
<?
        if ($module_doc_extensions_active) {
            echo implode("\n", doc_extensions_get_body_top_elements());
        }
?>
        <div style="width: 300px; margin:20px auto; border-radius:4px; padding:20px; background-color:white;">
            <form method="post" action="<?= htmlspecialchars(auth_get_logout_url(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="logout_token" value="<?= htmlspecialchars(auth_issue_submission_token('auth:logout'), ENT_QUOTES, 'UTF-8') ?>">
                <p>
                    Sie haben leider nicht die Berechtigung, um die Seite zu sehen. Sie können sich
                    <button type="submit" style="background: none; border: 0; padding: 0; color: inherit; text-decoration: underline; font: inherit; cursor: pointer;">ausloggen</button>
                    und mit einem anderen Benutzer anmelden, der die Berechtigung hat.
                </p>
            </form>
        </div>
<?
        if ($module_doc_extensions_active) {
?>
        <script>
<?
            array_map(function($extension) {
                echo '            ' . str_replace("\n", "\n            ", $extension) . "\n";
            }, doc_extensions_get_js_after_dom_setup());
?>
        </script>
<?
        }

        if ($module_doc_extensions_active) {
            echo implode("\n", doc_extensions_get_body_bottom_elements());
        }
?>
<? if ($syslet->has_activated_module('page-prep')) { ?>
        <script>pagePrep.closeRegistration();</script>
<? } ?>
    </body>
</html>
<?
    };
?>
