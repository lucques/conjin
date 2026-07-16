# mathjax-extensions Agent Notes

## Purpose

- This module extends the base `mathjax` module and depends on `doc-extensions`.
- `preprocessing.php` activates both `doc-extensions` and `mathjax`, so changes here must stay compatible with those modules.

## How The Module Is Used

- `processing.php` prepends `res/mathjax-extensions.js` into the document head.
- It uses `doc_extensions_prepend_head_element(...)` instead of a normal add call because the MathJax extension config must load before the MathJax library itself.
- Content code can call the PHP helpers defined in `processing.php`:
  - `mathjax_unic(string $symbol)` for selected Unicode symbols in math mode. Currently only `€` is supported.
  - `mathjax_term_var(int $index)` for shape-based math variables.
  - `mjax_eqset_start(...)`, `mjax_eqset_line(...)`, and `mjax_eqset_end()` to emit aligned equation sets using the custom `tightarray` environment.
- Use `$$...$$` display math blocks outside of `<p>` elements. They are block elements and must not be nested inside paragraphs.

## Equation Set Contract

Example:
```
    2x + 2 = 4   | -2
<=> 2x     = 2   | :2
<=>  x     = 1
```

- `mjax_eqset_start()` opens a `tightarray` with a generated alignment string.
- `mjax_eqset_line()` must only be used while an equation set is active.
- `mjax_eqset_end()` must close the active equation set.
- When an equivalence transformation uses `\pm \sqrt{...}`, it branches into two equations. End the active `mjax_eqset` there and pursue the two cases below, using separate equation sets if further transformations are needed.
- The implementation tracks active state through `$GLOBALS['mathjax_eqset_number_of_lines']`; preserve that invariant when editing the helper flow.

## Expression transformation

Example:
```
f(3) = 2*(3-2)^2 + 3
     = 2*1^2 + 3
     = 2*1 + 3
     = 5
```

- Use `\begin{align*}` for this.
- Refrain from using `mjax_eqset` for this, as the left-hand side of the equation remains the same.


## JS Extension Behavior

- `res/mathjax-extensions.js` defines the MathJax startup config.
- It registers the custom `tightarray` environment and enables the `color` and `unicode` TeX packages.
- It also supports optional post-render hooks through `window.mathJaxAfterRenderingHooks`.
- Numeric formatting helpers such as `mj_num(...)` and `mj_num_parens(...)` live in the JS file, so changes there affect client-side math rendering behavior.

## When Editing

- Read `processing.php` first for the PHP-side API and load order requirements.
- Read `res/mathjax-extensions.js` for MathJax startup, TeX package registration, and helper behavior.
- Read `README.md` for documented usage of hooks and the `tightarray` environment.
- Keep documentation and code examples aligned if you change the exposed helpers or the `tightarray` contract.
