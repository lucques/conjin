<?
    $init_processing = function(Module $module, Target $target) {
        doc_extensions_add_head_import('<script src="'. $module->get_url() . '/res/library.js"></script>');
    };


    ////////////
    // Macros //
    ////////////

    /**
     * Initializes a database with the given init script.
     * @param string $path_to_init_script 
     * @return string freshly generated id
     */
    function sql_js_inline_init_db(string $path_to_init_script) {
        $db_var = 'sql_js_inline_db_' . next_unique_id();
?>
        <script>
            const <?= $db_var ?> = sqlJs.then(function(SQL){
                const db = new SQL.Database();
                db.run(`<?= file_get_contents($path_to_init_script); ?>`);
                return db;
            });
        </script>
<?
        return $db_var;
    }

    /**
     * Executes a SQL statement and stores the results in a variable.
     * @param string $db_var 
     * @param string $sql 
     * @return string freshly generated id
     */
    function sql_js_inline_exec(string $db_var, string $sql) {
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
     * Inserts a database result into the page.
     * @param string $db_var 
     * @param string $sql 
     * @param string $title 
     * @return void 
     */
    function sql_js_inline_exec_and_print(string $db_var, string $sql, string $title = '', bool $truncated = false) {
        $results_var     = sql_js_inline_exec($db_var, $sql);
        $results_view_id = 'sql_js_inline_resultsview_' . next_unique_id();
?>
        <div id="<?= $results_view_id ?>" class="d-inline-flex gap-3 flex-wrap justify-content-around"></div>
        <script>
            <?= $results_var ?>.then(function(results) {
                document.getElementById("<?= $results_view_id ?>").innerHTML = sql_js_inline_resultsToHTML(results, `<?= $title ?>`, <?= $truncated ? 'true' : 'false' ?>);
            });
        </script>
<?
    }
?>