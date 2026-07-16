<?
    use Conjin\ModulesShared\GeoGebraHelpers as GGB;
    use Conjin\ModulesShared\GraphingTypes as GT;
    use Conjin\ModulesShared\GraphingTypes\System3DDep as G3DD;

    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-interbook');
        $c->activate_module('nav-build');
        $c->activate_module('geogebra-helpers');
    };
?>

<? $process = function (Target $target) { ?>

<h1>geogebra-helpers</h1>

<p>The typed helpers create a self-hosted GeoGebra 3D applet and configure its objects through the Apps API.</p>

<p id="geogebra-status">Loading applet…</p>

<?
    GGB\plot_3d(
        new GGB\Plot3DOptions(
            x_min: -5,
            x_max: 5,
            y_min: -5,
            y_max: 5,
            z_min: -3,
            z_max: 5,
            width: 700,
            height: 450,
            show_grid: true
        ),
        [
            'A' => new G3DD\Point(new G3DD\Vec('1', '-1', '1'), label: 'A', color: GT\Color::Red),
            'B' => new G3DD\Point(new G3DD\Vec('3', '2', '2'), label: 'B', color: GT\Color::Blue),
            'C' => new G3DD\Point(new G3DD\Vec('-2', '2', '0'), label: 'C', color: GT\Color::Green),
            'g' => new G3DD\Line(point_a: 'A', point_b: 'B', color: GT\Color::Purple),
            'triangle' => new G3DD\Polygon(vertices: ['A', 'B', 'C'], color: GT\Color::Orange),
        ],
        [
            new GGB\CustomAction(
                "document.getElementById('geogebra-status').textContent = 'Applet ready';"
            ),
        ]
    );
?>

<? }; ?>
