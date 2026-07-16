<? 
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->activate_module('db-mysql');
    };
?>

<? $process = function (Target $target) { ?>

<p>
    How to use a db. Live result from DB:
</p>

<?
    print_sql_result('eisdiele', 'SELECT * FROM sorte');
?>

<? }; ?>
