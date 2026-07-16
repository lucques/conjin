<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('mathjax');
    };
?>

<? $process = function (Target $target) { ?>

<p>Inline math: <span id="mathjax-inline">\(a^2 + b^2 = c^2\)</span></p>

<div id="mathjax-display">
\[
    x = \frac{-b \pm \sqrt{b^2 - 4ac}}{2a}
\]
</div>

<? }; ?>
