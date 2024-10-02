<?
    // Abbreviations:
    // - "ser" stands for "serialized"
    // - "date_iso" stands for "date in ISO format", e.g. `2023-08-28`
    //
    // Type of an entry:
    //
    // [
    //     'type'     => 'cancellation-date',
    //     'reason'   => 'Public holiday'
    // ]
    //
    // or
    //
    // [
    //     'type'     => 'cancellation-slot',
    //     'timeslot' => 1,
    //     'reason'   => 'Sickness'
    // ]
    //
    // or
    //
    // [
    //     'type'     => 'slot',
    //     'timeslot' => 1,
    // ]
    // 
    // or
    //
    // [
    //     'type'     => 'log-long',
    //     'id'       => 'log-2024-01-01-title-of-the-entry',   (used as anchor)
    //     'title'    => 'Title of the entry',
    //     'content'  => 'Content of the entry'
    //     'timeslot' => 1,                                     (optional field)
    // ]
    //
    // or
    //
    // [
    //     'type'     => 'log-short',
    //     'id'       => 'log-2024-01-01-title-of-the-entry',   (used as anchor)
    //     'title'    => 'Title of the entry',
    //     'content'  => 'Content of the entry'                 (optional field)
    //     'timeslot' => 1,                                     (optional field)
    // ]
    //
    // or
    //
    // [
    //     'type'     => 'topic-slot',
    //     'timeslot' => 1,
    //     'id'       => 'topic-my-unit-id-my-topic-id',
    //     'topic'    => Topic object,
    // ]
    //
    // or
    //
    // [
    //     'type'     => 'tag',
    //     'id'       => 'tag-2024-01-01-id-of-the-tag',        (used as anchor)
    //     'title'    => 'Name of the tag'
    // ]


    //////////
    // Time //
    //////////

    // Cancellations have to be performed first; afterwards the timetable can be
    // filled with slots.
    // Reason for this: It makes keeping track of `slot_earliest` and
    // `slot_latest` easier
    class Timetable {
        private $date_to_cancellation;             // date_iso -> string
        private $date_to_timeslot_to_cancellation; // date_iso -> int -> string

        private $date_to_slots;                    // date_iso -> list<slot_ser>
        private ?Slot $slot_earliest;
        private ?Slot $slot_latest;

        private bool $started_adding = false;      // For internal tracking

        public function __construct() {
            $this->date_to_cancellation = [];
            $this->date_to_timeslot_to_cancellation = [];

            $this->date_to_slots = [];
            $this->slot_earliest = null;
            $this->slot_latest = null;
        }


        ////////////
        // Cancel //
        ////////////

        public function cancel_slot(string $date_iso, int $timeslot, string $reason) {
            assert(!$this->started_adding, 'Cannot cancel slot after adding.');
            assert_iso_date($date_iso);

            if (!isset($this->date_to_timeslot_to_cancellation[$date_iso])) {
                $this->date_to_timeslot_to_cancellation[$date_iso] = [];
            }

            assert(!isset($this->date_to_timeslot_to_cancellation[$date_iso][$timeslot]), 'Slot already cancelled.');

            $this->date_to_timeslot_to_cancellation[$date_iso][$timeslot] = $reason;
        }

        public function cancel_date(string $date_iso, string $reason) {
            assert(!$this->started_adding, 'Cannot cancel date after adding.');
            assert_iso_date($date_iso);

            assert(!isset($this->date_to_cancellation[$date_iso]), 'Date already cancelled.');

            $this->date_to_cancellation[$date_iso] = $reason;
        }

        public function cancel_interval(string $from_iso, string $to_iso, string $reason) {
            assert_iso_date($from_iso);
            assert_iso_date($to_iso);

            $from = iso_to_date($from_iso);
            $to   = iso_to_date($to_iso);

            $cur_date = DateTime::createFromImmutable($from);

            while ($cur_date <= $to) {
                $this->cancel_date(date_to_iso($cur_date), $reason);
                $cur_date->modify('+1 day');
            }
        }


        /////////
        // Add //
        /////////

        public function may_be_added(string $date_iso, int $timeslot) {
            assert_iso_date($date_iso);

            $slot_ser = Slot::from_date_iso_timeslot($date_iso, $timeslot)->serialize();

            // The following must hold:
            // 1. The date is not cancelled
            // 2. The timeslot is not cancelled
            // 3. The slot is not already added
            return !isset($this->date_to_cancellation[$date_iso]) &&
                   !isset($this->date_to_timeslot_to_cancellation[$date_iso][$timeslot]) &&
                   !(isset($this->date_to_slots[$date_iso]) && in_array($slot_ser, $this->date_to_slots[$date_iso]));
        }

        public function add(string $date_iso, int $timeslot) {
            assert_iso_date($date_iso);

            assert($this->may_be_added($date_iso, $timeslot), 'Slot cannot be added.');

            if (!isset($this->date_to_slots[$date_iso])) {
                $this->date_to_slots[$date_iso] = [];
            }

            $slot     = Slot::from_date_iso_timeslot($date_iso, $timeslot);
            $slot_ser = $slot->serialize();

            $this->date_to_slots[$date_iso][] = $slot_ser;
            
            if ($this->slot_earliest === null || $slot->happens_before($this->slot_earliest)) {
                $this->slot_earliest = $slot;
            }
            if ($this->slot_latest === null || $this->slot_latest->happens_before($slot)) {
                $this->slot_latest = $slot;
            }
        }


        //////////////////////////////////////
        // Only callable after construction //
        //////////////////////////////////////

        public function get_date_to_cancellation(): array {
            return $this->date_to_cancellation;
        }

        public function get_date_to_timeslot_to_cancellation(): array {
            return $this->date_to_timeslot_to_cancellation;
        }

        // Returns `list<slot_ser>`
        public function get_slots_on(DateTimeInterface $date): array {
            if (!isset($this->date_to_slots[date_to_iso($date)])) {
                return [];
            }
            return $this->date_to_slots[date_to_iso($date)] ?? [];
        }

        public function get_slot_earliest(): ?Slot {
            return $this->slot_earliest;
        }

        public function get_slot_latest(): ?Slot {
            return $this->slot_latest;
        }

        public function get_number_of_slots(): int {
            $count = 0;
            foreach ($this->date_to_slots as $date => $slots) {
                $count += count($slots);
            }
            return $count;
        }

        public function get_number_of_slots_up_to(DateTimeInterface $date): int {
            $count = 0;
            $today = date_to_iso($date);
            foreach ($this->date_to_slots as $date => $slots) {
                if ($date <= $today) {
                    $count += count($slots);
                }
            }
            return $count;
        }


        ///////////////////////////
        // Convenience functions //
        ///////////////////////////

        public function add_repeatedly_within_interval(
            string $date_start_iso,
            string $date_end_iso,
            int $weekday,
            int $timeslot,
            int $weeks_offset,
            int $repeat_every_n_weeks
        ) {
            assert_iso_date($date_start_iso);
            assert_iso_date($date_end_iso);

            $date_start = iso_to_date($date_start_iso);
            $date_end   = iso_to_date($date_end_iso);

            $cur_date = DateTime::createFromImmutable($date_start);

            // 1. Week offset
            // 1.a. Finish remaining week
            if ($weeks_offset > 0 && ($cur_date->format('w')+6)%7 != 6) {
                $cur_date->modify('+' . (7 - ($cur_date->format('w')+6)%7) . ' days');
                $weeks_offset--;
            }
            // 1.b. Finish further weeks
            $cur_date->modify("+$weeks_offset weeks");

            // 2. Offset until first occurrence
            // If this week is already gone, forward by `weeks_repeat` weeks
            if (($cur_date->format('w')+6)%7 > $weekday) {
                $cur_date->modify('+' . (($weekday - ($cur_date->format('w')-1) + 7)%7) . ' days');
                $offs = $repeat_every_n_weeks-1;
                $cur_date->modify("+$offs week");
            }
            else {
                $cur_date->modify('+' . (($weekday - ($cur_date->format('w')-1) + 7)%7) . ' days');
            }

            // 3. Repeatedly add slots
            while ($cur_date <= $date_end) {
                $cur_date_iso = date_to_iso($cur_date);
                if ($this->may_be_added($cur_date_iso, $timeslot)) {
                    $this->add($cur_date_iso, $timeslot);
                }
                $cur_date->modify("+$repeat_every_n_weeks week");
            }
        }

        // Explanation for `weeks_offset_until_a` and `weeks_offset_from_a`:
        // `a` is some "base" week offset, "week A". All offsets are relative to this week.
        //
        // Format:
        //
        // $intervals = [
        //     [
        //         'from'                 => new DateTimeImmutable('2023-08-28'),
        //         'to'                   => new DateTimeImmutable('2023-10-06'),
        //         'weeks_offset_until_a' => 0
        //     ],
        //     ...
        // ]
        //
        // $slots_repeated = [
        //     [
        //         'weekday'       => 0,
        //         'timeslot'      => 1,
        //         'weeks_offset_from_a' => 0,
        //     ],
        //     ...
        // ]
        //
        // $repeat_every_n_weeks = 2
        public function add_sub_timetable(array $intervals, array $slots_repeated, int $repeat_every_n_weeks) {
            foreach ($intervals as $interval) {
                foreach ($slots_repeated as $slot_repeated) {
                    $this->add_repeatedly_within_interval(
                        $interval['from'],
                        $interval['to'],
                        $slot_repeated['weekday'],
                        $slot_repeated['timeslot'],
                        ($interval['weeks_offset_until_a'] + $slot_repeated['weeks_offset_from_a'])%$repeat_every_n_weeks,
                        $repeat_every_n_weeks);
                }
            }
        }
    }

    // Immutable
    class Slot {
        public function __construct(
            public readonly DateTimeImmutable $date,
            public readonly int $timeslot)
        {}

        public static function from_date_iso_timeslot(string $date_iso, int $timeslot): Slot {
            assert_iso_date($date_iso);
            return new Slot(iso_to_date($date_iso), $timeslot);
        }


        ////////////////
        // Comparison //
        ////////////////

        public function happens_before(Slot $that): bool {
            return date_to_iso($this->date) < date_to_iso($that->date) ||
                     (date_to_iso($this->date) == date_to_iso($that->date) && $this->timeslot < $that->timeslot); 
        }

        public function equals(Slot $that): bool {
            return date_to_iso($this->date) == date_to_iso($that->date) && $this->timeslot == $that->timeslot;
        }


        ///////////////////
        // Serialization //
        ///////////////////

        public function serialize(): string {
            return date_to_iso($this->date) . ':' . $this->timeslot;
        }

        public static function unserialize(string $serialized): Slot {
            return Slot::from_date_iso_timeslot(...explode(':', $serialized));
        }
    }


    //////////////
    // Syllabus //
    //////////////

    class Syllabus {
        private $units = [];

        // For convencience functions
        private ?Unit   $cur_unit = null;
        private ?string $cur_topic_title = null;
        private ?int    $cur_topic_number_of_slots = null;

        public function __construct() {}

        public function add(Unit $unit) {
            $this->units[] = $unit;
        }


        ///////////////////////////
        // Convenience functions //
        ///////////////////////////

        public function unit_begin(string $title) {
            assert($this->cur_unit == null, 'There is still a non-ended unit.');
            $this->cur_unit = new Unit($title);
        }

        public function unit_end() {
            assert($this->cur_unit != null, 'There is no unit to end.');
            $this->add($this->cur_unit);
            $this->cur_unit = null;
        }

        public function add_topic(string $title, int $number_of_slots) {
            assert($this->cur_unit != null, 'There is no unit.');
            $this->cur_unit->add(new Topic($this->cur_unit, $title, $number_of_slots));
        }

        public function topic_start(string $title, int $number_of_slots) {
            assert($this->cur_unit != null, 'There is no unit to add a topic to.');
            assert($this->cur_topic_title === null, '⊤here is still a non-ended topic.');
            $this->cur_topic_title = $title;
            $this->cur_topic_number_of_slots = $number_of_slots;
            ob_start();
        }

        public function topic_end() {
            assert($this->cur_unit != null, 'There is no unit to end a topic for.');
            assert($this->cur_topic_title !== null, 'There is no topic to end.');
            $content = ob_get_clean();
            $this->cur_unit->add(new Topic($this->cur_unit, $this->cur_topic_title, $this->cur_topic_number_of_slots, $content));
        }


        //////////////////////////////////////
        // Only callable after construction //
        //////////////////////////////////////

        // Returns `ArrayIterator<Topic>`
        public function iterate_topics(): ArrayIterator {
            $topics = [];
            foreach ($this->units as $unit) {
                foreach ($unit->iterate_topics() as $topic) {
                    $topics[] = $topic;
                }
            }
            return new ArrayIterator($topics);
        }

        public function get_units(): array {
            return $this->units;
        }
    }

    class Unit {
        public readonly string $id;

        private $topics;

        public function __construct(public readonly string $title, ?string $id = null) {
            $this->id = $id ?? kebabize($title);

            $this->topics = [];
        }

        public function add(Topic $topic) {
            $this->topics[] = $topic;
        }


        //////////////////////////////////////
        // Only callable after construction //
        //////////////////////////////////////

        // Returns `ArrayIterator<Topic>`
        public function iterate_topics(): ArrayIterator {
            return new ArrayIterator($this->topics);
        }

        public function get_number_of_slots(): int {
            $count = 0;
            foreach ($this->topics as $topic) {
                $count += $topic->number_of_slots;
            }
            return $count;
        }
    }

    // Immutable
    class Topic {
        public readonly string $id;

        public function __construct(
            public readonly Unit    $unit,
            public readonly string  $title,
            public readonly int     $number_of_slots,
            public readonly ?string $content = null,
            ?string $id = null
        ) {
            $this->id = $id ?? kebabize($title);
        }
    }


    //////////////
    // Classlog //
    //////////////

    class Classlog {
        // The first field `date_to_log` is just for convenience: If there
        // is only one log for a day, it is stored here. If there are multiple
        // logs for a day, this cannot be used.
        private array $date_to_log = [];             // date_iso -> entry_log
        private array $date_to_timeslot_to_log = []; // date_iso -> int -> entry_log 

        public function __construct() {}

        // `$log` is either of type `log-long` or `log-short`
        public function add(array $log, DateTimeInterface $date, ?int $timeslot = null) {
            $date_iso = date_to_iso($date);

            if ($timeslot === null) {
                assert(!isset($this->date_to_log[$date_iso]), 'There is already a log for that day.');
                assert(!isset($this->date_to_timeslot_to_log[$date_iso]), 'There are already logs for that day.');

                $this->date_to_log[$date_iso] = $log;
            }
            else {
                if (!isset($this->date_to_timeslot_to_log[$date_iso])) {
                    $this->date_to_timeslot_to_log[$date_iso] = [];
                }

                assert(!isset($this->date_to_timeslot_to_log[$date_iso][$timeslot]), 'There is already a log for that timeslot.');
                assert(!isset($this->date_to_log[$date_iso]), 'There is already a log for that day.');

                $this->date_to_timeslot_to_log[$date_iso][$timeslot] = $log;
            }
        }


        //////////////////////////////////////
        // Only callable after construction //
        //////////////////////////////////////

        public function count_logs_on_date(DateTimeInterface $date): int {
            $date_iso = date_to_iso($date);
            return (isset($this->date_to_log[$date_iso]) ? 1 : 0) +
                   (isset($this->date_to_timeslot_to_log[$date_iso]) ? count($this->date_to_timeslot_to_log[$date_iso]) : 0);
        }

        // Returns either `log-long` or `log-short`
        public function get_log_for_slot(Slot $slot): ?array {
            $date_iso = date_to_iso($slot->date);
            $timeslot = $slot->timeslot;

            // If the slot is uniquely filled
            if (isset($this->date_to_timeslot_to_log[$date_iso]) &&
                isset($this->date_to_timeslot_to_log[$date_iso][$timeslot]))
            {
                return [$this->date_to_timeslot_to_log[$date_iso][$timeslot]];
            }
            else {
                if (isset($this->date_to_log[$date_iso])) {
                    return $this->date_to_log[$date_iso];
                }
                else {
                    return null;
                }
            }
        }
    }


    ///////////////////////////////////////////////
    // Schedule = Timetable + Classlog + Syllabus //
    ///////////////////////////////////////////////

    // Type of a tag: string
    class Schedule {
        private array $date_to_entries = []; // date_iso -> list<entry>
        // Used for coloring; declares some *deterministic* order
        private array $unit_order = [];      // list<unit_id>

        public function __construct(
            public readonly Timetable $timetable,
            public readonly Classlog $classlog,
            public readonly ?Syllabus $syllabus = null
        ) {

            //////////////////////////////////////////
            // Fill in cancellations from timetable //
            //////////////////////////////////////////

            foreach ($this->timetable->get_date_to_cancellation() as $date_iso => $reason) {
                $this->date_to_entries[$date_iso] = [
                    [
                        'type'   => 'cancellation-date',
                        'reason' => $reason
                    ]
                ];
            }

            foreach ($this->timetable->get_date_to_timeslot_to_cancellation() as $date_iso => $timeslot_to_reason) {
                foreach ($timeslot_to_reason as $timeslot => $reason) {
                    if (!isset($this->date_to_entries[$date_iso])) {
                        $this->date_to_entries[$date_iso] = [];
                    }

                    $this->date_to_entries[$date_iso][] = [
                        'type'     => 'cancellation-slot',
                        'timeslot' => $timeslot,
                        'reason'   => $reason
                    ];
                }
            }


            //////////////////////////////////////////////
            // Fill in slots from classlog and syllabus //
            //////////////////////////////////////////////

            // Go through dates; start with earliest
            $cur_date = DateTime::createFromImmutable($this->timetable->get_slot_earliest()->date);
            
            // Go through topics of syllabus
            $topic_iterator =
                $this->syllabus != null
                ? $this->syllabus->iterate_topics()
                : new ArrayIterator([]);
            $topic_iterator->rewind();
            $topic_slots_used = 0;

            while ($cur_date <= $this->timetable->get_slot_latest()->date) {
                $cur_date_iso = date_to_iso($cur_date);

                $slots = $this->timetable->get_slots_on($cur_date);

                if (!isset($this->date_to_entries[$cur_date_iso])) {
                    $this->date_to_entries[$cur_date_iso] = [];
                }

                // Keep track of logs: All logs must be assigned to timetable slots
                $number_of_logs_for_date_left = $this->classlog->count_logs_on_date($cur_date);

                foreach ($slots as $cur_slot_ser) {
                    $cur_slot = Slot::unserialize($cur_slot_ser);

                    // Add slot entry
                    $this->date_to_entries[$cur_date_iso][] = [
                        'type'     => 'slot',
                        'timeslot' => $cur_slot->timeslot,
                    ];

                    // Schedule next topic, if topics left
                    if ($topic_iterator->valid()) {
                        $cur_topic = $topic_iterator->current();
                        // Update counter
                        $topic_slots_used++;
                        if ($topic_slots_used >= $topic_iterator->current()->number_of_slots) {
                            $topic_slots_used = 0;
                            $topic_iterator->next();
                        }
                        
                        // Register unit
                        if (!in_array($cur_topic->unit->id, $this->unit_order)) {
                            $this->unit_order[] = $cur_topic->unit->id;
                        }

                        $this->date_to_entries[$cur_date_iso][] = [
                            'type'     => 'topic-slot',
                            'timeslot' => $cur_slot->timeslot,
                            'id'       => 'topic-' . $cur_topic->unit->id . '-' . $cur_topic->id,
                            'topic'    => $cur_topic,
                        ];
                    }
                    
                    // Schedule log, if available
                    $cur_log = $this->classlog->get_log_for_slot($cur_slot);
                    if ($cur_log !== null) {
                        $this->date_to_entries[$cur_date_iso][] = $cur_log;

                        // Update counter
                        $number_of_logs_for_date_left--;
                    }
                }

                assert($number_of_logs_for_date_left == 0, 'Not all logs were scheduled.');
    
                $cur_date->modify('+1 day');
            }

            assert(!$topic_iterator->valid(), 'Not all topics were scheduled.');
        }

        public function add_tag(string $date_iso, string $tag, ?string $id = null) {
            assert_iso_date($date_iso);

            if (!isset($this->date_to_entries[$date_iso])) {
                $this->date_to_entries[$date_iso] = [];
            }

            assert(!in_array($tag, $this->date_to_entries[$date_iso]), 'Tag already added.');

            $this->date_to_entries[$date_iso][] = [
                'type'  => 'tag',
                'id'    => 'tag-' . $date_iso . '-' . ($id ?? kebabize($tag)),
                'title' => $tag,
            ];
        }


        //////////////////////////////////////
        // Only callable after construction //
        //////////////////////////////////////

        // $types: list of types; will be returned in that order, e.g. `['tag', 'log-short', 'log-long']`
        // Returns map of type `list<entry>`
        public function get_entries_on(DateTimeInterface $date, array $types): array {
            $date_iso = date_to_iso($date);
            $entries_all = $this->date_to_entries[$date_iso] ?? [];

            $entries_res = [];

            foreach ($types as $type) {
                $entries_res = array_merge($entries_res, array_filter($entries_all, function($entry) use ($type) {
                    return $entry['type'] == $type;
                }));
            }

            return $entries_res;
        }

        public function get_slot_earliest(): ?Slot {
            return $this->timetable->get_slot_earliest();
        }

        public function get_slot_latest(): ?Slot {
            return $this->timetable->get_slot_latest();
        }

        public function get_order_of_unit(string $unit_id): int {
            $order = array_search($unit_id, $this->unit_order);
            assert($order !== false, 'Unit not found.');
            return $order;
        }
    }
?>