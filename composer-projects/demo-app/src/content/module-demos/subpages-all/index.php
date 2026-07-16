<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');
        $c->activate_module('subpages-all');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'subpages-all');
    };
?>

<? $process = function (Target $target) { ?>

<p>
    The two example directories below are added as subpages automatically. The parent page only activates <code>subpages-all</code>; it does not call <code>add_subpage()</code>.
</p>
<ul>
    <li><a href="first-example/">First example</a></li>
    <li><a href="second-example/">Second example</a></li>
</ul>
<p>
    Directory names determine their order in the navigation.
</p>

<? }; ?>
