<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('mathjax-extensions');
        $c->activate_module('print-mode', [
            'size' => 'A4',
        ]);
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
    };
?>
