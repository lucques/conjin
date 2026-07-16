<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'MathJax');

        $c->activate_module('mathjax');
    };
?>

<? $process = function (Target $target) { ?>

<p>MathJax renders inline TeX such as <span id="mathjax-inline">\(a^2 + b^2 = c^2\)</span> and display TeX:</p>

<div id="mathjax-display">
\[
    x = \frac{-b \pm \sqrt{b^2 - 4ac}}{2a}
\]
</div>

<? }; ?>
