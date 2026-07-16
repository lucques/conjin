<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('exercise');
    };
?>

<?
    $process = function (Target $target) {
        ex_item('Invalid item');
    };
?>
