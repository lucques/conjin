<?
    ///////////////////////////
    // Parameters for Sek. 1 //
    ///////////////////////////

    define('GRADING_SEK_1_RAW_MIN_RELATIVE', [
        '1' => .96,
        '2' => .8,
        '3' => .6,
        '4' => .4,
        '5' => .2,
        '6' => 0
    ]);

    define('GRADING_SEK_1_TENDENCY_INTERVAL', .03);

    define('GRADING_SEK_1_FINE_MIN_RELATIVE', [
        '1'  => GRADING_SEK_1_RAW_MIN_RELATIVE['1'],
        '2+' => GRADING_SEK_1_RAW_MIN_RELATIVE['1'] - GRADING_SEK_1_TENDENCY_INTERVAL,
        '2'  => GRADING_SEK_1_RAW_MIN_RELATIVE['2'] + GRADING_SEK_1_TENDENCY_INTERVAL,
        '2-' => GRADING_SEK_1_RAW_MIN_RELATIVE['2'],
        '3+' => GRADING_SEK_1_RAW_MIN_RELATIVE['2'] - GRADING_SEK_1_TENDENCY_INTERVAL,
        '3'  => GRADING_SEK_1_RAW_MIN_RELATIVE['3'] + GRADING_SEK_1_TENDENCY_INTERVAL,
        '3-' => GRADING_SEK_1_RAW_MIN_RELATIVE['3'],
        '4+' => GRADING_SEK_1_RAW_MIN_RELATIVE['3'] - GRADING_SEK_1_TENDENCY_INTERVAL,
        '4'  => GRADING_SEK_1_RAW_MIN_RELATIVE['4'] + GRADING_SEK_1_TENDENCY_INTERVAL,
        '4-' => GRADING_SEK_1_RAW_MIN_RELATIVE['4'],
        '5+' => GRADING_SEK_1_RAW_MIN_RELATIVE['4'] - GRADING_SEK_1_TENDENCY_INTERVAL,
        '5'  => GRADING_SEK_1_RAW_MIN_RELATIVE['5'] + GRADING_SEK_1_TENDENCY_INTERVAL,
        '5-' => GRADING_SEK_1_RAW_MIN_RELATIVE['5'],
        '6+' => GRADING_SEK_1_RAW_MIN_RELATIVE['5'] - GRADING_SEK_1_TENDENCY_INTERVAL,
        '6'  => GRADING_SEK_1_RAW_MIN_RELATIVE['6'],
    ]);

    define('GRADING_SEK_1_SMALLEST_POINTS_1_NTH', 2);
    define('GRADING_SEK_1_ROUNDING_PRECISION', 2);


    ///////////////////////////
    // Parameters for Sek. 2 //
    ///////////////////////////

    define('GRADING_SEK_2_TEST_MIN_RELATIVE', [
        '15' => .9867,
        '14' => .9733,
        '13' => .96,
        '12' => .9067,
        '11' => .8533,
        '10' => .8,
        '9'  => .7333,
        '8'  => .6667,
        '7'  => .6,
        '6'  => .5333,
        '5'  => .4667,
        '4'  => .4,
        '3'  => .3333,
        '2'  => .2667,
        '1'  => .2,
        '0'  => 0
    ]);
    define('GRADING_SEK_2_TEST_ROUNDING_PRECISION', 2);


    define('GRADING_SEK_2_EXAM_MIN_RELATIVE', [
        '15' => .95,
        '14' => .9,
        '13' => .85,
        '12' => .8,
        '11' => .75,
        '10' => .7,
        '9'  => .65,
        '8'  => .6,
        '7'  => .55,
        '6'  => .5,
        '5'  => .45,
        '4'  => .4,
        '3'  => .33,
        '2'  => .27,
        '1'  => .2,
        '0'  => 0
    ]);
    define('GRADING_SEK_2_EXAM_ROUNDING_PRECISION', 1);


    define('GRADING_SEK_2_SMALLEST_POINTS_1_NTH', 2);

    
    ////////////////////////////
    // Grading configurations //
    ////////////////////////////

    define('GRADING_SEK_1_RAW', [
        'min_relative'          => GRADING_SEK_1_RAW_MIN_RELATIVE,
        'smallest_points_1_nth' => GRADING_SEK_1_SMALLEST_POINTS_1_NTH,
        'rounding_precision'    => GRADING_SEK_1_ROUNDING_PRECISION,
    ]);

    define('GRADING_SEK_1_FINE', [
        'min_relative'          => GRADING_SEK_1_FINE_MIN_RELATIVE,
        'smallest_points_1_nth' => GRADING_SEK_1_SMALLEST_POINTS_1_NTH,
        'rounding_precision'    => GRADING_SEK_1_ROUNDING_PRECISION,
    ]);

    define('GRADING_SEK_2_TEST', [
        'min_relative'          => GRADING_SEK_2_TEST_MIN_RELATIVE,
        'smallest_points_1_nth' => GRADING_SEK_2_SMALLEST_POINTS_1_NTH,
        'rounding_precision'    => GRADING_SEK_2_TEST_ROUNDING_PRECISION,
    ]);

    define('GRADING_SEK_2_EXAM', [
        'min_relative'          => GRADING_SEK_2_EXAM_MIN_RELATIVE,
        'smallest_points_1_nth' => GRADING_SEK_2_SMALLEST_POINTS_1_NTH,
        'rounding_precision'    => GRADING_SEK_2_EXAM_ROUNDING_PRECISION,
    ]);


    /////////////////////////////////////////////////////////////////////////////////
    // API for Notentabellen (only needed to unit-test against official documents) //
    /////////////////////////////////////////////////////////////////////////////////




    //////////////////////////////////////////
    // API for exams: Create grading tables //
    //////////////////////////////////////////

    // `$grading_config`: Constants like `GRADING_SEK_1_RAW`
    // `max`:             Maximum points achievable in the exam
    // Returns an array mapping grades to min and max points achievable
    // E.g. for Sek. 1 raw and max=100:
    // [
    //     '1' => ['min' => 96, 'max' => 100],
    //     '2' => ['min' => 80, 'max' => 95.5],
    //     '3' => ['min' => 60, 'max' => 79.5],
    //     '4' => ['min' => 40, 'max' => 59.5],
    //     '5' => ['min' => 20, 'max' => 39.5],
    //     '6' => ['min' => 0,  'max' => 19.5]
    // ]
    function grading_table_points(array $grading_config, int $max) {
        $grading_min_points = grading_min_points($grading_config, $max);

        $result = [];

        $cur_max = $max;
        foreach ($grading_min_points as $grade => $cur_min) {
            $result[$grade] = ['min' => $cur_min, 'max' => $cur_max];
            $cur_max = $cur_min - 1 / $grading_config['smallest_points_1_nth'];
        }

        return $result;
    }


    ////////////////////////
    // API for grade list //
    ////////////////////////

    class GradeList {
        private array $entries; // array of GradListEntry

        public function __construct(
            readonly public array $grading_config,
            readonly public array $max_points,                   // array of points for each exercise
            readonly public int $converted_to_bonus_points = 0,  // How many regular points have been converted to bonus points?
        ) {
            $this->entries = [];
        }

        public function add(string $student_last, string $student_first, ?array $points = null, ?float $bonus_points = 0): void {
            assert($points == null || count($points) === count($this->max_points), 'Number of points must coincide with number of exercises');

            $this->entries[] = new GradeListEntry($student_last, $student_first, $points, $bonus_points);
        }

        public function print() {
?>
<table class="table table-bordered border-dark">
    <thead>
        <tr>
            <th>Nachname</th>
            <th>Vorname</th>
<?
            foreach ($this->max_points as $i => $max) {
?>
            <th>Nr. <?= $i + 1 ?></th>
<?
            }
?>
            <th>Davon Bonus</th>
            <th>Summe</th>
            <th>Note</th>
        </tr>
        <tr>
            <th></th>
            <th></th>
<?
            foreach ($this->max_points as $i => $max) {
?>
            <th><?= $max ?></th>
<?
            }
?>
            <th>− <?= $this->converted_to_bonus_points ?></th>
            <th><?= $this->get_total_max_points() ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
<?
            foreach ($this->entries as $entry) {
?>
        <tr>
            <td><?= $entry->student_last ?></td>
            <td><?= $entry->student_first ?></td>
<?
                if ($entry->points != null) {
                    foreach ($entry->points as $p) {
?>
            <td><?= $p ?></td>
<?
                    }
?>
            <td><?= $entry->bonus_points ?></td>
            <td><?= $entry->get_total_points() ?></td>
            <td><?= $entry->get_grade($this->grading_config, $this->get_total_max_points()) ?></td>
<?
                }
                else {
                    foreach ($this->max_points as $_) {
?>
            <td></td>
<?
                    }
?>
            <td></td>
            <td></td>
            <td></td>
<?
                }
?>
        </tr>
<?
            }
?>
    </tbody>
</table>
<?
        }

        private function get_statistics_buckets(array $bucket_sizes): array {
            $grades = array_reverse(array_keys($this->grading_config['min_relative']));
            foreach ($bucket_sizes as $size) {
                if (!is_int($size) || $size <= 0) {
                    throw new InvalidArgumentException('Bucket sizes must be positive integers.');
                }
            }
            if (array_sum($bucket_sizes) !== count($grades)) {
                throw new InvalidArgumentException('Bucket sizes must cover every grade exactly.');
            }

            $buckets = [];
            $grade_buckets = [];
            $offset = 0;
            foreach ($bucket_sizes as $size) {
                $bucket_grades = array_slice($grades, $offset, $size);
                $label = (string) $bucket_grades[0];
                if ($size > 1) {
                    $label .= ' bis ' . $bucket_grades[$size - 1];
                }
                $bucket_index = count($buckets);
                $buckets[] = ['label' => $label, 'count' => 0];
                foreach ($bucket_grades as $grade) {
                    $grade_buckets[$grade] = $bucket_index;
                }
                $offset += $size;
            }

            foreach ($this->entries as $entry) {
                if ($entry->points === null) {
                    continue;
                }
                $grade = $entry->get_grade($this->grading_config, $this->get_total_max_points());
                $buckets[$grade_buckets[$grade]]['count']++;
            }
            return $buckets;
        }

        public function print_statistics_table(array $bucket_sizes): void {
            $buckets = $this->get_statistics_buckets($bucket_sizes);
            $total = array_sum(array_column($buckets, 'count'));
?>
<table class="table table-bordered border-dark">
    <thead>
        <tr>
            <th scope="col">Note</th>
<?
            foreach ($buckets as $bucket) {
?>
            <th scope="col"><?= htmlspecialchars($bucket['label'], ENT_QUOTES, 'UTF-8') ?></th>
<?
            }
?>
        </tr>
    </thead>
    <tbody>
        <tr>
            <th scope="row">Anzahl</th>
<?
            foreach ($buckets as $bucket) {
?>
            <td><?= $bucket['count'] ?></td>
<?
            }
?>
        </tr>
        <tr>
            <th scope="row">Anteil</th>
<?
            foreach ($buckets as $bucket) {
?>
            <td><?= number_format($total > 0 ? 100 * $bucket['count'] / $total : 0, 1, ',', '') ?> %</td>
<?
            }
?>
        </tr>
    </tbody>
</table>
<?
        }

        public function print_statistics_diagram(array $bucket_sizes): void {
            $buckets = $this->get_statistics_buckets($bucket_sizes);
            $total = array_sum(array_column($buckets, 'count'));
            $id = 'grading-statistics-' . next_unique_id();
            $json_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR;
?>
<div style="position: relative; height: 320px;">
    <canvas id="<?= $id ?>" role="img" aria-label="Notenverteilung">
<?
            foreach ($buckets as $bucket) {
?>
        <?= htmlspecialchars($bucket['label'], ENT_QUOTES, 'UTF-8') ?>: <?= $bucket['count'] ?> (<?= number_format($total > 0 ? 100 * $bucket['count'] / $total : 0, 1, ',', '') ?> %).
<?
            }
?>
    </canvas>
</div>
<script>
(() => {
    const total = <?= $total ?>;
    new Chart(document.getElementById(<?= json_encode($id, $json_flags) ?>), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($buckets, 'label'), $json_flags) ?>,
            datasets: [{
                label: 'Anzahl',
                data: <?= json_encode(array_column($buckets, 'count'), $json_flags) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.65)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1,
            }],
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: { title: { display: true, text: 'Note' } },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Anzahl' },
                    ticks: { precision: 0 },
                },
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: context => {
                            const count = context.parsed.y;
                            const percentage = (total > 0 ? 100 * count / total : 0)
                                .toLocaleString('de-DE', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
                            return `Anzahl: ${count} (${percentage} %)`;
                        },
                    },
                },
            },
        },
    });
})();
</script>
<?
        }

        private function get_total_max_points(): int {
            return array_sum($this->max_points) - $this->converted_to_bonus_points;
        }
    }

    class GradeListEntry {
        public function __construct(
            readonly public string $student_last,
            readonly public string $student_first,
            readonly public ?array $points,  // Points for each exercise
            readonly public float $bonus_points = 0
        ) {}

        public function get_total_points(): float {
            assert($this->points != null, 'Points not set for student: ' . $this->student_last . ', ' . $this->student_first);
            return array_sum($this->points) + $this->bonus_points;
        }

        public function get_grade(array $grading_config, int $max_points): string {
            assert($this->points != null, 'Points not set for student: ' . $this->student_last . ', ' . $this->student_first);

            $total_points = $this->get_total_points();
            $grading_min_points = grading_min_points($grading_config, $max_points);

            foreach ($grading_min_points as $grade => $min_points) {
                if ($total_points >= $min_points) {
                    return $grade;
                }
            }

            assert(false, 'No grade found for total points: ' . $total_points);
        }
    }


    //////////////
    // Internal //
    //////////////

    // `$grading_config`: Constants like `GRADING_SEK_1_RAW`
    // `max`:             Maximum points achievable in the exam
    // Returns an array mapping grades to minimum points achievable
    // E.g. for Sek. 1 raw and max=100:
    // [
    //     '1' => 96,
    //     '2' => 80,
    //     '3' => 60,
    //     '4' => 40,
    //     '5' => 20,
    //     '6' => 0
    // ]
    function grading_min_points(array $grading_config, int $max) {
        $result = array_map(function($relative) use ($grading_config, $max) {
            // First rounding to n decimal places. E.g., if n=2: 32.00125 -> 32.00
            $finely_rounded = round($relative * $max, $grading_config['rounding_precision']);

            // Ceiling
            // e.g.                                       ceil(2 * $finely_rounded) / 2
            return ceil($grading_config['smallest_points_1_nth'] * $finely_rounded) / $grading_config['smallest_points_1_nth'];
        }, $grading_config['min_relative']);

        return $result;
    }
?>
