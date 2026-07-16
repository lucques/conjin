# math-value-table

This module offers the printing of dynamic value tables.

Expressions are parsed and evaluated with Math.js, converted to TeX, and rendered with MathJax.

## Module relationships

### Dependencies

- `doc-extensions`: Activated automatically to load the module's browser script.
- `js-standard-lib`: Activated automatically for browser-global JavaScript helpers.
- `mathjax-extensions`: Activated automatically to provide MathJax and render the generated TeX.
- `mathjs`: Activated automatically to parse, validate, and evaluate expressions.
- `sync-dims`: Activated automatically to align the rendered expression chunks.

## Examples

See the [demo page](../../../../demo-app/src/content/module-demos/math-value-table/index.php).

## TODOs

- The DOM input element with `type="number"` does not allow non-numbers to be set to it (it uses the empty string instead).
