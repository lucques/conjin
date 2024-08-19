<?
    $init_processing_target = function(Module $module, Target $target) {
        doc_extensions_add_head_element('<script src="'. $module->get_url() . '/res/library.js"></script>');
    };


    ///////////////////////
    // Expression Macros //
    ///////////////////////

    // The following macros generate JS expressions

    /**
     * Returns a JS database object, initialized with the given init script.
     * @param string $path_to_init_script
     * @return string JS expression of type `Promise<Database>`
     */
    function sql_js_inline_init_db_var_exp(string $path_to_init_script): string {
        return 'sqlJs.then(function(SQL){const db = new SQL.Database(); db.run(`' . file_get_contents($path_to_init_script) . '`); return db;})';
    }


    //////////////////////
    // Statement Macros //
    //////////////////////

    // The following macros generate JS statements, namely variable bindings

    /**
     * Returns a JS var that holds a JS database object, initialized with the
     * given init script.
     * 
     * @return string freshly generated JS var
     */
    function sql_js_inline_init_db_var(string $path_to_init_script): string {
        $db_var = 'sql_js_inline_db_' . next_unique_id();
?>
        <script>
            const <?= $db_var ?> = <?= sql_js_inline_init_db_var_exp($path_to_init_script); ?>;
        </script>
<?
        return $db_var;
    }

    /**
     * Returns a JS var that holds a JS resultset obj, obtained by executing the
     * given SQL statement on the given database object.
     * 
     * @return string freshly generated JS var
     */
    function sql_js_inline_exec_var(string $db_var, string $sql) {
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
     * 
     * @param string $db_var JS var
     */
    function sql_js_inline_exec_and_print(string $db_var, string $sql, string $title = '', bool $truncated = false): void {
        // 1. Execute
        $results_var     = sql_js_inline_exec_var($db_var, $sql);
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
?>