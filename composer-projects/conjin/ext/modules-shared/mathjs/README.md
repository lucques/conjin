# mathjs

This external module makes the browser build of math.js available to Conjin targets. It is useful for evaluating expressions and working with math.js data types directly in page JavaScript.

## Usage

Activate `mathjs` during preprocessing. The module adds the bundled script to the document head and exposes the upstream `math` object globally.

```php
<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_module('mathjs');
    };
?>

<script>
const result = math.evaluate('sqrt(3^2 + 4^2)');
</script>
```

See the [mathjs demo](../../../../demo-app/src/content/module-demos-ext/mathjs/index.php) for an interactive example.

## Module relationships

### Dependencies

- `doc-extensions`: Activated automatically so the module can add the math.js script to the document head.

## Upstream project

- Project: [math.js](https://mathjs.org/)
- Version: 12.4.2
- License: Apache-2.0
- Source: `mathjs/lib/browser/math.js` from the locked npm dependency
- Local changes: The browser bundle is copied to `res/math.min.js`; its code is otherwise unchanged.
