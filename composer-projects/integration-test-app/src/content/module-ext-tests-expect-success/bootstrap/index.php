<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('bootstrap');
    };
?>

<? $process = function (Target $target) { ?>

<div id="bootstrap-css-probe" class="d-none">Bootstrap CSS probe</div>

<button
    id="bootstrap-collapse-toggle"
    class="btn btn-primary"
    type="button"
    data-bs-toggle="collapse"
    data-bs-target="#bootstrap-collapse"
    aria-expanded="false"
    aria-controls="bootstrap-collapse"
>
    Toggle collapse
</button>

<div id="bootstrap-collapse" class="collapse">
    Bootstrap JavaScript is working.
</div>

<? }; ?>
