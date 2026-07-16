<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('math-value-table');

        $c->update_module_config('template-interbook', [
            'content_width' => 'xl',
        ]);

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'math-value-table');
    };
?>

<? $process = function (Target $target) { ?>

<p>The module creates interactive value tables whose variable assignments and expressions can be fixed, editable, or editable after a click.</p>

<p>Edit the first row directly, click a value or expression in the second row to edit it, and click a hidden result to reveal it. The plus buttons add another expression or point.</p>

<div id="value-table-demo"></div>

<script>
    function setupValueTableDemo() {
        const valueTable = new ValueTable(
            'value-table-demo',
            [
                {name: 'x', desc: 'First variable'},
                {name: 'y', desc: 'Second variable'}
            ],
            true,
            true,
            {
                enabled: true,
                defaultAssignment: {x: 0, y: 0},
                defaultEditMode: 'always',
                defaultInitiallyInvisible: false,
                defaultDesc: 'New point'
            },
            {
                enabled: true,
                defaultRawExp: 'x + y',
                defaultEditMode: 'clickable',
                defaultInitiallyInvisible: false,
                defaultDesc: 'New expression',
                defaultTermInitiallyInvisible: false
            }
        );

        valueTable.addExp('x + y', 'never', false, 'Sum');
        valueTable.addExp('x^2&+&2*x*y&+&y^2', 'clickable', true, 'Square of the sum', true);

        valueTable.addPoint({x: 2, y: 3}, 'always', false, 'Editable');
        valueTable.addPoint({x: -1, y: 4}, 'clickable', true, 'Click to edit');
        valueTable.addPoint({x: 0.5, y: 1.5}, 'never', false, 'Fixed');

        valueTable.rerender();
    }

    if (typeof window.mathJaxAfterRenderingHooks !== 'undefined') {
        window.mathJaxAfterRenderingHooks.push(setupValueTableDemo);
    }
    else {
        window.mathJaxAfterRenderingHooks = [setupValueTableDemo];
    }
</script>

<? }; ?>
