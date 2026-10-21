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
    $submission_scope = 'demo:db-mysql:augment-cookie-dough-price';
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        ($_POST['req'] ?? null) === 'augment-cookie-dough-price'
    ) {
        $token_result = auth_consume_submission_token($_POST['submission_token'] ?? null, $submission_scope);
        if ($token_result === SubmissionTokenResult::Invalid) {
            send_response_and_exit(403, 'Invalid or expired form. Please reload the page and try again.');
        }
        if ($token_result === SubmissionTokenResult::Valid) {
            $GLOBALS['db']->select_db('eisdiele');
            $statement = $GLOBALS['db']->prepare(
                'UPDATE sorte SET preis = preis + 10 WHERE name = ?'
            );
            $name = 'Cookie Dough';
            $statement->bind_param('s', $name);
            $statement->execute();
            $price_augmented = $statement->affected_rows === 1;
        }
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
    <input type="hidden" name="submission_token" value="<?= htmlspecialchars(auth_issue_submission_token($submission_scope), ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="btn btn-primary">Increase Cookie Dough price by 10</button>
</form>

<?
    print_sql_result('eisdiele', 'SELECT * FROM sorte');
?>

<? }; ?>
