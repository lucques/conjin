<?
    $process_target = $process_syslet = function(Module $module, Processable $processable) {
        $url = htmlspecialchars($module->get_url(), ENT_QUOTES, 'UTF-8');
        doc_extensions_add_head_element('<link rel="stylesheet" href="' . $url . '/res/jsoneditor.min.css" />');
        doc_extensions_add_head_element('<script src="' . $url . '/res/jsoneditor.min.js"></script>');
    };

    function json_editor_print_inline(string $json): void {
        // Validate without re-encoding the data: preserve object/array distinctions
        // and the original number tokens for the browser's JSON parser.
        json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        $encoded = json_encode($json, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
        static $next_id = 0;
        $id = 'conjin-json-editor-' . ++$next_id;

        echo '<div id="' . $id . '" class="conjin-json-editor" style="width: 100%; height: 400px;"></div>';
        doc_extensions_add_js_after_dom_setup(<<<JS
            new JSONEditor(document.getElementById('$id'), { mode: 'view' }, JSON.parse($encoded));
        JS);
    }

    function json_editor_print_file(string $path): void {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('JSON file is not readable: ' . $path);
        }
        $json = file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException('Could not read JSON file: ' . $path);
        }
        json_editor_print_inline($json);
    }
?>
