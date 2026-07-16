# geogebra

Provides the self-hosted GeoGebra runtime and loads `deployggb.js`.

## Usage

Activate the module during preprocessing:

```php
$c->activate_module('geogebra');
```

Create and inject the applet with GeoGebra's JavaScript API. Pointing the applet at the module's bundled HTML5 codebase keeps all runtime requests local:

```php
<?
    $codebase_url = (new ModuleLocation('geogebra'))->get_url() . '/res/HTML5/5.0/web3d/';
?>

<div id="geogebra-applet"></div>

<script>
const applet = new GGBApplet({
    id: 'geogebraApi',
    appName: 'graphing',
    width: 640,
    height: 420,
    appletOnLoad(api) {
        api.evalCommand('f(x) = x^2 - 2');
    },
}, true);

applet.setHTML5Codebase(<?= json_encode($codebase_url) ?>);
applet.inject('geogebra-applet');
</script>
```

For typed PHP helpers and 3D plotting, use the `geogebra-helpers` module instead.

## Examples

See the [plain GeoGebra demo](../../../../demo-app/src/content/module-demos-ext/geogebra/index.php).

## Project info

- Bundle: `geogebra-math-apps-bundle-5-4-925-3.zip`
- [GeoGebra Apps Embedding documentation](https://geogebra.github.io/docs/reference/en/GeoGebra_Apps_Embedding/)
