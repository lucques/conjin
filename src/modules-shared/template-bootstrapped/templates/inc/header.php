<?
    $render = function(array $placeholders) {
        $module_doc_extensions_active = function_exists('doc_extensions_get_head_elements');
?>
<!doctype html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= $placeholders['title_for_head'] ?></title>
        <link href="<?= $placeholders['css_screen_url'] ?>" rel="stylesheet" media="screen">
        <link href="<?= $placeholders['css_print_url'] ?>" rel="stylesheet" media="print">
<?
        if ($module_doc_extensions_active) {
            array_map(function($element) {
                echo '        ' . str_replace("\n", "\n        ", $element) . "\n";
            }, doc_extensions_get_head_elements());
        }
?>
    </head>
    <body>
<?
        if ($module_doc_extensions_active) {
            echo implode("\n", doc_extensions_get_body_top_elements());
        }
    };
?>