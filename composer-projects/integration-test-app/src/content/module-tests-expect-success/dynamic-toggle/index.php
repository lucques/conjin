<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');

        $c->activate_module('dynamic-toggle', [
            'toggles' => [
                [
                    'css_class' => 'test-default-on',
                    'on_by_default' => true,
                ],
                [
                    'css_class' => 'test-default-off',
                    'on_by_default' => false,
                ],
            ],
        ]);
    };
?>

<? $process = function (Target $target) { ?>

<button id="toggle-default-on" type="button" onclick="dtToggle('test-default-on')">Toggle default-on class</button>
<button id="toggle-default-off" type="button" onclick="dtToggle('test-default-off')">Toggle default-off class</button>

<? }; ?>
