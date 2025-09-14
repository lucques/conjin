# graphing-types

Shared dependency module for graphing-related PHP data types.

This module is activated by `geogebra-helpers` and `jsxgraph-helpers` so shared graphing types can live behind a common module dependency.


## Modes

The following graphing modes exist:
- `sequence`
    - Can be drawn by `jsx_plot_sequences`
- `system-2d`
    - Can be drawn by `jsx_plot_2d`
- `system-3d-dependent`: Lines are constructed out of points etc. They depend on the points.
    - Can be drawn by `ggb_plot_3d`
- `system-3d-independent`: Lines are constructed out of points etc. They do not depend on the points; everything is inlined.
    - Can be drawn by `jsx_plot_3d_dynamic`
    - Can be drawn by `jsx_plot_3d_cavalier`