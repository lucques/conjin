<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'timetable');

        $c->add_subpage('calendar');
        $c->add_subpage('classlog');
        $c->add_subpage('syllabus');
    };
?>
