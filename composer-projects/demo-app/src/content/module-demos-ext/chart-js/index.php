<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'Chart.js');

        $c->activate_module('chart-js');
    };
?>

<? $process = function (Target $target) { ?>

<div style="max-width: 800px; margin: 30px auto;">
    <canvas id="example-chart">
        Weekly exercise completion: Monday 3, Tuesday 5, Wednesday 4, Thursday 6, Friday 8.
    </canvas>
</div>

<script>
const exampleChartContext = document.getElementById('example-chart');

new Chart(exampleChartContext, {
    type: 'bar',
    data: {
        labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
        datasets: [{
            label: 'Completed exercises',
            data: [3, 5, 4, 6, 8],
            backgroundColor: 'rgba(54, 162, 235, 0.65)',
            borderColor: 'rgb(54, 162, 235)',
            borderWidth: 1,
        }],
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0,
                },
            },
        },
    },
});
</script>

<? }; ?>
