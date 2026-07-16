<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->activate_module('fullcalendar');

        $c->add_subpage('bootstrap');
    };
?>

<? $process = function (Target $target) { ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('the-calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {});
    calendar.render();
});      
</script>

<div style="width:800px; margin: auto; margin-top:30px;">
    <div id="the-calendar"></div>
</div>

<? }; ?>
