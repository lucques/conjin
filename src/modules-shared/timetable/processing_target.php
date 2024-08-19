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
    // Macros for gathering data //
    ///////////////////////////////

    function classlog_start() {
        assert($GLOBALS['timetable_cur_classlog'] == null, 'There is still a non-ended classlog.');

        $GLOBALS['timetable_cur_classlog'] = new Classlog();

        // Buffer content in order to throw away on `classlog_end_get`
        ob_start();
    }

    function classlog_end_get(): Classlog {
        assert($GLOBALS['timetable_cur_classlog'] != null, 'There is no classlog to end.');

        // End last log
        if ($GLOBALS['timetable_cur_entry_long_date'] != null) {
            classlog_aux_end_last_log();
        }

        // Throw away output buffer
        ob_end_clean();

        $res = $GLOBALS['timetable_cur_classlog'];
        $GLOBALS['timetable_cur_classlog'] = null;

        return $res;
    }

    // Start a long entry
    function classlog_long(int $year, int $month, int $day, string $title, ?int $timeslot = null, ?string $id = null) {
        assert($GLOBALS['timetable_cur_classlog'] != null, 'There is no classlog.');

        // End last log
        if ($GLOBALS['timetable_cur_entry_long_date'] != null) {
            classlog_aux_end_last_log();
        }

        $GLOBALS['timetable_cur_entry_long_date']     = new DateTimeImmutable("$year-$month-$day");
        $GLOBALS['timetable_cur_entry_long_id']       = $id ?? kebabize($title);
        $GLOBALS['timetable_cur_entry_long_title']    = $title;
        $GLOBALS['timetable_cur_entry_long_timeslot'] = $timeslot;

        // Start buffering content
        ob_start();
    }

    function classlog_short(int $year, int $month, int $day, string $title, ?int $timeslot = null, ?string $id = null, ?string $content = null) {
        assert($GLOBALS['timetable_cur_classlog'] != null, 'There is no classlog.');

        $date = new DateTimeImmutable("$year-$month-$day");
        $log = [
            'type'  => 'log-short',
            'id'    => 'log' . date_to_iso($date) . '-' . ($id ?? kebabize($title)),
            'title' => $title
        ];

        if ($content !== null) {
            $log['content'] = $content;
        }

        $GLOBALS['timetable_cur_classlog']->add($log, $date, $timeslot);
    }


    /////////////////////////////////////////
    // Macros during building a long entry //
    /////////////////////////////////////////

    function classlog_h(int $level, string $title) {
        assert($GLOBALS['timetable_cur_classlog'] !== null, 'There is no long entry.');

?>
                <div><strong><?= $title ?></strong></div>
<?
    }            

    function classlog_img(string $file_name, string $width = null) {
        assert($GLOBALS['timetable_cur_classlog'] !== null, 'There is no long entry.');

        $style  = $width === null ? 'width:100%;' : ' width:' . $width . ';';
        $style .= ' max-width:100%;';
        $style .= ' border: 2px solid gray;';
        $style .= ' padding: 30px;';
?>
                <p class="text-center">
                    <img src="res/<?= date_to_iso($GLOBALS['timetable_cur_entry_long_date']) ?>/<?= $file_name ?>" alt="" loading="lazy" style="<?= $style ?>" class="rounded">
                </p>
<?
    }

    function classlog_a_file(string $file_name, string $text = null, bool $solution = false) {
        assert($GLOBALS['timetable_cur_classlog'] !== null, 'There is no long entry.');

        if ($text === null) {
            $text = $file_name;
        }
        $suffix = $solution ? ' (Lösungsvorschlag)' : '';

        echo '<a href="res/' . date_to_iso($GLOBALS['timetable_cur_entry_long_date']) . '/' . $file_name . '">' . $text . $suffix . '</a>';
    }

    function classlog_daily_exercise(string $width = null, bool $with_solution = true, string $solution_caption = 'Lösungsvorschlag zur TÜ') {
        assert($GLOBALS['timetable_cur_classlog'] !== null, 'There is no long entry.');

        classlog_h(2, 'Tägliche Übung');
        classlog_img('t_ue.png', $width);
        if ($with_solution) {
?>
                <p>
<?
            classlog_a_file('t_ue_loesung.pdf', $solution_caption);
?>
                </p>
<?
        }
    }


    /////////////
    // Helpers //
    /////////////

    function classlog_aux_end_last_log() {
        assert($GLOBALS['timetable_cur_entry_long_date'], 'There is no long entry.');

        // Stop buffering content
        $content = ob_get_clean();

        // Add
        $GLOBALS['timetable_cur_classlog']->add(
            [
                'type'           => 'log-long',
                'id'             => 'log-' . date_to_iso($GLOBALS['timetable_cur_entry_long_date']) . '-' . $GLOBALS['timetable_cur_entry_long_id'],
                'title'          => $GLOBALS['timetable_cur_entry_long_title'],
                'content'        => $content
            ],
            $GLOBALS['timetable_cur_entry_long_date'],
            $GLOBALS['timetable_cur_entry_long_timeslot']
        );
    }


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
            $entries = $sc->get_entries_on($date_cur, ['tag', 'log-long', 'log-short', 'topic-slot', 'cancellation-date', 'cancellation-slot']);
            
            // If there exists a `log` entry, then drop `topic` entries
            $exists_log = false;

            foreach ($entries as $entry) {
                // If there exists a `log` entry, then drop `topic` entries
                if ($entry['type'] == 'log-long' || $entry['type'] == 'log-short') {
                    $exists_log = true;
                }
                if ($entry['type'] == 'topic-slot' && $exists_log) {
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
                elseif ($entry['type'] == 'cancellation-date') {
                    $properties['title'] = $entry['reason'];
                    $properties['weight'] = 4;
                    $properties['display'] = 'background';
                    $properties['backgroundColor'] = 'gray';
                }
                else { // 'cancellation-slot'
                    $properties['title'] = $entry['reason'];
                    $properties['weight'] = 5;
                    $properties['display'] = 'background';
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
            <th>Datum</th>
            <th>Log</th>
        </tr>
    </thead>
    <tbody>
<?
        $slot_earliest = $sc->get_slot_earliest();
        $slot_latest = $sc->get_slot_latest();

        $cur_day = DateTime::createFromImmutable($slot_latest->date);
        while ($cur_day >= $slot_earliest->date) {
            $entries = $sc->get_entries_on($cur_day, ['log-long', 'log-short']);
            if (count($entries) > 0) {
                $date_str = weekday_abbrev($cur_day->format('w')) . '., ' . $cur_day->format('d.m.Y');
?>
        <tr>
            <td>
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
                        acc_single_item_start($entry['title'], variant: 'dark', item_id: $entry['id']);
                        echo $entry['content'];
                        acc_single_item_end();
                    }
                    else { // 'log-short'
                        echo '<div id="' . $entry['id'] . '">' . $entry['title'] . '</div>';
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

    function timetable_print_syllabus(Schedule $sc, ?string $calendar_url = null) {
?>
<table class="table table-bordered border-dark">
    <thead>
        <tr>
            <th></th>
            <th class="text-decoration-underline">Thema</th>
            <th class="text-decoration-underline">Blöcke</th>
            <th class="text-decoration-underline">Beginn</th>
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
                else { // 'topic'
                    // New unit?
                    if ($cur_unit_id == null || $cur_unit_id != $entry['topic']->unit->id) {
                        $cur_unit_id = $entry['topic']->unit->id;
                        $cur_unit_color = variant_to_color('layer_' . ($sc->get_order_of_unit($entry['topic']->unit->id)%5)+1);
                        $cur_unit_counter++;
                        $cur_topic_counter = 0;
?>
        <tr class="table-<?= $cur_unit_color ?> table-active border-dark" style="border-top-width:4px;">
            <td><strong><?= $cur_unit_counter ?></strong></td>
            <td><strong><?= $entry['topic']->unit->title ?> </strong></td>
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
            <td><?= $entry['topic']->title ?></td>
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
?>