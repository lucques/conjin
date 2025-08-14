<?
    $init_processing = function(Module $module) {
        // Add JS library
        doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/js/library.js"></script>');

        // Establish each toggle
        foreach ($module->config->get('toggles') as $toggle) {
            // Initialize CSS body class from memory or by default value
            $condition = 
                $toggle['on_by_default'] ?
                "localStorage.getItem('dynamic-toggle-" . $toggle['css_class'] . "') !== 'false'" :
                "localStorage.getItem('dynamic-toggle-" . $toggle['css_class'] . "') === 'true'";
            $script = 'if (' . $condition . ') { document.querySelector("body").classList.add("' .  $toggle['css_class'] . '"); }';
            doc_extensions_add_js_after_dom_setup($script);

            // Execute according to the initialized CSS body class
            doc_extensions_add_js_after_dom_setup('dtSetup("' . $toggle['css_class'] . '");');
        }
    };
?>