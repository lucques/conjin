<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'math.js');

        $c->activate_module('mathjs');
    };
?>

<? $process = function (Target $target) { ?>

<form id="mathjs-demo">
    <label for="mathjs-expression">Expression</label>
    <input id="mathjs-expression" type="text" value="sqrt(3^2 + 4^2)">
    <button type="submit">Evaluate</button>
</form>

<p>Result: <output id="mathjs-result" for="mathjs-expression"></output></p>

<script>
const mathjsDemo = document.getElementById('mathjs-demo');
const mathjsExpression = document.getElementById('mathjs-expression');
const mathjsResult = document.getElementById('mathjs-result');

function evaluateMathjsExpression() {
    try {
        mathjsResult.value = math.format(math.evaluate(mathjsExpression.value));
    } catch (error) {
        mathjsResult.value = error.message;
    }
}

mathjsDemo.addEventListener('submit', function(event) {
    event.preventDefault();
    evaluateMathjsExpression();
});

evaluateMathjsExpression();
</script>

<? }; ?>
