<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'Modules (external)');

        $c->add_subpage('bootstrap');
        $c->add_subpage('bootstrap-icons');
        $c->add_subpage('chart-js');
        $c->add_subpage('fullcalendar');
        $c->add_subpage('jsxgraph');
        $c->add_subpage('mathjax');
        $c->add_subpage('mathjs');
        $c->add_subpage('nerdamer');
        $c->add_subpage('paged-js');
        $c->add_subpage('picocss');
        $c->add_subpage('prism');
        $c->add_subpage('sql-js');
        $c->add_subpage('tom-select');
    };
?>

<? $process = function (Target $target) { ?>

<p>
    Demos fo various modules
</p>

<? }; ?>
