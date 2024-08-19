<?
    $init_preprocessing = function(Module $m, PreprocessContext $c) {
        $c->activate_module('fullcalendar'); // For displaying the calendar

        $c->activate_module('locale-de'); // First attempt at localization, still todo
    };
?>