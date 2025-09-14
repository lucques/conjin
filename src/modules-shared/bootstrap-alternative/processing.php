<?
    $add_primary_color_overrides = function(Module $module) {
        $primary = $module->config->get('primary');
        $primary_rgb = $module->config->get('primary_rgb');

        $custom_properties = [];
        if ($primary !== null) {
            if (!is_string($primary) || !preg_match('/^#[0-9a-fA-F]{6}$/', $primary)) {
                throw new InvalidArgumentException('bootstrap-alternative primary must be a six-digit hexadecimal CSS color');
            }
            $custom_properties[] = '--bs-primary: ' . $primary;
        }

        if ($primary_rgb !== null) {
            if (
                !is_string($primary_rgb) ||
                !preg_match('/^\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*$/', $primary_rgb, $matches) ||
                array_filter(array_slice($matches, 1), fn($channel) => (int)$channel > 255)
            ) {
                throw new InvalidArgumentException('bootstrap-alternative primary_rgb must contain three comma-separated values from 0 to 255');
            }
            $primary_rgb = implode(', ', array_map('intval', array_slice($matches, 1)));
            $custom_properties[] = '--bs-primary-rgb: ' . $primary_rgb;
        }

        if (count($custom_properties) > 0) {
            doc_extensions_add_head_element(
                '<style>:root { ' . implode('; ', $custom_properties) . '; }</style>'
            );
        }
    };

    $add_styles = function(Module $module) use ($add_primary_color_overrides) {
        doc_extensions_add_head_element('<link rel="stylesheet" href="' . $module->get_url() . '/res/core.css?v=2">');
        doc_extensions_add_head_element('<link rel="stylesheet" href="' . $module->get_url() . '/res/acc.css?v=3">');
        doc_extensions_add_head_element('<link rel="stylesheet" href="' . $module->get_url() . '/res/badge.css">');
        doc_extensions_add_head_element('<link rel="stylesheet" href="' . $module->get_url() . '/res/card.css">');
        doc_extensions_add_head_element('<link rel="stylesheet" href="' . $module->get_url() . '/res/collapse.css?v=2">');
        doc_extensions_add_head_element('<link rel="stylesheet" href="' . $module->get_url() . '/res/forms.css?v=3">');
        doc_extensions_add_head_element('<link rel="stylesheet" href="' . $module->get_url() . '/res/modal.css?v=2">');
        doc_extensions_add_head_element('<link rel="stylesheet" href="' . $module->get_url() . '/res/table.css">');
        $add_primary_color_overrides($module);
    };

    $init_processing_syslet = function(Module $module, Syslet $syslet, Target $root_target) use ($add_styles) {
        $add_styles($module);
    };

    $init_processing_target = function(Module $module, Target $target) use ($add_styles) {
        $add_styles($module);

        doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/acc.js" defer></script>');
        doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/collapse.js" defer></script>');
        doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/modal.js?v=3" defer></script>');

        load_def_from_script_and_call(__DIR__ . '/acc.php', 'init_processing_target', $module, $target);
        load_def_from_script_and_call(__DIR__ . '/collapse.php', 'init_processing_target', $module, $target);
    };

    // Kept identical to bootstrap-extended because the accordion macros expose
    // the same variants and generated class names.
    function variant_to_color($variant) {
        if (in_array($variant, ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'])) {
            return $variant;
        }

        if (in_array($variant, ['orange', 'green', 'blue'])) {
            return $variant;
        }

        switch ($variant) {
            case 'definition':  return 'orange';
            case 'explanation': return 'primary';
            case 'example':     return 'info';
            case 'theorem':     return 'dark';
            case 'proof':       return 'dark';
            case 'remark':      return 'light';
            case 'exercise':    return 'primary';
            case 'solution':    return 'success';
            case 'hint':        return 'orange';

            case 'layer_1':     return 'primary';
            case 'layer_2':     return 'success';
            case 'layer_3':     return 'danger';
            case 'layer_4':     return 'orange';
            case 'layer_5':     return 'info';

            case 'attention':   return 'warning';
            case 'important':   return 'danger';

            default:            assert(false, "$variant is not a variant");
        }
    }
?>
