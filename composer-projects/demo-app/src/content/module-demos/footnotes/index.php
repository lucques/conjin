<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('footnotes');
    };
?>

<? $process = function (Target $target) { ?>

<h1>footnotes</h1>

<p>The first reference<? footnote_start(); ?>Footnotes may contain <strong>HTML markup</strong>.<? footnote_end(); ?> and the second reference<? footnote_start(); ?>References and backlinks are numbered in insertion order.<? footnote_end(); ?> are inserted inline.</p>

<p>The supporting template renders the collected notes below the page content. Follow a number to its note and use the arrow to return to the reference.</p>

<? }; ?>
