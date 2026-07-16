<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('bootstrap');
        $c->activate_module('sql-js-knowledge-tagged');
    };
?>

<?
    $process = function(Target $target) {
        $knowledge = new TaggedDb();
        $knowledge->add('alpha', '<strong>Alpha</strong>', '<p data-entry="alpha">First entry</p>', ['shared', 'warm']);
        $knowledge->add('beta', '<strong>Beta</strong>', '<p data-entry="beta">Second entry</p>', ['cool', 'shared']);
        $knowledge->add('gamma', '<strong>Gamma</strong>', '<p data-entry="gamma">Untagged entry</p>');

        $db_var = sql_js_define_new_db_from_init_script_inline($knowledge->generate_sql_init_script());
        sql_js_print_tagged_db($db_var);
    };
?>
