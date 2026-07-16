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

    class TaggedDb {
        private array $items = [];         // array<TaggedItem>
        private array $items_to_tags = []; // array<(item_id: string, tag: string)>

        public function add($id, $title, $content, $tags = []) {

            $this->items[] = new TaggedItem(
                $id,
                $title,
                $content,
                $tags
            );

            foreach ($tags as $tag) {
                $this->items_to_tags[] = ['item_id' => $id, 'tag' => $tag];
            }
        }

        public function generate_sql_init_script() {
            $sql = '';

            $sql .= 'CREATE TABLE items (';
            $sql .= 'id TEXT PRIMARY KEY, ';
            $sql .= 'title TEXT, ';
            $sql .= 'content TEXT, ';
            $sql .= 'sorting INTEGER';
            $sql .= ');';
            $sql .= "\n\n";

            $sql .= 'CREATE TABLE items_to_tags (';
            $sql .= 'item_id TEXT, ';
            $sql .= 'tag TEXT';
            $sql .= ');';
            $sql .= "\n\n";

            if (count($this->items) > 0) {
                $sql .= 'INSERT INTO items (id, title, content, sorting) VALUES';
                $sql .= "\n";

                foreach ($this->items as $i => $item) {
                    $sql .= '(';
                    $sql .= '\'' . sql_js_aux_escape_string_for_sql_text($item->id) . '\', ';
                    $sql .= '\'' . sql_js_aux_escape_string_for_sql_text($item->title) . '\', ';
                    $sql .= '\'' . sql_js_aux_escape_string_for_sql_text($item->content) . '\', ';
                    $sql .= $i;
                    $sql .= ')';
                    $sql .= $i === count($this->items) - 1 ? ';' : ',';
                    $sql .= "\n";
                }
            }

            $sql .= "\n";

            if (count($this->items_to_tags) > 0) {
                $sql .= 'INSERT INTO items_to_tags (item_id, tag) VALUES';
                $sql .= "\n";

                foreach ($this->items_to_tags as $i => $item_to_tag) {
                    $sql .= '(';
                    $sql .= '\'' . sql_js_aux_escape_string_for_sql_text($item_to_tag['item_id']) . '\', ';
                    $sql .= '\'' . sql_js_aux_escape_string_for_sql_text($item_to_tag['tag']) . '\'';
                    $sql .= ')';
                    $sql .= $i === count($this->items_to_tags) - 1 ? ';' : ',';
                    $sql .= "\n";
                }
            }

            return $sql;
        }
    }

    class TaggedItem {
        public function __construct(
            public readonly string $id,
            public readonly string $title,
            public readonly string $content,
            public readonly array $tags
        ) {}
    }


    /////////////////////////
    // Top-level functions //
    /////////////////////////

    /**
     * Queries a tagged database and renders the result to the page.
     * 
     * @param string $db_var JS var that holds the db
     */
    function sql_js_print_tagged_db(string $db_var): void {
        $tags_view_id = 'sql_js_print_tagged_db_tags_' . next_unique_id();
        $items_view_id = 'sql_js_print_tagged_db_items_' . next_unique_id();
?>
        <div id="<?= $tags_view_id ?>"></div>
        <div id="<?= $items_view_id ?>"></div>
        <script>
            sql_js_installTaggedDb(<?= $db_var ?>, '<?= $tags_view_id ?>', '<?= $items_view_id ?>');
        </script>
<?
    }


    ///////////////////////////
    // Convenience functions //
    ///////////////////////////   

    function sql_js_add_gallery_item($g, $filename, $description = null, $tags = []) {
        $id = kebabize($filename);
        $title = '<span><strong>' . $filename . '</strong>';
        if (count($tags) > 0) {
            $title .= ' (' . implode(', ', $tags) . ')';
        }
        $title .= '</span>';

        $is_video = preg_match('/\.(mp4|webm|ogg)$/', $filename) === 1;
        $content = '';
        if ($is_video) {
            $content = '<video controls style="max-width: 100%;" preload="none"><source src="res/' . $filename . '" type="video/mp4"></video>';
        }
        else {
            $content = '<img src="res/' . $filename . '" class="img-fluid" style="max-width: 100%;" loading="lazy" alt="' . $filename . '">';
        }
        if ($description !== null) {
            $content .= '<p>' . $description . '</p>';
        }
        
        $g->add($id, $title, $content, $tags);
    }
?>