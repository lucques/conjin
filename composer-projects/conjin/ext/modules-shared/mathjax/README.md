# mathjax

This external module loads the bundled MathJax runtime so a target can render TeX notation as HTML.

## Usage

Activate the module and write TeX using MathJax's inline or display delimiters:

```php
$c->activate_module('mathjax');
```

```html
The inline expression \(a^2 + b^2 = c^2\) is followed by
\[
    x = \frac{-b \pm \sqrt{b^2 - 4ac}}{2a}.
\]
```

See the [demo page](../../../../demo-app/src/content/module-demos-ext/mathjax/index.php).

## Module relationships

### Dependencies

- `doc-extensions`: Activated automatically to add the MathJax script to the document head.

## Structure and lifecycle

The module loads `res/es5/tex-chtml.js` once during target processing. Without page-prep, MathJax scans and typesets the initial document when that script starts. With page-prep active, automatic initial typesetting is disabled and one awaited `typesetPromise()` pass runs after `initialContentReady` and before `renderedContentReady`, including labels created by registered JSXGraph board setup.

## Upstream project

- Project: [MathJax](https://www.mathjax.org/)
- Version: 3.2.2
- License: Apache-2.0
- Source: The pinned `mathjax` npm package; `build` copies its `es5` distribution into `res/es5`.
- Local changes: None; the module selects the TeX input and CommonHTML output component.

When page-prep is active, automatic initial typesetting is disabled. The module registers one explicit typeset pass after initialContentReady and before renderedContentReady, then runs post-render hooks. Pages without page-prep retain automatic typesetting.
