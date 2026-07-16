<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('sql-js-extensions');
    };
?>

<? $process = function (Target $target) { ?>

<?
    $db = sql_js_define_new_db_from_init_script_inline('CREATE TABLE existing_table (id INTEGER);');
    sql_js_query_resultset_and_print($db, 'SELECT * FROM missing_table');
?>

<? }; ?>
