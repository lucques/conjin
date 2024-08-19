<?
    $render_target = function(Module $template, Target $target, string $content, array $placeholders_overrides = []) {

        //////////////////////
        // Determine titles //
        //////////////////////

        $title_for_head =
            $target->has_activated_module('title')
            ? implode(' » ', get_plain_titles(true))
            : ($target->id ?? 'Unbenannt');


        //////////////////////////
        // Prepare placeholders //
        //////////////////////////

        $placeholders_default = [
            'title_for_head' => $title_for_head,
        ];
        $placeholders = array_merge($placeholders_default, $placeholders_overrides);

        
        ////////////
        // Render //
        ////////////
?>
<!doctype html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <title><?= $placeholders['title_for_head'] ?></title>
<?
        if ($target->has_activated_module('doc-extensions')) {
            array_map(function($extension) {
                echo '        ' . str_replace("\n", "\n        ", $extension) . "\n";
            }, doc_extensions_get_head_elements());
        }
?>
    </head>
    <body>
<?
        if ($target->has_activated_module('doc-extensions')) {
            echo implode("\n", doc_extensions_get_body_top_elements());
        }
?>
<?= $content ?>
    </body>
</html>
<?
    };
?>