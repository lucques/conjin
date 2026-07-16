<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->activate_module('title');
        $c->run_macro('title', 'set', 'GeoGebra');

        $c->activate_module('geogebra');
    };
?>

<?
    $process = function (Target $target) {
        $codebase_url = (new ModuleLocation('geogebra'))->get_url() . '/res/HTML5/5.0/web3d/';
?>

<p id="geogebra-status">Loading the applet…</p>
<div id="plain-geogebra-applet"></div>

<script>
const plainGeoGebraApplet = new GGBApplet({
    id: 'plainGeoGebraApi',
    appName: 'graphing',
    width: 640,
    height: 420,
    showToolBar: false,
    showMenuBar: false,
    showAlgebraInput: false,
    appletOnLoad(api) {
        api.evalCommand('f(x) = x^2 - 2');
        api.evalCommand('A = (1, f(1))');
        api.setColor('f', 76, 110, 245);
        api.setColor('A', 220, 53, 69);
        document.getElementById('geogebra-status').textContent = 'Applet ready: drag point A along the parabola.';
    },
}, true);

plainGeoGebraApplet.setHTML5Codebase(<?= json_encode($codebase_url) ?>);
plainGeoGebraApplet.inject('plain-geogebra-applet');
</script>

<?
    };
?>
