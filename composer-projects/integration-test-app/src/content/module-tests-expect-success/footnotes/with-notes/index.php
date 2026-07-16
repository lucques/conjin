<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');
        $c->activate_module('footnotes');
    };
?>

<? $process = function (Target $target) { ?>

<p id="footnote-source">
    First reference<? footnote_start(); ?>First <strong>footnote</strong><? footnote_end(); ?>
    and second reference<? footnote_start(); ?>Second footnote<? footnote_end(); ?>.
</p>

<? }; ?>
