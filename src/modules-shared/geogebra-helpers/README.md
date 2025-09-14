# geogebra-helpers

This module provides typed PHP helpers for embedding self-hosted GeoGebra apps, plotting 3D objects from `graphing-types`, and running GeoGebra Apps API calls once the applet has loaded.

It depends on the `geogebra` module, which loads `deployggb.js`. The helpers set the local HTML5 codebase to the bundled `res/HTML5/5.0/web3d/` directory by default.

## Example

```php
<?
    use Conjin\ModulesShared\GeoGebraHelpers as GGB;
    use Conjin\ModulesShared\GraphingTypes as GT;
    use Conjin\ModulesShared\GraphingTypes\System3DDep as G3DD;

    GGB\plot_3d(
        new GGB\Plot3DOptions(
            x_min: -5,
            x_max: 5,
            y_min: -4,
            y_max: 6,
            z_min: -3,
            z_max: 3,
            width: 700,
            height: 450,
            show_grid: true
        ),
        [
            'A' => new G3DD\Point(new G3DD\Vec('1', '-1', '2'), label: 'A', color: GT\Color::Red),
            'B' => new G3DD\Point(new G3DD\Vec('3', '2', '1'), label: 'B', color: GT\Color::Red),
            'g' => new G3DD\Line(
                point_a: 'A',
                point_b: 'B',
                color: GT\Color::Blue
            ),
        ]
    );
?>
```

For lower-level use, call `ggb_print_applet()` with `AppletOptions` and a list of `AppletAction` objects such as `EvalCommand`, `SetCoordSystem`, `SetVisible`, or `RegisterObjectUpdateListener`.
