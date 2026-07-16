<?
    $preprocess = function (TargetPreprocessContext $c) {
        $c->activate_template('template-navigable');

        $c->activate_module('nav-build');
        $c->activate_module('title');
        $c->run_macro('title', 'set', 'template-navigable (custom title)');

        // $c->activate_module('localization');
    };
?>

<? $process = function (Target $target) { ?>

<? load_def_from_script_and_call(__DIR__ . '/../../../lorem_ipsum.php', 'basic'); ?>

<? }; ?>
