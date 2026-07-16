<?
    require_once(__DIR__ . '/datatypes.php');
    
    $init_processing_target = function(Module $module, Target $target) {
        $GLOBALS['timetable_cur_classlog']            = null;
        $GLOBALS['timetable_cur_entry_long_date']     = null;
        $GLOBALS['timetable_cur_entry_long_id']       = null;
        $GLOBALS['timetable_cur_entry_long_title']    = null;
        $GLOBALS['timetable_cur_entry_long_timeslot'] = null;  // null means no timeslot specified      
    };


    ///////////////////////////////
    // Macros for printing views //
    ///////////////////////////////

    function timetable_print_calendar(Schedule $sc, int $title_level, ?string $classlog_url = null, ?string $syllabus_url = null) {

        $slot_earliest = $sc->get_slot_earliest();
        $slot_latest = $sc->get_slot_latest();

        // Determine whether there is a GET request for a specific date
        $date_requested = null;
        if (isset($_GET['date'])) {
            $date_requested = DateTime::createFromFormat('Y-m-d', $_GET['date']);
            if ($date_requested === false) {
                $date_requested = null;
            }
        }
        if ($date_requested === null) {
            $date_requested = new DateTime();
        }

        $date_cur = DateTime::createFromImmutable($slot_earliest->date);
        $html_id = 'timetable_calendar_' . next_unique_id();

        head_start();
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('<?= $html_id ?>');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        themeSystem: 'bootstrap5',
        height: 'auto',
        headerToolbar: {
            left:  'title',
            right: 'prev,today,next',
        },
        buttonText: { today: 'Heute' },
        datesSet: function(view) {
            const title = calendar.view.title;
            document.querySelector('.fc-toolbar-title').parentElement.innerHTML = '<h<?= $title_level ?>>' + title + '</<?= $title_level ?>>';
        },
        initialView: 'custom',
        views: {
            custom: {
                type: 'dayGrid',
                duration: { months: 1 },
            },
        },
        locale: 'de',
        firstDay: 1,
        weekends: false,
        eventOrder: 'weight',
        validRange: {
            start: '<?= date_to_iso($slot_earliest->date) ?>',
            end:   '<?= date_to_iso($slot_latest->date) ?>'
        },
        eventContent: function(arg) {
            return { html: '<span style="white-space:normal;">' + arg.event.title + '</span>' };
        },
        initialDate: '<?= date_to_iso($date_requested) ?>',
        events: [
<?
        while ($date_cur <= $slot_latest->date) {
            $entries = $sc->get_entries_on($date_cur, ['tag', 'log-long', 'log-short', 'topic-slot', 'slot', 'cancellation-date', 'cancellation-slot']);
            
            // If there exists a `log` entry, then drop `topic` entries
            $exists_log = false;
            $exists_topic = false;

            foreach ($entries as $entry) {
                // If there exists a `log` entry, then drop `topic` entries
                if ($entry['type'] == 'log-long' || $entry['type'] == 'log-short') {
                    $exists_log = true;
                }
                if ($entry['type'] == 'topic-slot') {
                    $exists_topic = true;
                }
                if (
                    ($entry['type'] == 'topic-slot' && $exists_log) ||
                    ($entry['type'] == 'slot' && ($exists_log || $exists_topic))
                ) {
                    continue;
                }

                echo "{\n";

                $properties = [];
                $properties['start'] = date_to_iso($date_cur);
                $properties['end']   = date_to_iso($date_cur);

                if ($entry['type'] == 'tag') {
                    $properties['title'] = $entry['title'];
                    $properties['id'] = $entry['id'];
                    $properties['backgroundColor'] = 'black';
                    $properties['weight'] = 0;

                    if ($syllabus_url != null) {
                        $properties['url'] = $syllabus_url . '#' . $entry['id'];
                    }
                }
                elseif ($entry['type'] == 'log-long') {
                    $properties['title'] = $entry['title'];
                    $properties['id'] = $entry['id'];
                    $properties['weight'] = 1;

                    if ($classlog_url != null) {
                        $properties['url'] = $classlog_url . '#' . $entry['id'];
                    }
                }
                elseif ($entry['type'] == 'log-short') {
                    $properties['title'] = $entry['title'];
                    $properties['id'] = $entry['id'];
                    $properties['weight'] = 2;

                    if ($classlog_url != null) {
                        $properties['url'] = $classlog_url . '#' . $entry['id'];
                    }
                }
                elseif ($entry['type'] == 'topic-slot') {
                    $properties['title']  = $entry['topic']->title;
                    $properties['id'] = $entry['id'];
                    $properties['weight'] = 3;

                    // Determine background color according to unit
                    $color = 'var(--bs-' . variant_to_color('layer_' . ($sc->get_order_of_unit($entry['topic']->unit->id)%5)+1) . '-border-subtle)';

                    $properties['backgroundColor'] = $color;
                    $properties['textColor'] = 'black';

                    if ($syllabus_url != null) {
                        $properties['url'] = $syllabus_url . '#' . $entry['id'];
                    }
                }
                elseif ($entry['type'] == 'slot') {
                    $properties['title'] = 'Unterricht';
                    $properties['backgroundColor'] = 'var(--bs-success)';
                    $properties['weight'] = 4;
                }
                elseif ($entry['type'] == 'cancellation-date') {
                    $properties['title'] = $entry['reason'];
                    $properties['weight'] = 5;
                    $properties['display'] = 'background';
                    $properties['backgroundColor'] = 'gray';
                }
                elseif ($entry['type'] == 'cancellation-slot') {
                    $properties['title'] = $entry['reason'];
                    $properties['weight'] = 6;
                    $properties['display'] = 'background';
                }
                else {
                    throw new Exception('Unknown entry type: ' . $entry['type']);
                }

                echo implode(",\n", array_map(function($key, $value) {
                    return "   $key: '$value'";
                }, array_keys($properties), array_values($properties)));
                echo "\n},\n";
            }

            $date_cur->modify('+1 day');
        }
?>
        ],
    });
    calendar.render();
});      
</script>
<?
        head_end();
?>
            <div id="<?= $html_id ?>" class="timetable_calendar"></div>
<?
    }

    function timetable_print_progress(Timetable $tt) {
        $slots_finished = $tt->get_number_of_slots_up_to(new DateTime());
        $slots_total = $tt->get_number_of_slots();

        $percentage = round($slots_finished / $slots_total * 100);
?>
<p>
    Bislang <?= $slots_finished ?> von <?= $slots_total ?> Unterrichtseinheiten abgeschlossen.
</p>
<div class="progress">
    <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"><?= $percentage ?>%</div>
</div>
<?
    }

    function timetable_print_classlog(Schedule $sc, ?string $calendar_url = null) {
?>
<table class="table">
    <thead>
        <tr>
            <th style="width:100px;">Datum</th>
            <th>Log</th>
        </tr>
    </thead>
    <tbody>
<?
        $slot_earliest = $sc->get_slot_earliest();
        $slot_latest = $sc->get_slot_latest();

        $cur_day = DateTime::createFromImmutable($slot_latest->date);
        $first_log_already_passed = false;  // The first log entry is shown as "open"
        while ($cur_day >= $slot_earliest->date) {
            $entries = $sc->get_entries_on($cur_day, ['log-long', 'log-short']);
            if (count($entries) > 0) {
                $date_str = weekday_abbrev($cur_day->format('w')) . '., ' . $cur_day->format('d.m.Y');
?>
        <tr>
            <td style="white-space: nowrap;">
<?
                if ($calendar_url != null) {
?>
                <a href="<?= $calendar_url . make_query_string(['date' => date_to_iso($cur_day)], base_queries: $_GET) ?>"><?= $date_str ?></a>
<?
                }
                else {
                    echo $date_str;
                }
?>
            </td>
            <td>
<?
                foreach ($entries as $entry) {
                    if ($entry['type'] == 'tag') {
                        echo '<div id="' . $entry['id'] . '"><strong>' . $entry['title'] . '</strong></div>';
                    }
                    elseif ($entry['type'] == 'log-long') {
                        acc_single_item_start($entry['title'], variant: 'dark', item_id: $entry['id'], open: !$first_log_already_passed);
                        echo $entry['content'];
                        acc_single_item_end();

                        $first_log_already_passed = true;
                    }
                    else { // 'log-short'
                        echo '<div id="' . $entry['id'] . '">' . $entry['title'] . '</div>';

                        $first_log_already_passed = true;
                    }
                }
?>
            </td>
        </tr>
<?
            }
            $cur_day->modify('-1 day');
        }
?>
    </tbody>
</table>
<?
    }

    function timetable_print_syllabus_with_schedule(Schedule $sc, ?string $calendar_url = null) {
?>
<table class="table table-bordered border-dark">
    <thead>
        <tr>
            <th></th>
            <th>Thema</th>
            <th>Blöcke</th>
            <th>Beginn</th>
        </tr>
    </thead>
    <tbody>
<?

        $slot_earliest = $sc->get_slot_earliest();
        $slot_latest   = $sc->get_slot_latest();

        $cur_unit_id       = null;
        $cur_unit_counter  = 0;

        $cur_topic_id      = null;
        $cur_topic_counter = null;
        
        $cur_date = DateTime::createFromImmutable($slot_earliest->date);
        while ($cur_date <= $slot_latest->date) {
            $entries = $sc->get_entries_on($cur_date, ['tag', 'topic-slot']);

            foreach ($entries as $entry) {
                if ($entry['type'] == 'tag') {
?>
        <tr id="<?= $entry['id'] ?>" class="table-secondary border-dark">
            <td colspan="3" class="text-center"><em><strong><?= $entry['title'] ?></strong></em></td>
            <td>
<?
                    if ($calendar_url != null) {
?>
                <a href="<?= $calendar_url . make_query_string(['date' => date_to_iso($cur_date)], base_queries: $_GET) ?>"><?= $cur_date->format('d.m.Y') ?></a>
<?
                    }
                    else {
                        echo $cur_date->format('d.m.Y');
                    }
?>
            </td>
        </tr>
<?
                }
                else { // 'topic-slot'
                    // New unit?
                    if ($cur_unit_id == null || $cur_unit_id != $entry['topic']->unit->id) {
                        $cur_unit_id = $entry['topic']->unit->id;
                        $cur_unit_color = variant_to_color('layer_' . ($sc->get_order_of_unit($entry['topic']->unit->id)%5)+1);
                        $cur_unit_counter++;
                        $cur_topic_counter = 0;
?>
        <tr class="table-<?= $cur_unit_color ?> table-active border-dark" style="border-top-width:4px;">
            <td><strong><?= $cur_unit_counter ?></strong></td>
            <td class="text-decoration-underline"><strong><?= $entry['topic']->unit->title ?> </strong></td>
            <td><strong><?= $entry['topic']->unit->get_number_of_slots() ?></strong></td>
            <td>
<?
                if ($calendar_url != null) {
?>
                <a href="<?= $calendar_url . make_query_string(['date' => date_to_iso($cur_date)], base_queries: $_GET) ?>"><?= $cur_date->format('d.m.Y') ?></a>
<?
                }
                else {
                    echo $cur_date->format('d.m.Y');
                }
?>
            </td>
        </tr>
<?
                    }

                    // New topic?
                    if ($cur_topic_id == null || $cur_topic_id != $entry['topic']->id) {
                        $cur_topic_id = $entry['topic']->id;
                        $cur_topic_counter++;
?>
        <tr id="<?= $entry['id'] ?>" class="table-<?= $cur_unit_color ?> border-dark">
            <td><?= $cur_unit_counter ?>.<?= $cur_topic_counter ?></td>
            <td>
                <span class="text-decoration-underline"><?= $entry['topic']->title ?></span><br>
                <?= $entry['topic']->content ?>
            </td>
            <td><?= $entry['topic']->number_of_slots ?></td>
            <td>
<?
                if ($calendar_url != null) {
?>
                <a href="<?= $calendar_url . make_query_string(['date' => date_to_iso($cur_date)], base_queries: $_GET) ?>"><?= $cur_date->format('d.m.Y') ?></a>
<?
                }
                else {
                    echo $cur_date->format('d.m.Y');
                }
?>
            </td>
        </tr>
<?
                    }
                }
            }

            $cur_date->modify('+1 day');
        }
?>
    </tbody>
</table>
<?
    }

    function timetable_print_syllabus(Syllabus $sy) {
?>
<table class="table table-bordered border-dark">
    <thead>
        <tr>
            <th></th>
            <th>Thema</th>
            <th>Blöcke</th>
        </tr>
    </thead>
    <tbody>
<?
        $cur_unit_counter = 1;

        foreach ($sy->get_units() as $unit) {
            $cur_unit_color = variant_to_color('layer_' . ($cur_unit_counter%5)+1);
?>
        <tr class="table-<?= $cur_unit_color ?> table-active border-dark" style="border-top-width:4px;">
            <td><strong><?= $cur_unit_counter ?></strong></td>
            <td class="text-decoration-underline"><strong><?= $unit->title ?> </strong></td>
            <td><strong><?= $unit->get_number_of_slots() ?></strong></td>
        </tr>
<?
            $cur_topic_counter = 1;
            foreach ($unit->iterate_topics() as $topic) {
?>
        <tr class="table-<?= $cur_unit_color ?> border-dark">
            <td><?= $cur_unit_counter ?>.<?= $cur_topic_counter ?></td>
            <td>
                <span class="text-decoration-underline"><?= $topic->title ?></span><br>
                <?= $topic->content ?>
            </td>
            <td><?= $topic->number_of_slots ?></td>
        </tr>
<?
                $cur_topic_counter++;
            }

            $cur_unit_counter++;
        }
?>
    </tbody>
</table>
<?
    }
        
?>