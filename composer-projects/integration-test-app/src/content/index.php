<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('nav-view');

        $c->add_subpage('module-demos-ext');
        $c->add_subpage('module-ext-tests-expect-success');
        $c->add_subpage('module-tests-expect-success');
        $c->add_subpage('module-tests-expect-fail');
    };

    $process = function (Target $target) {
?>

<p>The integration fixture starts here.</p>

<? nav_print(0); ?>

<?
    };
?>
