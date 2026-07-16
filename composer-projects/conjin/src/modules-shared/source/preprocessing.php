<?
    $init_preprocessing = function(Module $m, PreprocessContext $c) {
        $c->activate_module('prism');           // Nice code highlighting
        $c->activate_module('doc-extensions');  // Add body classes to configure prism
    };
?>