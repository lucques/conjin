<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('grading');
    };
?>

<? $process = function (Target $target) { ?>

<h1>grading</h1>

<h2>Grading table</h2>

<p>The following Sekundarstufe I table is calculated for 20 available points.</p>

<table class="table table-bordered border-dark">
    <thead>
        <tr>
            <th>Grade</th>
            <th>Minimum points</th>
            <th>Maximum points</th>
        </tr>
    </thead>
    <tbody>
<? foreach (grading_table_points(GRADING_SEK_1_RAW, 20) as $grade => $range) { ?>
        <tr>
            <th><?= $grade ?></th>
            <td><?= $range['min'] ?></td>
            <td><?= $range['max'] ?></td>
        </tr>
<? } ?>
    </tbody>
</table>

<h2>Grade list</h2>

<?
    $grade_list = new GradeList(GRADING_SEK_1_RAW, [8, 12], converted_to_bonus_points: 2);
    $grade_list->add('Lovelace', 'Ada', [7, 9], bonus_points: 1);
    $grade_list->add('Hopper', 'Grace', [8, 10]);
    $grade_list->add('Noether', 'Emmy');
    $grade_list->print();
?>

<? }; ?>
