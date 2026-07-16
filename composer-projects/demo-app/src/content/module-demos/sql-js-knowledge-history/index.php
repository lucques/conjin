<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('sql-js-knowledge-history');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'sql-js-knowledge-history');
    };
?>

<?
    $process = function(Target $target) {
        $history = new History();
?>

<p>The module turns historical events defined in PHP into a sql.js database and renders them chronologically in the browser. Events may use years, months, or exact dates.</p>

<?
    $history->add('1450', 'Invention of the printing press');
    $history->add('1789-07-14', 'Storming of the Bastille', tags: ['france']);
    $history->add('1914', 'First World War', tags: ['war'], date_end: '1918');
?>

<? $history->begin('1776-07-04', 'American Declaration of Independence', ['usa', 'north-america']); ?>
<p>The Second Continental Congress adopted the Declaration of Independence, announcing that the Thirteen Colonies regarded themselves as independent from Great Britain.</p>
<ul>
    <li>Thomas Jefferson drafted its main text.</li>
    <li>The declaration grounded independence in natural rights and government by the consent of the governed.</li>
</ul>
<? $history->end(); ?>

<?
        $db_var = sql_js_define_new_db_from_init_script_inline($history->generate_sql_init_script());
        sql_js_print_history_db($db_var);
    };
?>
