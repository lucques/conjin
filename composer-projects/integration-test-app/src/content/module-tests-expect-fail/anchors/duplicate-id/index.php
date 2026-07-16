<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->activate_module('anchors');
    };
?>

<?
    $process = function (Target $target) {
        anchor_create(2, 'duplicate');
        anchor_create(2, 'duplicate');
    };
?>
