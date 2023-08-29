<?
    /*
        This module offers a button on the page that activates the so-called
        "print mode". This is a rendering mode catered specifically for
        printing out the web page, typically the page-to-be-printed will be
        shown on the browser screen already.

        # Terminology
        - Mode *enabled*: Offers the button
        - Mode *requested*: User requests mode via query string in  
          URL (e.g. clicked on the button)
        - Mode *active*: Enabled and requested, or "alway-active" flag set

        # Features
        - Option to enable/disable mode (default: disabled)
        - Option for size (default: A4)
        - Option for orientation (default: portrait)
        - Functions:
            - `css_print_mode_start` and `css_print_mode_end`:
                - Work like `css_start` and `css_end` but only when print mode
                  is activated
            - `page_start` and `page_end` and `page_break` functions
                - Used to separate pages, allow orientation change on-the-fly.
            - ...

        # Design and Layout
        - Main styles must be provided by the template (both screen and print
          media)
        - The rendering as pages is provided by the `paper-css` module, which
          is based on markup containing so-called "sheets". The "sheet"
          containers are only added when print mode is active. 
            - DOM when "print mode" is activated
                ```
                <div class="sheet A5 landscape">
                    Content on first page
                </div>
                <div class="sheet">
                    Content on second page
                </div>
                ```
    */


    //////////////////
    // Dependencies //
    //////////////////

    $paperCssModule = new ModuleId('paper-css', is_shared: true, is_external: true);


    //////////
    // Init //
    //////////

    $init_processing = function(Module $module, Target $target) use ($paperCssModule) {
        $GLOBALS['print_mode_enabled']       = $module->config->get_or_default(['enabled'], false);
        $GLOBALS['print_mode_size']          = $module->config->get_or_default(['size'], 'A4');
        $GLOBALS['print_mode_is_portrait']   = $module->config->get_or_default(['is_portrait'], true);
        $GLOBALS['print_mode_requested']     = isset($_GET['print']) && $_GET['print'] === '1';
        $GLOBALS['print_mode_always_active'] = $module->config->get_or_default(['always_active'], false);
        
        if (is_print_mode_active()) {
            // Head imports
            doc_extensions_add_head_import('<link href="' . $paperCssModule->get_url() . '/res/paper_extended.css" rel="stylesheet" media="screen">');

            // Body classes
            doc_extensions_add_body_class($GLOBALS['print_mode_size']);
            if (!$GLOBALS['print_mode_is_portrait']) {
                doc_extensions_add_body_class('landscape');
            }
        }
    };
    

    //////////////////////////////////////
    // Functions offered for PROCESSING //
    //////////////////////////////////////

    function is_print_mode_enabled(): bool {
        return $GLOBALS['print_mode_enabled'];
    }

    function is_print_mode_active(): bool {
        return $GLOBALS['print_mode_always_active'] || ($GLOBALS['print_mode_enabled'] && $GLOBALS['print_mode_requested']);
    }

    function print_mode_get_query_string_to_activate(): array {
        return ['print' => true];
    }

    function print_mode_get_query_string_to_deactivate(): array {
        return ['print' => null];
    }

    function css_print_mode_start() {
        ob_start();
        if (is_print_mode_active()) {
            echo '<style>';
        }
    }

    function css_print_mode_end() {
        if (is_print_mode_active()) {
            echo '</style>';
            doc_extensions_add_css(ob_get_clean());
        }
        else {
            ob_end_clean();  // Throw away
        }
    }

    // If the template has built-in support for this module, then there will be
    // an automatic `page_start()` call in the header part of the template and
    // an automatic `page_end()` call in the footer part of the template.
    // All that is left to do to introduce a new page is therefore a call to
    // `page_break()` in the `index.php`.

    function page_start($orientation = null) {
        assert($orientation === null || $orientation === 'portrait' || $orientation === 'landscape');
        if (is_print_mode_active()) {
            if ($orientation === null) {
                echo '<div class="sheet ' . $GLOBALS['print_mode_size'] . ' ' . ($GLOBALS['print_mode_is_portrait'] ? 'portrait' : 'landscape') . '">';
            }
            elseif ($orientation === 'portrait') {
                echo '<div class="sheet portrait' . $GLOBALS['print_mode_size'] . '">';
            }
            else {
                echo '<div class="sheet landscape' . $GLOBALS['print_mode_size'] . '">';
            }
        }
    }

    function page_end() {
        if (is_print_mode_active()) {
            echo '</div>';
        }
    }
    
    function page_break($next_orientation = null) {
        page_end();
        page_start(orientation: $next_orientation);
    }
?>