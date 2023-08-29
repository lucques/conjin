<?
    /*
        This module offers to extend the HTML page in three ways.
        1) Collect import statements for `<head>` from other modules
        2) Let content files (`index.php`) add custom CSS to `<head>`
        3) Collect classes for `<body>`
    */

    $init_processing = function(Module $module, Target $target) {
        $GLOBALS['doc_extensions_head_imports'] = [];
        $GLOBALS['doc_extensions_css'] = [];
        $GLOBALS['doc_extensions_body_classes'] = [];
    };


    ///////////////////////////////////////
    // Used by other MODULES and CONTENT //
    ///////////////////////////////////////

    function doc_extensions_add_head_import(string $line): void {
        $GLOBALS['doc_extensions_head_imports'][] = $line;
    }
    
    function doc_extensions_add_css(string $ext): void {
        $GLOBALS['doc_extensions_css'][] = $ext;
    }

    function doc_extensions_add_body_class(string $class): void {
        $GLOBALS['doc_extensions_body_classes'][] = $class;
    }


    //////////////////////////
    // Used by CONTENT file //
    //////////////////////////

    function css_start(): void {
        ob_start();
        echo '<style>';
    }

    function css_end(): void {
        echo '</style>';
        doc_extensions_add_css(ob_get_clean());
    }


    ///////////////////////////
    // Used by TEMPLATE file //
    ///////////////////////////

    function doc_extensions_get_head_extensions(): array {
        return array_merge($GLOBALS['doc_extensions_css'], $GLOBALS['doc_extensions_head_imports']);
    }

    function doc_extensions_get_body_classes(): array {
        return $GLOBALS['doc_extensions_body_classes'];
    }
?>