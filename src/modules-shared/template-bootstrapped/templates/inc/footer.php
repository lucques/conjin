<?
    $render = function() {
        $module_doc_extensions_active = function_exists('doc_extensions_get_head_elements');
?>
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