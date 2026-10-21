<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');
        $c->activate_module('mathjax-extensions');
        $c->activate_module('prism');
        $c->activate_module('print-mode');
        $c->activate_module('sql-js-extensions');
        $c->activate_module('sync-dims');
    };
?>

<?
    $process = function (Target $target) {
        css_print_mode_start();
?>
#print-mode-only {
    color: rgb(12, 34, 56);
}
<?
        css_print_mode_end();
?>

<p id="first-page">First page: $x = 1$</p>
<?
        if (isset($_GET['prep_invalid_wait'])) {
?>
<script>
    pagePrep.register({
        id: 'fixture:invalid-wait',
        before: 'initialContentReady',
        run: async context => {
            await Promise.resolve();
            await context.wait(<?= json_encode($_GET['prep_invalid_wait']) ?>);
        },
    });
</script>
<?
        }
        if (isset($_GET['prep_child'])) {
?>
<script>
    pagePrep.register({
        id: 'fixture:parent',
        before: 'initialContentReady',
        run: context => new Promise(resolve => {
            window.releaseParent = () => {
                context.track({
                    id: 'fixture:child',
                    promise: new Promise(childResolve => { window.releaseChild = childResolve; }),
                });
                resolve();
            };
        }),
    });
</script>
<?
        }
        if (isset($_GET['broken_image'])) {
?>
<img id="broken-print-image" src="/missing-print-image.svg">
<?
        }
?>
<?
        if (isset($_GET['async_sql'])) {
            $db = sql_js_define_new_db_from_init_script_inline('CREATE TABLE examples (value TEXT); INSERT INTO examples VALUES (\'$x^2$\');');
?>
<script>
    const delayedPrintDb = new Promise(resolve => { window.releaseSql = resolve; }).then(() => <?= $db ?>);
</script>
<?
            if (isset($_GET['image_on_release'])) {
?>
<script>
    pagePrep.track({
        id: 'fixture:late-image',
        before: 'initialContentReady',
        promise: delayedPrintDb.then(() => {
            const image = document.createElement('img');
            image.id = 'late-print-image';
            if (<?= isset($_GET['lazy_image']) ? 'true' : 'false' ?>) {
                image.loading = 'lazy';
            }
            image.src = '/delayed-print-image.svg';
            document.querySelector('#first-page').append(image);
        }),
    });
</script>
<?
            }
?>
<div id="delayed-sql-tables">
<?
            sql_js_query_resultset_and_print('delayedPrintDb', isset($_GET['sql_error']) ? 'SELECT * FROM missing_table' : 'SELECT * FROM examples');
            sql_js_print_schema_with_queried_results('delayedPrintDb', [[
                'name' => 'examples',
                'columns' => [['type' => 'data', 'name' => 'value']],
                'sql' => 'SELECT * FROM examples',
                'open' => true,
            ]], show_button: false);
?>
</div>
<span class="d-inline-block" data-sync-width-id="print-test">Short</span>
<span class="d-inline-block" data-sync-width-id="print-test">A considerably longer label</span>
<?
            if (isset($_GET['layout_probe'])) {
?>
<script>
    pagePrep.register({
        id: 'fixture:layout-probe',
        after: 'layoutResourcesReady',
        before: 'readyForPagination',
        run: () => {
            window.layoutRuns = (window.layoutRuns ?? 0) + 1;
            window.beforePagination = {
                tables: document.querySelectorAll('#delayed-sql-tables table').length,
                math: document.querySelectorAll('#delayed-sql-tables mjx-container').length,
                widths: Array.from(document.querySelectorAll('[data-sync-width-id="print-test"]'), element => element.style.width),
            };
        },
    });
</script>
<?
            }
            if (isset($_GET['layout_gate'])) {
?>
<script>
    window.layoutSteps = [];
    pagePrep.register({
        id: 'fixture:layout-gate',
        after: 'layoutResourcesReady',
        before: 'readyForPagination',
        run: () => {
            window.layoutSteps.push('first');
            return new Promise(resolve => { window.releaseLayout = resolve; });
        },
    });
    pagePrep.register({
        id: 'fixture:layout-peer',
        after: 'layoutResourcesReady',
        before: 'readyForPagination',
        run: () => { window.layoutSteps.push('second'); },
    });
</script>
<?
            }
        }
?>
<?
        page_break('a4', false);
?>
<p id="second-page">Second page</p>

<?
        page_break('a5', true);
?>
<p id="third-page">Third page</p>

<?
        page_break('a5', false);
?>
<p id="fourth-page">Fourth page</p>

<?
    };
?>
