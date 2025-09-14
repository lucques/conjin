# bootstrap-alternative

This internal module implements the accordion and collapse macros offered by
`bootstrap-extended`, without loading Bootstrap. Existing content can therefore
use the same `acc_*`, `collapse_button()`, `collapse_a_start()`, and
`collapse_a_end()` APIs unchanged. It also provides Bootstrap-compatible card,
badge, form, modal, and table styles. Modals use Bootstrap-compatible `modal`
markup and `data-bs-toggle="modal"` / `data-bs-dismiss="modal"` attributes.

Tables support Bootstrap's `.table` styles, compact, bordered, borderless,
striped-row, striped-column, hover, active, contextual variant, caption, group
divider, and responsive wrapper classes.

The module defines the same global functions as `bootstrap-extended` (including
`variant_to_color`). It therefore cannot be activated together with
`bootstrap-extended`.

The macro definitions intentionally remain in the separate `acc.php` and
`collapse.php` files. The module's `processing.php` loads both and adds their
standalone CSS and JavaScript.

## Customizing the primary color

The module's buttons, focus indicators, primary accordions, backgrounds, and
borders derive their colors from the Bootstrap-style CSS variables
`--bs-primary` and `--bs-primary-rgb`. Configure both values when activating the
module to replace the default Bootstrap blue for a target:

```php
$c->activate_module('bootstrap-alternative', [
    'primary' => '#6f42c1',
    'primary_rgb' => '111, 66, 193',
]);
```

`primary` must be a six-digit hexadecimal CSS color. `primary_rgb` must contain
the same color as three comma-separated values from `0` to `255`. Both settings
default to `null`; a `null` setting leaves the corresponding built-in CSS
variable unchanged. When configured, the module automatically adds an inline
`:root` override after its stylesheets. Hover, focus, and subtle component
colors are derived automatically, so no additional CSS is required.
