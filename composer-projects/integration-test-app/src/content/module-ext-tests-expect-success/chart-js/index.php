<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('chart-js');
    };
?>

<? $process = function (Target $target) { ?>

<canvas id="chart-js-canvas"></canvas>

<script>
new Chart(document.getElementById('chart-js-canvas'), {
    type: 'bar',
    data: {
        labels: ['First', 'Second', 'Third'],
        datasets: [{
            label: 'Values',
            data: [2, 4, 3],
        }],
    },
});
</script>

<? }; ?>
