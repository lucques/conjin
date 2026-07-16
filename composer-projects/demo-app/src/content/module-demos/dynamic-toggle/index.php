<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->activate_module('dynamic-toggle', [
            'toggles' => [
                [
                    'css_class' => 'demo-default-on',
                    'on_by_default' => true,
                ],
                [
                    'css_class' => 'demo-default-off',
                    'on_by_default' => false,
                ],
            ],
        ]);
    };
?>

<?
    $process = function (Target $target) {
        css_start();
?>
.toggle-state::after {
    content: "off";
    color: #a00;
}

body.demo-default-on #default-on-state::after,
body.demo-default-off #default-off-state::after {
    content: "on";
    color: #080;
}
<?
        css_end();
?>

<h1>dynamic-toggle</h1>
<p>The module toggles classes on <code>&lt;body&gt;</code> and persists each choice in local storage.</p>

<p>
    <button id="toggle-default-on" type="button" onclick="dtToggle('demo-default-on')">Toggle default-on class</button>
    Current state: <strong id="default-on-state" class="toggle-state"></strong>
</p>

<p>
    <button id="toggle-default-off" type="button" onclick="dtToggle('demo-default-off')">Toggle default-off class</button>
    Current state: <strong id="default-off-state" class="toggle-state"></strong>
</p>

<p>Reload the page to verify that both choices persist.</p>

<?
    };
?>
