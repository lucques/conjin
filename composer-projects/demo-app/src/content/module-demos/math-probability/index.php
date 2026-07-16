<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('math-probability');
    };
?>

<? $process = function (Target $target) { ?>

<h1>math-probability</h1>

<p>The module can sample a probability distribution reproducibly and convert the resulting absolute frequencies to relative frequencies.</p>

<?
    $seed = 42;
    $sample_size = 1000;
    $pdf = [
        'Red' => 0.5,
        'Green' => 0.3,
        'Blue' => 0.2,
    ];
    $absolute_frequencies = prob_sample_absolute_frequencies($seed, $sample_size, $pdf);
    $relative_frequencies = prob_absolute_to_relative_frequencies($absolute_frequencies);
?>

<p>The following sample uses seed <code><?= $seed ?></code> and contains <?= $sample_size ?> draws.</p>

<table class="table table-bordered border-dark">
    <thead>
        <tr>
            <th>Outcome</th>
            <th>Probability</th>
            <th>Absolute frequency</th>
            <th>Relative frequency</th>
        </tr>
    </thead>
    <tbody>
<? foreach ($pdf as $outcome => $probability) { ?>
        <tr>
            <th><?= $outcome ?></th>
            <td><?= number_format($probability, 3) ?></td>
            <td><?= $absolute_frequencies[$outcome] ?></td>
            <td><?= number_format($relative_frequencies[$outcome], 3) ?></td>
        </tr>
<? } ?>
    </tbody>
</table>

<? }; ?>
