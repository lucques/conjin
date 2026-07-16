<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-exam', [
            'institution' => 'Integration Test',
            'class'       => '11',
            'title'       => 'Print mode',
        ]);
        $c->activate_module('nav-build');
        $c->activate_module('mathjax-extensions');
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
