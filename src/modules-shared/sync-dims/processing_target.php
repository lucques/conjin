<?
    $init_processing_target = function(Module $module, Target $target) {
        doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/js/script.js"></script>');
?>
  
<? js_after_dom_setup_start(); ?>
setupSyncWidths();
setupSyncHeights();
syncWidths();
syncHeights();
window.addEventListener('resize', syncWidths);
window.addEventListener('resize', syncHeights);
<? js_after_dom_setup_end(); ?>

<?
    }
?>