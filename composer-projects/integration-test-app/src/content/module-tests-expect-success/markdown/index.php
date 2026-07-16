<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');
        $c->activate_module('markdown');
    };
?>

<? $process = function (Target $target) { ?>

<div id="markdown-output">
<? md_start(); ?>
## Markdown contract

A paragraph with **strong text**, *emphasized text*, and `inline code`.

- First item
- Second item

> A quoted paragraph.

[Internal link](/module-tests-expect-success/markdown/)
<? md_end(); ?>
</div>

<? }; ?>
