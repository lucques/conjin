<?
    $render = function(Module $template, array $placeholders) {
        $module_doc_extensions_active = function_exists('doc_extensions_get_head_elements');
        $module_print_mode_active     = function_exists('is_print_mode_on');
        $module_footnotes_active      = function_exists('footnotes_any');
        $module_references_active     = function_exists('ref_any');

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
        if ($module_print_mode_active) { page_end(); };
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