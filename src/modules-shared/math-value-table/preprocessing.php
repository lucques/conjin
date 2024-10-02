<?
    $init_preprocessing = function(Module $m, PreprocessContext $c) {
        $c->activate_module('doc-extensions');
        $c->activate_module('js-standard-lib');
        $c->activate_module('mathjs');
        $c->activate_module('sync-dims');

    };
?>