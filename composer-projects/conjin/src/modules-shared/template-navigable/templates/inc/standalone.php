<?
    $render = function(Module $template, Target $target, string $content, string $title_for_head, array $placeholders) {
        $module_doc_extensions_active = function_exists('doc_extensions_get_head_elements');
        $module_localization_active   = function_exists('get_language');
        $module_footnotes_active      = function_exists('footnotes_any');
        $module_references_active     = function_exists('ref_any');
?>
<!doctype html>
<html lang="<?= $module_localization_active ? get_language() : 'en' ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= htmlspecialchars($title_for_head) ?></title>
        <link href="<?= $placeholders['css_print_mode_url'] ?>" rel="stylesheet">
<?
        foreach (print_mode_get_css_elements() as $css_element) {
            echo '        ' . str_replace("\n", "\n        ", $css_element) . "\n";
        }
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
        $body_classes_html =
            count($body_classes) > 0
            ? ' class="' . implode(' ', $body_classes) . '"'
            : '';
?>
    <body<?= $body_classes_html ?> data-target-ids="<?= implode(' ', $target->get_ids()) ?>">
<?
        if ($module_doc_extensions_active) {
            echo implode("\n", doc_extensions_get_body_top_elements());
        }
?>
        <div id="content">
            <main>
<? page_start(); ?>
<?= $content ?>
<?
        if ($module_footnotes_active && footnotes_any()) {
?>
                <section>
<? footnotes_print_list(); ?>
                </section>
<?
        }
        if ($module_references_active && ref_any()) {
?>
                <section>
<? ref_print_list(); ?>
                </section>
<?
        }
        page_end();
?>
            </main>
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
            echo implode("\n", doc_extensions_get_body_bottom_elements());
        }
?>
    </body>
</html>
<?
    };
?>
