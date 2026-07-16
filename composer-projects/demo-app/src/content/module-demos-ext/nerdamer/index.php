<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'Nerdamer');

        $c->activate_module('nerdamer');
    };
?>

<? $process = function (Target $target) { ?>

<p>
    The expanded form of <code>(x + 1)^3</code> is
    <strong id="nerdamer-result"></strong>.
</p>

<script>
document.getElementById('nerdamer-result').textContent = nerdamer('(x + 1)^3').expand().text();
</script>

<? }; ?>
