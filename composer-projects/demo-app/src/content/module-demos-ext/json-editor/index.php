<?
    $preprocess = function(TargetPreprocessContext $c) {
        $c->activate_template('template-generic');
        $c->activate_module('nav-build');
        
        $c->activate_module('title');
        $c->run_macro('title', 'set', 'JSONEditor');
        $c->activate_module('json-editor');
    };
?>

<? $process = function(Target $target) { ?>

<h2>Inline JSON</h2>
<? json_editor_print_inline('{"name":"Conjin","enabled":true,"modules":["json-editor","doc-extensions"]}'); ?>

<h2>JSON file</h2>
<? json_editor_print_file(__DIR__ . '/example.json'); ?>

<? }; ?>
