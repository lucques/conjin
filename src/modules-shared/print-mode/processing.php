<?
    //////////////////
    // Dependencies //
    //////////////////

    $pagedJsModule = new ModuleLocation('paged-js');


    //////////
    // Init //
    //////////

    $init_processing_target = function(Module $module, Target $target) use ($pagedJsModule) {
        $GLOBALS['print_mode_size']        = $module->config->get('size');
        $GLOBALS['print_mode_is_portrait'] = $module->config->get('is_portrait');
        $GLOBALS['print_mode_always_on']   = $module->config->get('always_on');
        $GLOBALS['print_mode_requested']   = isset($_GET['print']) && $_GET['print'] === '1';
        
        if (is_print_mode_on()) {
            // Filename of the CSS file that specifies the page layout
            $css_path = $module->get_url() . '/res/page-' . $GLOBALS['print_mode_size'] . '-' . ($GLOBALS['print_mode_is_portrait'] ? 'portrait' : 'landscape') . '.css';

            // Load paged-js library
            doc_extensions_add_head_element('<script src="'. $pagedJsModule->get_url() . '/res/paged.js"></script>');
            // Nicer design of the pages for screen
            doc_extensions_add_head_element('<link href="'. $module->get_url() . '/res/interface.css" rel="stylesheet" type="text/css" />');

            // `setupPrintMode`: Import function
            doc_extensions_add_head_element('<script src="'. $module->get_url() . '/res/library.js"></script>');

            // `setupPrintMode`: Register function as hook
            if ($target->has_activated_module('mathjax-extensions')) {
                // Activate `paged.js` after MathJax has finished rendering
                doc_extensions_add_head_element('<script>if (typeof window.mathJaxAfterRenderingHooks !== \'undefined\') { window.mathJaxAfterRenderingHooks.push(() => {setupPrintMode(\'' . $css_path . '\')}); } else { window.mathJaxAfterRenderingHooks = [() => {setupPrintMode(\'' . $css_path . '\')}]; }</script>');
            }
            else {
                // Activate `paged.js` after DOM is ready
                doc_extensions_add_head_element('<script>document.addEventListener("DOMContentLoaded", function() {setupPrintMode(\'' . $css_path . '\')});</script>');
            }
        }
    };
    

    ///////////////////////////////////
    // Functions offered to TEMPLATE //
    ///////////////////////////////////

    function is_print_mode_on(): bool {
        return $GLOBALS['print_mode_requested'] || $GLOBALS['print_mode_always_on'];
    }

    function is_print_mode_always_on(): bool {
        return $GLOBALS['print_mode_always_on'];
    }

    function print_mode_get_query_string_to_request_on(): array {
        return ['print' => true];
    }

    function print_mode_get_query_string_to_request_off(): array {
        return ['print' => null];
    }


    //////////////////////////////////
    // Functions offered to CONTENT //
    //////////////////////////////////

    function css_print_mode_start() {
        ob_start();
        if (is_print_mode_on()) {
            echo '<style>';
        }
    }

    function css_print_mode_end() {
        if (is_print_mode_on()) {
            echo '</style>';
            doc_extensions_add_head_element(ob_get_clean());
        }
        else {
            ob_end_clean();  // Throw away
        }
    }

    function page_start(?string $size = null, ?bool $is_portrait = null) {
        if (is_print_mode_on()) {
            $size = $size ?? $GLOBALS['print_mode_size'];
            $is_portrait = $is_portrait ?? $GLOBALS['print_mode_is_portrait'];

            $orientation = $is_portrait ? 'portrait' : 'landscape';

            echo "<div class=\"sheet $size-$orientation\">\n";
        }
    }

    function page_end() {
        if (is_print_mode_on()) {
            echo "</div>\n";
        }
    }
    
    function page_break(?string $next_size = null, ?bool $is_next_portrait = null) {
        page_end();
        page_start($next_size, $is_next_portrait);
    }

    // "Please turn" sign
    function page_break_with_sign(string $text = '(Bitte wenden)', float $margin_top = 0.5, string $style = '') {
?>
        <p class="text-center" style="margin-top: <?= $margin_top ?>cm;<?= $style !== '' ? ' ' . $style : '' ?>"><em><?= $text ?></em></p>
<?
        page_break();
    }    
?>