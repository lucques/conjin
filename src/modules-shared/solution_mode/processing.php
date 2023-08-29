<?
    /*
        This module offers a button on the page that activates the so-called
        "solution mode". If the button is not clicked, the page remains in
        "exercise mode". The whole target is viewed as an exercise sheet and
        when the solution mode is activated, the same page including the
        solutions are shown.

        Terminology:
        - Mode *enabled*: Offers the button
        - Mode *requested*: User requests mode via query string in
          URL (e.g. clicked on the button)
        - Mode *active*: Enabled and requested

        If this module is activated, it offers:
        - Option to enable/disable mode (default: disabled)
        - Lots of functions

        Any decisions on how to design the print page is not handled by this
        module.
    */

    $init_processing = function(Module $module, Target $target) {
        $GLOBALS['solution_mode_enabled']   = $module->config->get_or_default(['enabled'], false);
        $GLOBALS['solution_mode_requested'] = isset($_GET['solution']) && $_GET['solution'] === '1';

        if (is_solution_mode_active()) {
            doc_extensions_add_body_class('solution-mode');
        }
        else {
            doc_extensions_add_body_class('exercise-mode');
        }
    };


    //////////////////////////////////////
    // Functions offered for PROCESSING //
    //////////////////////////////////////

    function is_solution_mode_enabled(): bool {
        return $GLOBALS['solution_mode_enabled'];
    }

    function is_solution_mode_active(): bool {
        return $GLOBALS['solution_mode_enabled'] && $GLOBALS['solution_mode_requested'];
    }

    function solution_mode_get_queries_to_activate(): array {
        return ['solution' => true];
    }

    function solution_mode_get_queries_to_deactivate(): array {
        return ['solution' => null];
    }

    function span_hide_in_exercise_begin() {
        if (!is_solution_mode_active()) {
            echo '<span style="visibility:hidden;">';
        }
    }

    function span_hide_in_exercise_end() {
        if (!is_solution_mode_active()) {
            echo '</span>';
        }
    }

    function solution_only_begin() {
        if (is_solution_mode_active()) {

        }
        else {
            ob_start();
        }
    }

    function solution_only_end() {
        if (is_solution_mode_active()) {

        }
        else {
            ob_end_clean();
        }
    }

    function exercise_only_begin() {
        if (!is_solution_mode_active()) {

        }
        else {
            ob_start();
        }
    }

    function exercise_only_end() {
        if (!is_solution_mode_active()) {

        }
        else {
            ob_end_clean();
        }
    }

    function span_fill_the_gap_begin() {
        if (!is_solution_mode_active()) {
            echo '<span class="fill-the-gap"><span>';
        }
    }

    function span_fill_the_gap_end() {
        if (!is_solution_mode_active()) {
            echo '</span></span>';
        }
    }

    function span_fill_the_gap($html) {
        span_fill_the_gap_begin();
        echo $html;
        span_fill_the_gap_end();
    }

    function fill_the_gap($html) {
        echo '<span class="fill-the-gap"><span>' . $html . '</span></span>';
    }

    function fill_the_gap_div($html) {
        echo '<div class="fill-the-gap"><div>' . $html . '</div></div>';
    }

    function fill_the_gap_div_begin() {
        echo '<div class="fill-the-gap"><div>';
    }

    function fill_the_gap_div_end() {
        echo '</div></div>';
    }

    function fill_the_gap_exercise_begin() {
        echo '<div class="fill-the-gap-exercise">';
    }

    function fill_the_gap_exercise_end() {
        echo '</div>';
    }

    function fill_the_gap_solution_begin() {
        echo '<div class="fill-the-gap-solution">';
    }

    function fill_the_gap_solution_end() {
        echo '</div>';
    }
?>