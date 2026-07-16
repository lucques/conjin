<?
    $init_processing_target = function(Module $module, Target $target) {       
        $GLOBALS['test_next_number'] = $module->config->get('exercise_start');
        $GLOBALS['test_points']      = 0;
    };


    function exam_task_add_points(int $points) {
        $GLOBALS['test_points'] += $points;
    }

    function exam_task_set_next_number(int $number) {
        $GLOBALS['test_next_number'] = $number;
    }


    // Task
    // $margin-top in cm
    function exam_task_start(
        array $points,
        array $points_bonus = [],
        float $margin_top = 0.5,
        float $margin_bottom = 0,
        string $title = '',
        string $desc = '',
        string $class = '',
        string $style = ''
    ) {
        $margin_top = is_sol_mode_on() ? 0.5 : $margin_top;
        $margin_bottom = is_sol_mode_on() ? 0 : $margin_bottom;
?>
<div class="exercise"<?= $class !== '' ? ' class="' . $class . '"' : '' ?> style="margin-top: <?= $margin_top ?>cm; margin-bottom: <?= $margin_bottom ?>cm;<?= $style !== '' ? ' ' . $style : '' ?>">
    <div class="header">
        <span class="title"><span class="enum"><?= $title !== '' ? $title : 'Aufgabe ' . $GLOBALS['test_next_number'] ?></span><?= $desc !== '' ? ' &nbsp;<span class="desc">(' . $desc . ')</span>' : '' ?></span>
<?
        // Calculate points
        $points_total = array_reduce($points, function($carry, $item) {
            return $carry + $item;
        }, 0);
        $points_bonus_total = array_reduce($points_bonus, function($carry, $item) {
            return $carry + $item;
        }, 0);

        // Prepare text
        $text = '';

        if ($points_total > 0) {
            $text .= $points_total . ' P.';

            if (count($points) > 1) {
                $text .= ' (' . implode(' + ', $points) . ')';
            }
        }

        if ($points_total > 0 && $points_bonus_total > 0) {
            $text .= ' + ';
        }

        if ($points_bonus_total > 0) {
            $text .= $points_bonus_total . ' Bonusp.';
        }

        // Add to global points
        $GLOBALS['test_points'] += $points_total;

        if ($text !== '') {
?>
        <span class="points">/ <?= $text ?></span>
<?
        }
        else {
?>
        <span class="points"><em>(ohne Bepunktung)</em></span>
<?
    }
?>
    </div>
    <div class="body">
<?
    }

    function exam_task_suspend() {
?>
    </div>
</div>
<?
    }

    function exam_task_resume() {
?>
<div class="exercise">
    <div class="body">
<?
    }

    function exam_task_end() {
?>
    </div>
</div>
<?
        // Increment counter
        $GLOBALS['test_next_number']++;
    }

    function exam_sol_start() {
        sol_start();
?>
<div class="exercise">
    <div class="header">
        <span class="title"><span class="enum">Lösung</span></span>
    </div>
    <div class="body">
<?
    }

    function exam_sol_end() {
?>
    </div>
</div>
<?
        sol_end();
    }
    
    function exam_page_break(float $margin_top = 0.5, string $style = '') {
        // Only page break if not in solution mode
        if (!is_sol_mode_on()) {
            page_break_with_sign(margin_top: $margin_top, style: $style);
        }
    }

    function exam_page_break_in_sol_mode() {
        // Only page break if in solution mode
        if (is_sol_mode_on()) {
            page_break();
        }
    }

    // Grading table
    function exam_print_grading_table(array $grading_config, float $margin_top = 0.5, string $style = '') {
        // Only print if not in solution mode
        if (!is_sol_mode_on()) {
            $grading_table = grading_table_points($grading_config, $GLOBALS['test_points']);

            echo '<div class="grades-table" style="margin-top: ' . $margin_top . 'cm; ' . ($style !== '' ? ' ' . $style : '') . '">';
            echo 'Benotung:&nbsp;&nbsp; ';

            foreach ($grading_table as $grade => $points) {
                echo $points['min'] . '−' . $points['max'] . ' ≙ ' . $grade . ';&nbsp;&nbsp; ';
            }
            echo '</div>';
        }
    }
?>
