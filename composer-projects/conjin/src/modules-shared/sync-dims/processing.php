<?
    $init_processing_target = function(Module $module, Target $target) {
        doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/js/script.js"></script>');

        // Register early, so MathJax can call this after its initial rendering changed element dimensions.
        doc_extensions_add_head_element(<<<HTML
<script>
if (typeof window.mathJaxAfterRenderingHooks !== 'undefined') {
    window.mathJaxAfterRenderingHooks.push(setupAndSyncDims);
}
else {
    window.mathJaxAfterRenderingHooks = [setupAndSyncDims];
}
</script>
HTML);

        // Set up after the DOM exists, because syncing measures rendered elements.
?>
  
<? js_after_dom_setup_start(); ?>
setupAndSyncDims();
window.addEventListener('resize', syncDims);
<? js_after_dom_setup_end(); ?>

<?
    }
?>