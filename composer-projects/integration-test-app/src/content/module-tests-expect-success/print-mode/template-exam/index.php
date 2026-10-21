<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-exam', [
            'institution' => 'Integration Test',
            'class'       => '11',
            'title'       => 'Print mode',
        ]);
        $c->activate_module('nav-build');
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

<p id="first-page">First page</p>
<?
        if (isset($_GET['load_gate'])) {
?>
<img id="print-mode-load-gate" src="./res/load-gate.svg" alt="">
<?
        }
?>
<?
        page_break('a4', false);
?>
<p id="second-page">Second page</p>

<?
    };
?>
