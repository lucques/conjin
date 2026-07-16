# jsxgraph-helpers

Provides typed PHP helpers for plotting sequences, Cartesian systems, and independent 3D systems with the bundled JSXGraph runtime.

## Usage

```php
$c->activate_module('jsxgraph-helpers');
```

```php
<?
    use Conjin\ModulesShared\GraphingTypes as GT;
    use Conjin\ModulesShared\GraphingTypes\System2D as G2D;
    use Conjin\ModulesShared\JSXGraphHelpers as JSX;

    JSX\plot_2d(
        new JSX\GraphOptions(
            x_min: -5,
            x_max: 5,
            y_min: -4,
            y_max: 6,
            width: 700,
            height: 450
        ),
        [
            'graph_f' => new G2D\FunctionGraph(
                function_js: '(x) => 0.5 * x * x - 2',
                label: 'f',
                color: GT\Color::Blue
            ),
            'A' => new G2D\Point(
                x_fun_js: '() => 2',
                y_fun_js: '() => 0',
                label: 'A',
                color: GT\Color::Red
            ),
        ]
    );
?>
```

## API

### `plot_sequences(SequencePlotOptions $options, array $elements)`

Prints a board containing explicit and recursive `graphing-types` sequences.

### `plot_2d(GraphOptions $options, array $elements)`

Prints a Cartesian board containing 2D points, lines, function graphs, integrals, controls, or custom JSXGraph code.

### `plot_3d_dynamic(Plot3DDynamicOptions $options, array $elements)`

Prints an interactive JSXGraph 3D view containing independent points, lines, planes, and polygons. The view direction can be changed by pointer interaction or optional view sliders.

### `plot_3d_cavalier(Plot3DCavalierOptions $options, array $elements)`

Prints a two-dimensional cavalier projection of the same independent 3D element types. Projection, axes, grid, navigation, panning, and zooming are configurable.

## Module relationships

### Dependencies

- `graphing-types`: Activated automatically to provide typed plot elements and controls.
- `jsxgraph`: Activated automatically to load the bundled JSXGraph stylesheet and runtime.
- `mathjax`: Activated automatically for mathematical labels and control values.

### Requirements

- A template supporting `doc-extensions`.

## Examples

See the [demo page](../../../../demo-app/src/content/module-demos/jsxgraph-helpers/index.php) for sequences, Cartesian plots, dynamic 3D views, and cavalier 3D projections.

## VS Code integration

VS Code marks the `processing.php` file as having syntax errors. It is due to some bug with PHP and JS combination. I did not manage to find a better way so I just live with it (exclude it in VS Code settings file).
