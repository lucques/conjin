<?
    $init_preprocessing = function(Module $m, PreprocessContext $c) {
        $c->activate_module('bootstrap', ['import_standalone_css' => false]);
        $c->activate_module('doc-extensions'); // Used to support accordions for `sync-dims` module
    };
?>