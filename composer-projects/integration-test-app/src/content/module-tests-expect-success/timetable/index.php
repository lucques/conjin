<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('timetable');
    };
?>

<? $process = function (Target $target) {
    $timetable = new Timetable();
    $timetable->add('2024-01-08', 1);
    $timetable->add('2024-01-15', 1);
    $timetable->add('2024-01-22', 1);
    $timetable->add('2024-01-29', 1);

    $schedule = new Schedule($timetable);
?>

<? timetable_print_calendar($schedule, 2); ?>

<? }; ?>
