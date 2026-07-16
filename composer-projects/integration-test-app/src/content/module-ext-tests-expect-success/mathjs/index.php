<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('mathjs');
    };
?>

<? $process = function (Target $target) { ?>

<p id="mathjs-result"></p>

<script>
document.getElementById('mathjs-result').textContent = math.format(
    math.evaluate('sqrt(3^2 + 4^2)')
);
</script>

<? }; ?>
