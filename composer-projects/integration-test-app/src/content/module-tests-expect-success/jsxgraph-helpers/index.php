<?
    use Conjin\ModulesShared\GraphingTypes as GT;
    use Conjin\ModulesShared\GraphingTypes\System2D as G2D;
    use Conjin\ModulesShared\JSXGraphHelpers as JSX;

    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');
        $c->activate_module('jsxgraph-helpers');
    };
?>

<? $process = function (Target $target) { ?>

<p id="jsxgraph-status" data-ready="false">Loading</p>

<?
    JSX\plot_2d(
        new JSX\GraphOptions(
            x_min: -4,
            x_max: 4,
            y_min: -4,
            y_max: 4,
            width: 560,
            height: 360
        ),
        [
            'graph_f' => new G2D\FunctionGraph(
                function_js: '(x) => x * x - 1',
                color: GT\Color::Blue
            ),
            'ready' => new GT\CustomElement(
                "document.getElementById('jsxgraph-status').dataset.ready = 'true';"
            ),
        ]
    );
?>

<? }; ?>
