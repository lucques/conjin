<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        $c->activate_module('favicons', [
            'res_module' => 'favicons',
            'path' => '/demo',
        ]);
    };
?>

<?
    $process = function (Target $target) {
        $favicon_url = (new ModuleLocation('favicons'))->get_url() . '/res/demo';
?>

<h1>favicons</h1>
<p>The module added both images below to the document head. The browser uses the smaller image as its shortcut icon and the larger image as an Apple touch icon.</p>

<figure>
    <img src="<?= $favicon_url ?>/favicon-32x32.png" width="32" height="32" alt="32 by 32 favicon">
    <figcaption><code>favicon-32x32.png</code></figcaption>
</figure>

<figure>
    <img src="<?= $favicon_url ?>/favicon-apple-touch-icon-180x180.png" width="180" height="180" alt="180 by 180 Apple touch icon">
    <figcaption><code>favicon-apple-touch-icon-180x180.png</code></figcaption>
</figure>

<?
    };
?>
