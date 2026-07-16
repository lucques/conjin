<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('mathjax-extensions');
    };
?>

<? $process = function (Target $target) { ?>

<div id="tightarray-fixture">
\begin{tightarray}[1em]{rl}
    2x + 2 & = 8 \\
    2x & = 6
\end{tightarray}
</div>

<div id="equation-set-fixture">
<?
    mjax_eqset_start('r', 'l');
    mjax_eqset_line('2x + 2', '8', '-2');
    mjax_eqset_line('2x', '6', ':2');
    mjax_eqset_line('x', '3');
    mjax_eqset_end();
?>
</div>

<div id="helper-fixture">
$$
    p = 12~<?= mathjax_unic('€') ?>,\qquad
    <?= mathjax_term_var(0) ?> + <?= mathjax_term_var(1) ?> = 7,\qquad
    \vec{u} = <?= mjax_vec_2d('2', '-1') ?>
$$
</div>

<output id="mathjax-hook-status">waiting</output>

<script>
    function markMathJaxRenderingComplete() {
        document.getElementById('mathjax-hook-status').textContent = 'hook called';
    }

    if (typeof window.mathJaxAfterRenderingHooks !== 'undefined') {
        window.mathJaxAfterRenderingHooks.push(markMathJaxRenderingComplete);
    }
    else {
        window.mathJaxAfterRenderingHooks = [markMathJaxRenderingComplete];
    }
</script>

<? }; ?>
