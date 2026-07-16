<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('prism');
    };
?>

<? $process = function (Target $target) { ?>

<pre id="prism-listing" class="line-numbers" data-line="2"><code class="language-java">public class Greeting {
    public static void main(String[] args) {
        System.out.println("Hello, Prism!");
    }
}</code></pre>

<? }; ?>
