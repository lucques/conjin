<?
    //////////
    // Init //
    //////////

    $init_processing_target = function(Module $module, Target $target) {
        if ($module->config->get('import_from_url') !== null) {
            doc_extensions_add_head_element('<script src="'. $module->config->get('import_from_url') . '/res/library.js"></script>');
            doc_extensions_add_head_element('<link href="'. $module->config->get('import_from_url') . '/res/styles.css" rel="stylesheet">');
        }
        else {
            doc_extensions_add_head_element('<script src="'. $module->get_url() . '/res/library.js"></script>');
            doc_extensions_add_head_element('<link href="'. $module->get_url() . '/res/styles.css" rel="stylesheet">');
        }
    };


    ///////////////////////////////////
    // JS database expression macros //
    ///////////////////////////////////

    // The following macros generate JS expressions

    /**
     * Returns a JS database object, initialized by inlining the given init script.
     * @param string $sql SQL script to initialize the database; not escaped
     * @return string JS expression of type `Promise<Database>`
     */
    function sql_js_new_db_from_init_script_inline(string $sql): string {
        return 'sqlJs.then(function(SQL){const db = new SQL.Database(); db.run(`' . sql_js_aux_escape_string_for_js_literal($sql) . '`); return db;})';
    }

    /**
     * Returns a JS database object, initialized by fetching the given init script.
     * @return string JS expression of type `Promise<Database>`
     */
    function sql_js_new_db_from_init_script_fetch(string $url_to_init_script): string {
        return 'fetch(`' . $url_to_init_script . '`)
            .then(response => response.text())
            .then(sql => sqlJs.then(SQL => {
                const db = new SQL.Database();
                db.run(sql);
                return db;
            }))';
    }

    /**
     * Returns a JS database object, initialized with the given SQLite database
     * @return string JS expression of type `Promise<Database>`
     */
    function sql_js_new_db_from_db_file_fetch(string $url_to_db_file): string {
        return 'fetch(`' . $url_to_db_file . '`)
            .then(response => response.arrayBuffer())
            .then(buffer => sqlJs.then(SQL => new SQL.Database(new Uint8Array(buffer))))';
    }


    ///////////////////////////////////
    // JS database definition macros //
    ///////////////////////////////////

    // The following macros generate JS statements, namely variable bindings

    /**
     * Defines a JS var that holds a JS database object, initialized by inlining
     * the given init script.
     * 
     * @param string $sql SQL script to initialize the database; not escaped
     * @return string freshly generated JS var name
     */
    function sql_js_define_new_db_from_init_script_inline(string $sql): string {
        $db_var = 'sql_js_inline_db_' . next_unique_id();
?>
        <script>
            const <?= $db_var ?> = <?= sql_js_new_db_from_init_script_inline($sql); ?>;
        </script>
<?
        return $db_var;
    }

    /**
     * Defines a JS var that holds a JS database object, initialized by inlining
     * the init script given by the path.
     * 
     * @return string freshly generated JS var name
     */
    function sql_js_define_new_db_from_init_script_inline_by_path(string $path_to_init_script): string {
        return sql_js_define_new_db_from_init_script_inline(file_get_contents($path_to_init_script));
    }

    /**
     * Defines a JS var that holds a JS database object, initialized by fetching
     * the given init script.
     * 
     * @return string freshly generated JS var name
     */
    function sql_js_define_new_db_from_init_script_fetch(string $url_to_init_script): string {
        $db_var = 'sql_js_inline_db_' . next_unique_id();
?>
        <script>
            const <?= $db_var ?> = <?= sql_js_new_db_from_init_script_fetch($url_to_init_script); ?>;
        </script>
<?
        return $db_var;
    }

    /**
     * Defines a JS var that holds a JS database object, initialized with the
     * given SQLite database.
     * 
     * @return string freshly generated JS var name
     */
    function sql_js_define_new_db_from_db_file_fetch(string $path_to_db_file): string {
        $db_var = 'sql_js_inline_db_' . next_unique_id();
?>
        <script>
            const <?= $db_var ?> = <?= sql_js_new_db_from_db_file_fetch($path_to_db_file); ?>;
        </script>
<?
        return $db_var;
    }

    
    ////////////////////////////////////
    // JS resultset definition macros //
    ////////////////////////////////////

    /**
     * Returns a JS var that holds a JS resultset obj, obtained by executing the
     * given SQL statement on the given database object.
     * 
     * @return string freshly generated JS var
     */
    function sql_js_query_resultset(string $db_var, string $sql) {
        $results_var = 'sql_js_inline_results_' . next_unique_id();
?>
        <script>
            const <?= $results_var ?> = <?= $db_var ?>.then(function(db) {
                return db.exec(`<?= $sql ?>`);
            });
        </script>
<?
        return $results_var;
    }

    /**
     * Queries a database and renders the result to the page.
     * 
     * @param string $db_var JS var
     */
    function sql_js_query_resultset_and_print(string $db_var, string $sql, string $title = '', bool $truncated = false): void {
        // 1. Execute
        $results_var     = sql_js_query_resultset($db_var, $sql);
        // 2. Print
        $results_view_id = 'sql_js_inline_resultsview_' . next_unique_id();
?>
        <div id="<?= $results_view_id ?>" class="stack"></div>
        <script>
            <?= $results_var ?>.then(function(results) {
                document.getElementById("<?= $results_view_id ?>").innerHTML = sql_js_inline_resultsToHTML(results, `<?= $title ?>`, <?= $truncated ? 'true' : 'false' ?>);
            });
        </script>
<?
    }

    /**
     * Queries a database and renders the result to the page.
     * 
     * @param string $db_var JS var
     * @param array $tables list of tables; each of the form:
     *     [
     *       name => 'table_name',
     *       columns => [ 
     *         [type => 'data', name => 'col_name', isPartOfPrimaryKey => 'true', isPartOfForeignKey => 'true'],
     *         [type => 'ellipsis'],
     *         ...,
     *       sql => 'SELECT col_name FROM ...'  // optional
     *       truncated => false  whether to show ... at the end of the results // optional
     *       open => false whether to show the results by default // optional
     *     ]
     */
    function sql_js_print_schema_with_queried_results(string $db_var, array $tables, bool $show_button = true): void {
        // 1. Join SQL queries together to a single one, but only if `sql` is set
        $sql = implode('; ', array_map(fn($table) => $table['sql'], array_filter($tables, fn($table) => isset($table['sql']))));
        // 2. Execute
        $results_var = sql_js_query_resultset($db_var, $sql);
        // 3. Print
        $results_view_id = 'sql_js_inline_resultsview_' . next_unique_id();
?>
       <div id="<?= $results_view_id ?>" class="stack"></div>
       <script>
           <?= $results_var ?>.then(function(results) {
                const tables = <?= json_encode($tables) ?>;
                // Go through tables. If a table has a query, then add the results to it.
                for (let i = 0; i < tables.length; i++) {
                    if (tables[i].sql) {
                        tables[i].result = results[i];
                    }
                }
                document.getElementById("<?= $results_view_id ?>").innerHTML = sql_js_inline_schemaWithResultsToHTML(tables, <?= $show_button ? 'true' : 'false' ?>);
           
           });
       </script>
<?
    }


        /**
     * Queries a database and renders the result to the page.
     * 
     * @param array $tables list of tables; each of the form:
     *     [
     *       name => 'table_name',
     *       columns => [ 
     *         [type => 'data', name => 'col_name', isPartOfPrimaryKey => 'true', isPartOfForeignKey => 'true'],
     *         [type => 'ellipsis'],
     *         ...,
     *     ]
     */
    function sql_js_print_schema(array $tables): void {
        // 3. Print
        $results_view_id = 'sql_js_inline_resultsview_' . next_unique_id();
?>
       <div id="<?= $results_view_id ?>" class="stack"></div>
       <script>
            document.getElementById("<?= $results_view_id ?>").innerHTML = sql_js_inline_schemaWithResultsToHTML(<?= json_encode($tables) ?>, false);
       </script>
<?
    }

    /////////////
    // Helpers //
    /////////////

    function sql_js_aux_escape_string_for_js_literal(string $str): string {
        return addcslashes($str, '`\\$');
    }

    function sql_js_aux_escape_string_for_sql_text(string $str): string {
        return str_replace("'", "''", $str);
    }
?>