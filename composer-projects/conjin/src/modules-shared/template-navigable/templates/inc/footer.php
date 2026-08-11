<?
    $render = function(Module $template, Processable $processable, array $placeholders) {
        $module_doc_extensions_active = $processable->has_activated_module('doc-extensions');
        $module_footnotes_active      = $processable->has_activated_module('footnotes');
        $module_references_active     = $processable->has_activated_module('references');

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
?>
            </main>
        </div>
        <script>
            setupCommon();
<?
        if ($template->config->get('sidebar') !== null) {
?>
            setupSidebar();
<?
        }
        if ($module_doc_extensions_active) {
            array_map(function($extension) {
                echo '            ' . str_replace("\n", "\n            ", $extension) . "\n";
            }, doc_extensions_get_js_after_dom_setup());
        }
?>
        </script>
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
