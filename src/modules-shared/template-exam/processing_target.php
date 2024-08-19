<?
    /*
        This module offers to include exercises for the test template.
    */

    $init_processing_target = function(Module $module, Target $target) {       
        $GLOBALS['test_next_number'] = 1;
        $GLOBALS['test_points']      = 0;
    };


    function task_set_next_number(int $number) {
        $GLOBALS['test_next_number'] = $number;
    }


    // Task
    // $margin-top in cm
    function task_start(array $points, array $points_bonus = [], float $margin_top = 0.5, string $title = '', string $desc = '', string $class = '', string $style = '') {
?>
<div class="exercise"<?= $class !== '' ? ' class="' . $class . '"' : '' ?> style="margin-top: <?= $margin_top ?>cm;<?= $style !== '' ? ' ' . $style : '' ?>">
    <div class="header">
        <span class="title"><span class="enum"><?= $title !== '' ? $title : 'Aufgabe ' . $GLOBALS['test_next_number'] ?></span><?= $desc !== '' ? ' &nbsp;<span class="desc">' . $desc . '</span>' : '' ?></span>
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

    function task_suspend() {
?>
    </div>
</div>
<?
    }

    function task_resume() {
?>
<div class="exercise">
    <div class="body">
<?
    }

    function task_end() {
?>
    </div>
</div>
<?
        // Increment counter
        $GLOBALS['test_next_number']++;
    }

    // "Please turn" sign
    function page_break_with_sign(string $text = '(Bitte wenden)', float $margin_top = 0.5, string $style = '') {
?>
        <p class="text-center" style="margin-top: <?= $margin_top ?>cm;<?= $style !== '' ? ' ' . $style : '' ?>"><em><?= $text ?></em></p>
<?
        page_break();
    }

    // Grading table
    function print_grading_table(array $grading_config, float $margin_top = 0.5, string $style = '') {
        $grading_table = grading_table_points($grading_config, $GLOBALS['test_points']);

        echo '<div class="grades-table" style="margin-top: ' . $margin_top . 'cm; ' . ($style !== '' ? ' ' . $style : '') . '">';
        echo 'Benotung:&nbsp;&nbsp; ';

        foreach ($grading_table as $grade => $points) {
            echo $points['min'] . '−' . $points['max'] . ' ≙ ' . $grade . ';&nbsp;&nbsp; ';
        }
        echo '</div>';
    }
?>