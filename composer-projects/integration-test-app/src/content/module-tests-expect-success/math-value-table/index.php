<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');
        $c->activate_module('math-value-table');
    };
?>

<? $process = function (Target $target) { ?>

<div id="value-table-fixture" data-ready="false"></div>

<script>
    function setupIntegrationValueTable() {
        const integrationValueTable = new ValueTable(
            'value-table-fixture',
            [
                {name: 'x', desc: 'Input'}
            ],
            false,
            true,
            {
                enabled: true,
                defaultAssignment: {x: 0},
                defaultEditMode: 'always',
                defaultInitiallyInvisible: false,
                defaultDesc: ''
            },
            {
                enabled: true,
                defaultRawExp: 'x + 1',
                defaultEditMode: 'clickable',
                defaultInitiallyInvisible: false,
                defaultDesc: 'Added expression',
                defaultTermInitiallyInvisible: false
            }
        );

        integrationValueTable.addPoint({x: 3}, 'always', false, '');
        integrationValueTable.addExp('2*x', 'never', false, 'Double');
        integrationValueTable.addExp('x + 1', 'never', true, 'Hidden result');
        integrationValueTable.rerender();
        document.getElementById('value-table-fixture').dataset.ready = 'true';
    }

    if (typeof window.mathJaxAfterRenderingHooks !== 'undefined') {
        window.mathJaxAfterRenderingHooks.push(setupIntegrationValueTable);
    }
    else {
        window.mathJaxAfterRenderingHooks = [setupIntegrationValueTable];
    }
</script>

<? }; ?>
