<?
    $init_processing_target = function(Module $module, Target $target) {
        $GLOBALS['doc_extensions_head_elements']      = [];
        $GLOBALS['doc_extensions_body_classes']       = [];
        $GLOBALS['doc_extensions_body_top_elements']  = [];
        $GLOBALS['doc_extensions_js_after_dom_setup'] = [];
    };


    /////////////////////////////////
    // Used by MODULES and CONTENT //
    /////////////////////////////////

    function doc_extensions_add_head_element(string $line): void {
        $GLOBALS['doc_extensions_head_elements'][] = $line;
    }
    
    function doc_extensions_add_body_class(string $class): void {
        $GLOBALS['doc_extensions_body_classes'][] = $class;
    }

    function doc_extensions_add_body_top_element(string $line): void {
        $GLOBALS['doc_extensions_body_top_elements'][] = $line;
    }

    function doc_extensions_add_js_after_dom_setup(string $line): void {
        $GLOBALS['doc_extensions_js_after_dom_setup'][] = $line;
    }


    ///////////////
    // Shortcuts //
    ///////////////

    function css_start(): void {
        ob_start();
        echo "<style>\n";
    }

    function css_end(): void {
        echo "</style>";
        doc_extensions_add_head_element(ob_get_clean());
    }

    function head_start(): void {
        ob_start();
    }

    function head_end(): void {
        doc_extensions_add_head_element(ob_get_clean());
    }

    function body_top_element_start(): void {
        ob_start();
    }

    function body_top_element_end(): void {
        doc_extensions_add_body_top_element(ob_get_clean());
    }

    function js_after_dom_setup_start(): void {
        ob_start();
    }

    function js_after_dom_setup_end(): void {
        doc_extensions_add_js_after_dom_setup(ob_get_clean());
    }


    //////////////////////
    // Used by TEMPLATE //
    //////////////////////

    function doc_extensions_get_head_elements(): array {
        return $GLOBALS['doc_extensions_head_elements'];
    }

    function doc_extensions_get_body_classes(): array {
        return $GLOBALS['doc_extensions_body_classes'];
    }

    function doc_extensions_get_body_top_elements(): array {
        return $GLOBALS['doc_extensions_body_top_elements'];
    }

    function doc_extensions_get_js_after_dom_setup(): array {
        return $GLOBALS['doc_extensions_js_after_dom_setup'];
    }
?>