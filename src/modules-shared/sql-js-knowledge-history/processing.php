<?
    //////////
    // Init //
    //////////

    $init_processing_target = function(Module $module, Target $target) {
        if ($module->config->get('import_from_url') !== null) {
            doc_extensions_add_head_element('<script src="'. $module->config->get('import_from_url') . '/res/library.js"></script>');
        }
        else {
            doc_extensions_add_head_element('<script src="'. $module->get_url() . '/res/library.js"></script>');
        }
    };

    
    ///////////////
    // Datatypes //
    ///////////////

    class History {
        private array $events = [];         // array<Event>
        private array $events_to_tags = []; // array<(event_id: string, tag: string)>

        // Cache for convenience functions
        private $cur_date_begin = null;
        private $cur_title = null;
        private $cur_date_end = null;
        private $cur_tags = null;

        public function add(
            string $date_begin, // must be either an iso date, iso month, or iso year
            string $title,
            ?string $description = null,
            array $tags = [],
            ?string $date_end = null
        ) {
            $id = kebabize($date_begin . '-' . $title);

            $time_unit = null;
            if (is_iso_year($date_begin) && ($date_end === null || is_iso_year($date_end))) {
                $time_unit = 'y';
            }
            elseif (is_iso_month($date_begin) && ($date_end === null || is_iso_month($date_end))) {
                $time_unit = 'm';
            }
            elseif (is_iso_date($date_begin) && ($date_end === null || is_iso_date($date_end))) {
                $time_unit = 'd';
            }
            else {
                throw new Exception('date_begin and date_end must be of the same type, namely either iso year, iso month, or iso date');
            }

            $this->events[] = new Event(
                $id,
                floor_to_iso_date($date_begin),
                ceil_to_iso_date($date_end ?? $date_begin),
                $time_unit,
                $title,
                $description,
                $tags
            );

            foreach ($tags as $tag) {
                $this->events_to_tags[] = ['event_id' => $id, 'tag' => $tag];
            }
        }

        public function generate_sql_init_script() {
            $sql = '';

            $sql .= 'CREATE TABLE events (';
            $sql .= 'id TEXT PRIMARY KEY, ';
            $sql .= 'date_begin TEXT, ';
            $sql .= 'date_end TEXT, ';
            $sql .= 'time_unit TEXT, ';
            $sql .= 'title TEXT, ';
            $sql .= 'description TEXT';
            $sql .= ');';
            $sql .= "\n\n";

            $sql .= 'CREATE TABLE events_to_tags (';
            $sql .= 'event_id TEXT, ';
            $sql .= 'tag TEXT';
            $sql .= ');';
            $sql .= "\n\n";

            if (count($this->events) > 0) {
                $sql .= 'INSERT INTO events (id, date_begin, date_end, time_unit, title, description) VALUES';
                $sql .= "\n";

                foreach ($this->events as $i => $event) {
                    $sql .= '(';
                    $sql .= '\'' . sql_js_aux_escape_string_for_sql_text($event->id) . '\', ';
                    $sql .= '\'' . $event->date_begin . '\', ';
                    $sql .= '\'' . $event->date_end . '\', ';
                    $sql .= '\'' . $event->time_unit . '\', ';
                    $sql .= '\'' . sql_js_aux_escape_string_for_sql_text($event->title) . '\', ';
                    $sql .= $event->description === null ? 'NULL' : '\'' . sql_js_aux_escape_string_for_sql_text($event->description) . '\'';
                    $sql .= ')';
                    $sql .= $i === count($this->events) - 1 ? ';' : ',';
                    $sql .= "\n";
                }
            }

            $sql .= "\n";

            if (count($this->events_to_tags) > 0) {
                $sql .= 'INSERT INTO events_to_tags (event_id, tag) VALUES';
                $sql .= "\n";

                foreach ($this->events_to_tags as $i => $event_to_tag) {
                    $sql .= '(';
                    $sql .= '\'' . sql_js_aux_escape_string_for_sql_text($event_to_tag['event_id']) . '\', ';
                    $sql .= '\'' . sql_js_aux_escape_string_for_sql_text($event_to_tag['tag']) . '\'';
                    $sql .= ')';
                    $sql .= $i === count($this->events_to_tags) - 1 ? ';' : ',';
                    $sql .= "\n";
                }
            }

            return $sql;
        }


        ///////////////////////////
        // Convenience functions //
        ///////////////////////////

        public function begin(string $date_begin, string $title, array $tags = [], ?string $date_end = null) {
            assert($this->cur_date_begin === null, 'begin() called before end()');

            $this->cur_date_begin = $date_begin;
            $this->cur_title = $title;
            $this->cur_date_end = $date_end;
            $this->cur_tags = $tags;

            ob_start();
        }

        public function end() {
            assert($this->cur_date_begin !== null, 'end() called without a corresponding begin()');

            $description = ob_get_clean();
            
            $this->add(
                date_begin: $this->cur_date_begin,
                title: $this->cur_title,
                description: $description,
                tags: $this->cur_tags,
                date_end: $this->cur_date_end
            );

            $this->cur_date_begin = null;
            $this->cur_title = null;
            $this->cur_date_end = null;
            $this->cur_tags = null;
        }
    }

    class Event {
        public function __construct(
            public readonly string $id,
            public readonly string $date_begin,       // iso date, e.g. '2021-01-01'
            public readonly string $date_end,         // iso date, e.g. '2021-12-31'
            public readonly string $time_unit,        // either 'y', 'm', 'd'
            public readonly string $title,
            public readonly ?string $description,
            public readonly array $tags
        ) {}
    }


    /////////////////////////
    // Top-level functions //
    /////////////////////////

    /**
     * Queries a history database and renders the result to the page.
     * 
     * @param string $db_var JS var that holds the db
     */
    function sql_js_print_history_db(string $db_var): void {
        // Results view
        $results_view_id = 'sql_js_results_' . next_unique_id();
?>
        <div id="<?= $results_view_id ?>" class="stack"></div>
        <script>
            sql_js_printHistoryDb(<?= $db_var ?>, '<?= $results_view_id ?>');
        </script>
<?
    }
?>