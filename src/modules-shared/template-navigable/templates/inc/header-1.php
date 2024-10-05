<?
    $render = function(Module $template, ?array $target_ids, array $placeholders) {
        $module_doc_extensions_active = function_exists('doc_extensions_get_head_elements');
        $module_print_mode_active     = function_exists('is_print_mode_on');
?>
<!doctype html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= $placeholders['title_for_head'] ?></title>
<?
        if (!$module_print_mode_active || !is_print_mode_on()) {
?>
        <link href="<?= $placeholders['css_screen_url'] ?>" rel="stylesheet" media="screen">
        <link href="<?= $placeholders['css_print_url'] ?>" rel="stylesheet" media="print">
<?
        }
        else {
            // This is only the template-specific part of the print mode CSS.
            // Another part is included by the "print-mode" module.
?>
        <link href="<?= $placeholders['css_print_mode_screen_url'] ?>" rel="stylesheet" media="screen">
        <link href="<?= $placeholders['css_print_mode_print_url'] ?>" rel="stylesheet" media="print">

<?
        }
?>
        <script src="<?= $placeholders['library_js_url'] ?>"></script>
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
        $body_classes = [...$body_classes, ...$placeholders['body_classes']];

        $body_classes_html = count($body_classes) > 0
            ? ' class="' . implode(' ', $body_classes) . '"'
            : '';
?>
    <body<?= $body_classes_html ?><?= $target_ids !== null ? ' data-target-ids="' . implode(' ', $target_ids) . '"' : '' ?>>
<?
        if ($module_doc_extensions_active) {
            echo implode("\n", doc_extensions_get_body_top_elements());
        }
    };
?>