<?
    ////////////////
    // PROCESSING //
    ////////////////

    $init_processing_target = function(Module $module, Target $target) {
        load_def_from_script_and_call(__DIR__ . '/acc.php', 'init_processing_target', $module, $target);
        load_def_from_script_and_call(__DIR__ . '/modals.php', 'init_processing_target', $module, $target);
    };


    //////////////////////
    // Used by template //
    //////////////////////

    function variant_to_color($variant) {
        // Colors *are* variants
        if (in_array($variant, ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark', 'orange'])) {
            return $variant;
        } 

        switch ($variant) {
            // Variants: "Mathy" stuff
            case 'definition': return 'orange';
            case 'example':    return 'info';
            case 'theorem':    return 'dark';
            case 'proof':      return 'dark';
            case 'remark':     return 'light';
            case 'exercise':   return 'primary';
            case 'solution':   return 'success';
            case 'hint':       return 'orange';

            // Layers
            case 'layer_1':    return 'primary';
            case 'layer_2':    return 'success';
            case 'layer_3':    return 'danger';
            case 'layer_4':    return 'orange';
            case 'layer_5':    return 'info';

            // More
            case 'attention':  return 'warning';
            case 'important':  return 'danger';

            default:           assert(false, "$variant is not a variant");
        }
    }
?>