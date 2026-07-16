<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('geogebra');
    };
?>

<?
    $process = function (Target $target) {
        $codebase_url = (new ModuleLocation('geogebra'))->get_url() . '/res/HTML5/5.0/web3d/';
?>

<p id="geogebra-status" data-ready="false">Loading</p>
<div id="plain-geogebra-applet"></div>

<script>
const plainGeoGebraApplet = new GGBApplet({
    id: 'plainGeoGebraApi',
    appName: 'graphing',
    width: 480,
    height: 320,
    showToolBar: false,
    showMenuBar: false,
    showAlgebraInput: false,
    appletOnLoad(api) {
        window.plainGeoGebraApi = api;
        const commandSucceeded = api.evalCommand('H = (2, 3)');
        const status = document.getElementById('geogebra-status');
        status.dataset.commandSucceeded = String(commandSucceeded);
        status.dataset.ready = 'true';
    },
}, true);

plainGeoGebraApplet.setHTML5Codebase(<?= json_encode($codebase_url) ?>);
plainGeoGebraApplet.inject('plain-geogebra-applet');
</script>

<?
    };
?>
