<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');

        $c->activate_module('exercise');

        $c->activate_module('title');    
        $c->run_macro('title', 'set', 'variants');
    };
?>

<? $process = function (Target $target) { ?>

<? html_h(2, 'Colors'); ?>

<? acc_start(); ?>
<? acc_item_start('primary', variant: 'primary'); acc_item_end(); ?>
<? acc_item_start('primary', variant: 'primary', open: true); acc_item_end(); ?>
<? acc_end(); ?>
<? acc_start(); ?>
<? acc_item_start('secondary', variant: 'secondary'); acc_item_end(); ?>
<? acc_item_start('secondary', variant: 'secondary', open: true); acc_item_end(); ?>
<? acc_end(); ?>
<? acc_start(); ?>
<? acc_item_start('success', variant: 'success'); acc_item_end(); ?>
<? acc_item_start('success', variant: 'success', open: true); acc_item_end(); ?>
<? acc_end(); ?>
<? acc_start(); ?>
<? acc_item_start('danger', variant: 'danger'); acc_item_end(); ?>
<? acc_item_start('danger', variant: 'danger', open: true); acc_item_end(); ?>
<? acc_end(); ?>
<? acc_start(); ?>
<? acc_item_start('warning', variant: 'warning'); acc_item_end(); ?>
<? acc_item_start('warning', variant: 'warning', open: true); acc_item_end(); ?>
<? acc_end(); ?>
<? acc_start(); ?>
<? acc_item_start('info', variant: 'info'); acc_item_end(); ?>
<? acc_item_start('info', variant: 'info', open: true); acc_item_end(); ?>
<? acc_end(); ?>
<? acc_start(); ?>
<? acc_item_start('light', variant: 'light'); acc_item_end(); ?>
<? acc_item_start('light', variant: 'light', open: true); acc_item_end(); ?>
<? acc_end(); ?>
<? acc_start(); ?>
<? acc_item_start('dark', variant: 'dark'); acc_item_end(); ?>
<? acc_item_start('dark', variant: 'dark', open: true); acc_item_end(); ?>
<? acc_end(); ?>
<? acc_start(); ?>
<? acc_item_start('orange', variant: 'orange'); acc_item_end(); ?>
<? acc_item_start('orange', variant: 'orange', open: true); acc_item_end(); ?>
<? acc_end(); ?>



<? html_h(2, 'Mathy stuff'); ?>

<? acc_start(); ?>
<? acc_item_start('definition', variant: 'definition'); acc_item_end(); ?>
<? acc_item_start('definition', variant: 'definition', open: true); acc_item_end(); ?>
<? acc_end(); ?>
<? acc_start(); ?>
<? acc_item_start('example', variant: 'example'); acc_item_end(); ?>
<? acc_item_start('example', variant: 'example', open: true); acc_item_end(); ?>
<? acc_end(); ?>
<? acc_start(); ?>
<? acc_item_start('theorem', variant: 'theorem'); acc_item_end(); ?>
<? acc_item_start('theorem', variant: 'theorem', open: true); acc_item_end(); ?>
<? acc_end(); ?>
<? acc_start(); ?>
<? acc_item_start('proof', variant: 'proof'); acc_item_end(); ?>
<? acc_item_start('proof', variant: 'proof', open: true); acc_item_end(); ?>
<? acc_end(); ?>
<? acc_start(); ?>
<? acc_item_start('exercise', variant: 'exercise'); acc_item_end(); ?>
<? acc_item_start('exercise', variant: 'exercise', open: true); acc_item_end(); ?>
<? acc_end(); ?>
<? acc_start(); ?>
<? acc_item_start('solution', variant: 'solution'); acc_item_end(); ?>
<? acc_item_start('solution', variant: 'solution', open: true); acc_item_end(); ?>
<? acc_end(); ?>
<? acc_start(); ?>
<? acc_item_start('hint', variant: 'hint'); acc_item_end(); ?>
<? acc_item_start('hint', variant: 'hint', open: true); acc_item_end(); ?>
<? acc_end(); ?>


<? html_h(2, 'Layers'); ?>

<? acc_start(); ?>
<? acc_item_start('layer_1', variant: 'layer_1'); acc_item_end(); ?>
<? acc_item_start('layer_1', variant: 'layer_1', open: true); acc_item_end(); ?>
<? acc_end(); ?>
<? acc_start(); ?>
<? acc_item_start('layer_2', variant: 'layer_2'); acc_item_end(); ?>
<? acc_item_start('layer_2', variant: 'layer_2', open: true); acc_item_end(); ?>
<? acc_end(); ?>
<? acc_start(); ?>
<? acc_item_start('layer_3', variant: 'layer_3'); acc_item_end(); ?>
<? acc_item_start('layer_3', variant: 'layer_3', open: true); acc_item_end(); ?>
<? acc_end(); ?>
<? acc_start(); ?>
<? acc_item_start('layer_4', variant: 'layer_4'); acc_item_end(); ?>
<? acc_item_start('layer_4', variant: 'layer_4', open: true); acc_item_end(); ?>
<? acc_end(); ?>
<? acc_start(); ?>
<? acc_item_start('layer_5', variant: 'layer_5'); acc_item_end(); ?>
<? acc_item_start('layer_5', variant: 'layer_5', open: true); acc_item_end(); ?>
<? acc_end(); ?>


<? html_h(2, 'More'); ?>
<? acc_start(); ?>
<? acc_item_start('attention', variant: 'attention'); acc_item_end(); ?>
<? acc_item_start('attention', variant: 'attention', open: true); acc_item_end(); ?>
<? acc_end(); ?>
<? acc_start(); ?>
<? acc_item_start('important', variant: 'important'); acc_item_end(); ?>
<? acc_item_start('important', variant: 'important', open: true); acc_item_end(); ?>
<? acc_end(); ?>

<? }; ?>