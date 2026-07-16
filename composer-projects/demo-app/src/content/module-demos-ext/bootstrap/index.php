<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');


        $c->activate_module('bootstrap');
    };
?>

<? $process = function (Target $target) { ?>

<div class="container">
    <h1>
        This is <span class="badge bg-primary">Bootstrap</span> indeed.
    </h1>
</div>

<? }; ?>