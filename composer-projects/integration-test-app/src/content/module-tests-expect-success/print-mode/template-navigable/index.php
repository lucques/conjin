<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');
        $c->activate_module('mathjax-extensions');
        $c->activate_module('print-mode');
    };
?>

<?
    $process = function (Target $target) {
        css_print_mode_start();
?>
#print-mode-only {
    color: rgb(12, 34, 56);
}
<?
        css_print_mode_end();
?>

<p id="first-page">First page: $x = 1$</p>
<?
        page_break('a4', false);
?>
<p id="second-page">Second page</p>

<?
        page_break('a5', true);
?>
<p id="third-page">Third page</p>

<?
        page_break('a5', false);
?>
<p id="fourth-page">Fourth page</p>

<?
    };
?>
