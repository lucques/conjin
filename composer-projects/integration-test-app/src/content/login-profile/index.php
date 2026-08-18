<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('subpages-all');
        $c->set_login_profile('standalone');
    };

    $process = function (Target $target) {
?>

<p>Login profile test parent.</p>

<?
    };
?>
