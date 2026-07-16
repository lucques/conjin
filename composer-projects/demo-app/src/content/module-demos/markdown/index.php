<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('markdown');
    };
?>

<? $process = function (Target $target) { ?>

<h1>markdown</h1>

<p>Content between <code>md_start()</code> and <code>md_end()</code> is parsed with the Composer-provided Parsedown library.</p>

<div id="markdown-demo">
<? md_start(); ?>
## Rendered Markdown

This paragraph contains **strong text**, *emphasized text*, and [`inline code`](https://en.wikipedia.org/wiki/Markdown).

> Markdown can express quotations without writing the corresponding HTML.

- Lists
- Links
- Code blocks

```php
echo "Hello from Markdown";
```
<? md_end(); ?>
</div>

<? }; ?>
