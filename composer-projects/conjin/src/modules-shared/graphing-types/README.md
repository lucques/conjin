# graphing-types

Provides shared immutable PHP data types for describing graphs. It does not render anything by itself.

## Usage

The renderer modules activate this module automatically. Construct its types and pass them to the corresponding renderer:

```php
use Conjin\ModulesShared\GraphingTypes\System2D as G2D;

$point = new G2D\Point('() => 1', '() => 2');
```

## API

### Common types

- `Slider`, `Checkbox`, `CustomElement`, and `CoordinateMarker`
- `Color`, `Stroke`, and `Opacity`
- Element marker interfaces for each graphing mode
- `translate_color_to_hex()` and `translate_opacity_to_float()`

### Graphing modes

- **`sequence`**: `ExplicitSequence`, `RecursiveSequence`, and `SequencePlotOptions`; rendered by `plot_sequences()` from `jsxgraph-helpers`.
- **`system-2d`**: `Point`, `Line`, `FunctionGraph`, and `Integral`; rendered by `plot_2d()` from `jsxgraph-helpers`.
- **`system-3d-dependent`**: Points, lines, planes, and polygons whose objects refer to each other; rendered by `plot_3d()` from `geogebra-helpers`.
- **`system-3d-independent`**: Self-contained points, lines, planes, and polygons; rendered by `plot_3d_dynamic()` or `plot_3d_cavalier()` from `jsxgraph-helpers`.

## Module relationships

### Used or extended by

- `geogebra-helpers`: Activates the module automatically for dependent 3D plots.
- `jsxgraph-helpers`: Activates the module automatically for sequences, 2D plots, and independent 3D plots.
