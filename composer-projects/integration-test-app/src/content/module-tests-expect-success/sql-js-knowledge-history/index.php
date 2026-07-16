<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('bootstrap');
        $c->activate_module('sql-js-knowledge-history');
    };
?>

<?
    $process = function(Target $target) {
        $history = new History();
        $history->add('1776-07-04', 'American Declaration of Independence', tags: ['usa']);

        $history->begin('1914', 'First World War', ['war'], '1918');
?>
<p data-event-description="first-world-war">A ranged event with an HTML description.</p>
<?
        $history->end();
        $history->add('1915-07', 'Month range', tags: ['correspondence'], date_end: '1916-03');

        $db_var = sql_js_define_new_db_from_init_script_inline($history->generate_sql_init_script());
?>

<section id="history-result">
    <? sql_js_print_history_db($db_var); ?>
</section>

<section id="tag-result">
    <? sql_js_query_resultset_and_print(
        $db_var,
        'SELECT event_id, tag FROM events_to_tags ORDER BY event_id'
    ); ?>
</section>

<section id="empty-history-result">
    <?
        $empty_history = new History();
        $empty_db_var = sql_js_define_new_db_from_init_script_inline($empty_history->generate_sql_init_script());
        sql_js_print_history_db($empty_db_var);
    ?>
</section>

<? }; ?>
