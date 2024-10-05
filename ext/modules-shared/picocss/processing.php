<?
    $init_processing_target = function(Module $module, Target $target) {
        $classless_or_not = ($module->config->get('classless') ? '.classless' : '');

        if ($module->config->get('import_from_url') !== null) {
            doc_extensions_add_head_element('<link href="' . $module->config->get('import_from_url') . '/css/pico' . $classless_or_not . '.min.css" rel="stylesheet">');
        }
        else {
            doc_extensions_add_head_element('<link rel="stylesheet" href="'. $module->get_url() . '/res/pico' . $classless_or_not . '.min.css">');
        }
    };
?>