<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro('title', 'set', plain: 'template-interbook (Custom title; without h1)', plain_short: 'template-interbook', is_part_of_content: false);
    };
?>

<? $process = function (Target $target) { ?>

<? css_start(); ?>
.bordered {
    border: 1px solid black;
}
<? css_end(); ?>

<? load_def_from_script_and_call(__DIR__ . '/../../../lorem_ipsum.php', 'basic'); ?>
<? load_def_from_script_and_call(__DIR__ . '/../../../lorem_ipsum.php', 'acc'); ?>

<? }; ?>
