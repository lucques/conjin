<?
    $render_target = function(ModuleLocation $template_self, Module $template, Target $target, string $content, array $placeholders_overrides = []) {

        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $title_for_head =
            $target->has_activated_module('title')
            ? implode(' » ', get_plain_titles(true))
            : ($target->id ?? 'Unbenannt');


        ///////////////////////
        // Make placeholders //
        ///////////////////////
        
        $placeholders_default = $template_self->load_def_from_script_and_call('templates/inc/default_placeholders.php', 'default_placeholders', $title_for_head);
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);

        
        ////////////
        // Render //
        ////////////

        $module_doc_extensions_active = $target->has_activated_module('doc-extensions');
?>
<!doctype html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= $placeholders['title_for_head'] ?></title>
<?
        if ($module_doc_extensions_active) {
            array_map(function($extension) {
                echo '        ' . str_replace("\n", "\n        ", $extension) . "\n";
            }, doc_extensions_get_head_elements());
        }
?>
    </head>
    <body>
<?
        if ($module_doc_extensions_active) {
            echo implode("\n", doc_extensions_get_body_top_elements());
        }
?>
<?= $content ?>
<?
        if ($module_doc_extensions_active) {
            echo implode("\n", doc_extensions_get_body_bottom_elements());
        }
?>
    </body>
</html>
<?
    };
?>