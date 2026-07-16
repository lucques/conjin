<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('hamster');
    };
?>

<? $process = function (Target $target) { ?>

<h1>hamster</h1>

<h2>Program listing</h2>

<? hamster_source_file(__DIR__ . '/res/program.java', lang: 'java', line_numbers: true); ?>

<h2>Before and after</h2>

<? hamster_before_after('res/before.svg', 'res/after.svg'); ?>

<? }; ?>
