<?
    //////////
    // Init //
    //////////

    $init_processing_target = function(Module $module, Target $target) {
        $GLOBALS['print_mode_module']      = $module;
        $GLOBALS['print_mode_size']        = strtolower($module->config->get('size'));
        $GLOBALS['print_mode_is_portrait'] = $module->config->get('is_portrait');
        $GLOBALS['print_mode_css_elements'] = [];
    };
    

    ///////////////////////////////////
    // Functions offered to TEMPLATE //
    ///////////////////////////////////

    function print_mode_prepare_document(Target $target): void {
        $module = $GLOBALS['print_mode_module'];
        $paged_js_module = new ModuleLocation('paged-js');
        $css_path = $module->get_url() . '/res/page-' . $GLOBALS['print_mode_size'] . '-' . ($GLOBALS['print_mode_is_portrait'] ? 'portrait' : 'landscape') . '.css';

        doc_extensions_add_head_element('<script src="'. $paged_js_module->get_url() . '/res/paged.js"></script>');
        doc_extensions_add_head_element('<link id="print-mode-interface-stylesheet" href="'. $module->get_url() . '/res/interface.css" rel="stylesheet" type="text/css" />');
        doc_extensions_add_head_element('<script src="'. $module->get_url() . '/res/library.js"></script>');

        if ($target->has_activated_module('mathjax-extensions')) {
            doc_extensions_add_head_element('<script>setupPrintModeAfterMathJax(\'' . $css_path . '\');</script>');
        }
        else {
            doc_extensions_add_head_element('<script>document.addEventListener("DOMContentLoaded", function() {setupPrintMode(\'' . $css_path . '\')});</script>');
        }
    }

    function print_mode_get_css_elements(): array {
        return $GLOBALS['print_mode_css_elements'];
    }


    //////////////////////////////////
    // Functions offered to CONTENT //
    //////////////////////////////////

    function css_print_mode_start() {
        ob_start();
    }

    function css_print_mode_end() {
        $GLOBALS['print_mode_css_elements'][] = '<style>' . ob_get_clean() . '</style>';
    }

    function page_start(?string $size = null, ?bool $is_portrait = null) {
        $size = $size ?? $GLOBALS['print_mode_size'];
        $is_portrait = $is_portrait ?? $GLOBALS['print_mode_is_portrait'];

        $orientation = $is_portrait ? 'portrait' : 'landscape';

        echo "<div class=\"sheet $size-$orientation\">\n";
    }

    function page_end() {
        echo "</div>\n";
    }
    
    function page_break(?string $next_size = null, ?bool $is_next_portrait = null) {
        page_end();
        page_start($next_size, $is_next_portrait);
    }

    // "Please turn" sign
    function page_break_with_sign(string $text = 'Bitte wenden', float $margin_top = 0.5, string $style = '') {
?>
        <p class="text-center" style="margin-top: <?= $margin_top ?>cm;<?= $style !== '' ? ' ' . $style : '' ?>"><em><?= $text ?></em></p>
<?
        page_break();
    }
?>
