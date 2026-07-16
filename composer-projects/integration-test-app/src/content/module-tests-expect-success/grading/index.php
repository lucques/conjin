<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');
        $c->activate_module('grading');
    };
?>

<? $process = function (Target $target) { ?>

<?
    $minimum_table = function (array $config, int $minimum, int $maximum): array {
        $rows = [];
        for ($max_points = $minimum; $max_points <= $maximum; $max_points++) {
            $rows[$max_points] = array_map(
                fn (array $range): float => $range['min'],
                grading_table_points($config, $max_points)
            );
        }
        return $rows;
    };

    $load_reference_table = function (string $filename): array {
        $json = file_get_contents(__DIR__ . '/res/' . $filename);
        assert($json !== false, 'Could not read grading reference table: ' . $filename);
        return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    };

    $print_grading_table = function (string $kind, string $name, array $rows): void {
        $grades = array_keys(reset($rows));
?>
<table data-grading-kind="<?= $kind ?>" data-grading-name="<?= $name ?>">
    <caption><?= $kind === 'reference' ? 'PDF reference values' : 'Computed values' ?></caption>
    <thead>
        <tr>
            <th>Maximum</th>
<? foreach ($grades as $grade) { ?>
            <th><?= $grade ?></th>
<? } ?>
        </tr>
    </thead>
    <tbody>
<? foreach ($rows as $maximum => $minimums) { ?>
        <tr>
            <th><?= $maximum ?></th>
<? foreach ($minimums as $minimum) { ?>
            <td><?= $minimum ?></td>
<? } ?>
        </tr>
<? } ?>
    </tbody>
</table>
<?
    };

    $tables = [
        'sekundarstufe-1' => [
            'reference' => $load_reference_table('sekundarstufe-1_cleaned.json'),
            'computed' => $minimum_table(GRADING_SEK_1_RAW, 10, 109),
        ],
        'sekundarstufe-2-tests' => [
            'reference' => $load_reference_table('sekundarstufe-2-tests_cleaned.json'),
            'computed' => $minimum_table(GRADING_SEK_2_TEST, 25, 100),
        ],
        'sekundarstufe-2-exams' => [
            'reference' => $load_reference_table('sekundarstufe-2-exams_cleaned.json'),
            'computed' => $minimum_table(GRADING_SEK_2_EXAM, 10, 59),
        ],
    ];
?>

<? foreach ($tables as $name => $table) { ?>
<section data-grading-comparison="<?= $name ?>">
    <h2><?= $table['reference']['source'] ?></h2>
    <? $print_grading_table('reference', $name, $table['reference']['rows']); ?>
    <? $print_grading_table('computed', $name, $table['computed']); ?>
</section>
<? } ?>

<div id="grade-list">
<?
    $grade_list = new GradeList(GRADING_SEK_1_RAW, [8, 12], converted_to_bonus_points: 2);
    $grade_list->add('Lovelace', 'Ada', [7, 9], bonus_points: 1);
    $grade_list->add('Hopper', 'Grace', [8, 10]);
    $grade_list->add('Noether', 'Emmy');
    $grade_list->print();
?>
</div>

<? }; ?>
