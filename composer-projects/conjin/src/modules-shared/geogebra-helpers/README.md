# geogebra-helpers

Provides typed PHP helpers for embedding self-hosted GeoGebra applets, plotting 3D objects, and running GeoGebra Apps API calls after loading.

## Usage

```php
$c->activate_module('geogebra-helpers');
```

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

## API

### `plot_3d(Plot3DOptions $options, array $elements, array $actions = []): AppletRef`

Prints a 3D applet containing `graphing-types` elements, followed by optional `AppletAction` objects.

### `ggb_print_applet(AppletOptions $options = new AppletOptions(), array $actions = []): AppletRef`

Prints a configurable applet and runs actions such as `EvalCommand`, `SetCoordSystem`, `SetVisible`, or `RegisterObjectUpdateListener` when it loads. The HTML5 codebase defaults to the bundled self-hosted version.

## Module relationships

### Dependencies

- `geogebra`: Activated automatically to load `deployggb.js` and the bundled GeoGebra runtime.
- `graphing-types`: Activated automatically to provide typed plot elements.

### Requirements

- A template supporting `doc-extensions`.

## Examples

See the [demo page](../../../../demo-app/src/content/module-demos/geogebra-helpers/index.php).
