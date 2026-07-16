<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'JSXGraph');

        $c->activate_module('jsxgraph');
    };
?>

<? $process = function (Target $target) { ?>

<div id="jsxgraph-demo-board" class="jxgbox" style="width: 500px; height: 500px;"></div>

<script>
const jsxGraphDemoBoard = JXG.JSXGraph.initBoard('jsxgraph-demo-board', {
    axis: true,
    boundingbox: [-5, 5, 5, -5],
});

const jsxGraphDemoCenter = jsxGraphDemoBoard.create('point', [0, 0], {
    name: 'A',
});
const jsxGraphDemoRadius = jsxGraphDemoBoard.create('point', [2, 1], {
    name: 'B',
});

jsxGraphDemoBoard.create('circle', [jsxGraphDemoCenter, jsxGraphDemoRadius]);
</script>

<? }; ?>
