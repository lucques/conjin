<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('sol-mode', [
            'restrict_to_static_users' => ['admin'],
        ]);
    };
?>

<? $process = function (Target $target) { ?>

<p id="question">Question</p>
<? sol_start(); ?>
<p id="solution">Solution</p>
<? sol_end(); ?>

<p>
    The answer is <? sol_start(); ?><span id="gap-answer">complete</span><? sol_gap(); ?><span id="gap-placeholder">________</span><? sol_end(); ?>.
</p>

<? }; ?>
