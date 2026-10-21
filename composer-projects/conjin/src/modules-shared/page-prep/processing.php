<?
    $process_target = $process_syslet = function(Module $module, Processable $processable) {
        doc_extensions_prepend_head_element('<script src="' . $module->get_url() . '/res/page-prep.js"></script>');
    };
?>
