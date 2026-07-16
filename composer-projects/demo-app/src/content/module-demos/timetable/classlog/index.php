<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'classlog');

        $c->activate_module('timetable');
    };
?>

<? $process = function (Target $target) {
    $sc = load_def_from_script_and_call(__DIR__ . '/../../../schedule.php', 'schedule');
?>

<? timetable_print_classlog($sc, '../calendar/'); ?>

<? }; ?>
