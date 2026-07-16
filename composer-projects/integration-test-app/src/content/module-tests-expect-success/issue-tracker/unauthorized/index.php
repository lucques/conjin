<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');
        $c->activate_module('issue-tracker');
    };
?>

<? $process = function (Target $target) { ?>

<? require __DIR__ . '/../_page.php'; ?>
<? issue_tracker_test_page('issue-tracker-test-unauthorized.sqlite', 0); ?>

<? }; ?>
