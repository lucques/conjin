<?    
    $process_target = function(Module $module, Target $target) {
        if ($target->has_activated_module('page-prep')) {
            doc_extensions_add_head_element('<script>window.MathJax ??= {}; MathJax.startup ??= {}; MathJax.startup.typeset = false;</script>');
        }
        doc_extensions_add_head_element('<script src="' . $module->get_url() . '/res/es5/tex-chtml.js" id="MathJax-script"></script>');
        if ($target->has_activated_module('page-prep')) {
            doc_extensions_add_head_element(<<<'HTML'
<script>
pagePrep.register({
    id: 'mathjax:final-typeset',
    after: 'initialContentReady',
    before: 'renderedContentReady',
    run: async () => {
        if (!window.MathJax?.startup?.promise) {
            throw new Error('MathJax did not initialize.');
        }
        await MathJax.startup.promise;
        if (!MathJax.typesetPromise) {
            throw new Error('MathJax did not initialize.');
        }
        await MathJax.typesetPromise();
        for (const hook of window.mathJaxAfterRenderingHooks ?? []) {
            await hook();
        }
    },
});
</script>
HTML);
        }
    };
?>