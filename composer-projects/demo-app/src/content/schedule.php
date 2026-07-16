<?
    $schedule = function() {

        ///////////////
        // Timetable //
        ///////////////

        $tt = new Timetable();

        // Holidays: Single
        $tt->cancel_date('2023-10-03', 'Tag d. dt. Einheit');
        $tt->cancel_date('2023-10-30', 'Ref.tag');
        $tt->cancel_date('2023-10-31', 'Frei');
        $tt->cancel_date('2023-11-01', 'Frei');
        $tt->cancel_date('2024-03-08', 'Tag d. Frau'); 
        $tt->cancel_date('2024-05-01', '1. Mai');
        $tt->cancel_date('2024-05-09', 'Christi Himmelfahrt');
        $tt->cancel_date('2024-05-10', 'Frei');
        $tt->cancel_date('2024-05-17', 'Pfingsten'); 
        $tt->cancel_date('2024-05-20', 'Frei');
        $tt->cancel_date('2024-05-21', 'Frei');

        // Holidays: Intervals
        $tt->cancel_interval('2023-10-09', '2023-10-15', 'Herbstferien');
        $tt->cancel_interval('2023-12-21', '2024-01-03', 'Weihnachtsferien');
        $tt->cancel_interval('2024-02-05', '2024-02-18', 'Winterferien');
        $tt->cancel_interval('2024-03-25', '2024-04-03', 'Osterferien');


        // Irregular cancellations
        $tt->cancel_date('2023-08-28', 'Krank');
        $tt->cancel_date('2023-08-29', 'Krank');
        $tt->cancel_date('2023-09-07', 'Krank');
        $tt->cancel_date('2023-10-05', 'Krank');
        $tt->cancel_date('2024-06-21', 'Krank');
        $tt->cancel_date('2024-06-27', 'Krank');

        // Irregular additional lessons
        $tt->add('2023-11-02', 3);
        $tt->add('2023-12-12', 3);


        // Erster Abschnitt
        $intervals = [
            [
                'from'                 => '2023-08-28',
                'to'                   => '2023-10-06',
                'weeks_offset_until_a' => 0
            ],
            // Herbstferien
            [
                'from'                 => '2023-10-16',
                'to'                   => '2023-11-13',
                'weeks_offset_until_a' => 0
            ],
        ];
        $slots_repeated = [
            [
                'weekday'             => 0,
                'timeslot'            => 1,
                'weeks_offset_from_a' => 0,
            ],
            [
                'weekday'             => 1,
                'timeslot'            => 4,
                'weeks_offset_from_a' => 0,
            ],
            [
                'weekday'             => 0,
                'timeslot'            => 1,
                'weeks_offset_from_a' => 1,
            ],
            [
                'weekday'             => 3,
                'timeslot'            => 3,
                'weeks_offset_from_a' => 1
            ]
        ];
        $tt->add_sub_timetable($intervals, $slots_repeated, 2);

        // Zweiter Abschnitt
        $intervals = [
            [
                'from'                 => '2023-11-14',
                'to'                   => '2023-12-20',
                'weeks_offset_until_a' => 0
            ],
            // Weihnachtsferien
            [
                'from'                 => '2024-01-04',
                'to'                   => '2024-02-02',
                'weeks_offset_until_a' => 1
            ],
            // Winterferien
        ];
        $slots_repeated = [
            [
                'weekday'             => 0,
                'timeslot'            => 1,
                'weeks_offset_from_a' => 0,
            ],
            [
                'weekday'             => 4,
                'timeslot'            => 3,
                'weeks_offset_from_a' => 0
            ],
            [
                'weekday'             => 0,
                'timeslot'            => 1,
                'weeks_offset_from_a' => 1,
            ],
            [
                'weekday'             => 3,
                'timeslot'            => 3,
                'weeks_offset_from_a' => 1,
            ]
        ];
        $tt->add_sub_timetable($intervals, $slots_repeated, 2);

        // Dritter Abschnitt
        $intervals = [
            // Winterferien
            [
                'from'                 => '2024-02-19',
                'to'                   => '2024-03-22',
                'weeks_offset_until_a' => 0
            ],
            // Osterferien
            [
                'from'                 => '2024-04-04',
                'to'                   => '2024-07-12',
                'weeks_offset_until_a' => 1
            ],
        ];
        $slots_repeated = [
            [
                'weekday'             => 0,
                'timeslot'            => 1,
                'weeks_offset_from_a' => 0,
            ],
            [
                'weekday'             => 4,
                'timeslot'            => 1,
                'weeks_offset_from_a' => 0
            ],
            [
                'weekday'             => 0,
                'timeslot'            => 1,
                'weeks_offset_from_a' => 1,
            ],
            [
                'weekday'             => 3,
                'timeslot'            => 3,
                'weeks_offset_from_a' => 1,
            ]
        ];
        $tt->add_sub_timetable($intervals, $slots_repeated, 2);


        //////////////
        // Classlog //
        //////////////

        $cl = load_def_from_script_and_call(__DIR__ . '/module-demos/timetable/classlog/classlog.php', 'classlog');


        //////////////
        // Syllabus //
        //////////////

        $sy = new Syllabus();

        $sy->unit_begin('Wiederholung und ein sehr langer Unit-Titel an dieser Stelle');
        $sy->add_topic('Wiederholung und ein sehr langer Topic-Titel an dieser Stelle', 6);
        $sy->unit_end();
    
        $sy->unit_begin('Zuordnungen');
        $sy->add_topic('Zuordnungen', 7);
        $sy->unit_end();
    
        $sy->unit_begin('Prozentrechnung');
        $sy->add_topic('Prozentrechnung', 13);
        $sy->add_topic('Netto vs. Brutto', 2);
        $sy->unit_end();
    
        $sy->unit_begin('Zinsrechnung');
        $sy->add_topic('Einführung', 1);
        $sy->add_topic('Zinseszinsen', 1);
        $sy->add_topic('Anwendung von Zinsenszinsen', 1);
        $sy->add_topic('Üben vor KA', 1);
        $sy->add_topic('Klassenarbeit', 1);
        $sy->add_topic('Halbjahresabschluss', 1);
        $sy->unit_end();
    
        $sy->unit_begin('Rationale Zahlen');
        $sy->add_topic('Rationale Zahlen', 16);
        $sy->unit_end();
    
        $sy->unit_begin('Planimetrie');
        $sy->add_topic('Planimetrie', 14);
        $sy->unit_end();
    
        $sy->unit_begin('Körperdarstellung');
        $sy->add_topic('Körperdarstellung', 6);
        $sy->unit_end();


        //////////////
        // Schedule //
        //////////////

        $sc = new Schedule($tt, $cl, $sy);

        // Tests & Klassenarbeiten
        $sc->add_tag('2023-09-25', 'Test 1');
        $sc->add_tag('2023-11-20', 'Klassenarbeit 1');
        $sc->add_tag('2023-12-18', 'Test 2');
        $sc->add_tag('2024-01-29', 'Klassenarbeit 2');
        $sc->add_tag('2024-01-18', 'UB');
        $sc->add_tag('2024-04-15', 'Test 3');
        $sc->add_tag('2024-06-03', 'Test 4');
        $sc->add_tag('2024-06-17', 'Klassenarbeit 3');

        return $sc;
    }
?>
