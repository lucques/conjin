<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'conjin-demo');

        $c->activate_module('nav-build');


        $c->add_subpage('module-demos');
        $c->add_subpage('module-demos-ext');
    };
    
    $process = function(Target $t) {
?>

<p>
    Explore the pages below to see Conjin templates, built-in modules, and optional integrations in focused examples.
</p>

<?
    };
?>
