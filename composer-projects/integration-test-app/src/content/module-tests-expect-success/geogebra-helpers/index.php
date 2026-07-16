<?
    use Conjin\ModulesShared\GeoGebraHelpers as GGB;
    use Conjin\ModulesShared\GraphingTypes as GT;
    use Conjin\ModulesShared\GraphingTypes\System3DDep as G3DD;

    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');
        $c->activate_module('nav-build');
        $c->activate_module('geogebra-helpers');
    };
?>

<? $process = function (Target $target) { ?>

<p id="geogebra-status" data-ready="false">Loading</p>

<?
    $applet = GGB\plot_3d(
        new GGB\Plot3DOptions(
            x_min: -4,
            x_max: 4,
            y_min: -4,
            y_max: 4,
            z_min: -4,
            z_max: 4,
            width: 560,
            height: 360
        ),
        [
            'A' => new G3DD\Point(new G3DD\Vec('1', '0', '1'), label: 'A', color: GT\Color::Red),
            'B' => new G3DD\Point(new G3DD\Vec('-1', '2', '0'), label: 'B', color: GT\Color::Blue),
            'g' => new G3DD\Line(point_a: 'A', point_b: 'B'),
        ],
        [
            new GGB\CustomAction(
                "document.getElementById('geogebra-status').dataset.ready = 'true';"
            ),
        ]
    );
?>

<span id="geogebra-api" data-api-variable="<?= htmlspecialchars($applet->api_var_name, ENT_QUOTES) ?>"></span>

<? }; ?>
