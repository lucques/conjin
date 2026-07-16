<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'db-mysql');

        // Deactivated for now
        $c->activate_module('db-mysql');
    };
?>

<? $process = function (Target $target) { ?>

<?
    $price_augmented = false;
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        ($_POST['req'] ?? null) === 'augment-cookie-dough-price' &&
        auth_has_postdedup_uuid() &&
        auth_register_check_postdedup_uuid()
    ) {
        $GLOBALS['db']->select_db('eisdiele');
        $statement = $GLOBALS['db']->prepare(
            'UPDATE sorte SET preis = preis + 10 WHERE name = ?'
        );
        $name = 'Cookie Dough';
        $statement->bind_param('s', $name);
        $statement->execute();
        $price_augmented = $statement->affected_rows === 1;
    }
?>

<p>
    How to use a db. Live result from DB:
</p>

<? if ($price_augmented) { ?>
    <div class="alert alert-success" role="alert">
        The price of Cookie Dough was increased by 10.
    </div>
<? } ?>

<form method="post">
    <input type="hidden" name="req" value="augment-cookie-dough-price">
    <input type="hidden" name="request_uuid" value="<?= auth_get_cur_postdedup_uuid() ?>">
    <button type="submit" class="btn btn-primary">Increase Cookie Dough price by 10</button>
</form>

<?
    print_sql_result('eisdiele', 'SELECT * FROM sorte');
?>

<? }; ?>
