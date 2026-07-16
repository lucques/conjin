<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
    };
?>

<? $process = function (Target $target) { ?>

<p>This target must not appear in guest navigation.</p>

<? }; ?>
