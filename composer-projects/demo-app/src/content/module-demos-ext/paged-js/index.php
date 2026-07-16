<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'Paged.js');

        $c->activate_module('paged-js');
    };
?>

<?
    $process = function (Target $target) {
        $paged_js_url = (new ModuleLocation('paged-js'))->get_url() . '/res/paged.js';
?>

<p id="paged-js-status" data-ready="false">Rendering the paged preview…</p>

<template id="paged-js-source">
    <article>
        <h2>A small paged document</h2>
        <p>Paged.js turns ordinary HTML into pages that can be previewed in the browser and printed.</p>
        <p>This preview uses an A5 page with a page number in the footer.</p>
    </article>
</template>

<div id="paged-js-preview"></div>

<script src="<?= $paged_js_url ?>"></script>
<script>
const pagedJsSource = document.getElementById('paged-js-source');
const pagedJsPreview = document.getElementById('paged-js-preview');
const pagedJsStatus = document.getElementById('paged-js-status');
const pagedJsStyles = [{
    [window.location.href]: `
        @page {
            size: A5;
            margin: 20mm;

            @bottom-center {
                content: counter(page);
            }
        }

        article {
            font-family: sans-serif;
        }
    `,
}];

new Paged.Previewer()
    .preview(pagedJsSource.content, pagedJsStyles, pagedJsPreview)
    .then((flow) => {
        pagedJsStatus.dataset.ready = 'true';
        pagedJsStatus.dataset.pageCount = String(flow.total);
        pagedJsStatus.textContent = `Rendered ${flow.total} page${flow.total === 1 ? '' : 's'}.`;
    });
</script>

<?
    };
?>
