<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('bootstrap');
        $c->activate_module('bootstrap-icons');
    };
?>

<? $process = function (Target $target) { ?>

<span id="bootstrap-icon" class="bi bi-alarm" aria-label="Alarm"></span>

<? }; ?>
