<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('paged-js');
    };
?>

<?
    $process = function (Target $target) {
        $paged_js_url = (new ModuleLocation('paged-js'))->get_url() . '/res/paged.js';
?>

<p id="paged-js-status" data-ready="false">Rendering</p>

<template id="paged-js-source">
    <article>
        <h2>Paged.js health check</h2>
        <p>This content should be rendered into a page.</p>
    </article>
</template>

<div id="paged-js-preview"></div>

<script src="<?= $paged_js_url ?>"></script>
<script>
const pagedJsSource = document.getElementById('paged-js-source');
const pagedJsPreview = document.getElementById('paged-js-preview');
const pagedJsStatus = document.getElementById('paged-js-status');
const pagedJsStyles = [{
    [window.location.href]: '@page { size: A5; margin: 20mm; }',
}];

new Paged.Previewer()
    .preview(pagedJsSource.content, pagedJsStyles, pagedJsPreview)
    .then((flow) => {
        pagedJsStatus.dataset.ready = 'true';
        pagedJsStatus.dataset.pageCount = String(flow.total);
        pagedJsStatus.textContent = 'Ready';
    });
</script>

<?
    };
?>
