<?
    $init_processing_target = function(Module $module, Target $target) {
        doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/tau-prolog.js"></script>');
        doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/tau-prolog-interface.js"></script>');

        // doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/javascript.inc.js"></script>');
        // doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/klipse_plugin.js"></script>');
        // doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/pretty_format.js"></script>');
        // doc_extensions_add_head_element('<link rel="stylesheet" type="text/css" href="' . $module->get_url() . '/res/codemirror.css">');
        // doc_extensions_add_head_element('<link rel="stylesheet" type="text/css" href="' . $module->get_url() . '/res/prolog.css">');
        // doc_extensions_add_head_element("<script> window.klipse_settings = { selector_prolog_rules: '.language-prolog-rules', selector_prolog_query: '.language-prolog-query' }; </script>");

        // doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/editor.bundle.js"></script>');
        // doc_extensions_add_head_element('<link rel="stylesheet" type="text/css" href="https://storage.googleapis.com/app.klipse.tech/css/codemirror.css">');
        // doc_extensions_add_head_element('<link rel="stylesheet" type="text/css" href="https://storage.googleapis.com/app.klipse.tech/css/prolog.css">');
        // doc_extensions_add_head_element("<script> window.klipse_settings = {  selector_prolog_rules: '.language-prolog-rules', selector_prolog_query: '.language-prolog-query' }; </script>");
        // doc_extensions_add_head_element('<script src="https://storage.googleapis.com/app.klipse.tech/plugin_prod/js/klipse_plugin.min.js?v=7.7.1-a"></script>');
        // doc_extensions_add_head_element("<script>import {EditorView, minimalSetup} from \"codemirror\"; let editor = new EditorView({ extensions: minimalSetup, parent: document.body }) </script>");
    };
?>