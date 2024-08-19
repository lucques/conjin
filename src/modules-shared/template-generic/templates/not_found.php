<?
    $render_not_found = function(Module $template, Syslet $syslet, ?array $target_ids, $placeholders_overrides = []) {
        // Placeholders: Merge default values with overrides
        $placeholders_default = [
            'title_for_head' => 'Seite nicht gefunden',
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
            Die Seite konnte leider nicht gefunden werden.
        </div>
    </body>
</html>
<?
    };
?>