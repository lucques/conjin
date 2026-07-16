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

<? $process = function (Target $target) { ?>

<p>Favicon integration fixture</p>

<? }; ?>
