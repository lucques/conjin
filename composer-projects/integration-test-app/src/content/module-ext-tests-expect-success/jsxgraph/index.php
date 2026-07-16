<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('jsxgraph');
    };
?>

<? $process = function (Target $target) { ?>

<div id="jsxgraph-board" class="jxgbox" style="width: 400px; height: 400px;"></div>

<script>
const jsxGraphBoard = JXG.JSXGraph.initBoard('jsxgraph-board', {
    axis: true,
    boundingbox: [-5, 5, 5, -5],
});
const jsxGraphCenter = jsxGraphBoard.create('point', [0, 0], {
    name: 'A',
});
const jsxGraphRadius = jsxGraphBoard.create('point', [2, 1], {
    name: 'B',
});

jsxGraphBoard.create('circle', [jsxGraphCenter, jsxGraphRadius]);
</script>

<? }; ?>
