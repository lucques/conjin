<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'Prism');

        $c->activate_module('prism');
    };
?>

<? $process = function (Target $target) { ?>

<p>Prism highlights source code and can add line numbers, highlighted lines, and a copy button.</p>

<pre class="line-numbers" data-line="2"><code class="language-java">public class Greeting {
    public static void main(String[] args) {
        System.out.println("Hello, Prism!");
    }
}</code></pre>

<? }; ?>
