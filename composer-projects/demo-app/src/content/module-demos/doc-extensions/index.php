<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
    };
?>

<? $process = function (Target $target) { ?>

<? doc_extensions_add_body_class('some-class'); ?>

<? body_top_element_start(); ?>
<div>
    <p>Some extra top element</p> 
</div>
<? body_top_element_end(); ?>

<? body_top_element_start(); ?>
<div>
    <p>Another extra top element</p> 
</div>
<? body_top_element_end(); ?>


<? css_start(); ?>
.some-class {
    color: red;
}
<? css_end(); ?>

<p>
    Styles etc. may be added.
</p>

<? }; ?>