<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('bootstrap');
        $c->activate_module('sql-js-extensions');
    };
?>

<? $process = function (Target $target) { ?>

<h1>Adversarial integration fixture</h1>

<p>This target exercises each browser-facing interaction with the module and includes adversarial values where relevant.</p>

<section id="inline-query">
    <h2>Inline initialization and query rendering</h2>
    <p>The initialization value <code>Vanilla `${literal}&lt;/script&gt;</code> tries both JavaScript template interpolation and inline-script termination. The query alias <code>`display name`</code> also contains backticks that would terminate an unescaped template literal. Finally, the database value verifies that result cells are escaped before being assigned through <code>innerHTML</code>.</p>
    <p>The result title deliberately accepts HTML. Its <code>&lt;/script&gt;</code> value checks safe JavaScript serialization, but the browser consumes that part as markup instead of displaying it as text.</p>
    <?
        $inline_db = sql_js_define_new_db_from_init_script_inline(
            'CREATE TABLE inventory (name TEXT, quantity INTEGER);'
            . 'INSERT INTO inventory VALUES (\'Vanilla `${literal}</script>\', 3), (\'Chocolate\', 7);'
        );
    ?>
    <? sql_js_query_resultset_and_print(
        $inline_db,
        'SELECT name AS `display name`, quantity FROM inventory ORDER BY quantity DESC',
        'Inventory `${literal}</script>',
        true
    ); ?>
</section>

<section id="fetched-query">
    <h2>Initialization from a fetched SQL script</h2>
    <p>This interaction fetches a text SQL fixture in the browser, initializes a database from it, and verifies that its asynchronous query result is rendered.</p>
    <?
        $fetched_db = sql_js_define_new_db_from_init_script_fetch(
            '/module-tests-expect-success/sql-js-extensions/res/inventory.sql'
        );
    ?>
    <? sql_js_query_resultset_and_print($fetched_db, 'SELECT name, quantity FROM inventory ORDER BY quantity'); ?>
</section>

<section id="path-query">
    <h2>Initialization from a server-side SQL path</h2>
    <p>This interaction reads the SQL fixture during PHP processing, safely serializes its contents into inline JavaScript, and verifies the resulting database.</p>
    <? $path_db = sql_js_define_new_db_from_init_script_inline_by_path(__DIR__ . '/res/inventory.sql'); ?>
    <? sql_js_query_resultset_and_print($path_db, 'SELECT COUNT(*) AS total FROM inventory'); ?>
</section>

<section id="database-file-query">
    <h2>Initialization from a fetched SQLite file</h2>
    <p>This interaction fetches a binary SQLite fixture, passes its bytes into SQL.js, and verifies that the loaded database can be queried.</p>
    <?
        $file_db = sql_js_define_new_db_from_db_file_fetch(
            '/module-tests-expect-success/sql-js-extensions/res/inventory.sqlite'
        );
    ?>
    <? sql_js_query_resultset_and_print($file_db, 'SELECT name, quantity FROM inventory ORDER BY quantity DESC'); ?>
</section>

<section id="schema-with-results">
    <h2>Schema with queried results</h2>
    <p>The schema-only table deliberately precedes the queried tables. This verifies that result arrays are consumed in query order rather than incorrectly indexed by table position. The results also exercise truncation, initial expansion, and collapse controls.</p>
    <? sql_js_print_schema_with_queried_results(
        $inline_db,
        [
            [
                'name' => 'metadata_only',
                'columns' => [
                    ['type' => 'data', 'name' => 'description'],
                ],
            ],
            [
                'name' => 'inventory',
                'columns' => [
                    ['type' => 'data', 'name' => 'name', 'isPartOfPrimaryKey' => true],
                    ['type' => 'data', 'name' => 'quantity'],
                ],
                'sql' => 'SELECT name, quantity FROM inventory ORDER BY quantity DESC',
                'truncated' => true,
            ],
            [
                'name' => 'inventory_open',
                'columns' => [
                    ['type' => 'data', 'name' => 'name'],
                    ['type' => 'data', 'name' => 'quantity'],
                ],
                'sql' => 'SELECT name, quantity FROM inventory ORDER BY quantity',
                'open' => true,
            ],
        ]
    ); ?>
</section>

<section id="schema-only">
    <h2>Schema without query results</h2>
    <p>This interaction renders schema metadata without executing a query and includes primary-key and ellipsis column variants.</p>
    <? sql_js_print_schema([
        [
            'name' => 'audit_log',
            'columns' => [
                ['type' => 'data', 'name' => 'id', 'isPartOfPrimaryKey' => true],
                ['type' => 'ellipsis'],
            ],
        ],
    ]); ?>
</section>

<? }; ?>
