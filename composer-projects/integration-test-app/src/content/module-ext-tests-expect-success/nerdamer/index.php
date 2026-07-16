<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('nerdamer');
    };
?>

<? $process = function (Target $target) { ?>

<output id="nerdamer-result"></output>

<script>
document.getElementById('nerdamer-result').textContent = nerdamer('(x + 1)^3').expand().text();
</script>

<? }; ?>
