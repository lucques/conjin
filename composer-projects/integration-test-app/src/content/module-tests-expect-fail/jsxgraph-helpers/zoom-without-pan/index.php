<?
    use Conjin\ModulesShared\JSXGraphHelpers as JSX;

    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');
        $c->activate_module('jsxgraph-helpers');
    };
?>

<? $process = function (Target $target) { ?>

<?
    JSX\plot_2d(
        new JSX\GraphOptions(
            viewport: new JSX\Viewport2D(
                width: 320,
                height: 240,
                x_min: -1,
                x_max: 1,
                y_min: -1,
                y_max: 1,
            ),
            zoom: JSX\Zoom::EnabledWithNav,
            pan: JSX\Pan::Disabled
        ),
        []
    );
?>

<? }; ?>
