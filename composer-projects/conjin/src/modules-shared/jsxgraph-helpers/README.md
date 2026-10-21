# jsxgraph-helpers

Provides typed PHP helpers for plotting sequences, Cartesian systems, and independent 3D systems with the bundled JSXGraph runtime.

## Usage

```php
$c->activate_module('jsxgraph-helpers');
```

## API

### `plot_sequences(SequencePlotOptions $options, array $elements)`

Prints a board containing explicit and recursive `graphing-types` sequences.

```php
<?
    use Conjin\ModulesShared\GraphingTypes as GT;
    use Conjin\ModulesShared\JSXGraphHelpers as JSX;

    JSX\plot_sequences(
        new JSX\SequencePlotOptions(
            y_min: 0,
            y_max: 10,
            width: 400,
            height: 300
        ),
        [
            'sequence' => new GT\ExplicitSequence(
                function_js: '(n) => n',
                n_end: 10
            ),
        ]
    );
?>
```

Each explicit or recursive sequence may set nullable integer `n_start` and `n_end` rendering bounds. A null `n_start` resolves to 1 by default or 0 when `start_from_zero` is true; for recursive sequences, `init_val_js` then represents the term at that intrinsic first index. A later `n_start` suppresses earlier points without changing the sequence's intrinsic first index or recursive calculation.

`SequencePlotOptions` accepts nullable float `x_min` and `x_max` initial viewport bounds. If omitted, `x_min` is the smallest resolved sequence start and `x_max` is the largest finite sequence end; when no finite end exists, `x_max` defaults to 10. Panning or zooming synchronizes the rendered points with the visible integer indices without changing the sequences' `n_start` and `n_end` domains.

### `plot_2d(GraphOptions $options, array $elements)`

Prints a Cartesian board containing 2D points, lines, function graphs, integrals, controls, or custom JSXGraph code.

```php
<?
    use Conjin\ModulesShared\GraphingTypes\System2D as G2D;
    use Conjin\ModulesShared\JSXGraphHelpers as JSX;

    JSX\plot_2d(
        new JSX\GraphOptions(
            viewport: new JSX\Viewport2D(
                width: 400,
                height: 300,
                x_min: -5,
                x_max: 5,
                y_min: -5,
                y_max: 5,
            ),
        ),
        [
            'graph' => new G2D\FunctionGraph('(x) => x * x'),
        ]
    );
?>
```

`GraphOptions` takes a `Viewport2D`, which combines the pixel dimensions and initial coordinate bounds. Function graphs without `x_start_fun_js` or `x_end_fun_js` follow the current horizontal viewport while panning and zooming; setting either function-graph bound explicitly restricts that side of its domain.

### `Viewport2D`

The constructor accepts explicit `width`, `height`, `x_min`, `x_max`, `y_min`, and `y_max` values. Dimensions must be positive, and coordinate bounds must be finite and increasing.

The named factories preserve equal units per pixel on both coordinate axes:

- `Viewport2D::fromXRange()` derives the vertical bounds from the horizontal range. Its optional `x_axis_y_px` is measured downwards from the top edge and defaults to `height / 2`.
- `Viewport2D::fromYRange()` derives the horizontal bounds from the vertical range. Its optional `y_axis_x_px` is measured from the left edge and defaults to `width / 2`.
- `Viewport2D::fromFittedBounds()` centers the rectangle given by `x_min`, `x_max`, `y_min`, and `y_max` inside the requested pixel dimensions. It expands one coordinate range as needed to show the entire rectangle at equal x- and y-axis unit scales; matching aspect ratios preserve both ranges.
- `Viewport2D::fromCenterAndUnitsPerPixel()` derives both ranges from a coordinate center and a positive `units_per_pixel` value. `center_x` and `center_y` default to zero.

```php
$viewport = JSX\Viewport2D::fromXRange(
    width: 400,
    height: 300,
    x_min: -5,
    x_max: 5,
);
```

To fit a coordinate rectangle while keeping equal unit scales:

```php
$viewport = JSX\Viewport2D::fromFittedBounds(
    width: 600,
    height: 300,
    x_min: -2,
    x_max: 2,
    y_min: -2,
    y_max: 2,
);
```

This produces outer coordinate bounds of `x = [-4, 4]` and `y = [-2, 2]`. The requested square is centered with equal horizontal margins, and one unit occupies 75 pixels on either axis.

## Interaction behavior

Plot option objects expose interaction enums from `Conjin\ModulesShared\JSXGraphHelpers`. `Zoom::EnabledWithNav` and `Pan::EnabledWithNav` make the feature available through visible navigation controls. `Rotation::EnabledWithSliders`, available on `Plot3DDynamicOptions`, makes rotation available through visible view sliders.

`WheelBehavior`, `PinchBehavior`, `TwoFingerTranslationBehavior`, and the 2D or 3D one-finger and mouse-drag behaviors grant additional input methods only while their corresponding feature is enabled. These permissions may remain dormant when that feature is disabled.

```php
use Conjin\ModulesShared\JSXGraphHelpers as JSX;

new JSX\GraphOptions(
    viewport: new JSX\Viewport2D(
        width: 400,
        height: 300,
        x_min: -5,
        x_max: 5,
        y_min: -5,
        y_max: 5,
    ),
    zoom: JSX\Zoom::EnabledWithNav,
    pan: JSX\Pan::EnabledWithNav,
    wheel_behavior: JSX\WheelBehavior::AllowForZoom,
    pinch_behavior: JSX\PinchBehavior::AllowPinchForZoom,
    one_finger_drag_behavior: JSX\OneFingerDragBehavior2D::AllowForPan,
    mouse_drag_behavior: JSX\MouseDragBehavior2D::AllowForPan
)
```

The defaults preserve each renderer's established behavior: sequences and Cartesian plots disable all interactions; cavalier plots provide navigation zoom and pan plus one-finger and mouse-drag panning; dynamic 3D plots provide navigation zoom and pan plus slider, one-finger, and mouse-drag rotation. Wheel zoom, pinch zoom, and two-finger translation are disabled by default.

The helpers reject combinations that JSXGraph cannot faithfully render. In particular, zoom navigation requires pan navigation because JSXGraph's navigation bar always exposes working pan controls, and JSXGraph cannot independently configure mouse and touch panning or mouse and one-finger rotation.

## Print rendering

All plot option objects accept a `print_mode` value from `Conjin\ModulesShared\JSXGraphHelpers\PrintMode`. `PrintMode::Disabled` always uses normal graph rendering, `PrintMode::Enabled` always uses print rendering, and the default `PrintMode::Auto` enables print rendering when the target has activated the `print-mode` module. `PrintMode::Enabled` affects only the graph rendering and does not activate the module.

Print rendering doubles the configured axis and major-tick thickness and uses black coordinate axes and black grid lines while retaining the grid's normal opacity. Dynamic and cavalier 3D axes already use black in both modes.

## Axes, ticks, spacing, and grid

`GraphOptions` and `SequencePlotOptions` accept `Axes2D`, `Spacing2D`, and `Grid2D` through their `axes`, `spacing`, and `grid` arguments. Both 3D renderers accept `Axes3D` and `Spacing3D`; the cavalier renderer also accepts `Grid2D` for its existing flat background grid. All types are in `Conjin\ModulesShared\JSXGraphHelpers`.

`Axis`, `Axes2D`, `Axes3D`, `Ticks`, and `Grid2D` are final readonly values constructed with `::enabled(...)` or `::disabled()`. Disabled values accept no configuration. Omitted plot arguments preserve the renderer defaults: enabled axes, major ticks and numbers, automatic Cartesian spacing, integer sequence indices, and fixed unit spacing in 3D. Grids default to enabled wherever a grid is supported. Omit `axes` and `grid` when using these defaults; pass them only to customize or disable the feature. Nuggets and other callers do not need `axes: Axes2D::enabled()`, `axes: Axes3D::enabled()`, or `grid: Grid2D::enabled()`.

```php
new JSX\GraphOptions(
    viewport: $viewport,
    axes: JSX\Axes2D::enabled(
        default: JSX\Axis::enabled(
            thickness: 1.5,
            ticks: JSX\Ticks::enabled(numbers_font_size_adjust: -1),
        ),
        x: JSX\Axis::enabled(label: 't'),
        y: JSX\Axis::disabled(),
    ),
    spacing: new JSX\Spacing2D(
        default: JSX\Spacing::fixed(major: 1, subdivisions: 5),
        x: JSX\Spacing::fixed(major: 2, subdivisions: 4),
    ),
    grid: JSX\Grid2D::enabled(minor: true),
);
```

### Inheritance and visibility

`Axes2D::enabled()` accepts nullable `default`, `x`, and `y` axes; `Axes3D::enabled()` additionally accepts `z`. An omitted coordinate inherits `default`. An explicit enabled axis merges its omitted fields with the enabled default, then with built-in defaults. `default: Axis::disabled()` hides unspecified axes; an explicit `Axis::enabled()` re-enables a coordinate using built-in defaults. `Axes2D::disabled()` or `Axes3D::disabled()` hides every axis and its annotations.

An enabled `Axis` accepts nullable `label`, `label_font_size_adjust`, `thickness`, and `ticks`. Null means inherit; `label: ''` explicitly hides the axis name. Built-in labels are `x/y`, `n/y` for sequences, and `x/y/z` in 3D. Thickness is a finite positive float in pixels, defaults to `1`, and also controls major-tick thickness and the extra margin before numbers.

An enabled `Ticks` accepts nullable `minor`, `numbers`, `numbers_font_size_adjust`, and `numbers_formatter_js`. Nested tick fields inherit individually. `Ticks::disabled()` hides tick marks and numbers, while `Ticks::enabled(numbers: false)` keeps marks without numbers. The formatter is a JavaScript function expression receiving the coordinate, for example `"(x) => Math.round(x * 60) + '/60'"`; it changes text only. Literal axis labels are encoded safely as JavaScript strings.

`base_font_size` remains a plot option using `GraphingTypes\FontSize` and defaults to `FontSize::MD`. `XS`, `SM`, `MD`, `LG`, and `XL` render as 10, 12, 14, 16, and 18 pixels. Integer font adjustments add two pixels per step. Axis names default to adjustment `0`; tick numbers default to `-1`. Resolved font sizes must remain positive. Print mode doubles each resolved axis and major-tick thickness independently.

### Shared spacing

`Spacing2D` and `Spacing3D` have nullable `default` and coordinate fields. An explicit coordinate replaces its whole spacing specification; an omitted coordinate inherits `default`, then the renderer default. `Spacing::fixed(major: ..., subdivisions: ...)` defines a finite positive interval. `Spacing::automatic(subdivisions: ...)` selects a readable interval from the current 2D viewport and updates during zoom. Subdivisions must be a positive integer and default to `5`: five subintervals give four interior minor positions. One subdivision gives no minor positions.

In Cartesian and sequence plots, one spacing function per coordinate drives both axis ticks and grid lines, anchored at zero. Hiding axes, ticks, numbers, or the grid never changes this spacing. Minor ticks default to enabled in 2D; the sequence x coordinate defaults to fixed spacing `1` with one subdivision, while its y coordinate uses automatic spacing. `Grid2D::enabled()` draws major grid lines; `minor: true` additionally draws the shared minor positions as lines. `Grid2D::disabled()` hides the grid independently of axes and ticks.

### Existing 3D capabilities

Both 3D renderers retain fixed major ticks, configurable axis names and appearance, and the existing number formatting. Automatic spacing, minor ticks, and custom number formatters are unsupported in 3D and cause an `InvalidArgumentException` when requested. Subdivisions have no visual effect on 3D major ticks. This API does not add coordinate-plane grids to dynamic 3D.

Cavalier's `Grid2D` controls its existing flat background grid. Its horizontal and vertical intervals continue to use the y and z major spacing in board coordinates, including when axes are disabled. This preserves the existing projection behavior; it does not reinterpret the background as a spatial grid. Minor background grid lines are unsupported and rejected. `axes_extend_to_negative` and the projection options remain plot options.

### Migrating from Conjin 2.x

The old flat constructor arguments are removed in Conjin 3.0. Migrate all callers together, including calls that pass optional arguments positionally; named arguments are recommended.

| Old argument | New configuration |
| --- | --- |
| `show_axes: true` | Omit `axes` (enabled by default) |
| `show_axes: false` | `axes: Axes2D::disabled()` or `Axes3D::disabled()` |
| `x_label: 't'` | `axes: Axes2D::enabled(x: Axis::enabled(label: 't'))` |
| `axis_thickness: 1.5` | `axes.default: Axis::enabled(thickness: 1.5)` |
| `axis_label_font_size_adjust` | `axes.default.label_font_size_adjust` |
| `axis_numbers_font_size_adjust` | `axes.default.ticks.numbers_font_size_adjust` |
| `show_ticks: false` | `axes.default: Axis::enabled(ticks: Ticks::disabled())` |
| `x_ticks_label_formatter` | `axes.x.ticks.numbers_formatter_js` |
| `x_ticks_distance: 2` | `spacing: new Spacing2D(x: Spacing::fixed(2))` |
| `x_ticks_minors: 4` | `spacing.x: Spacing::automatic(subdivisions: 5)`, or `fixed(..., subdivisions: 5)` |
| 3D `ticks_distance: 2` | `spacing: new Spacing3D(default: Spacing::fixed(2))` |
| `show_grid: true` | Omit `grid` (enabled by default) |
| `show_grid: false` | `grid: Grid2D::disabled()` |

Dotted names in this table indicate nested configuration fields, not PHP syntax. The same coordinate mappings apply to y and z where supported. GeoGebra's separate plot options are unchanged.

## Point-label positioning

`GraphOptions`, `Plot3DDynamicOptions`, and `Plot3DCavalierOptions` expose `point_label_auto_position_min_distance` and `point_label_auto_position_max_distance` in pixels. JSXGraph tests positions within this distance range from each labeled point's anchor while avoiding conflicts with other elements. The defaults are `8` and `14`. Both values must be finite and positive, and the maximum must be greater than the minimum; a small range such as `0.1` to `0.2` keeps automatic conflict-aware positioning active while making its distance from the anchor nearly invisible.

### `plot_3d_dynamic(Plot3DDynamicOptions $options, array $elements)`

Prints an interactive JSXGraph 3D view containing independent points, lines, planes, and polygons. The view direction can be changed by pointer interaction or optional view sliders. All three coordinate axes use the same unit scale, even when their configured ranges have different spans. The helper derives the board and 3D viewport from the canvas dimensions, reserves space for enabled controls, and fits the bounding box's projected width and height at the configured view angles into the remaining area. Set `show_bounding_box` to display the twelve edges of that box. `azimuth` and `elevation` are specified in radians, determine the initial slider positions and reset-button target, and default to the isometric angles π/4 and atan(1/√2). Azimuth must be between 0 and 2π; elevation must be between -π/2 and π/2.

```php
<?
    use Conjin\ModulesShared\GraphingTypes\System3DIndep as G3D;
    use Conjin\ModulesShared\JSXGraphHelpers as JSX;

    JSX\plot_3d_dynamic(
        new JSX\Plot3DDynamicOptions(
            x_min: -5,
            x_max: 5,
            y_min: -5,
            y_max: 5,
            z_min: -5,
            z_max: 5,
            width: 400,
            height: 300
        ),
        [
            'A' => new G3D\Point(new G3D\Vec('1', '2', '3')),
        ]
    );
?>
```

### `plot_3d_cavalier(Plot3DCavalierOptions $options, array $elements)`

Prints a two-dimensional cavalier projection of the same independent 3D element types. Projection, axes, grid, navigation, panning, and zooming are configurable. Set `show_bounding_box` to display the twelve projected edges of the configured three-dimensional bounds.

```php
<?
    use Conjin\ModulesShared\GraphingTypes\System3DIndep as G3D;
    use Conjin\ModulesShared\JSXGraphHelpers as JSX;

    JSX\plot_3d_cavalier(
        new JSX\Plot3DCavalierOptions(
            x_min: -5,
            x_max: 5,
            y_min: -5,
            y_max: 5,
            z_min: -5,
            z_max: 5,
            width: 400,
            height: 300
        ),
        [
            'A' => new G3D\Point(new G3D\Vec('1', '2', '3')),
        ]
    );
?>
```

## Module relationships

### Dependencies

- `graphing-types`: Activated automatically to provide typed plot elements and controls.
- `jsxgraph`: Activated automatically to load the bundled JSXGraph stylesheet and runtime.
- `mathjax`: Activated automatically for mathematical labels and control values.

### Requirements

- A template supporting `doc-extensions`.

## Examples

See the [demo page](../../../../demo-app/src/content/module-demos/jsxgraph-helpers/index.php) for sequences, Cartesian plots, dynamic 3D views, and cavalier 3D projections.

## Initial document readiness

With page-prep active, the plot helpers register board creation before `initialContentReady`. The final awaited MathJax pass then typesets the document, including board labels, before `renderedContentReady`; resources, measurements and pagination follow. Without page-prep, boards retain their normal DOM-ready initialization. Later interactive updates remain outside initial preparation.

Custom board setup can call `jsx_register_initial_board(id, create)` during page setup, before page-prep registration closes. The callback may return a promise and must finish creating its initial labels before resolving. Callbacks must not wait for `initialContentReady` or a later barrier because they contribute to that barrier. Boards created independently of this helper must register their own initial-content task.
