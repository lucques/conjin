<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('mathjax-extensions');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'mathjax-extensions');
    };
?>

<? $process = function (Target $target) { ?>

<p>This page demonstrates only the configuration, TeX environment, hooks, and helpers added by <code>mathjax-extensions</code>.</p>

<h2><code>tightarray</code></h2>

<p>The custom environment supports per-column alignment and optional spacing between columns.</p>

\begin{tightarray}[1cm]{lcr}
  left & centered & right \\
  a & b & c
\end{tightarray}

<h2>Equation-set helpers</h2>

<p>The PHP helpers generate aligned, equivalent equation steps using <code>tightarray</code>.</p>

<?
    mjax_eqset_start('r', 'l');
    mjax_eqset_line('2x + 2', '8', '-2');
    mjax_eqset_line('2x', '6', ':2');
    mjax_eqset_line('x', '3');
    mjax_eqset_end();
?>

<h2>Unicode, term-variable, and vector helpers</h2>

$$
    p = 12~<?= mathjax_unic('€') ?>,\qquad
    <?= mathjax_term_var(0) ?> + <?= mathjax_term_var(1) ?> = 7
$$

$$
    \vec{u} = <?= mjax_vec_2d('2', '-1') ?>,\qquad
    \vec{v} = <?= mjax_vec_3d('1', '0', '3') ?>
$$

<h2>Number-formatting helpers</h2>

<p><code>mj_num()</code> uses a decimal comma and replaces trailing zeroes with invisible placeholders; <code>mj_num_parens()</code> additionally wraps negative values in parentheses.</p>

<div id="number-formatting-demo"></div>

<script>
    document.getElementById('number-formatting-demo').innerHTML = String.raw`
        \begin{tightarray}{rr}
            2 \cdot ${mj_num(12)} & = ${mj_num(24)} \\
            2 \cdot ${mj_num(12.5)} & = ${mj_num(25)} \\
            ${mj_num_parens(-2.75)} & < ${mj_num_parens(3)}
        \end{tightarray}
    `;
</script>

<h2>Post-render hook</h2>

<p>The module runs registered hooks after MathJax completes its initial rendering. Status: <output id="mathjax-hook-status">waiting</output>.</p>

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
