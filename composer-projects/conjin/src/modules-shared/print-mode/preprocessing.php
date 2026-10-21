<?
    $preprocess_target = $preprocess_syslet = function(Module $m, PreprocessContext $c) {
        $c->activate_module('doc-extensions');
        $c->activate_module('page-prep');
        $c->activate_module('paged-js');
    };
?>
