<?
    $process_target = function(Module $module, Target $target) {
        doc_extensions_add_head_element('<link href="'  . $module->get_url() . '/res/prism.css" rel="stylesheet">');
        $manual = $target->has_activated_module('page-prep') ? ' data-manual' : '';
        doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/prism.js"' . $manual . '></script>');
        if ($target->has_activated_module('page-prep')) {
            doc_extensions_add_head_element(<<<'HTML'
<script>
pagePrep.register({
    id: 'prism:final-highlight',
    after: 'initialContentReady',
    before: 'renderedContentReady',
    run: () => Prism.highlightAll(),
});
</script>
HTML);
        }
    };
?>