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

    define('GRADING_SEK_2_SMALLEST_POINTS_1_NTH', 2);

    
    ////////////////////////////
    // Grading configurations //
    ////////////////////////////

    define('GRADING_SEK_1_RAW', [
        'min_relative'          => GRADING_SEK_1_RAW_MIN_RELATIVE,
        'smallest_points_1_nth' => GRADING_SEK_1_SMALLEST_POINTS_1_NTH
    ]);

    define('GRADING_SEK_1_FINE', [
        'min_relative'          => GRADING_SEK_1_FINE_MIN_RELATIVE,
        'smallest_points_1_nth' => GRADING_SEK_1_SMALLEST_POINTS_1_NTH
    ]);

    define('GRADING_SEK_2_TEST', [
        'min_relative'          => GRADING_SEK_2_TEST_MIN_RELATIVE,
        'smallest_points_1_nth' => GRADING_SEK_2_SMALLEST_POINTS_1_NTH
    ]);

    define('GRADING_SEK_2_EXAM', [
        'min_relative'          => GRADING_SEK_2_EXAM_MIN_RELATIVE,
        'smallest_points_1_nth' => GRADING_SEK_2_SMALLEST_POINTS_1_NTH
    ]);


    ///////////////////////////
    // Create grading tables //
    ///////////////////////////

    function grading_min_points(array $grading_config, int $max) {
        $result = array_map(function($relative) use ($grading_config, $max) {
            // First rounding: 32.00125 -> 32.00
            $finely_rounded = round($relative * $max, 2);

            // Ceiling
            // e.g.                                       ceil(2 * $finely_rounded) / 2
            return ceil($grading_config['smallest_points_1_nth'] * $finely_rounded) / $grading_config['smallest_points_1_nth'];
        }, $grading_config['min_relative']);

        return $result;
    }

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
?>